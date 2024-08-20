<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id = (int)$_POST['id'];
$id_video = (int)$_POST['id_video'];
if(empty($_POST['id_room'])) {
    $id_room = null;
} else {
    $id_room = (int)$_POST['id_room'];
}
$type = strip_tags($_POST['type']);
$file = strip_tags($_POST['file']);
if(empty($file)) $file=null;
if(empty($_POST['duration'])) {
    $duration = null;
} else {
    $duration = (float)$_POST['duration'];
}
$params = $_POST['params'];
if($type=='logo' || $type=='text') {
    $font = "NotoSans-Regular.ttf";
} else {
    $font = null;
}
if($id==null) {
    $priority = 1;
    $query = "SELECT MAX(priority) as priority FROM svt_video_project_slides WHERE id_video_project=$id_video;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $priority = $row['priority']+1;
        }
    }
    $query = "INSERT INTO svt_video_project_slides(id_video_project,type,id_room,file,duration,params,priority,font) VALUES(?,?,?,?,?,?,?,?);";
} else {
    $query = "UPDATE svt_video_project_slides SET file=?,duration=?,params=?,id_room=?,font=? WHERE id=? AND id_video_project=?;";
}
if($smt = $mysqli->prepare($query)) {
    if($id==null) {
        $smt->bind_param('isisdsis',$id_video,$type,$id_room,$file,$duration,$params,$priority,$font);
    } else {
        $smt->bind_param('sdsisii',$file,$duration,$params,$id_room,$font,$id,$id_video);
    }
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
    echo json_encode(array("status" => "error"));
}