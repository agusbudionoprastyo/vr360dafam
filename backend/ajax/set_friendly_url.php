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
$settings = get_settings();
$type = $_POST['type'];
$id = (int)$_POST['id'];
$friendly_url = str_replace("'","",strip_tags($_POST['friendly_url']));
$friendly_url = str_replace("\"","",$friendly_url);
$friendly_url = str_replace(" ","_",$friendly_url);
$friendly_url = strtolower($friendly_url);
$furl_blacklist = explode(",",$settings['furl_blacklist']);
if(!empty($friendly_url)) {
    if(in_array($friendly_url,$furl_blacklist)) {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
        exit;
    }
    switch($type) {
        case 'virtual_tour':
            $query_check = "SELECT id FROM svt_virtualtours WHERE friendly_url='$friendly_url' AND id != $id;";
            break;
        case 'landing':
            $query_check = "SELECT id FROM svt_virtualtours WHERE friendly_l_url='$friendly_url' AND id != $id;";
            break;
        case 'showcase':
            $query_check = "SELECT id FROM svt_showcases WHERE friendly_url='$friendly_url' AND id != $id;";
            break;
        case 'globe':
            $query_check = "SELECT id FROM svt_globes WHERE friendly_url='$friendly_url' AND id != $id;";
            break;
    }
    $result_check = $mysqli->query($query_check);
    if($result_check) {
        if($result_check->num_rows>0) {
            ob_end_clean();
            echo json_encode(array("status"=>"error"));
            exit;
        }
    }
}
switch($type) {
    case 'virtual_tour':
        $query = "UPDATE svt_virtualtours SET friendly_url=? WHERE id=?;";
        break;
    case 'landing':
        $query = "UPDATE svt_virtualtours SET friendly_l_url=? WHERE id=?;";
        break;
    case 'showcase':
        $query = "UPDATE svt_showcases SET friendly_url=? WHERE id=?;";
        break;
    case 'globe':
        $query = "UPDATE svt_globes SET friendly_url=? WHERE id=?;";
        break;
}
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('si',$friendly_url,$id);
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