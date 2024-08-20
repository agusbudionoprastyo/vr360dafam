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
$query = "SELECT v.code,v.external,IFNULL(COUNT(r.id),0) as count_rooms FROM svt_virtualtours as v
LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id
WHERE v.id = $id_virtualtour
GROUP BY v.id";
$result = $mysqli->query($query);
$code = "";
$count_rooms = 0;
$external = 0;
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $count_rooms = $row['count_rooms'];
        $external = $row['external'];
    }
}
ob_end_clean();
echo json_encode(array("code"=>$code,"count_rooms"=>$count_rooms,"external"=>$external));