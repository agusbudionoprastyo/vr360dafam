<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si_l']!=session_id() && $_SESSION['svt_si']!=session_id()) { die('Invalid request'); }
define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if(!AJAX_REQUEST) { die('Invalid request'); }
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once('../vendor/PHPMailer/Exception.php');
require_once('../vendor/PHPMailer/PHPMailer.php');
require_once('../vendor/PHPMailer/SMTP.php');
require_once('../functions.php');
require_once("../../db/connection.php");
$settings = get_settings();
$name = $settings['name'];
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
$mail_user_add_subject = $settings['mail_user_add_subject'];
$mail_user_add_body = $settings['mail_user_add_body'];
$mail_forgot_subject = $settings['mail_forgot_subject'];
$mail_forgot_body = $settings['mail_forgot_body'];
$username = '';
if(isset($_POST['email'])) {
    $email = $_POST['email'];
    $query_m = "SELECT username FROM svt_users WHERE email='$email';";
    $result_m = $mysqli->query($query_m);
    if($result_m) {
        if ($result_m->num_rows == 1) {
            $row_m = $result_m->fetch_array(MYSQLI_ASSOC);
            $username = $row_m['username'];
        }
    }
}
$notify_id=0;
$body='';
switch ($_POST['type']) {
    case 'validate':
        $subject = $name . ' - Test email';
        $body = 'This is a test e-mail for validating mail server settings.';
        break;
    case 'forgot':
        $query = "SELECT id FROM svt_users WHERE email='$email' LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $id_user = $row['id'];
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>"Invalid e-mail"));
                exit;
            }
        }
        $verification_code = generateRandomString(16);
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        $hostName = $_SERVER['HTTP_HOST'];
        if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
        $url = $protocol."://".$hostName.$pathInfo['dirname'];
        $url = str_replace("/ajax","",$url)."/login.php?forgot=1&email=$email&verification_code=$verification_code";
        $subject = $mail_forgot_subject;
        $mail_forgot_body = str_replace("%LINK%","<a href='$url'>$url</a>",$mail_forgot_body);
        $mail_forgot_body = str_replace("%VERIFICATION_CODE%",$verification_code,$mail_forgot_body);
        $mail_forgot_body = str_replace("%USER_NAME%",$username,$mail_forgot_body);
        $body = $mail_forgot_body;
        break;
    case 'activate':
        $id_user = (int)$_POST['id_user'];
        $query_m = "SELECT email,hash FROM svt_users WHERE id=$id_user;";
        $result_m = $mysqli->query($query_m);
        if($result_m) {
            if ($result_m->num_rows == 1) {
                $row_m = $result_m->fetch_array(MYSQLI_ASSOC);
                $email = $row_m['email'];
                $hash = $row_m['hash'];
                $currentPath = $_SERVER['PHP_SELF'];
                $pathInfo = pathinfo($currentPath);
                $hostName = $_SERVER['HTTP_HOST'];
                if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
                $url = $protocol."://".$hostName.$pathInfo['dirname'];
                $url = str_replace("/ajax","",$url)."/validate_email.php?email=$email&hash=$hash";
                $subject = $mail_activate_subject;
                $mail_activate_body = str_replace("%LINK%","<a href='$url'>$url</a>",$mail_activate_body);
                $mail_activate_body = str_replace("%USER_NAME%",$username,$mail_activate_body);
                $body = $mail_activate_body;
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
                exit;
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error"));
            exit;
        }
        break;
    case 'user_add':
        if(!$settings['notify_useradd']) {
            ob_end_clean();
            echo json_encode(array("status"=>"ok"));
            exit;
        }
        $password = $_POST['password_svt'];
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        $hostName = $_SERVER['HTTP_HOST'];
        if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
        $url = $protocol."://".$hostName.$pathInfo['dirname'];
        $url = str_replace("/ajax","",$url)."/index.php";
        $subject = $mail_user_add_subject;
        $mail_user_add_body = str_replace("%LINK%","<a href='$url'>$url</a>",$mail_user_add_body);
        $mail_user_add_body = str_replace("%USER_NAME%",$username,$mail_user_add_body);
        $mail_user_add_body = str_replace("%PASSWORD%",$password,$mail_user_add_body);
        $body = $mail_user_add_body;
        break;
    case 'notify':
        if(!$settings['notify_registrations']) {
            ob_end_clean();
            echo json_encode(array("status"=>"ok"));
            exit;
        }
        $id_user = $_POST['id_user'];
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
        break;
    case 'form':
        $form_data = array();
        parse_str($_POST['form_data'], $form_data);
        $title = $form_data['title'];
        $subject = _("Form").": ".$title;
        $body = "";
        for($i=1;$i<=10;$i++) {
            if(isset($form_data['form_field_'.$i])) {
                $form_label = $form_data['form_label_'.$i];
                $form_field = $form_data['form_field_'.$i];
                $form_field = strip_tags($form_field);
                $body .= "$form_label: $form_field<br>";
            }
        }
        break;
    case 'lead':
        $vt_name = $_POST['vt_name'];
        $lead_name = $_POST['lead_name'];
        $lead_email = $_POST['lead_email'];
        $lead_phone = $_POST['lead_phone'];
        if(isset($_POST['room_name'])) {
            $subject = _("Lead").": ".$vt_name." - ".$_POST['room_name'];
        } else {
            $subject = _("Lead").": ".$vt_name;
        }
        $body = _("Name").": $lead_name<br>"._("E-Mail").": $lead_email<br>"._("Phone").": $lead_phone";
        break;
    case 'vt_create':
        if(!$settings['notify_vt_create']) {
            ob_end_clean();
            echo json_encode(array("status"=>"ok"));
            exit;
        }
        $id_user = $_POST['id_user'];
        if(get_user_role($id_user)!='customer') {
            ob_end_clean();
            echo json_encode(array("status"=>"ok"));
            exit;
        }
        $id_vt = $_POST['id_vt'];
        if(!empty($settings['notify_email'])) {
            $email = $settings['notify_email'];
        } else {
            $email = $settings['smtp_from_email'];
        }
        $subject = _("New tour created");
        $query = "SELECT u.username,u.email,p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $username = $row['username'];
                $email_u = $row['email'];
                $plan = $row['plan'];
            }
        }
        $query = "SELECT name FROM svt_virtualtours WHERE id=$id_vt;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $tour = $row['name'];
            }
        }
        $body = _("Username").": $username<br>"._("E-Mail").": $email_u<br>"._("Plan").": $plan<br>"._("Tour").": $tour";
        $subject_q = str_replace("'","\'",$subject);
        $body_q = str_replace("'","\'",$body);
        $result_ins = $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notified) VALUES($id_user,'$subject_q','$body_q',1);");
        if($result_ins) {
            $notify_id = $mysqli->insert_id;
        }
        break;
}
$mail = new PHPMailer(true);
$result = send_email($mail,$smtp_server,$smtp_auth,$smtp_username,$smtp_password,$smtp_secure,$smtp_port,$smtp_from_email,$smtp_from_name,$email,$subject,$body);
if($_POST['type']=='activate') {
    $notify_id=0;
    $body='';
    if(!$settings['notify_registrations']) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
        exit;
    }
    $id_user = $_POST['id_user'];
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
    $mail = new PHPMailer(true);
    $result = send_email($mail,$smtp_server,$smtp_auth,$smtp_username,$smtp_password,$smtp_secure,$smtp_port,$smtp_from_email,$smtp_from_name,$email,$subject,$body);
    ob_end_clean();
    echo $result;
    exit;
} else {
    ob_end_clean();
    echo $result;
    exit;
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
        switch ($_POST['type']) {
            case 'validate':
                $mysqli->query("UPDATE svt_settings SET smtp_valid=1;");
                break;
            case 'forgot':
                $mysqli->query("UPDATE svt_users SET forgot_code='$verification_code' WHERE id=$id_user;");
                break;
        }
        return json_encode(array("status"=>"ok"));
    } catch (Exception $e) {
        switch ($_POST['type']) {
            case 'validate':
                $mysqli->query("UPDATE svt_settings SET smtp_valid=0;");
                break;
            case 'notify':
            case 'vt_create':
                if($notify_id!=0) {
                    $mysqli->query("UPDATE svt_notifications SET notified=0 WHERE id=$notify_id;");
                }
                break;
        }
        return json_encode(array("status"=>"error","msg"=>$mail->ErrorInfo));
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