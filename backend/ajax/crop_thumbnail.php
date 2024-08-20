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
require_once(dirname(__FILE__)."/../../db/connection.php");
require_once(dirname(__FILE__)."/../functions.php");
require_once(dirname(__FILE__).'/ImageResizeException.php');
require_once(dirname(__FILE__).'/ImageResize.php');
use \Gumlet\ImageResize;
$settings = get_settings();
$id_room = $_POST['id_room'];
$image = $_POST['image'];
$image = base64_decode(explode(",",$_POST['image'])[1]);
$crop_data = $_POST['crop_data'];
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
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
$im = @imagecreatefromstring($image);
$image_cropped = imageCrop($im, [
    'x' => $crop_data['x'],
    'y' => $crop_data['y'],
    'width' => $crop_data['width'],
    'height' => $crop_data['height']
]);
$name_thumb = 'thumb_'.time().'.jpg';
if($s3_enabled) {
    $path_thumb_dest = "s3://$s3_bucket_name/viewer/panoramas/thumb_custom/".$name_thumb;
} else {
    $path_thumb_dest = dirname(__FILE__).'/../../viewer/panoramas/thumb_custom/'.$name_thumb;
}
imagejpeg($image_cropped, $path_thumb_dest,100);
try {
    $image = new ImageResize($path_thumb_dest);
    $image->quality_jpg = 100;
    $image->interlace = 1;
    $image->resizeToBestFit(213,120);
    $image->gamma(false);
    $image->save($path_thumb_dest);
} catch (ImageResizeException $e) {}
if(file_exists($path_thumb_dest)) {
    if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
        try {
            $s3Client->putObjectAcl([
                'Bucket' => $s3_bucket_name,
                'Key' => "viewer/panoramas/thumb_custom/$name_thumb",
                'ACL' => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            exit;
        }
    }
    $mysqli->query("UPDATE svt_rooms SET thumb_image='$name_thumb' WHERE id=$id_room;");
    ob_end_clean();
    echo json_encode(array("status"=>"ok","thumb_image"=>$name_thumb));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}