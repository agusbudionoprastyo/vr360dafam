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
$id_room = (int)$_POST['id_room'];
$view_tooltip = strip_tags($_POST['view_tooltip']);
$auto_open = (int)$_POST['auto_open'];
if(!empty($_POST['from_hour'])) {
    $from_hour = strip_tags($_POST['from_hour']);
} else {
    $from_hour = null;
}
if(!empty($_POST['from_hour'])) {
    $to_hour = strip_tags($_POST['to_hour']);
} else {
    $to_hour = null;
}
$array_lang = json_decode($_POST['array_lang'],true);
$mysqli->query("UPDATE svt_rooms_alt SET auto_open=0 WHERE id_room=$id_room");
$query = "UPDATE svt_rooms_alt SET view_tooltip=?,auto_open=?,from_hour=?,to_hour=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sissi',$view_tooltip,$auto_open,$from_hour,$to_hour,$id);
    $result = $smt->execute();
    if($result) {
        save_input_langs($array_lang,'svt_rooms_alt_lang','id_room_alt',$id);
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