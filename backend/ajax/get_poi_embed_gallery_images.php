<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_poi = (int)$_POST['id_poi'];
$array = array();
$query = "SELECT * FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi ORDER BY priority;";
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