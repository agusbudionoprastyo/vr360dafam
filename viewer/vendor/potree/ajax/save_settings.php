<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
ob_start();
require_once("../../../../db/connection.php");
require_once("../../../../backend/functions.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$id_poi = $_POST['id_poi'];
$settings = $_POST['settings'];
$path = $_POST['path'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
$parsedUrl = parse_url($path);
$path = $parsedUrl['path'];
$dir_pc = basename($path);
if($s3_enabled) {
    $settings_path = "s3://$s3_bucket_name/viewer/pointclouds/$dir_pc/settings_$id_poi.json";
} else {
    $settings_path = "../../../pointclouds/$dir_pc/settings_$id_poi.json";
}
$result = file_put_contents($settings_path,$settings);
if($result) {
    ob_end_clean();
    echo "ok";
}