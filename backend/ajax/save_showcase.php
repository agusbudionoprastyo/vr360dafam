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
$id_showcase = (int)$_POST['id'];
$code = "";
$logo_exist = "";
$favicon_ok = 1;
$query = "SELECT code,logo FROM svt_showcases WHERE id=$id_showcase LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $logo_exist = $row['logo'];
    }
}
$name = strip_tags($_POST['name']);
$bg_color = strip_tags($_POST['bg_color']);
if(empty($bg_color)) $bg_color='#eeeeee';
$logo = strip_tags($_POST['logo']);
$banner = strip_tags($_POST['banner']);
$list_s_vt = $_POST['list_s_vt'];
$list_s_type = $_POST['list_s_type'];
$list_s_priority = $_POST['list_s_priority'];
$header_html = htmlspecialchars_decode($_POST['header_html']);
$footer_html = htmlspecialchars_decode($_POST['footer_html']);
$custom_css = $_POST['custom_css'];
$sort_settings = $_POST['sort_settings'];
$url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'showcase'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_'.$code.'.css';
if(file_exists($url_css) && $custom_css=='') {
    @unlink($url_css);
} else {
    if($custom_css!='') {
        @file_put_contents($url_css,$custom_css);
    }
}
$query = "UPDATE svt_showcases SET name=?,bg_color=?,logo=?,banner=?,header_html=?,footer_html=?,sort_settings=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssssssi',$name,$bg_color,$logo,$banner,$header_html,$footer_html,$sort_settings,$id_showcase);
    $result = $smt->execute();
    if($result) {
        $mysqli->query("DELETE FROM svt_showcase_list WHERE id_showcase=$id_showcase;");
        foreach ($list_s_vt as $index=>$id_vt) {
            $type = $list_s_type[$index];
            $priority = $list_s_priority[$index];
            $mysqli->query("INSERT INTO svt_showcase_list(id_showcase,id_virtualtour,type_viewer,priority) VALUES($id_showcase,$id_vt,'$type',$priority);");
        }
        $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        if(empty($logo)) {
            if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code")) {
                array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code" . DIRECTORY_SEPARATOR ."*.*"));
                rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code" . DIRECTORY_SEPARATOR);
            }
        } else {
            if($logo!=$logo_exist) {
                if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code")) {
                    array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code" . DIRECTORY_SEPARATOR ."*.*"));
                    rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "s_$code" . DIRECTORY_SEPARATOR);
                }
            }
            $favicon_ok = generate_favicons('showcase',$id_showcase);
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok","favicon"=>$favicon_ok));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}