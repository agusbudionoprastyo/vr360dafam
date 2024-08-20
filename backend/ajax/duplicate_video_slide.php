<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
ini_set('max_execution_time', 9999);
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id = (int)$_POST['id'];
$id_video = (int)$_POST['id_video'];
$mysqli->query("CREATE TEMPORARY TABLE svt_video_project_slides_tmp SELECT * FROM svt_video_project_slides WHERE id = $id;");
$mysqli->query("UPDATE svt_video_project_slides_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_video_project_slides),priority=(SELECT MAX(priority)+1 FROM svt_video_project_slides WHERE id_video_project=$id_video);");
$mysqli->query("INSERT INTO svt_video_project_slides SELECT * FROM svt_video_project_slides_tmp;");
$mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_video_project_slides_tmp;");
ob_end_clean();
echo json_encode(array("status"=>"ok"));