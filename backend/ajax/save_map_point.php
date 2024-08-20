<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_room = (int)$_POST['id_room'];
$position = strip_tags($_POST['position']);
$map_type = strip_tags($_POST['map_type']);
$tmp = explode(",",$position);
$top = $tmp[0];
$left = $tmp[1];
switch ($map_type) {
    case 'floorplan':
        $query = "UPDATE svt_rooms SET map_top=?,map_left=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('iii', $top,$left,$id_room);
            $result = $smt->execute();
            if($result) {
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
        break;
    case 'map':
        $query = "UPDATE svt_rooms SET lat=?,lon=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('ssi', $top,$left,$id_room);
            $result = $smt->execute();
            if($result) {
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
        break;
}