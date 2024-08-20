<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
require_once(__DIR__."/../backend/vendor/PHPMailer/Exception.php");
require_once(__DIR__."/../backend/vendor/PHPMailer/PHPMailer.php");
require_once(__DIR__."/../backend/vendor/PHPMailer/SMTP.php");

$settings = get_settings();
$days_expire_notification = $settings['days_expire_notification'];

$now = date('Y-m-d H:i');
$tomorrow = date('Y-m-d H:i',strtotime("+$days_expire_notification days"));

if($settings['notify_plan_expires']) {
    $query = "SELECT u.id,u.username,u.email,u.expire_plan_date,p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE DATE_FORMAT(u.expire_plan_date, '%Y-%m-%d %H:%i')='$now';";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_user = $row['id'];
                $username = $row['username'];
                $email_u = $row['email'];
                $plan = $row['plan'];
                $expire_plan_date = date('d M Y - H:i',strtotime($row['expire_plan_date']));
                $subject = $settings['mail_plan_expired_subject'];
                $body = $settings['mail_plan_expired_body'];
                $body = str_replace("%USER_NAME%",$username,$body);
                $body = str_replace("%PLAN_NAME%",$plan,$body);
                $body = str_replace("%EXPIRE_DATE%",$expire_plan_date,$body);
                $body = str_replace('<p><br></p>','<br>',$body);
                $body = str_replace('<p>','<p style="padding:0;margin:0;">',$body);
                $subject_q = str_replace("'","\'",$subject);
                $body_q = str_replace("'","\'",$body);
                $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notify_user,notified) VALUES($id_user,'$subject_q','$body_q',1,0);");
            }
        }
    }
}
if($settings['notify_plan_expiring']) {
    $query = "SELECT u.id,u.username,u.email,u.expire_plan_date,p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE DATE_FORMAT(u.expire_plan_date, '%Y-%m-%d %H:%i')='$tomorrow';";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_user = $row['id'];
                $username = $row['username'];
                $email_u = $row['email'];
                $plan = $row['plan'];
                $expire_plan_date = date('d M Y - H:i',strtotime($row['expire_plan_date']));
                $subject = $settings['mail_plan_expiring_subject'];
                $body = $settings['mail_plan_expiring_body'];
                $body = str_replace("%USER_NAME%",$username,$body);
                $body = str_replace("%PLAN_NAME%",$plan,$body);
                $body = str_replace("%EXPIRE_DATE%",$expire_plan_date,$body);
                $body = str_replace('<p><br></p>','<br>',$body);
                $body = str_replace('<p>','<p style="padding:0;margin:0;">',$body);
                $subject_q = str_replace("'","\'",$subject);
                $body_q = str_replace("'","\'",$body);
                $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notify_user,notified) VALUES($id_user,'$subject_q','$body_q',1,0);");
            }
        }
    }
}

$smtp_server = $settings['smtp_server'];
$smtp_auth = $settings['smtp_auth'];
$smtp_username = $settings['smtp_username'];
$smtp_password = $settings['smtp_password'];
$smtp_secure = $settings['smtp_secure'];
$smtp_port = $settings['smtp_port'];
$smtp_from_email = $settings['smtp_from_email'];
$smtp_from_name = $settings['smtp_from_name'];

if(!empty($settings['notify_email'])) {
    $email = $settings['notify_email'];
} else {
    $email = $settings['smtp_from_email'];
}

$query = "SELECT * FROM svt_notifications WHERE notified=0;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $id_user = $row['id_user'];
            $subject = $row['subject'];
            $body = $row['body'];
            $notify_user = $row['notify_user'];
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->CharSet = 'UTF-8';
                $mail->SMTPDebug = 2;
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
                if($notify_user) {
                    $user_email = '';
                    $query_m = "SELECT email FROM svt_users WHERE id=$id_user;";
                    $result_m = $mysqli->query($query_m);
                    if($result_m) {
                        if ($result_m->num_rows == 1) {
                            $row_m = $result_m->fetch_array(MYSQLI_ASSOC);
                            $user_email = $row_m['email'];
                        }
                    }
                    if(empty($user_email)) {
                        $mail->addAddress($email);
                    } else {
                        $mail->addAddress($user_email);
                        $mail->addBCC($email);
                    }
                } else {
                    $mail->addAddress($email);
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $body = parse_body_images($body);
                $mail->Body = $body;
                $mail->send();
                $mysqli->query("UPDATE svt_notifications SET notified=1 WHERE id=$id");
            } catch (Exception $e) {
                echo $e."<br>";
            }
            sleep(5);
        }
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