<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_product = (int)$_POST['id_product'];
$array = array();
$query = "SELECT * FROM svt_product_images WHERE id_product=$id_product ORDER BY priority;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode($array);