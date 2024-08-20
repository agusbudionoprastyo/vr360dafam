<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$measure = $_POST['measure'];
$id = (int)$measure['id'];
$label = strip_tags($measure['label']);
$pitch_start = (float)$measure['pitch_start'];
$pitch_end = (float)$measure['pitch_end'];
$yaw_start = (float)$measure['yaw_start'];
$yaw_end = (float)$measure['yaw_end'];
$params = strip_tags($measure['params']);
$visible_multiview_ids = strip_tags($measure['visible_multiview_ids']);
$query = "UPDATE svt_measures SET pitch_start=?,pitch_end=?,yaw_start=?,yaw_end=?,params=?,label=?,visible_multiview_ids=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ddddsssi',$pitch_start,$pitch_end,$yaw_start,$yaw_end,$params,$label,$visible_multiview_ids,$id);
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
    echo json_encode(array("status" => "error"));
}