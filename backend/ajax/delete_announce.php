<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
if(!get_user_role($_SESSION['id_user'])=='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
session_write_close();
$id_advertisement = (int)$_POST['id_advertisement'];
$query = "DELETE FROM svt_advertisements WHERE id=$id_advertisement;";
$result = $mysqli->query($query);
if($result) {
    $mysqli->query("ALTER TABLE svt_advertisements AUTO_INCREMENT = 1;");
    include("../../services/clean_images.php");
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}