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
$field = strip_tags($_POST['field']);
$checked = $_POST['checked'];
$mysqli->query("UPDATE svt_assign_virtualtours SET $field=$checked WHERE id_virtualtour=$id_vt AND id_user=$id_user;");