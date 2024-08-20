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
$id = (int)$_POST['id'];
$title = strip_tags($_POST['title']);
$description = strip_tags($_POST['description']);
$array_lang = json_decode($_POST['array_lang'],true);
$query = "UPDATE svt_gallery SET title=?,description=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssi', $title,$description,$id);
    $result = $smt->execute();
    if($result) {
        save_input_langs($array_lang,'svt_gallery_lang','id_gallery',$id);
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