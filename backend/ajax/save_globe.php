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
$id_globe = (int)$_POST['id'];
$code = "";
$logo_exist = "";
$favicon_ok = 1;
$query = "SELECT code,logo FROM svt_globes WHERE id=$id_globe LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $logo_exist = $row['logo'];
    }
}
$name = strip_tags($_POST['name']);
$pointer_size = $_POST['pointer_size'];
if(empty($pointer_size) || $pointer_size<=0) $pointer_size=15;
$pointer_size = (int)$pointer_size;
$pointer_color = strip_tags($_POST['pointer_color']);
if(empty($pointer_color)) $pointer_color='rgb(255,255,255)';
$pointer_border = strip_tags($_POST['pointer_border']);
if(empty($pointer_border)) $pointer_border='rgb(0,0,0)';
$logo = strip_tags($_POST['logo']);
$center_altitude = $_POST['center_altitude'];
if(empty($center_altitude)) $center_altitude = NULL; else $center_altitude = (int)$center_altitude;
$min_altitude = $_POST['min_altitude'];
if(empty($min_altitude)) $min_altitude = NULL; else $min_altitude = (int)$min_altitude;
$center_lat = str_replace(',','.',$_POST['center_lat']);
$center_lon = str_replace(',','.',$_POST['center_lon']);
$zoom_duration = $_POST['zoom_duration'];
if(empty($zoom_duration)) $zoom_duration=1;
if($zoom_duration < 1) $zoom_duration=1;
$zoom_duration = (int)$zoom_duration;
$default_view = strip_tags($_POST['default_view']);
$type = strip_tags($_POST['type']);
$custom_css = $_POST['custom_css'];
$url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'globe'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_'.$code.'.css';
if(file_exists($url_css) && $custom_css=='') {
    @unlink($url_css);
} else {
    if($custom_css!='') {
        @file_put_contents($url_css,$custom_css);
    }
}
$query = "UPDATE svt_globes SET name=?,type=?,logo=?,pointer_size=?,pointer_color=?,pointer_border=?,center_altitude=?,min_altitude=?,center_lat=?,center_lon=?,zoom_duration=?,default_view=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssissiissisi', $name,$type,$logo,$pointer_size,$pointer_color,$pointer_border,$center_altitude,$min_altitude,$center_lat,$center_lon,$zoom_duration,$default_view,$id_globe);
    $result = $smt->execute();
    if($result) {
        $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        if(empty($logo)) {
            if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "g_$code")) {
                array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "g_$code" . DIRECTORY_SEPARATOR ."*.*"));
                rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "g_$code" . DIRECTORY_SEPARATOR);
            }
        } else {
            if($logo!=$logo_exist) {
                if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "g_$code")) {
                    array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "g_$code" . DIRECTORY_SEPARATOR ."*.*"));
                    rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "g_$code" . DIRECTORY_SEPARATOR);
                }
            }
            $favicon_ok = generate_favicons('globe',$id_globe);
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