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
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_room = (int)$_POST['id_room'];
$sleep = $_POST['sleep'];
if($sleep=='') $sleep=0; else $sleep = (int)$sleep;
$action = strip_tags($_POST['action']);
$params = strip_tags($_POST['params']);
$array_lang = json_decode($_POST['array_lang'],true);
$query = "SELECT IFNULL(MAX(priority_2),0) as priority_2,priority_1 FROM svt_presentations WHERE id_room=$id_room GROUP BY id_room,priority_1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $priority_1 = $row['priority_1'];
        $priority_2 = $row['priority_2'];
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
        die();
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    die();
}
$priority_2 = $priority_2 + 1;
$query ="INSERT INTO svt_presentations(id_virtualtour,id_room,action,params,sleep,priority_1,priority_2) VALUES(?,?,?,?,?,?,?);";
if ($smt = $mysqli->prepare($query)) {
    $smt->bind_param('iissiii',$id_virtualtour,$id_room,$action,$params,$sleep,$priority_1,$priority_2);
    $result = $smt->execute();
    if ($result) {
        $id_presentation = $mysqli->insert_id;
        save_input_langs($array_lang,'svt_presentations_lang','id_presentation',$id_presentation);
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