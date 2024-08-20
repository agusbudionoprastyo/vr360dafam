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
$id = (int)$_POST['id'];
$query = "DELETE FROM svt_categories WHERE id=$id; ";
$result = $mysqli->query($query);
if($result) {
    $mysqli->query("ALTER TABLE svt_categories AUTO_INCREMENT = 1;");
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}