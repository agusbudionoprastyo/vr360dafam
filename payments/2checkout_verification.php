<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) {
    //DEMO CHECK
    die();
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
    'curlVerifySsl' => 0
);
$tco = new TwocheckoutFacade($config);

$orderData = $tco->order()->getOrder(array('RefNo' => $_REQUEST['refno']));

if(($orderData['Status']=='AUTHRECEIVED') || ($orderData['Status']=='COMPLETE') && $orderData['ApproveStatus']=='OK') {
    $id_subscription_2checkout = $orderData['Items'][0]['ProductDetails']['Subscriptions'][0]['SubscriptionReference'];
    $id_product_2checkout = $orderData['Items'][0]['Code'];
    if(isset($orderData['CustomerDetails']['CustomerReference'])) {
        $id_customer_2checkout = $orderData['CustomerDetails']['CustomerReference'];
    } else if(isset($_SESSION['id_user_2co'])) {
        $id_user = $_SESSION['id_user_2co'];
        unset($_SESSION['id_user_2co']);
        $customer = $tco->apiCore()->call( '/subscriptions/'.$id_subscription_2checkout.'/customer/', array(), 'GET' );
        if($customer) {
            $id_customer_2checkout = $customer['CustomerReference'];
            $mysqli->query("UPDATE svt_users SET id_customer_2checkout='$id_customer_2checkout' WHERE id=$id_user;");
        }
    }
    $query = "UPDATE svt_users SET id_subscription_2checkout=?,id_plan=(SELECT id FROM svt_plans WHERE id_product_2checkout=? LIMIT 1),expire_plan_date=NULL,status_subscription_2checkout=1 WHERE id_customer_2checkout=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('sss',$id_subscription_2checkout,$id_product_2checkout,$id_customer_2checkout);
        $result = $smt->execute();
    }
}

header("Location: ../backend/index.php?p=change_plan");
exit;