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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once('../backend/vendor/PHPMailer/Exception.php');
require_once('../backend/vendor/PHPMailer/PHPMailer.php');
require_once('../backend/vendor/PHPMailer/SMTP.php');

register_shutdown_function("fatal_handler");

$mail = new PHPMailer(true);
$mail_n = new PHPMailer(true);

$method = $_SERVER["REQUEST_METHOD"];
if($method!='POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(array("message"=>"invalid method $method"));
    exit;
}

switch($method) {
    case 'POST':
        if(!empty($_POST)) {
            $params = $_POST;
        } else {
            $content = trim(file_get_contents("php://input"));
            $params = json_decode($content, true);
        }
        break;
}

$saas = check_if_saas();

if(!$saas) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(array("message"=>"unauthorized"));
    exit;
}

$mandatory_params = ['username','email','password'];
check_api_missing_params($params,$mandatory_params);

if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("api/register.php","",$_SERVER['SCRIPT_NAME']);

register_user($params);
exit;

function register_user($params) {
    global $mysqli,$mail,$mail_n;
    $settings = get_settings();
    if (isset($params['id_plan']) && $params['id_plan'] !== '') {
        $id_plan = $params['id_plan'];
    } else {
        $id_plan = $settings['default_id_plan'];
    }
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
    $validate_email = $settings['validate_email'];
    if($validate_email) {
        $active = 0;
        $hash = md5(rand(0,1000));
    } else {
        $active = 1;
        $hash = "";
    }
    $query = "INSERT INTO svt_users(username,email,password,role,id_plan,hash,active) VALUES(?,?,MD5(?),'customer',?,?,?); ";
    if ($smt = $mysqli->prepare($query)) {
        $smt->bind_param('sssisi', $params['username'], $params['email'], $params['password'], $id_plan, $hash,$active);
        $result = $smt->execute();
        if ($result) {
            $insert_id = $mysqli->insert_id;
            update_plans_expires_date($insert_id);
            validate_mail($validate_email,$params['email'],$insert_id);
            ob_end_clean();
            http_response_code(200);
            echo json_encode(array("message"=>"ok","id_user"=>$insert_id,"validate_mail"=>(int)$validate_email));
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

function validate_mail($validate,$email,$id_user) {
    global $mysqli,$base_url,$mail,$mail_n;
    $settings = get_settings();
    $smtp_server = $settings['smtp_server'];
    $smtp_auth = $settings['smtp_auth'];
    $smtp_username = $settings['smtp_username'];
    $smtp_password = $settings['smtp_password'];
    $smtp_secure = $settings['smtp_secure'];
    $smtp_port = $settings['smtp_port'];
    $smtp_from_email = $settings['smtp_from_email'];
    $smtp_from_name = $settings['smtp_from_name'];
    $mail_activate_subject = $settings['mail_activate_subject'];
    $mail_activate_body = $settings['mail_activate_body'];
    $username = '';
    $query_m = "SELECT username FROM svt_users WHERE email='$email';";
    $result_m = $mysqli->query($query_m);
    if($result_m) {
        if ($result_m->num_rows == 1) {
            $row_m = $result_m->fetch_array(MYSQLI_ASSOC);
            $username = $row_m['username'];
        }
    }
    $notify_id=0;
    $body='';
    if($validate) {
        $query_m = "SELECT email,hash FROM svt_users WHERE id=$id_user;";
        $result_m = $mysqli->query($query_m);
        if($result_m) {
            if ($result_m->num_rows == 1) {
                $row_m = $result_m->fetch_array(MYSQLI_ASSOC);
                $email = $row_m['email'];
                $hash = $row_m['hash'];
                $url = $base_url."backend/validate_email.php?email=$email&hash=$hash";
                $subject = $mail_activate_subject;
                $mail_activate_body = str_replace("%LINK%","<a href='$url'>$url</a>",$mail_activate_body);
                $mail_activate_body = str_replace("%USER_NAME%",$username,$mail_activate_body);
                $body = $mail_activate_body;
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
        send_email($mail,$smtp_server,$smtp_auth,$smtp_username,$smtp_password,$smtp_secure,$smtp_port,$smtp_from_email,$smtp_from_name,$email,$subject,$body);
    }
    $notify_id=0;
    $body='';
    if($settings['notify_registrations']) {
        if(!empty($settings['notify_email'])) {
            $email = $settings['notify_email'];
        } else {
            $email = $settings['smtp_from_email'];
        }
        $subject = _("New registered user");
        $query = "SELECT u.username,u.email,u.expire_plan_date,p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $username = $row['username'];
                $email_u = $row['email'];
                $plan = $row['plan'];
                $expire_plan_date = $row['expire_plan_date'];
            }
        }
        $body = _("Username").": $username<br>"._("E-Mail").": $email_u<br>"._("Plan").": $plan<br>"._("Expires on").": $expire_plan_date";
        $subject_q = str_replace("'","\'",$subject);
        $body_q = str_replace("'","\'",$body);
        $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notified) VALUES($id_user,'$subject_q','$body_q',1);");
        send_email($mail_n,$smtp_server,$smtp_auth,$smtp_username,$smtp_password,$smtp_secure,$smtp_port,$smtp_from_email,$smtp_from_name,$email,$subject,$body);
    }
}

function send_email($mail,$smtp_server,$smtp_auth,$smtp_username,$smtp_password,$smtp_secure,$smtp_port,$smtp_from_email,$smtp_from_name,$email,$subject,$body) {
    global $mysqli,$verification_code,$id_user,$notify_id;
    $body = str_replace('<p><br></p>','<br>',$body);
    $body = str_replace('<p>','<p style="padding:0;margin:0;">',$body);
    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 1;
        $mail->Timeout = 10;
        $mail->Host = $smtp_server;
        $mail->SMTPAuth = $smtp_auth;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        switch($smtp_secure) {
            case 'ssl':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                break;
            case 'tls':
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
        }
        $mail->Port = $smtp_port;
        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $body = parse_body_images($body);
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>$mail->ErrorInfo));
        exit;
    }
}

function parse_body_images($body) {
    global $mail;
    if(preg_match('/img.*?>/', $body)){
        preg_match_all('/(<img[^>]+>)/i', $body, $matches);
        $i = 1;
        foreach ($matches[0] as $img) {
            preg_match('/src="(.*?)"/', $img, $m);
            $src = $m[1];
            if ((strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0)) {
                continue;
            }
            $id = 'img_' . ($i++);
            preg_match('/src="(.*?)"/', $img, $m);
            $imgdata = explode(',', $m[1]);
            $mime = explode(';', $imgdata[0]);
            $imgtype = explode(':', $mime[0]);
            $encodedData = str_replace(' ','+',$imgdata[1]);
            $decodedData = base64_decode($encodedData);
            $mail->AddStringEmbeddedImage($decodedData, $id, $id, $mime[1], $imgtype[1] );
            $body = str_replace($img, '<img alt="" src="cid:'.$id.'" style="border: none;" />', $body);
            $i++;
        }
    }
    return $body;
}