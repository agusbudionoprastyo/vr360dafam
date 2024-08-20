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
$yaw = (float)$_POST['yaw'];
$pitch = (float)$_POST['pitch'];
$embed_type = strip_tags($_POST['embed_type']);
if(empty($embed_type)) {
    $query = "UPDATE svt_markers SET embed_type=NULL WHERE id=$id;";
} else {
    $coord_1 = ($pitch+5).",".($yaw-10);
    $coord_2 = ($pitch-5).",".($yaw-10);
    $coord_3 = ($pitch+5).",".($yaw+10);
    $coord_4 = ($pitch-5).",".($yaw+10);
    $embed_coords = "$coord_1|$coord_2|$coord_3|$coord_4";
    $embed_size = "300,150";
    if($_POST['embed_type']=='selection') {
        $embed_content="border-width:3px;";
    } else {
        $embed_content="";
    }
    $query = "UPDATE svt_markers SET embed_type='$embed_type',embed_size='$embed_size',embed_coords='$embed_coords',embed_content='$embed_content' WHERE id=$id;";
}
$result=$mysqli->query($query);
if($result) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}