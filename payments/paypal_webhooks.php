<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if(file_exists("../config/demo.inc.php")) {
    require_once("../config/demo.inc.php");
    if($_SERVER['SERVER_ADDR']==DEMO_SERVER_IP) {
        //DEMO MODE
        die();
    }
}
require_once("../backend/functions.php");
require_once("../db/connection.php");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$settings = get_settings();
$client_id = $settings['paypal_client_id'];
$client_secret = $settings['paypal_client_secret'];
if($settings['paypal_live']) {
    $url_paypal = "api-m.paypal.com";
} else {
    $url_paypal = "api-m.sandbox.paypal.com";
}
$input = @file_get_contents("php://input");
$paypal_response = json_decode($input,true);

if(isset($paypal_response['event_type']) && ($paypal_response['event_type']=="BILLING.SUBSCRIPTION.CANCELLED" || $paypal_response['event_type']=="BILLING.SUBSCRIPTION.PAYMENT.FAILED")) {
    $id_subscription = $paypal_response['resource']['id'];
    $access_token = get_token($client_id, $client_secret);
    $subscription = get_subscription($access_token,$id_subscription);
    $end_date = $subscription['billing_info']['next_billing_time'];
    $tmp = explode("T",$end_date);
    $end_date = $tmp[0];
    $mysqli->query("UPDATE svt_users SET expire_plan_date='$end_date',id_subscription_paypal=NULL,status_subscription_paypal=0 WHERE id_subscription_paypal='$id_subscription';");
}

function get_token($client_id, $client_secret) {
    global $url_paypal;
    $access_token = '';
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
        die();
    } else {
        $response = json_decode($result,true);
        $access_token = $response['access_token'];
    }
    curl_close($ch);
    return $access_token;
}

function get_subscription($access_token,$id_subscription_paypal) {
    global $url_paypal;
    $response = mull;
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
        die();
    } else {
        $response = json_decode($result,true);
    }
    curl_close($ch);
    return $response;
}

