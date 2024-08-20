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
$start_date = strip_tags($_POST['start_date']);
$end_date = strip_tags($_POST['end_date']);
$start_url = strip_tags($_POST['start_url']);
$end_url = strip_tags($_POST['end_url']);
$query = "UPDATE svt_virtualtours SET start_date=?,end_date=?,start_url=?,end_url=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssssi',$start_date,$end_date,$start_url,$end_url,$id_virtualtour);
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