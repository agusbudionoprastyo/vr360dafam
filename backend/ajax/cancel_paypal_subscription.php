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
$id_user = $_SESSION['id_user'];
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
$id_subscription_paypal = $user_info['id_subscription_paypal'];
if(empty($id_subscription_paypal)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
}
$client_id = $settings['paypal_client_id'];
$client_secret = $settings['paypal_client_secret'];
if($settings['paypal_live']) {
    $url_paypal = "api-m.paypal.com";
} else {
    $url_paypal = "api-m.sandbox.paypal.com";
}
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
    } else {
        echo json_encode(array("status"=>"error","msg"=>"An error has occurred, please try again later"));
        die();
    }
}
curl_close($ch);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/billing/subscriptions/'.$id_subscription_paypal.'/cancel');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{
  "reason": "Subscription canceled by user"
}');
$headers = array();
$headers[] = 'Content-Type: application/json';
$headers[] = 'Authorization: Bearer '.$access_token;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
    echo json_encode(array("status"=>"error","msg"=>curl_error($ch)));
    die();
} else {
    if($httpcode==204) {
        $mysqli->query("UPDATE svt_users SET expire_plan_date='$end_date',id_subscription_paypal=NULL,status_subscription_paypal=0 WHERE id=$id_user;");
        $query = "SELECT u.id,u.username,u.email,u.expire_plan_date,p.name as plan,p.id as id_plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user;";
        $result = $mysqli->query($query);
        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $id_user = $row['id'];
                    $username = $row['username'];
                    $email_u = $row['email'];
                    $plan = $row['plan'];
                    $id_plan = $row['id_plan'];
                    set_user_log($id_user,'unsubscribe_plan',json_encode(array("id"=>$id_plan,"name"=>$plan)),date('Y-m-d H:i:s', time()));
                    if ($settings['notify_plan_cancels']) {
                        $expire_plan_date = $row['expire_plan_date'];
                        $subject = $settings['mail_plan_canceled_subject'];
                        $body = $settings['mail_plan_canceled_body'];
                        $body = str_replace("%USER_NAME%", $username, $body);
                        $body = str_replace("%PLAN_NAME%", $plan, $body);
                        $body = str_replace('<p><br></p>', '<br>', $body);
                        $body = str_replace('<p>', '<p style="padding:0;margin:0;">', $body);
                        $subject_q = str_replace("'", "\'", $subject);
                        $body_q = str_replace("'", "\'", $body);
                        $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notify_user,notified) VALUES($id_user,'$subject_q','$body_q',1,0);");
                    }
                }
            }
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("An error has occurred, please try again later")));
    }
}
curl_close($ch);