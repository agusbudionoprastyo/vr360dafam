<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
if(get_user_role($id_user)=='administrator') {
    $query = "SELECT id FROM svt_virtualtours WHERE id=$id_virtualtour; ";
} else {
    $query = "SELECT id FROM svt_virtualtours WHERE id_user=$id_user AND id=$id_virtualtour; ";
}
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $mysqli->query("DELETE FROM svt_access_log WHERE id_virtualtour=$id_virtualtour;");
        $mysqli->query("DELETE FROM svt_rooms_access_log WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);");
        $mysqli->query("DELETE FROM svt_access_log_room WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);");
        $mysqli->query("DELETE FROM svt_access_log_poi WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour));");
        $mysqli->query("UPDATE svt_pois SET access_count=0 WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);");
        $mysqli->query("UPDATE svt_rooms SET access_count=0 WHERE id_virtualtour=$id_virtualtour;");
        $mysqli->query("ALTER TABLE svt_rooms_access_log AUTO_INCREMENT = 1;");
        $mysqli->query("ALTER TABLE svt_access_log_room AUTO_INCREMENT = 1;");
        $mysqli->query("ALTER TABLE svt_access_log_poi AUTO_INCREMENT = 1;");
        $mysqli->query("ALTER TABLE svt_access_log AUTO_INCREMENT = 1;");
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}