<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_product = (int)$_POST['id_product'];
$array_images_priority = json_decode($_POST['array_images_priority'],true);
foreach ($array_images_priority as $priority=>$id) {
    $mysqli->query("UPDATE svt_product_images SET priority=$priority WHERE id=$id AND id_product=$id_product;");
}
ob_end_clean();