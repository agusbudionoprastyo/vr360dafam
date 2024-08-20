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
$id_virtualtour = $_POST['id_virtualtour'];
$s3_enabled = false;
if(empty($id_virtualtour)) {
    $id_virtualtour=NULL;
} else {
    $s3_params = check_s3_tour_enabled($id_virtualtour);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
}
$file = $_POST['file'];
$query = "INSERT INTO svt_sound_library(id_virtualtour,file) VALUES(?,?);";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('is',  $id_virtualtour,$file);
    $result = $smt->execute();
    if ($result) {
        update_user_space_storage($id_user,false);
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}