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

$saas = check_if_saas();

if(!$saas) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(array("message"=>"unauthorized"));
    exit;
}

get_plans_api();
exit;

function get_plans_api() {
    global $mysqli;
    $plans = array();
    $query = "SELECT * FROM svt_plans;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                unset($row['id_product_stripe']);
                unset($row['id_price_stripe']);
                unset($row['id_price2_stripe']);
                unset($row['id_plan_paypal']);
                unset($row['id_plan2_paypal']);
                unset($row['id_product_2checkout']);
                unset($row['id_product2_2checkout']);
                unset($row['customize_menu']);
                $plans[] = $row;
            }
            ob_end_clean();
            http_response_code(200);
            echo json_encode(array("message"=>"ok","data"=>$plans));
            exit;
        } else {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(array("message"=>"no plans found"));
            exit;
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
}