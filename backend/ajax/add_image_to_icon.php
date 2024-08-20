<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_virtualtour = $_POST['id_virtualtour'];
$image = strip_tags($_POST['image']);
if(empty($id_virtualtour)) {
    $id_virtualtour=NULL;
}
$query = "INSERT INTO svt_icons(id_virtualtour,image) VALUES(?,?);";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('is',  $id_virtualtour,$image);
    $result = $smt->execute();
    if ($result) {
        update_user_space_storage($id_user,false);
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