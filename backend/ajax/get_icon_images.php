<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$array = array();
if(empty($id_virtualtour)) {
    $query = "SELECT id,image,id_virtualtour FROM svt_icons WHERE id_virtualtour IS NULL ORDER BY id DESC;";
} else {
    $query = "SELECT id,image,id_virtualtour FROM svt_icons WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
}
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if(empty($row['id_virtualtour'])) $row['id_virtualtour']='';
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode($array);