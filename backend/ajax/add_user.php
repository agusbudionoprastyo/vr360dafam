<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once(dirname(__FILE__).'/../functions.php');
$user_info = get_user_info($_SESSION['id_user']);
if($user_info['role']!='administrator') {
    die();
}
$settings = get_settings();
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
$username = strip_tags($_POST['username_svt']);
$email = strip_tags($_POST['email_svt']);
$password = strip_tags($_POST['password_svt']);
$role = strip_tags($_POST['role_svt']);
if($role=='super_admin') {
    $super_admin = 1;
    $role = 'administrator';
} else {
    $super_admin = 0;
}
$query_check = "SELECT id FROM svt_users WHERE username=?;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('s', $username);
    $result_check = $smt->execute();
    if ($result_check) {
        $result_check = get_result($smt);
        if (count($result_check) > 0) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Username already registered!")));
            exit;
        }
    }
}
$query_check = "SELECT id FROM svt_users WHERE email=?;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('s', $email);
    $result_check = $smt->execute();
    if ($result_check) {
        $result_check = get_result($smt);
        if (count($result_check) > 0) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("E-mail already registered!")));
            exit;
        }
    }
}
$query = "INSERT INTO svt_users(username,email,password,role,super_admin) VALUES(?,?,MD5(?),?,?); ";
if ($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssssi',$username,$email,$password,$role,$super_admin);
    $result = $smt->execute();
    if ($result) {
        $insert_id = $mysqli->insert_id;
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id_user"=>$insert_id));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}