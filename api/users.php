<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once("../db/connection.php");
require_once("../backend/functions.php");
require_once("api_functions.php");
require_once("vendor/autoload.php");

register_shutdown_function("fatal_handler");

$method = $_SERVER["REQUEST_METHOD"];
if($method!='GET') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(array("message"=>"invalid method $method"));
    exit;
}

if(!empty($_GET)) {
    $params = $_GET;
} else {
    $content = trim(file_get_contents("php://input"));
    $params = json_decode($content, true);
}

$mandatory_params = ['token'];
check_api_missing_params($params,$mandatory_params);
$payload = validate_token($params['token']);
$id_user = $payload['id_user'];

$user_api_info = get_user_info($id_user);
$user_api_role = $user_api_info['role'];
$demo = check_if_demo($id_user);
$saas = check_if_saas();

if($user_api_role!='administrator' || $demo || !$saas) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(array("message"=>"unauthorized"));
    exit;
}

if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("api/user.php","",$_SERVER['SCRIPT_NAME']);

get_users_api($params);
exit;

function get_users_api($params) {
    global $mysqli,$base_url;
    $users = array();
    if(isset($params['limit']) && $params['limit']!='') {
        $limit = (int)$params['limit'];
    } else {
        $limit = 99999;
    }
    if(isset($params['offset']) && $params['offset']!='') {
        $offset = (int)$params['offset'];
    } else {
        $offset = 0;
    }
    $query = "SELECT u.*,COALESCE(p.name, '--') as plan_name,(SELECT COUNT(*) FROM svt_virtualtours WHERE id_user=u.id) as count_vt FROM svt_users as u LEFT JOIN svt_plans as p ON p.id = u.id_plan LIMIT $offset,$limit;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                unset($row['password']);
                unset($row['forgot_code']);
                unset($row['hash']);
                unset($row['id_customer_stripe']);
                unset($row['id_customer_2checkout']);
                unset($row['id_subscription_stripe']);
                unset($row['id_subscription_paypal']);
                unset($row['id_subscription_2checkout']);
                unset($row['status_subscription_stripe']);
                unset($row['status_subscription_paypal']);
                unset($row['status_subscription_2checkout']);
                unset($row['storage_space']);
                unset($row['google_identifier']);
                unset($row['facebook_identifier']);
                unset($row['twitter_identifier']);
                unset($row['wechat_identifier']);
                unset($row['qq_identifier']);
                unset($row['2fa_secretkey']);
                unset($row['max_storage_space']);
                if(!empty($row['avatar'])) {
                    $row['avatar'] = $base_url."backend/".$row['avatar'];
                }
                $users[] = $row;
            }
            ob_end_clean();
            http_response_code(200);
            echo json_encode(array("message"=>"ok","data"=>$users));
            exit;
        } else {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(array("message"=>"no users found"));
            exit;
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
}