<?php
header('Access-Control-Allow-Origin: *');
ob_start();
session_start();
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
if(isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
} else {
    die();
}
session_write_close();
$plan_permissions = get_plan_permission($id_user);
$n_ai_generate_month = $plan_permissions['n_ai_generate_month'];
if($n_ai_generate_month!=-1) {
    $ai_generated = get_user_ai_generated($id_user);
    if($ai_generated>=$n_ai_generate_month) {
        die();
    }
}
$settings = get_settings();
$api_key = $settings['ai_key'];
$prompt = $_POST['prompt'];
$style = $_POST['style'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://backend.blockadelabs.com/api/v1/skybox");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('api_key' => $api_key, 'prompt' => $prompt, 'skybox_style_id' => $style)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec($ch);
curl_close($ch);
if(!empty($server_output)) {
    $response = json_decode($server_output,true);
    $pusher_channel = $response['pusher_channel'];
    $pusher_event = $response['pusher_event'];
    if(!empty($pusher_channel) && !empty($pusher_event)) {
        $response = str_replace("'","\'",$response);
        $mysqli->query("INSERT INTO svt_ai_log(id_user, date_time, response) VALUES($id_user,NOW(),'$server_output');");
        ob_end_clean();
        echo json_encode(array('status'=>'ok','pusher_channel'=>$pusher_channel,'pusher_event'=>$pusher_event));
    } else {
        ob_end_clean();
        echo json_encode(array('status'=>'error','output'=>$server_output));
    }
} else {
    ob_end_clean();
    echo json_encode(array('status'=>'error'));
}