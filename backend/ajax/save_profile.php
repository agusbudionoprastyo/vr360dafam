<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once(dirname(__FILE__).'/../functions.php');
$settings = get_settings();
$id_user = $_SESSION['id_user'];
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$username = strip_tags($_POST['username_svt']);
$email = strip_tags($_POST['email_svt']);
$language = strip_tags($_POST['language_svt']);
$avatar = strip_tags($_POST['avatar_svt']);
$first_name = strip_tags($_POST['first_name']);
$last_name = strip_tags($_POST['last_name']);
$company = strip_tags($_POST['company']);
$tax_id = strip_tags($_POST['tax_id']);
$street = strip_tags($_POST['street']);
$city = strip_tags($_POST['city']);
$province = strip_tags($_POST['province']);
$postal_code = strip_tags($_POST['postal_code']);
$country = strip_tags($_POST['country']);
$tel = strip_tags($_POST['tel']);
$query_check = "SELECT id FROM svt_users WHERE username=? AND id!=?;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('si', $username, $id_user);
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
$query_check = "SELECT id FROM svt_users WHERE email=? AND id!=?;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('si', $email, $id_user);
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
$reload = 0;
if (strpos($avatar, 'data:image') !== false) {
    $avatar_image = base64_decode(explode(",",$avatar)[1]);
    $im = @imagecreatefromstring($avatar_image);
    $name_avatar = 'avatar_'.time().'.jpg';
    imagejpeg($im, dirname(__FILE__).'/../assets/'.$name_avatar,100);
    if(file_exists(dirname(__FILE__).'/../assets/'.$name_avatar)) {
        $query = "UPDATE svt_users SET avatar=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si', $name_avatar, $id_user);
            $smt->execute();
            $reload = true;
        }
    }
}
$query_l = "SELECT language,username FROM svt_users WHERE id=? LIMIT 1;";
if($smt = $mysqli->prepare($query_l)) {
    $smt->bind_param('i',  $id_user);
    $result_l = $smt->execute();
    if ($result_l) {
        $result_l = get_result($smt);
        if (count($result_l) == 1) {
            $row_l = array_shift($result_l);
            $language_exist = $row_l['language'];
            $username_exist = $row_l['username'];
            if($language!=$language_exist) {
                $_SESSION['lang']=$language;
                $reload = 1;
            }
            if($username!=$username_exist) {
                $reload = 1;
            }
        }
    }
}
session_write_close();
$query = "UPDATE svt_users SET username=?,email=?,language=?,first_name=?,last_name=?,company=?,tax_id=?,street=?,city=?,province=?,postal_code=?,country=?,tel=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssssssssssssi',  $username,$email,$language,$first_name,$last_name,$company,$tax_id,$street,$city,$province,$postal_code,$country,$tel,$id_user);
    $result = $smt->execute();
    if ($result) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok","reload_page"=>$reload));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Error")));
    }
} else {
    echo json_encode(array("status"=>"error","msg"=>_("Error")));
}