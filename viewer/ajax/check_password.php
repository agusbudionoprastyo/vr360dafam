<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$password = str_replace("'","\'",$_POST['password']);
$type = $_POST['type'];

$query = "SELECT id FROM svt_virtualtours WHERE id=$id_virtualtour AND password_$type = '$password' LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"incorrect"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}