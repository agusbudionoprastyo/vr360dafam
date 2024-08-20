<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_video = (int)$_POST['id'];
$name = strip_tags($_POST['name']);
$fade = $_POST['fade'];
if(empty($fade)) $fade=0.3;
$fade = (float)$fade;
$fps = (float)$_POST['fps'];
$resolution_w = $_POST['resolution_w'];
if(empty($resolution_w)) $resolution_w=1920;
$resolution_w = (int)$resolution_w;
$resolution_h = $_POST['resolution_h'];
if(empty($resolution_h)) $resolution_h=1080;
$resolution_h = (int)$resolution_h;
$audio = strip_tags($_POST['audio']);
if($audio=="0") $audio=NULL;
$watermark_logo = strip_tags($_POST['watermark_logo']);
$watermark_pos = strip_tags($_POST['watermark_pos']);
$watermark_opacity = (float)$_POST['watermark_opacity'];
$query = "UPDATE svt_video_projects SET name=?,fade=?,resolution_w=?,resolution_h=?,audio=?,watermark_logo=?,watermark_pos=?,watermark_opacity=?,fps=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sdiisssddi',$name,$fade,$resolution_w,$resolution_h,$audio,$watermark_logo,$watermark_pos,$watermark_opacity,$fps,$id_video);
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