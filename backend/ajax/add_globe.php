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
$name = strip_tags($_POST['name']);
$query = "INSERT INTO svt_globes(id_user,name) VALUES(?,?); ";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('is',  $id_user,$name);
    $result = $smt->execute();
    if ($result) {
        $insert_id = $mysqli->insert_id;
        $code = md5($insert_id);
        $mysqli->query("UPDATE svt_globes SET code='$code' WHERE id=$insert_id;");
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id"=>$insert_id));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}