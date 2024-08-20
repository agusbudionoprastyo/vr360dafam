<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$query = "SELECT name FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1; ";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $name = $row['name'];
        $_SESSION['id_virtualtour_sel'] = $id_virtualtour;
        $_SESSION['name_virtualtour_sel'] = $name;
        session_write_close();
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}
