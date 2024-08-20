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
$id_marker = (int)$_POST['id'];
$show_room = (int)$_POST['show_room'];
$color = strip_tags($_POST['color']);
$background = strip_tags($_POST['background']);
$icon = strip_tags($_POST['icon']);
$id_icon_library = (int)$_POST['id_icon_library'];
$tooltip_type = strip_tags($_POST['tooltip_type']);
$tooltip_visibility = strip_tags($_POST['tooltip_visibility']);
$tooltip_background = strip_tags($_POST['tooltip_background']);
$tooltip_color = strip_tags($_POST['tooltip_color']);
$tooltip_text = strip_tags($_POST['tooltip_text'],['p','b','strong','i','s','br','ul','li','h1','h2','h3','h4','h5','h6']);
$css_class = strip_tags($_POST['css_class']);
$embed_content = strip_tags($_POST['embed_content']);
if(empty($embed_content)) $embed_content=NULL;
$animation = strip_tags($_POST['animation']);
$icon_type = strip_tags($_POST['icon_type']);
$sound = strip_tags($_POST['sound']);
if(empty($sound)) {
    $sound = NULL;
} else {
    $sound = "content/$sound";
}
$array_lang = json_decode($_POST['array_lang'],true);
$query = "UPDATE svt_markers SET show_room=?,color=?,background=?,icon=?,id_icon_library=?,tooltip_type=?,tooltip_visibility=?,tooltip_text=?,css_class=?,embed_content=?,animation=?,icon_type=?,tooltip_background=?,tooltip_color=?,sound=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('isssissssssssssi',$show_room,$color,$background,$icon,$id_icon_library,$tooltip_type,$tooltip_visibility,$tooltip_text,$css_class,$embed_content,$animation,$icon_type,$tooltip_background,$tooltip_color,$sound,$id_marker);
    $result = $smt->execute();
    if($result) {
        save_input_langs($array_lang,'svt_markers_lang','id_marker',$id_marker);
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