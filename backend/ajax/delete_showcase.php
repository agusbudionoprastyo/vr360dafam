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
$id_showcase = (int)$_POST['id_showcase'];
$code = "";
$query = "SELECT code FROM svt_showcases WHERE id=$id_showcase LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
    }
}
$query = "DELETE FROM svt_showcases WHERE id=$id_showcase;";
$result = $mysqli->query($query);
if($result) {
    $mysqli->query("ALTER TABLE svt_showcases AUTO_INCREMENT = 1;");
    include("../../services/clean_images.php");
    $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
    if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code")) {
        array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code" . DIRECTORY_SEPARATOR ."*.*"));
        rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code" . DIRECTORY_SEPARATOR);
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}