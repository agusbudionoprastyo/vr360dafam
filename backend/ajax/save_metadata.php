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
$meta_title = strip_tags($_POST['meta_title']);
$meta_description = strip_tags($_POST['meta_description']);
$meta_image = strip_tags($_POST['meta_image']);
switch($type) {
    case 'virtual_tour':
        $query = "UPDATE svt_virtualtours SET meta_title=?,meta_description=?,meta_image=? WHERE id=?;";
        break;
    case 'landing':
        $query = "UPDATE svt_virtualtours SET meta_title_l=?,meta_description_l=?,meta_image_l=? WHERE id=?;";
        break;
    case 'showcase':
        $query = "UPDATE svt_showcases SET meta_title=?,meta_description=?,meta_image=? WHERE id=?;";
        break;
    case 'globe':
        $query = "UPDATE svt_globes SET meta_title=?,meta_description=?,meta_image=? WHERE id=?;";
        break;
}
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssi',$meta_title,$meta_description,$meta_image,$id);
    $result = $smt->execute();
    if($result) {
        if($type=='virtual_tour') {
            if(isset($_POST['array_lang'])) {
                $array_lang = json_decode($_POST['array_lang'],true);
                save_input_langs($array_lang,'svt_virtualtours_lang','id_virtualtour',$id);
            }
        }
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