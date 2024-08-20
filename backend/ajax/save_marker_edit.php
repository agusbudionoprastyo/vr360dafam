<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_marker = (int)$_POST['id'];
$id_room_target = (int)$_POST['id_room_target'];
$yaw = $_POST['yaw'];
$pitch = $_POST['pitch'];
if($yaw=='') $yaw = NULL; else $yaw = (float)$yaw;
if($pitch=='') $pitch = NULL; else $pitch = (float)$pitch;
$lookat = (int)$_POST['lookat'];
$query = "UPDATE svt_markers SET id_room_target=?,yaw_room_target=?,pitch_room_target=?,lookat=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('iddii', $id_room_target,$yaw,$pitch,$lookat,$id_marker);
    $result = $smt->execute();
    if($result) {
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