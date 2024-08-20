<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
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
$name = strip_tags($_POST['name']);
$name = ucfirst($name);
$map_image = strip_tags($_POST['map_image']);
$map_type = strip_tags($_POST['map_type']);
if($s3_enabled) {
    $map_image = str_replace($s3_url."viewer/maps/","",$map_image);
} else {
    $map_image = str_replace("../viewer/maps/","",$map_image);
}
$query = "INSERT INTO svt_maps(id_virtualtour,map,name,map_type) VALUES(?,?,?,?);";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('isss',  $id_virtualtour,$map_image,$name,$map_type);
    $result = $smt->execute();
    if ($result) {
        $id_map = $mysqli->insert_id;
        $map_image_gt = $map_image;
        include("../../services/generate_thumb.php");
        update_user_space_storage($id_user,false);
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id"=>$id_map));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}