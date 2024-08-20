<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$license = strip_tags($_POST['license']);
$license2 = strip_tags($_POST['license2']);
$purchase_code = strip_tags($_POST['purchase_code']);
if(empty($license)) {
    $purchase_code="";
}
if(empty($purchase_code)) {
    $license="";
}
$query = "UPDATE svt_settings SET license=?,license2=?,purchase_code=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sss', $license,$license2,$purchase_code);
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