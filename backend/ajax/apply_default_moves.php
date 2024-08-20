<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$p = strip_tags($_POST['p']);
$rotateX = (int)$_POST['rotateX'];
$rotateZ = (int)$_POST['rotateZ'];
$size = (float)$_POST['size'];
$scale = (int)$_POST['scale'];
$apply_perspective = $_POST['apply_perspective'];
$apply_size = $_POST['apply_size'];
$apply_scale = $_POST['apply_scale'];
$query_add = "";
if($apply_perspective) {
    $query_add .= "rotateX=$rotateX,rotateZ=$rotateZ,";
}
if($apply_size) {
    $query_add .= "size_scale=$size,";
}
if($apply_scale) {
    $query_add .= "scale=$scale,";
}
$query_add = rtrim($query_add,",");
switch ($p) {
    case 'markers':
        $query_a = "UPDATE svt_markers SET $query_add WHERE embed_type IS NULL AND id_room IN (SELECT DISTINCT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
        break;
    case 'pois':
        $query_a = "UPDATE svt_pois SET $query_add WHERE embed_type IS NULL AND id_room IN (SELECT DISTINCT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
        break;
}
$result_a = $mysqli->query($query_a);
if($result_a) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
}