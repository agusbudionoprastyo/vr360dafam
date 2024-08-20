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
$show_in_first_page = (int)$_POST['show_in_first_page'];
$mysqli->query("UPDATE svt_virtualtours SET show_in_first_page=0,show_in_first_page_l=0;");
$mysqli->query("UPDATE svt_globes SET show_in_first_page=0;");
$mysqli->query("UPDATE svt_showcases SET show_in_first_page=0;");
switch($_POST['w']) {
    case 'vt':
        $query = "UPDATE svt_virtualtours SET show_in_first_page=$show_in_first_page WHERE id=$id;";
        break;
    case 'landing':
        $query = "UPDATE svt_virtualtours SET show_in_first_page_l=$show_in_first_page WHERE id=$id;";
        break;
    case 'globe':
        $query = "UPDATE svt_globes SET show_in_first_page=$show_in_first_page WHERE id=$id;";
        break;
    case 'showcase':
        $query = "UPDATE svt_showcases SET show_in_first_page=$show_in_first_page WHERE id=$id;";
        break;
}
$result = $mysqli->query($query);
if($result) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}