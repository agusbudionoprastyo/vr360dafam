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
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$id_gallery = (int)$_POST['id_gallery'];
$visible = (int)$_POST['visible'];
$mysqli->query("UPDATE svt_gallery SET visible=$visible WHERE id_virtualtour=$id_virtualtour AND id=$id_gallery;");