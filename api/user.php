<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT");
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
if($method!='POST' && $method!='GET' && $method!='PUT') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(array("message"=>"invalid method $method"));
    exit;
}

switch($method) {
    case 'GET':
        $params = $_GET;
        break;
    case 'POST':
        if(!empty($_POST)) {
            $params = $_POST;
        } else {
            $content = trim(file_get_contents("php://input"));
            $params = json_decode($content, true);
        }
        break;
    case 'PUT':
        parse_str(file_get_contents("php://input"), $_PUT);
        foreach ($_PUT as $key => $value) {
            unset($_PUT[$key]);
            $params[str_replace('amp;', '', $key)] = $value;
        }
        break;
}

$mandatory_params = ['token'];
check_api_missing_params($params,$mandatory_params);
$payload = validate_token($params['token']);
$id_user = $payload['id_user'];

$user_api_info = get_user_info($id_user);
$user_api_role = $user_api_info['role'];
$user_api_superadmin = $user_api_info['super_admin'];
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

switch($method) {
    case 'GET': //GET USER
        $mandatory_params = ['id'];
        check_api_missing_params($params,$mandatory_params);
        $user_info = get_user_info($params['id']);
        unset($user_info['password']);
        unset($user_info['forgot_code']);
        unset($user_info['hash']);
        unset($user_info['id_customer_stripe']);
        unset($user_info['id_customer_2checkout']);
        unset($user_info['id_subscription_stripe']);
        unset($user_info['id_subscription_paypal']);
        unset($user_info['id_subscription_2checkout']);
        unset($user_info['status_subscription_stripe']);
        unset($user_info['status_subscription_paypal']);
        unset($user_info['status_subscription_2checkout']);
        unset($user_info['storage_space']);
        unset($user_info['google_identifier']);
        unset($user_info['facebook_identifier']);
        unset($user_info['twitter_identifier']);
        unset($user_info['wechat_identifier']);
        unset($user_info['qq_identifier']);
        unset($user_info['2fa_secretkey']);
        unset($user_info['max_storage_space']);
        if(!empty($user_info['avatar'])) {
            $user_info['avatar'] = $base_url."backend/".$user_info['avatar'];
        }
        if(empty($user_info)) {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(array("message"=>"user not found"));
            exit;
        } else {
            ob_end_clean();
            http_response_code(200);
            echo json_encode(array("message"=>"ok",'data'=>$user_info));
            exit;
        }
        break;
    case 'POST': //ADD USER
        $mandatory_params = ['username','email','password','role'];
        check_api_missing_params($params,$mandatory_params);
        if($params['role']=='super_admin') {
            $super_admin = 1;
            $params['super_admin'] = 1;
            $role = 'administrator';
        } else {
            $super_admin = 0;
            $params['super_admin'] = 0;
            $role = $params['role'];
        }
        if(empty($params['id_plan'])) $params['id_plan']=0;
        if($role!='super_admin' && $role!='administrator' && $role!='editor' && $role!='customer') {
            ob_end_clean();
            http_response_code(407);
            echo json_encode(array("message"=>"invalid role value: $role"));
            exit;
        }
        if($user_api_superadmin==0 && $super_admin==1) {
            ob_end_clean();
            http_response_code(406);
            echo json_encode(array("message"=>"you can not add a super_admin user from an admin account"));
            exit;
        }
        add_user($params);
        break;
    case 'PUT': //EDIT USER
        $mandatory_params = ['id'];
        check_api_missing_params($params,$mandatory_params);
        $user_info = get_user_info($params['id']);
        if($user_info['super_admin']==1 && $user_api_superadmin==0) {
            ob_end_clean();
            http_response_code(406);
            echo json_encode(array("message"=>"you can not edit a super_admin user from an admin account"));
            exit;
        }
        if(!empty($params['role'])) {
            if($params['role']!='super_admin' && $params['role']!='administrator' && $params['role']!='editor' && $params['role']!='customer') {
                ob_end_clean();
                http_response_code(407);
                echo json_encode(array("message"=>"invalid role value: {$params['role']}"));
                exit;
            }
            if($params['role']=='super_admin' && $user_api_superadmin==0) {
                ob_end_clean();
                http_response_code(406);
                echo json_encode(array("message"=>"you can not set super_admin role from an admin account"));
                exit;
            }
        }
        edit_user($params);
        break;
}

