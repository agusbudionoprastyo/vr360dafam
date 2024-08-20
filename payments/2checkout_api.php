<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
ob_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) {
    //DEMO CHECK
    die();
}
if(file_exists("../config/demo.inc.php")) {
    require_once("../config/demo.inc.php");
    if(($_SERVER['SERVER_ADDR']==DEMO_SERVER_IP) && ($_SERVER['REMOTE_ADDR']!=DEMO_DEVELOPER_IP)) {
        $demo = true;
    } else {
        $demo = false;
    }
} else {
    $demo = false;
}
require_once(__DIR__."/../backend/functions.php");
require_once(__DIR__."/../db/connection.php");
require(__DIR__."/../backend/vendor/2checkout-php-sdk/autoloader.php");
use Tco\TwocheckoutFacade;
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$settings = get_settings();
$merchant_code = $settings['2checkout_merchant'];
$secret_key = $settings['2checkout_secret'];
if(empty($secret_key) || empty($merchant_code)) {
    exit;
}

$config = array(
    'sellerId' => $merchant_code,
    'secretKey' => $secret_key,
    'jwtExpireTime' => 30,
    'curlVerifySsl' => 0,
    'buyLinkSecretWord' => '@sg?9KPrz-&Z7C6tv!C#-UM##M7N94?XEvm%-57SnrU!A9ZqS?GbZJNDSyJsYmnh'
);
$tco = new TwocheckoutFacade($config);

header('Content-Type: application/json');
$endpoint = $_POST['endpoint'];
switch ($endpoint) {
    case 'create_customer':
        $id_user = $_SESSION['id_user'];
        $user_info = get_user_info($id_user);
        if(empty($user_info['id_customer_2checkout'])) {
            $_SESSION['id_user_2co']=$id_user;
            session_write_close();
            $CustomerReference = 0;
            $buyLinkParameters = array (
                'email' => $user_info['email'],
                'prod' => $_POST['id_product'],
                'qty' => 1,
                'return-url' => $_POST['url'],
                'return-type' => 'redirect',
                'test' => ($_POST['live']) ? 0 : 1,
                'lock' => 1,
                'empty-cart' => 1,
                'merchant' => $merchant_code,
                'origin-url' => $_POST['origin_url'],
                'src' => $_POST['origin_url']
            );
        } else {
            $CustomerReference = $user_info['id_customer_2checkout'];
            $buyLinkParameters = array (
                'email' => $user_info['email'],
                'prod' => $_POST['id_product'],
                'qty' => 1,
                'return-url' => $_POST['url'],
                'return-type' => 'redirect',
                'customer-ref' => $CustomerReference,
                'test' => ($_POST['live']) ? 0 : 1,
                'lock' => 1,
                'empty-cart' => 1,
                'merchant' => $merchant_code,
                'origin-url' => $_POST['origin_url'],
                'src' => $_POST['origin_url']
            );
        }
        $buyLinkSignature = $tco->getBuyLinkSignature($buyLinkParameters);
        $buyLinkParameters['signature'] = $buyLinkSignature;
        $redirectTo = 'https://secure.2checkout.com/checkout/buy/?' . ( http_build_query( $buyLinkParameters ) );
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id" => $CustomerReference,"url"=>$redirectTo));
        exit;
        break;
    case 'subscription_end_date':
        $id_user = $_SESSION['id_user'];
        $user = get_user_info($id_user);
        $id_subscription_2checkout = $user['id_subscription_2checkout'];
        $end_date = get_subscription_expiration_date($id_subscription_2checkout);
        if($end_date!==false) {
            $end_date = date('d M Y',strtotime($end_date));
        } else {
            $end_date = '--';
        }
        $name_plan = '--';
        $query = "SELECT p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $name_plan = $row['plan'];
            }
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok","end_date"=>$end_date,"name"=>$name_plan));
        break;
    case 'cancel_subscription':
        $id_user = $_SESSION['id_user'];
        if(!$demo) {
            $user = get_user_info($id_user);
            $id_subscription_2checkout = $user['id_subscription_2checkout'];
            $end_date = get_subscription_expiration_date($id_subscription_2checkout);
            if($end_date!==false) {
                $end_date = date('Y-m-d H:i:s',strtotime($end_date));
                cancel_subscription($id_subscription_2checkout);
                $result = $mysqli->query("UPDATE svt_users SET expire_plan_date='$end_date',id_subscription_2checkout=NULL,status_subscription_2checkout=0 WHERE id=$id_user;");
                if($result) {
                    $query = "SELECT u.id,u.username,u.email,u.expire_plan_date,p.name as plan,p.id as id_plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user;";
                    $result = $mysqli->query($query);
                    if($result) {
                        if($result->num_rows>0) {
                            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                                $id_user = $row['id'];
                                $username = $row['username'];
                                $email_u = $row['email'];
                                $plan = $row['plan'];
                                $id_plan = $row['id_plan'];
                                set_user_log($id_user,'unsubscribe_plan',json_encode(array("id"=>$id_plan,"name"=>$plan)),date('Y-m-d H:i:s', time()));
                                if($settings['notify_plan_cancels']) {
                                    $expire_plan_date = $row['expire_plan_date'];
                                    $subject = $settings['mail_plan_canceled_subject'];
                                    $body = $settings['mail_plan_canceled_body'];
                                    $body = str_replace("%USER_NAME%",$username,$body);
                                    $body = str_replace("%PLAN_NAME%",$plan,$body);
                                    $body = str_replace('<p><br></p>','<br>',$body);
                                    $body = str_replace('<p>','<p style="padding:0;margin:0;">',$body);
                                    $subject_q = str_replace("'","\'",$subject);
                                    $body_q = str_replace("'","\'",$body);
                                    $mysqli->query("INSERT INTO svt_notifications(id_user,subject,body,notify_user,notified) VALUES($id_user,'$subject_q','$body_q',1,0);");
                                }
                            }
                        }
                    }
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok"));
                    exit;
                } else {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
                    exit;
                }
            } else {
                ob_end_clean();
                echo json_encode(array("status" => "error", "msg" => "Error, retry later."));
                exit;
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>"Demo mode, insufficient permission."));
            exit;
        }
        break;
}

function get_subscription_expiration_date($id_subscription_2checkout) {
    global $tco;
    $ExpirationDate = false;
    try {
        $result = $tco->apiCore()->call( '/subscriptions/'.$id_subscription_2checkout.'/', array(), 'GET' );
        if($result) {
            $ExpirationDate = $result['ExpirationDate'];
        }
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
    return $ExpirationDate;
}

function cancel_subscription($id_subscription_2checkout) {
    global $tco;
    try {
        $tco->apiCore()->call( '/subscriptions/'.$id_subscription_2checkout.'/', array("ChurnReasons"=>array('CHURN_REASON_DONT_NEED')), 'DELETE' );
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}