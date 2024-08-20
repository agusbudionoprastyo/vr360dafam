<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si_l']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once('../functions.php');
$username = strip_tags($_POST['username_svt']);
$password = strip_tags($_POST['password_svt']);
$remember_me = (int)$_POST['remember_svt'];
$autologin = (int)$_POST['autologin'];
$result = $mysqli->query("SHOW COLUMNS FROM svt_users LIKE 'email';");
if($result) {
    if ($result->num_rows==0) {
        $mysqli->query("ALTER TABLE svt_users ADD `email` varchar(100) DEFAULT NULL AFTER `username`;");
    }
}
$id_user = 0;
$query = "SELECT id FROM svt_users WHERE (username=? OR email=?) LIMIT 1;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ss', $username, $username);
    $result = $smt->execute();
    if ($result) {
        $result = get_result($smt);
        if (count($result) == 1) {
            $row = array_shift($result);
            $id_user = $row['id'];
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"incorrect_username"));
            exit;
        }
    }
}

$settings = get_settings();
$twofa_enabled = $settings['2fa_enable'];

$query = "SELECT * FROM svt_users WHERE id=? AND password=MD5(?) LIMIT 1;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('is', $id_user, $password);
    $result = $smt->execute();
    if ($result) {
        $result = get_result($smt);
        if (count($result) == 1) {
            $row = array_shift($result);
            if($row['active']) {
                if($autologin==0 && $twofa_enabled && !empty($row['2fa_secretkey'])) {
                    $_SESSION['id_user_2fa'] = $id_user;
                    session_write_close();
                    ob_end_clean();
                    echo json_encode(array("status"=>"2fa"));
                } else {
                    try {
                        $browser = parse_user_agent();
                        set_user_log($id_user,'login',$_SERVER['REMOTE_ADDR']." - ".$browser['browser']." ".$browser['version']." - ".$browser['platform'],date('Y-m-d H:i:s', time()));
                    } catch (Exception $e) {}
                    $_SESSION['id_user'] = $id_user;
                    unset($_SESSION['lang']);
                    if($remember_me==1) {
                        $cookieExpiration = time() + (30 * 24 * 60 * 60);
                        setcookie("svt_login", 1, $cookieExpiration, "/");
                        setcookie("svt_username", encrypt_decrypt('encrypt',$username,'svt'), $cookieExpiration, "/");
                        setcookie("svt_password", encrypt_decrypt('encrypt',$password,'svt'), $cookieExpiration, "/");
                    } else {
                        $cookieExpiration = time() - 3600;
                        setcookie('svt_login', '', $cookieExpiration, "/");
                        setcookie('svt_username', '', $cookieExpiration, "/");
                        setcookie('svt_password', '', $cookieExpiration, "/");
                        unset($_COOKIE['svt_autologin']);
                        unset($_COOKIE['svt_username']);
                        unset($_COOKIE['svt_password']);
                    }
                    session_write_close();
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok","id"=>$row['id'],"role"=>$row['role'],"email"=>$row['email']));
                }
            } else {
                ob_end_clean();
                $cookieExpiration = time() - 3600;
                setcookie('svt_login', '', $cookieExpiration, "/");
                setcookie('svt_username', '', $cookieExpiration, "/");
                setcookie('svt_password', '', $cookieExpiration, "/");
                unset($_COOKIE['svt_autologin']);
                unset($_COOKIE['svt_username']);
                unset($_COOKIE['svt_password']);
                echo json_encode(array("status"=>"blocked"));
            }
        } else {
            ob_end_clean();
            $cookieExpiration = time() - 3600;
            setcookie('svt_login', '', $cookieExpiration, "/");
            setcookie('svt_username', '', $cookieExpiration, "/");
            setcookie('svt_password', '', $cookieExpiration, "/");
            unset($_COOKIE['svt_autologin']);
            unset($_COOKIE['svt_username']);
            unset($_COOKIE['svt_password']);
            echo json_encode(array("status"=>"incorrect_password"));
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}