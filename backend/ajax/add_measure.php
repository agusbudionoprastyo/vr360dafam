<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_room = (int)$_POST['id_room'];
$yaw = (float)$_POST['yaw'];
$pitch = (float)$_POST['pitch'];
$yaw_end = $yaw + 10;
$params = '{"line_size":3,"color_line":"#ffffff","color_text":"#ffffff","color_outline_text":"","size_text":18,"start_plug":"arrow2","end_plug":"arrow2","start_plug_color":"#ffffff","end_plug_color":"#ffffff","start_plug_size":1,"end_plug_size":1}';
$query = "INSERT INTO svt_measures(id_room, pitch_start, yaw_start, pitch_end, yaw_end, params) VALUES(?,?,?,?,?,?);";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('idddds',  $id_room,$pitch,$yaw,$pitch,$yaw_end,$params);
    $result = $smt->execute();
    if ($result) {
        $insert_id = $mysqli->insert_id;
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id"=>$insert_id));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}