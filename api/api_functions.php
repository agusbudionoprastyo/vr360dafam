<?php

function check_api_missing_params($params,$mandatory_params) {
    $missing_params = "";
    foreach ($mandatory_params as $mandatory_param) {
        if(!isset($params[$mandatory_param])) {
            $missing_params .= $mandatory_param.",";
        } else if(empty($params[$mandatory_param])) {
            $missing_params .= $mandatory_param.",";
        }
    }
    $missing_params = rtrim($missing_params,",");
    if(!empty($missing_params)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(array("message"=>"missing parameters: $missing_params"));
        exit;
    }
}

function validate_token($token) {
    if(empty($token)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(array("message"=>"missing token"));
        exit;
    }
    $signer = new MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256('12345678901234567890123456789012');
    $parser = new MiladRahimi\Jwt\Parser($signer);
    try {
        $payload = $parser->parse($token);
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(array("message"=>"invalid token"));
        exit;
    }
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(array("message"=>"expired token"));
        exit;
    }
    return $payload;
}

function check_if_demo($id_user) {
    $demo = false;
    if(file_exists("../config/demo.inc.php")) {
        require_once("../config/demo.inc.php");
        $demo_developer_ip=DEMO_DEVELOPER_IP;
        $demo_server_ip=DEMO_SERVER_IP;
        $demo_user_id=DEMO_USER_ID;
        if(($_SERVER['SERVER_ADDR']==$demo_server_ip) && ($_SERVER['REMOTE_ADDR']!=$demo_developer_ip) && ($id_user==$demo_user_id)) {
            $demo = true;
        }
    }
    return $demo;
}

function check_if_saas() {
	return true;
}

function fatal_handler() {
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;
    $error = error_get_last();
    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>format_error( $errno, $errstr, $errfile, $errline)));
        exit;
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line: $errline";
    return $content;
}