<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id_map = (int)$_POST['id_map'];
$map_name = strip_tags($_POST['map_name']);
$point_color = strip_tags($_POST['point_color']);
$point_size = (int)$_POST['point_size'];
$north_degree = (int)$_POST['north_degree'];
if((empty($point_size)) || ($point_size<=0)) {
    $point_size=20;
}
$zoom_level = (int)$_POST['zoom_level'];
$zoom_to_point = (int)$_POST['zoom_to_point'];
$width_d = (int)$_POST['width_d'];
$width_m = (int)$_POST['width_m'];
$default_view = strip_tags($_POST['default_view']);
$info_link = strip_tags($_POST['info_link']);
$info_type = strip_tags($_POST['info_type']);
$id_room_default = (int)$_POST['id_room_default'];
if((empty($width_d)) || ($width_d<=0)) {
    $width_d=300;
}
if((empty($width_m)) || ($width_m<=0)) {
    $width_d=225;
}
if(empty($id_room_default) || $id_room_default==0) {
    $id_room_default=NULL;
}
$array_lang = json_decode($_POST['array_lang'],true);
$query = "UPDATE svt_maps SET name=?,point_color=?,point_size=?,north_degree=?,zoom_level=?,zoom_to_point=?,width_d=?,width_m=?,default_view=?,info_link=?,info_type=?,id_room_default=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssiiiiiisssii',$map_name,$point_color,$point_size,$north_degree,$zoom_level,$zoom_to_point,$width_d,$width_m,$default_view,$info_link,$info_type,$id_room_default,$id_map);
    $result = $smt->execute();
    if($result) {
        save_input_langs($array_lang,'svt_maps_lang','id_map',$id_map);
        ob_end_clean();
        echo json_encode(array("status"=>"ok","l"=>$array_lang));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}