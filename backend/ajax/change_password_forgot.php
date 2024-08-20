<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si_l']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once(dirname(__FILE__).'/../functions.php');
$settings = get_settings();
set_language($settings['language'],$settings['language_domain']);
$forgot_code = strip_tags($_POST['forgot_code']);
$password = strip_tags($_POST['password']);
$query = "SELECT id FROM svt_users WHERE forgot_code=? LIMIT 1;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('s', $forgot_code);
    $result = $smt->execute();
    if ($result) {
        $result = get_result($smt);
        if (count($result) == 1) {
            $row = array_shift($result);
            $id_user = $row['id'];
            $query = "UPDATE svt_users SET password=MD5(?),forgot_code='' WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si', $password,$id_user);
                $result = $smt->execute();
                if($result) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok"));
                } else {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>_("Error, retry later")));
                }
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>_("Invalid verification code")));
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Invalid verification code")));
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Error, retry later")));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Error, retry later")));
}