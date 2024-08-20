<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once("../db/connection.php");
$email = $_GET['email'];
$hash = $_GET['hash'];

$query = "SELECT u.id FROM svt_users as u WHERE u.email='$email' AND u.`hash`='$hash' LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $id = $row['id'];
        $_SESSION['id_user'] = $id;
        $mysqli->query("UPDATE svt_users SET active=1,hash=NULL WHERE id=$id;");
        header("Location: index.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}