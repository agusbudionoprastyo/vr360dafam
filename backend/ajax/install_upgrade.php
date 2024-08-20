<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
ini_set('max_execution_time', 600000);
set_time_limit(600000);
ini_set('memory_limit', -1);
if(file_exists(dirname(__FILE__).'/../../update_svt_m.zip')) {
    $file = dirname(__FILE__).'/../../update_svt_m.zip';
} else {
    $file = dirname(__FILE__).'/../../update_svt.zip';
}
if(file_exists($file)) {
    $path = pathinfo(realpath($file), PATHINFO_DIRNAME);
    $zip = new ZipArchive;
    $res = $zip->open($file);
    if ($res === TRUE) {
        $zip->extractTo($path);
        $zip->close();
        unlink($file);
        $lang = $_SESSION['lang'];
        unset($_SESSION['id_user']);
        session_destroy();
        session_start();
        $_SESSION['lang'] = $lang;
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