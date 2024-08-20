<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$item_key = $_POST['item_key'];
if(isset($_SESSION['cart_key'])) {
    $cart_key = $_SESSION['cart_key'];
} else {
    $cart_key = "cart_$id_virtualtour".md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
}
$woocommerce_store_url = $_POST['woocommerce_store_url'];
$woocommerce_cocart_url_api = $woocommerce_store_url."/wp-json/cocart/v2";
$status = remove_woocommerce_cart_item($cart_key,$item_key);
if($status) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}

function remove_woocommerce_cart_item($cart_key,$item_key) {
    global $woocommerce_cocart_url_api;
    $curl = curl_init($woocommerce_cocart_url_api.'/cart/item/'.$item_key.'?cart_key='.$cart_key);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'User-Agent: CoCart API/v2',
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response, true);
    return ($result['cart_key']==$cart_key) ? true : false;
}