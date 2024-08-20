<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_room = (int)$_POST['id_room'];
$array = array();
$query = "SELECT * FROM svt_rooms_alt WHERE poi=0 AND id_room=$id_room ORDER BY priority;";
$result = $mysqli->query($query);
if($result) {
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $row['array_lang'] = array();
        $query_l = "SELECT * FROM svt_rooms_alt_lang WHERE id_room_alt=".$row['id'];
        $result_l = $mysqli->query($query_l);
        if($result_l) {
            if ($result_l->num_rows > 0) {
                while ($row_l = $result_l->fetch_array(MYSQLI_ASSOC)) {
                    $row['array_lang'][]=$row_l;
                }
            }
        }
        if(!empty($row['from_hour'])) {
            $row['from_hour'] = date("H:i", strtotime($row['from_hour']));
        }
        if(!empty($row['to_hour'])) {
            $row['to_hour'] = date("H:i", strtotime($row['to_hour']));
        }
        $array[]=$row;
    }
}
ob_end_clean();
echo json_encode($array);