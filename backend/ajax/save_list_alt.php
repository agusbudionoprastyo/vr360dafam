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
$list_alt = strip_tags($_POST['list_alt']);
$list_alt = str_replace(array('<br/>','<br>'),' ',$list_alt);
$language = $_POST['language'];
if(empty($language)) {
    $query = "UPDATE svt_virtualtours SET list_alt=? WHERE id=?;";
} else {
    $query_c = "SELECT id_virtualtour FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour AND language='$language' LIMIT 1;";
    $result_c = $mysqli->query($query_c);
    if($result_c) {
        if($result_c->num_rows==1) {
            $query = "UPDATE svt_virtualtours_lang SET list_alt=? WHERE id_virtualtour=? AND language=?;";
        } else {
            $query = "INSERT INTO svt_virtualtours_lang(list_alt,id_virtualtour,language) VALUES(?,?,?);";
        }
    }
}
if($smt = $mysqli->prepare($query)) {
    if(empty($language)) {
        $smt->bind_param('si', $list_alt,$id_virtualtour);
    } else {
        $smt->bind_param('sis', $list_alt,$id_virtualtour,$language);
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
    echo json_encode(array("status"=>"error"));
}