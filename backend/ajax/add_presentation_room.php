<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id_room = (int)$_POST['id_room'];
$sleep = $_POST['sleep'];
if($sleep=='') $sleep=0; else $sleep = (int)$sleep;
$video_wait_end = (int)$_POST['video_wait_end'];
$override_pos_presentation = (int)$_POST['override_pos_presentation'];
$yaw = (float)$_POST['yaw'];
$pitch = (float)$_POST['pitch'];
$hfov = (int)$_POST['hfov'];
if($override_pos_presentation==1) {
    $pos = "$yaw,$pitch,$hfov";
} else {
    $pos = NULL;
}
$id_virtualtour = (int)$_POST['id_virtualtour'];
$query = "SELECT IFNULL(MAX(priority_1),0) as priority_1 FROM svt_presentations WHERE id_virtualtour=$id_virtualtour;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $priority_1 = $row['priority_1'];
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
        die();
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
    die();
}
$priority_1 = $priority_1 + 1;
$query = "INSERT INTO svt_presentations(id_virtualtour,id_room,action,params,sleep,priority_1,priority_2,video_wait_end,pos) VALUES(?,?,'goto',?,?,?,1,?,?);";
if ($smt = $mysqli->prepare($query)) {
    $smt->bind_param('iiiiiis',$id_virtualtour,$id_room,$id_room,$sleep,$priority_1,$video_wait_end,$pos);
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