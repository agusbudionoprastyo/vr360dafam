<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
if($settings['paypal_live']) {
    $url_paypal = "api-m.paypal.com";
} else {
    $url_paypal = "api-m.sandbox.paypal.com";
}
$id_subscription_paypal = $user_info['id_subscription_paypal'];
if(empty($id_subscription_paypal)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
}
$client_id = $settings['paypal_client_id'];
$client_secret = $settings['paypal_client_secret'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $client_secret);
$headers = array();
$headers[] = 'Accept: application/json';
$headers[] = 'Accept-Language: en_US';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(array("status"=>"error","msg"=>curl_error($ch)));
    die();
} else {
    $response = json_decode($result,true);
    if(isset($response['error'])) {
        echo json_encode(array("status"=>"error","msg"=>$response['error_description']));
        die();
    } else {
        if(isset($response['access_token'])) {
            $access_token = $response['access_token'];
        } else {
            echo json_encode(array("status"=>"error","msg"=>"An error has occurred, please try again later"));
            die();
        }
    }
}
curl_close($ch);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/billing/subscriptions/'.$id_subscription_paypal);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Authorization: Bearer '.$access_token;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(array("status"=>"error","msg"=>curl_error($ch)));
    die();
} else {
    $response = json_decode($result,true);
    if(isset($response['plan_id'])) {
        $id_paypal_plan = $response['plan_id'];
        $end_date = $response['billing_info']['next_billing_time'];
        $tmp = explode("T",$end_date);
        $end_date = $tmp[0];
        $query = "SELECT name FROM svt_plans WHERE id_plan_paypal='$id_paypal_plan' OR id_plan2_paypal='$id_paypal_plan' LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $name = $row['name'];
                ob_end_clean();
                echo json_encode(array("status"=>"ok","name"=>$name,"end_date"=>$end_date));
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
        }
    } else {
        echo json_encode(array("status"=>"error","msg"=>"An error has occurred, please try again later"));
        die();
    }
}
curl_close($ch);