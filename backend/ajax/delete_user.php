<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user_del = $_POST['id_user'];
$id_user_assign = $_POST['id_user_assign'];
$id_user = $_SESSION['id_user'];
session_write_close();
if(!get_user_role($id_user)=='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$id_user_del = xor_deobfuscator($id_user_del);
if($id_user_assign!=0) {
    $query = "UPDATE svt_virtualtours SET id_user=? WHERE id_user=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('ii', $id_user_assign,$id_user_del);
        $smt->execute();
    }
}
$query = "DELETE FROM svt_users WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('i', $id_user_del);
    $result = $smt->execute();
    if($result) {
        $mysqli->query("ALTER TABLE svt_users AUTO_INCREMENT = 1;");
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