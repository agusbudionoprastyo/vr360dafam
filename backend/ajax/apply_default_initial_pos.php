<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$yaw = (int)$_POST['yaw'];
$pitch = (int)$_POST['pitch'];
$apply_yaw = $_POST['apply_yaw'];
$apply_pitch = $_POST['apply_pitch'];
$query_add = "";
if($apply_yaw) {
    $query_add .= "yaw=$yaw,";
}
if($apply_pitch) {
    $query_add .= "pitch=$pitch,";
}
$query_add = rtrim($query_add,",");
$query_a = "UPDATE svt_rooms SET $query_add WHERE id_virtualtour=$id_virtualtour;";
$result_a = $mysqli->query($query_a);
if($result_a) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
}