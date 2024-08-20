<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
ini_set('max_input_time', 9999);
require_once(dirname(__FILE__).'/../../db/connection.php');
require_once(dirname(__FILE__).'/../functions.php');
$settings = get_settings();
$points = explode(",",$_POST['points']);
$panorama_image = $_POST['panorama_image'];
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
$s3Client = null;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$blur_tmp = 'blur_'.time().'.png';
$panorama_image_new = 'pano_'.time().'.jpg';
if($s3_enabled) {
    $path_panorama = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image";
    $path_panorama_new = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image_new";
    $path_panorama_original = "s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image";
    $path_panorama_original_new = "s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image_new";
} else {
    $path_panorama = dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image;
    $path_panorama_new = dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image_new;
    $path_panorama_original = dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image;
    $path_panorama_original_new = dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image_new;
}
resizeCropPolygonImage($path_panorama, dirname(__FILE__).'/../tmp_panoramas/'.$blur_tmp, $points, 2);
$polygonPerimeter = getPolygonCropCorners($points,2);
$X = $polygonPerimeter[0]['min'];
$Y = $polygonPerimeter[1]['min'];
list($width, $height) = getimagesize(dirname(__FILE__).'/../tmp_panoramas/'.$blur_tmp);
list($newwidth, $newheight) = getimagesize($path_panorama);
$png = imagecreatefrompng(dirname(__FILE__).'/../tmp_panoramas/'.$blur_tmp);
$jpeg = imagecreatefromjpeg($path_panorama);
$out = imagecreatetruecolor($newwidth, $newheight);
imagealphablending($out, true);
imagesavealpha($out, true);
imagecopyresampled($out, $jpeg, 0, 0, 0, 0, $newwidth, $newheight, $newwidth, $newheight);
imagedestroy($jpeg);
imagecopyresampled($out, $png, $X, $Y, 0 , 0, $width, $height, $width, $height);
imagedestroy($png);
imageinterlace($out, true);
imagejpeg($out, $path_panorama_new, 100);
imagedestroy($out);
unlink(dirname(__FILE__).'/../tmp_panoramas/'.$blur_tmp);
if(file_exists($path_panorama_new)) {
    rename($path_panorama_original, $path_panorama_original_new);
    if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
        try {
            $s3Client->putObjectAcl([
                'Bucket' => $s3_bucket_name,
                'Key' => "viewer/panoramas/$panorama_image_new",
                'ACL' => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {}
        try {
            $s3Client->putObjectAcl([
                'Bucket' => $s3_bucket_name,
                'Key' => "viewer/panoramas/original/$panorama_image_new",
                'ACL' => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {}
    }
    $mysqli->query("UPDATE svt_rooms SET panorama_image='$panorama_image_new',multires_status=0,blur=1 WHERE panorama_image='$panorama_image';");
    $panorama_image_gt = $panorama_image_new;
    include("../../services/generate_thumb.php");
    include("../../services/generate_pano_mobile.php");
    generate_multires(false,$id_virtualtour);
    ob_end_clean();
}

function resizeCropPolygonImage($source, $dest = null, $points = array(), $numCoords = 2) {
    $numPoints = count($points) / $numCoords;
    if ($numPoints < 3) {
        return;
    }
    list($width, $height, $file_type) = getimagesize($source);
    switch ($file_type) {
        case 1:
            $srcImage = imagecreatefromgif($source);
            if (function_exists(ImageGIF)) {
                $imgType = "gif";
            } else {
                $imgType = "jpeg";
            }
            break;
        case 2:
            $srcImage = imagecreatefromjpeg($source);
            $imgType = "jpeg";
            break;
        case 3:
            $srcImage = imagecreatefrompng($source);
            $imgType = "png";
            break;
        default:
            return;
    }
    $mergeImage = ImageCreateTrueColor($width, $height);
    imagecopyresampled($mergeImage, $srcImage, 0, 0, 0, 0, $width, $height, imagesx($srcImage), imagesy($srcImage));
    $size = ['sm' => ['w' => intval($width / 4), 'h' => intval($height / 4)],
        'md' => ['w' => intval($width / 2), 'h' => intval($height / 2)],
    ];
    $sm = imagecreatetruecolor($size['sm']['w'], $size['sm']['h']);
    imagecopyresampled($sm, $mergeImage, 0, 0, 0, 0, $size['sm']['w'], $size['sm']['h'], $width, $height);
    for ($x = 1; $x <= 10; $x++) {
        imagefilter($sm, IMG_FILTER_GAUSSIAN_BLUR, 999);
    }
    imagefilter($sm, IMG_FILTER_SMOOTH, 99);
    imagefilter($sm, IMG_FILTER_BRIGHTNESS, 10);
    $md = imagecreatetruecolor($size['md']['w'], $size['md']['h']);
    imagecopyresampled($md, $sm, 0, 0, 0, 0, $size['md']['w'], $size['md']['h'], $size['sm']['w'], $size['sm']['h']);
    imagedestroy($sm);
    for ($x = 1; $x <= 10; $x++) {
        imagefilter($md, IMG_FILTER_GAUSSIAN_BLUR, 999);
    }
    imagefilter($md, IMG_FILTER_SMOOTH, 99);
    imagefilter($md, IMG_FILTER_BRIGHTNESS, 10);
    imagecopyresampled($mergeImage, $md, 0, 0, 0, 0, $width, $height, $size['md']['w'], $size['md']['h']);
    $maskPolygon = imagecreatetruecolor($width, $height);
    $borderColor = imagecolorallocate($maskPolygon, 1, 254, 255);
    imagefill($maskPolygon, 0, 0, $borderColor);
    $transparency = imagecolortransparent($maskPolygon, imagecolorallocate($maskPolygon, 255, 1, 254));
    imagesavealpha($maskPolygon, true);
    imagefilledpolygon($maskPolygon, $points, $numPoints, $transparency);
    imagesavealpha($mergeImage, true);
    imagecopymerge($mergeImage, $maskPolygon, 0, 0, 0, 0, $width, $height, 100);
    $polygonPerimeter = getPolygonCropCorners($points, $numCoords);
    $polygonX = $polygonPerimeter[0]['min'];
    $polygonY = $polygonPerimeter[1]['min'];
    $polygonWidth = $polygonPerimeter[0]['max'] - $polygonPerimeter[0]['min'];
    $polygonHeight = $polygonPerimeter[1]['max'] - $polygonPerimeter[1]['min'];
    $destImage = ImageCreateTrueColor($polygonWidth, $polygonHeight);
    imagesavealpha($destImage, true);
    imagealphablending($destImage, true);
    imagecopy($destImage, $mergeImage,
        0, 0,
        $polygonX, $polygonY,
        $polygonWidth, $polygonHeight);
    $borderRGB = imagecolorsforindex($destImage, $borderColor);
    $borderTransparency = imagecolorallocatealpha($destImage, $borderRGB['red'], $borderRGB['green'], $borderRGB['blue'], 127);
    imagesavealpha($destImage, true);
    imagealphablending($destImage, true);
    imagefill($destImage, 0, 0, $borderTransparency);
    if (!$dest) {
        header('Content-Type: image/png');
        imagepng($destImage);
    } else {
        imagepng($destImage, $dest);
    }
    imagedestroy($maskPolygon);
    imagedestroy($srcImage);
    imagedestroy($destImage);
    if ($dest) {
        return $dest;
    }
}

function getPolygonCropCorners($points, $numCoords) {
    $perimeter = array();
    for ( $i = 0; $i < count($points); $i++ ) {
        $axisIndex = $i % $numCoords;
        if (count($perimeter) < $axisIndex) {
            $perimeter[] = array();
        }
        $min = isset($perimeter[$axisIndex]['min']) ? $perimeter[$axisIndex]['min'] : $points[$i];
        $max = isset($perimeter[$axisIndex]['max']) ? $perimeter[$axisIndex]['max'] : $points[$i];
        $perimeter[$axisIndex]['min'] = min($min, $points[$i] - 2);
        $perimeter[$axisIndex]['max'] = max($max, $points[$i] + 2);
    }
    return $perimeter;
}