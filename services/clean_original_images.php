<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) {
    //DEMO CHECK
    die();
}
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
ini_set('max_input_time', 9999);
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
$id_virtualtour = $_POST['id_virtualtour'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
}
$result = $mysqli->query("SELECT panorama_image FROM svt_rooms WHERE type='image' AND id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $panorama_image = $row['panorama_image'];
            if($s3_enabled) {
                unlink("s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image");
            } else {
                unlink($path.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
            }
        }
    }
}
$result = $mysqli->query("SELECT panorama_image FROM svt_rooms_alt WHERE id_room IN (SELECT id FROM svt_rooms WHERE type='image' AND id_virtualtour=$id_virtualtour);");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $panorama_image = $row['panorama_image'];
            if($s3_enabled) {
                unlink("s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image");
            } else {
                unlink($path.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
            }
        }
    }
}