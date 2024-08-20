<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_gallery = (int)$_POST['id_gallery'];
$filename = $_POST['filename'];
$rotate = (int)$_POST['rotate'];
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
$s3_enabled = false;
$s3_url = "";
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$ext = explode('.',$filename);
$ext = strtolower(end($ext));
$milliseconds = round(microtime(true) * 1000);
$name = "gallery_".$milliseconds.".$ext";
if($s3_enabled) {
    $path_gallery = "s3://$s3_bucket_name/viewer/gallery/";
} else {
    $path_gallery = dirname(__FILE__).'/../../viewer/gallery/';
}
if(!file_exists($path_gallery.'o_'.$filename)) {
    copy($path_gallery.$filename,$path_gallery.'o_'.$name);
} else {
    rename($path_gallery.'o_'.$filename,$path_gallery.'o_'.$name);
}
$result = $mysqli->query("SELECT rotate FROM svt_gallery WHERE id=$id_gallery LIMIT 1;");
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $rotate_exist = $row['rotate'];
        $rotate = $rotate_exist + $rotate;
    }
}
if((strtolower($ext)=='jpg') || (strtolower($ext)=='jpeg')) {
    try {
        $src_img = imagecreatefromjpeg($path_gallery.'o_'.$name);
        imageinterlace($src_img, true);
        $rotate_img = imagerotate($src_img, $rotate, 0);
        imagejpeg($rotate_img, $path_gallery.$name);
    } catch (Exception $e) {}
} elseif (strtolower($ext)=='png') {
    try {
        $src_img = imagecreatefrompng($path_gallery.'o_'.$name);
        imageinterlace($src_img, true);
        imagealphablending($src_img, true);
        imagesavealpha($src_img, true);
        $rotate_img = imagerotate($src_img, $rotate, 0);
        imagepng($rotate_img, $path_gallery.$name);
    } catch (Exception $e) {}
} elseif (strtolower($ext)=='webp') {
    try {
        $src_img = imagecreatefromwebp($path_gallery.'o_'.$name);
        $rotate_img = imagerotate($src_img, $rotate, 0);
        imagewebp($rotate_img, $path_gallery.$name);
    } catch (Exception $e) {}
}
$mysqli->query("UPDATE svt_gallery SET image='$name',rotate=$rotate WHERE id=$id_gallery");
$gallery_image_gt = $name;
require_once("../../services/generate_thumb.php");
ob_end_clean();
exit;