<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../functions.php");
require_once("../../db/connection.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_vt = (int)$_GET['id_vt'];
if(get_user_role($id_user)=='customer') {
    $query = "SELECT id FROM svt_virtualtours WHERE id=$id_vt AND id_user=$id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==0) {
            die();
        }
    }
}
$filename = "leads_$id_vt.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename);
$query = "SELECT * FROM svt_leads WHERE id_virtualtour=$id_vt";
$flag = false;
$output = fopen('php://output', 'w');
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while ($row=$result->fetch_array(MYSQLI_ASSOC)) {
            unset($row['id']);
            unset($row['id_virtualtour']);
            if (!$flag) {
                fputcsv($output, array_keys($row),";",'"');
                $flag = true;
            }
            fputcsv($output, array_values($row),";",'"');
        }
    }
}
exit;