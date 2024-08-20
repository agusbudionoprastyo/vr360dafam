<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
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
if($method!='POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(array("message"=>"invalid method $method"));
    exit;
}

if(!empty($_POST)) {
    $params = $_POST;
} else {
    $content = trim(file_get_contents("php://input"));
    $params = json_decode($content, true);
}

$mandatory_params = ['username','password'];
check_api_missing_params($params,$mandatory_params);

if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("api/login.php","",$_SERVER['SCRIPT_NAME']);

check_login($params['username'],$params['password']);
exit;

function check_login($username,$password) {
    global $mysqli,$base_url;
    $username = strip_tags($username);
    $password = strip_tags($password);
    $query = "SELECT id FROM svt_users WHERE (username=? OR email=?) LIMIT 1;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('ss', $username, $username);
        $result = $smt->execute();
        if ($result) {
            $result = get_result($smt);
            if (count($result) == 1) {
                $row = array_shift($result);
                $id_user = $row['id'];
                $query = "SELECT * FROM svt_users WHERE id=? AND password=MD5(?) LIMIT 1;";
                if($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('is', $id_user, $password);
                    $result = $smt->execute();
                    if ($result) {
                        $result = get_result($smt);
                        if (count($result) == 1) {
                            $row = array_shift($result);
                            if($row['active']) {
                                $signer = new MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256('12345678901234567890123456789012');
                                $generator = new MiladRahimi\Jwt\Generator($signer);
                                $expirationTime = time() + 86400;
                                $jwt = $generator->generate(['id_user' => $id_user,'exp'=>$expirationTime]);
                                $token_login = encrypt_decrypt('encrypt',$id_user,date('Ymd'));
                                $login_url = $base_url."backend/login.php?token=".$token_login;
                                ob_end_clean();
                                http_response_code(200);
                                echo json_encode(array("message"=>"ok","token"=>$jwt,"login_url"=>$login_url));
                                exit;
                            } else {
                                ob_end_clean();
                                http_response_code(201);
                                echo json_encode(array("message"=>"blocked"));
                                exit;
                            }
                        } else {
                            ob_end_clean();
                            http_response_code(202);
                            echo json_encode(array("message"=>"incorrect password"));
                            exit;
                        }
                    } else {
                        ob_end_clean();
                        http_response_code(500);
                        echo json_encode(array("message"=>"error"));
                        exit;
                    }
                } else {
                    ob_end_clean();
                    http_response_code(500);
                    echo json_encode(array("message"=>"error"));
                    exit;
                }
            } else {
                ob_end_clean();
                http_response_code(203);
                echo json_encode(array("message"=>"incorrect username"));
                exit;
            }
        } else {
            ob_end_clean();
            http_response_code(500);
            echo json_encode(array("message"=>"error"));
            exit;
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
}