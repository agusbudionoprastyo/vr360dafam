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
$id_vt = (int)$_POST['id_vt'];
$id_user = (int)$_POST['id_user'];
$checked = $_POST['checked'];
if($checked==1) {
    $mysqli->query("INSERT INTO svt_assign_virtualtours(id_user,id_virtualtour) VALUES($id_user,$id_vt);");
} else {
    $mysqli->query("DELETE FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_vt;");
}