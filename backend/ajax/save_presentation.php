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
$presentation_type = strip_tags($_POST['presentation_type']);
$presentation_video = strip_tags($_POST['presentation_video']);
$presentation_inactivity = $_POST['presentation_inactivity'];
$auto_presentation_speed = $_POST['auto_presentation_speed'];
if(empty($auto_presentation_speed) || ($auto_presentation_speed==0)) {
    $auto_presentation_speed = 10;
}
$auto_presentation_speed = (int)$auto_presentation_speed;
if(empty($presentation_inactivity)) $presentation_inactivity=0;
$presentation_inactivity = (int)$presentation_inactivity;
$presentation_loop = (int)$_POST['presentation_loop'];
$presentation_stop_click = (int)$_POST['presentation_stop_click'];
$presentation_stop_id_room = (int)$_POST['presentation_stop_id_room'];
$presentation_view_pois = (int)$_POST['presentation_view_pois'];
$presentation_view_measures = (int)$_POST['presentation_view_measures'];
$query = "UPDATE svt_virtualtours SET auto_presentation_speed=?,presentation_type=?,presentation_video=?,presentation_inactivity=?,presentation_loop=?,presentation_stop_click=?,presentation_stop_id_room=?,presentation_view_pois=?,presentation_view_measures=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('issiiiiiii',$auto_presentation_speed,$presentation_type,$presentation_video,$presentation_inactivity,$presentation_loop,$presentation_stop_click,$presentation_stop_id_room,$presentation_view_pois,$presentation_view_measures,$id_virtualtour);
    $result = $smt->execute();
    if ($result) {
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