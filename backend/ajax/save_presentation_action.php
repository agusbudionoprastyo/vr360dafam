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
$sleep = $_POST['sleep'];
$params = strip_tags($_POST['params']);
$array_lang = json_decode($_POST['array_lang'],true);
if($sleep=='') $sleep=0;
$sleep = (int)$sleep;
$query = "UPDATE svt_presentations SET sleep=?,params=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('isi',$sleep,$params,$id);
    $result = $smt->execute();
    if ($result) {
        save_input_langs($array_lang,'svt_presentations_lang','id_presentation',$id);
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