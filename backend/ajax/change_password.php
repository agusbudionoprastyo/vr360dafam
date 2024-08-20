<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = (int)$_SESSION['id_user'];
session_write_close();
$id = (int)$_POST['id_svt'];
if($id!=$id_user) {
    if(!get_user_role($id_user)=='administrator') {
        ob_end_clean();
        echo json_encode(array("status" => "error"));
        exit;
    }
}
$password = strip_tags($_POST['password_svt']);
$query = "UPDATE svt_users SET password=MD5(?) WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('si', $password, $id);
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