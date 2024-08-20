<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si_l']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$settings = get_settings();
set_language($settings['language'],$settings['language_domain']);
$id_plan = $settings['default_id_plan'];
$username = strip_tags($_POST['username_svt']);
$email = strip_tags($_POST['email_svt']);
$password = strip_tags($_POST['password_svt']);
$social_provider = strip_tags($_POST['social_provider']);
$social_identifier = strip_tags($_POST['social_identifier']);
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
if(!empty($social_provider) && !empty($social_identifier)) {
    $field_identifier = strtolower($social_provider)."_identifier";
    $query_check = "SELECT id FROM svt_users WHERE $field_identifier=?;";
    if($smt = $mysqli->prepare($query_check)) {
        $smt->bind_param('s', $social_identifier);
        $result_check = $smt->execute();
        if ($result_check) {
            $result_check = get_result($smt);
            if (count($result_check) > 0) {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
                exit;
            }
        }
    }
}
$validate_email = $settings['validate_email'];
if($validate_email) {
    $active = 0;
    $hash = md5(rand(0,1000));
} else {
    $active = 1;
    $hash = "";
}
$query = "INSERT INTO svt_users(username,email,password,role,id_plan,active,hash) VALUES(?,?,MD5(?),'customer',?,?,?); ";
if ($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssiis',$username,$email,$password,$id_plan,$active,$hash);
    $result = $smt->execute();
    if ($result) {
        $user_id = $mysqli->insert_id;
        $plan = get_plan($id_plan);
        $browser = parse_user_agent();
        $date = date('Y-m-d H:i:s', (time() + 1));
        set_user_log($user_id,'register',$_SERVER['REMOTE_ADDR']." - ".$browser['browser']." ".$browser['version']." - ".$browser['platform'],date('Y-m-d H:i:s', time()));
        if(!empty($id_plan)) {
            set_user_log($user_id,'subscribe_plan',json_encode(array("id"=>$id_plan,"name"=>$plan['name'])),date('Y-m-d H:i:s', (time() + 1)));
        }
        if(!$validate_email) {
            $_SESSION['id_user'] = $user_id;
            set_user_log($user_id,'login',$_SERVER['REMOTE_ADDR']." - ".$browser['browser']." ".$browser['version']." - ".$browser['platform'],date('Y-m-d H:i:s', (time() + 2)));
        }
        if(!empty($social_provider) && !empty($social_identifier)) {
            $field_identifier = strtolower($social_provider)."_identifier";
            $mysqli->query("UPDATE svt_users SET $field_identifier='$social_identifier' WHERE $field_identifier IS NULL AND id=".$user_id);
            unset($_SESSION['social_identifier']);
            unset($_SESSION['social_provider']);
        }
        session_write_close();
        update_plans_expires_date($user_id);
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id_user"=>$user_id,"validate_email"=>$validate_email));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}