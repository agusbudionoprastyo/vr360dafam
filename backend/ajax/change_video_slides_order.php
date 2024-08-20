<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$id_video = (int)$_POST['id_video'];
$array_slides_priority = json_decode($_POST['array_slides_priority'],true);
foreach ($array_slides_priority as $priority=>$id) {
    $mysqli->query("UPDATE svt_video_project_slides SET priority=$priority WHERE id=$id AND id_video_project=$id_video;");
}
ob_end_clean();