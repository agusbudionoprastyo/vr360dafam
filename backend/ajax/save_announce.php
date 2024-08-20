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
$id_advertisement = (int)$_POST['id'];
$name = strip_tags($_POST['name']);
$link = strip_tags($_POST['link']);
$type = str_replace("t_","",$_POST['type']);
$image = strip_tags($_POST['image']);
$video = strip_tags($_POST['video']);
$iframe_link = strip_tags($_POST['iframe_link']);
$youtube = strip_tags($_POST['youtube']);
$countdown = $_POST['countdown'];
if(empty($countdown)) $countdown=0;
$countdown = (int)$countdown;
switch($type) {
    case 'video':
        if($countdown<-1) $countdown=-1;
        break;
    default:
        if($countdown<0) $countdown=0;
        break;
}
$auto_assign = (int)$_POST['auto_assign'];
$list_s_vt = $_POST['list_s_vt'];
$list_p_vt = $_POST['list_p_vt'];
if(!empty($list_p_vt)) {
    if(count($list_p_vt)>0) {
        $id_plans = implode(",",$list_p_vt);
    } else {
        $id_plans = "";
    }
} else {
    $id_plans = "";
}
$query = "UPDATE svt_advertisements SET name=?,type=?,link=?,image=?,video=?,iframe_link=?,youtube=?,countdown=?,auto_assign=?,id_plans=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssssssiisi', $name,$type,$link,$image,$video,$iframe_link,$youtube,$countdown,$auto_assign,$id_plans,$id_advertisement);
    $result = $smt->execute();
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$mysqli->query("DELETE FROM svt_assign_advertisements WHERE id_advertisement=$id_advertisement;");
foreach ($list_s_vt as $id_vt) {
    $id_vt = (int)$id_vt;
    $mysqli->query("DELETE FROM svt_assign_advertisements WHERE id_virtualtour=$id_vt;");
    $mysqli->query("INSERT INTO svt_assign_advertisements(id_advertisement,id_virtualtour) VALUES($id_advertisement,$id_vt);");
}
if($result) {
    if($auto_assign==1) {
        $mysqli->query("UPDATE svt_advertisements SET auto_assign=0 WHERE id!=$id_advertisement;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}