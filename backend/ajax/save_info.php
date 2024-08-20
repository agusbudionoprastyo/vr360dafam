<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$html = $_POST['html'];
$lang = $_POST['lang'];
if(empty($lang)) {
    $query = "UPDATE svt_virtualtours SET info_box=? WHERE id=?;";
} else {
    $query = "UPDATE svt_virtualtours_lang SET info_box=? WHERE id_virtualtour=? AND language=?;";
}
if($smt = $mysqli->prepare($query)) {
    if(empty($lang)) {
        $smt->bind_param('si', $html,$id_virtualtour);
    } else {
        $smt->bind_param('sis', $html,$id_virtualtour,$lang);
    }
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
    echo json_encode(array("status"=>"error"));
}