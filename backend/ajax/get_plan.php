<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require(__DIR__.'/../../db/connection.php');
session_write_close();
$id = (int)$_POST['id'];
$return = array();
$query = "SELECT *,(SELECT COUNT(*) FROM svt_users WHERE id_plan=$id) as count_usage FROM svt_plans WHERE id=$id LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $return=$row;
    }
}
ob_end_clean();
echo json_encode($return);