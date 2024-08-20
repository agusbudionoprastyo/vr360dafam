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
$type = strip_tags($_POST['type']);
$array = array();
$query = "SELECT id FROM svt_job_queue WHERE id_virtualtour=$id_virtualtour AND type='$type';";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $array[]=$row['id'];
        }
    }
}
ob_end_clean();
echo json_encode($array);