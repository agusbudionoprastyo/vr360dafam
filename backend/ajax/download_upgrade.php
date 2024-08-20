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
session_write_close();
$version = $_POST['version'];
if(file_exists(dirname(__FILE__).'/../../update_svt_m.zip')) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
    exit;
}
if(file_exists(dirname(__FILE__).'/../../update_svt.zip')) {
    unlink(dirname(__FILE__).'/../../update_svt.zip');
}
$url = base64_decode("aHR0cHM6Ly9zaW1wbGVkZW1vLml0L3N2dF9yZXBvLw==").$version.base64_decode("L3VwZGF0ZV9zdnQuemlw");
if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
    $options = array('http' => array('user_agent' => base64_decode('c3Z0X3VzZXJfYWdlbnQ=')),"ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false));
    $context = stream_context_create($options);
    $file = file_get_contents($url, false, $context);
    if(empty($file)) {
        $file = curl_get_file_contents($url);
    }
} else {
    $file = curl_get_file_contents($url);
}
file_put_contents(dirname(__FILE__).'/../../update_svt.zip', $file);
if(file_exists(dirname(__FILE__).'/../../update_svt.zip')) {
    if(filesize(dirname(__FILE__).'/../../update_svt.zip')>0) {
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

function curl_get_file_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, base64_decode('c3Z0X3VzZXJfYWdlbnQ='));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}