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
$password = strip_tags($_POST['password']);
$password_title = strip_tags($_POST['password_title']);
$password_description = strip_tags($_POST['password_description']);
$protect_type = strip_tags($_POST['protect_type']);
$protect_send_email = (int)$_POST['protect_send_email'];
$protect_email = strip_tags($_POST['protect_email']);
$protect_remember = (int)$_POST['protect_remember'];
$protect_mc_form = $_POST['protect_mc_form'];
if(empty($password)) {
    $query = "UPDATE svt_virtualtours SET password=NULL,password_title=?,password_description=?,protect_type=?,protect_send_email=?,protect_email=?,protect_remember=?,protect_mc_form=? WHERE id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('sssisisi',$password_title,$password_description,$protect_type,$protect_send_email,$protect_email,$protect_remember,$protect_mc_form,$id_virtualtour);
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
} else {
    if($password=="keep_password") {
        $query = "UPDATE svt_virtualtours SET password_title=?,password_description=?,protect_type=?,protect_send_email=?,protect_email=?,protect_remember=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('sssisii',$password_title,$password_description,$protect_type,$protect_send_email,$protect_email,$protect_remember,$id_virtualtour);
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
    } else {
        $query = "UPDATE svt_virtualtours SET password=MD5(?),password_title=?,password_description=?,protect_type=?,protect_send_email=?,protect_email=?,protect_remember=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('ssssisii',$password,$password_title,$password_description,$protect_type,$protect_send_email,$protect_email,$protect_remember,$id_virtualtour);
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
    }
}