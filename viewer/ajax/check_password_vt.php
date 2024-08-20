<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$code = $_POST['code'];
$password = str_replace("'","\'",$_POST['password']);

$query = "SELECT id FROM svt_virtualtours WHERE code='$code' AND password=MD5('$password');";
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