function add_user($params) {
    global $mysqli;
    $query_check = "SELECT id FROM svt_users WHERE username=?;";
    if($smt = $mysqli->prepare($query_check)) {
        $smt->bind_param('s', $params['username']);
        $result_check = $smt->execute();
        if ($result_check) {
            $result_check = get_result($smt);
            if (count($result_check) > 0) {
                ob_end_clean();
                http_response_code(201);
                echo json_encode(array("message"=>"Username {$params['username']} already registered"));
                exit;
            }
        }
    }
    $query_check = "SELECT id FROM svt_users WHERE email=?;";
    if($smt = $mysqli->prepare($query_check)) {
        $smt->bind_param('s', $params['email']);
        $result_check = $smt->execute();
        if ($result_check) {
            $result_check = get_result($smt);
            if (count($result_check) > 0) {
                ob_end_clean();
                http_response_code(202);
                echo json_encode(array("message"=>"E-Mail {$params['email']} already registered"));
                exit;
            }
        }
    }
    $query = "INSERT INTO svt_users(username,email,password,role,super_admin,id_plan) VALUES(?,?,MD5(?),?,?,?); ";
    if ($smt = $mysqli->prepare($query)) {
        $smt->bind_param('ssssii',$params['username'],$params['email'],$params['password'],$params['role'],$params['super_admin'],$params['id_plan']);
        $result = $smt->execute();
        if ($result) {
            $insert_id = $mysqli->insert_id;
            if(!empty($params['first_name'])) {
                $query = "UPDATE svt_users SET first_name=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['first_name'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['last_name'])) {
                $query = "UPDATE svt_users SET last_name=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['last_name'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['company'])) {
                $query = "UPDATE svt_users SET company=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['company'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['tax_id'])) {
                $query = "UPDATE svt_users SET tax_id=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['tax_id'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['street'])) {
                $query = "UPDATE svt_users SET street=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['street'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['city'])) {
                $query = "UPDATE svt_users SET city=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['city'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['postal_code'])) {
                $query = "UPDATE svt_users SET postal_code=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['postal_code'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['province'])) {
                $query = "UPDATE svt_users SET province=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['province'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['country'])) {
                $query = "UPDATE svt_users SET country=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['country'],$insert_id);
                    $smt->execute();
                }
            }
            if(!empty($params['tel'])) {
                $query = "UPDATE svt_users SET tel=? WHERE id=?;";
                if ($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('si',$params['tel'],$insert_id);
                    $smt->execute();
                }
            }
            update_plans_expires_date($insert_id);
            ob_end_clean();
            http_response_code(200);
            echo json_encode(array("message"=>"ok","id_user"=>$insert_id));
            exit;
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

function edit_user($params) {
    global $mysqli;
    $query_check = "SELECT id FROM svt_users WHERE id=?;";
    if($smt = $mysqli->prepare($query_check)) {
        $smt->bind_param('i', $params['id']);
        $result_check = $smt->execute();
        if ($result_check) {
            $result_check = get_result($smt);
            if (count($result_check) == 0) {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(array("message"=>"User id not found"));
                exit;
            }
        }
    }
    if(!empty($params['username'])) {
        $query_check = "SELECT id FROM svt_users WHERE username=? AND id!=?;";
        if($smt = $mysqli->prepare($query_check)) {
            $smt->bind_param('si', $params['username'],$params['id']);
            $result_check = $smt->execute();
            if ($result_check) {
                $result_check = get_result($smt);
                if (count($result_check) > 0) {
                    ob_end_clean();
                    http_response_code(201);
                    echo json_encode(array("message"=>"Username {$params['username']} already registered"));
                    exit;
                } else {
                    $query = "UPDATE svt_users SET username=? WHERE id=?;";
                    if ($smt = $mysqli->prepare($query)) {
                        $smt->bind_param('si',$params['username'],$params['id']);
                        $smt->execute();
                    }
                }
            }
        }
    }
    if(!empty($params['email'])) {
        $query_check = "SELECT id FROM svt_users WHERE email=? AND id!=?;";
        if($smt = $mysqli->prepare($query_check)) {
            $smt->bind_param('si', $params['email'],$params['id']);
            $result_check = $smt->execute();
            if ($result_check) {
                $result_check = get_result($smt);
                if (count($result_check) > 0) {
                    ob_end_clean();
                    http_response_code(202);
                    echo json_encode(array("message"=>"E-Mail {$params['email']} already registered"));
                    exit;
                } else {
                    $query = "UPDATE svt_users SET email=? WHERE id=?;";
                    if ($smt = $mysqli->prepare($query)) {
                        $smt->bind_param('si',$params['email'],$params['id']);
                        $smt->execute();
                    }
                }
            }
        }
    }
    if(!empty($params['password'])) {
        $query = "UPDATE svt_users SET password=MD5(?) WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['password'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['role'])) {
        if($params['role']=='super_admin') {
            $super_admin = 1;
            $role = 'administrator';
        } else {
            $super_admin = 0;
            $role = $params['role'];
        }
        $query = "UPDATE svt_users SET role=?,super_admin=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('sii',$role,$super_admin,$params['id']);
            $smt->execute();
        }
    }
    if (isset($params['id_plan']) && $params['id_plan'] !== '') {
        $query = "UPDATE svt_users SET id_plan=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('ii',$params['id_plan'],$params['id']);
            $smt->execute();
        }
        update_plans_expires_date($params['id']);
    }
    if (isset($params['active']) && $params['active'] !== '') {
        $query = "UPDATE svt_users SET active=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('ii',$params['active'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['first_name'])) {
        $query = "UPDATE svt_users SET first_name=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['first_name'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['last_name'])) {
        $query = "UPDATE svt_users SET last_name=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['last_name'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['company'])) {
        $query = "UPDATE svt_users SET company=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['company'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['tax_id'])) {
        $query = "UPDATE svt_users SET tax_id=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['tax_id'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['street'])) {
        $query = "UPDATE svt_users SET street=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['street'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['city'])) {
        $query = "UPDATE svt_users SET city=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['city'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['postal_code'])) {
        $query = "UPDATE svt_users SET postal_code=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['postal_code'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['province'])) {
        $query = "UPDATE svt_users SET province=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['province'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['country'])) {
        $query = "UPDATE svt_users SET country=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['country'],$params['id']);
            $smt->execute();
        }
    }
    if(!empty($params['tel'])) {
        $query = "UPDATE svt_users SET tel=? WHERE id=?;";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$params['tel'],$params['id']);
            $smt->execute();
        }
    }
    ob_end_clean();
    http_response_code(200);
    echo json_encode(array("message"=>"ok"));
    exit;
}