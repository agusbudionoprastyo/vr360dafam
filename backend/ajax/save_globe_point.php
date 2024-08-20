<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_globe = (int)$_POST['id_globe'];
$id_vt = (int)$_POST['id_vt'];
$position = strip_tags($_POST['position']);
$tmp = explode(",",$position);
$top = $tmp[0];
$left = $tmp[1];
$query = "UPDATE svt_globe_list SET lat=?,lon=? WHERE id_globe=? AND id_virtualtour=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssii', $top,$left,$id_globe,$id_vt);
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