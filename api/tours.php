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

if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("api/tours.php","",$_SERVER['SCRIPT_NAME']);

get_tours_api($params,$id_user);
exit;

function get_tours_api($params,$id_user) {
    global $mysqli,$base_url;
    $tours = array();
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
    $where = "";
    switch(get_user_role($id_user)) {
        case 'customer':
            $where = " AND v.id_user=$id_user ";
            break;
        case 'editor':
            $where  = " AND v.id IN () ";
            $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $ids = $row['ids'];
                    $where = " AND v.id IN ($ids) ";
                }
            }
            break;
    }
    $query = "SELECT v.id,v.external,v.name,v.date_created,v.author,v.id_user,v.active,COUNT(DISTINCT r.id) as count_rooms,v.background_image
            FROM svt_virtualtours as v 
            LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id
            WHERE 1=1 $where
            GROUP BY v.id,v.external,v.name,v.date_created,v.author,v.id_user,v.active,v.background_image
            ORDER BY v.date_created DESC,v.id DESC
            LIMIT $offset,$limit;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                if(!empty($row['background_image'])) {
                    $row['background_image'] = $base_url.'viewer/content/'.$row['background_image'];
                }
                $tours[] = $row;
            }
            ob_end_clean();
            http_response_code(200);
            echo json_encode(array("message"=>"ok","data"=>$tours));
            exit;
        } else {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(array("message"=>"no tours found"));
            exit;
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
}