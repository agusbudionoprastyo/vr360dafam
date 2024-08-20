<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id = (int)$_POST['id'];
$sleep = $_POST['sleep'];
$video_wait_end = (int)$_POST['video_wait_end'];
$override_pos_presentation = (int)$_POST['override_pos_presentation'];
$yaw = (float)$_POST['yaw'];
$pitch = (float)$_POST['pitch'];
$hfov = (int)$_POST['hfov'];
if($override_pos_presentation==1) {
    $pos = "$yaw,$pitch,$hfov";
} else {
    $pos = NULL;
}
if($sleep=='') $sleep=0;
$sleep = (int)$sleep;
$query = "UPDATE svt_presentations SET sleep=?,video_wait_end=?,pos=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('iisi',$sleep,$video_wait_end,$pos,$id);
    $result = $smt->execute();
    if ($result) {
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