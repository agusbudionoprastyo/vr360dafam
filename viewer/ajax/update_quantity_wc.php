<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
$id_virtualtour = (int)$_POST['id_virtualtour'];
if(isset($_SESSION['cart_key'])) {
    $cart_key = $_SESSION['cart_key'];
} else {
    $cart_key = "cart_$id_virtualtour".md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
}
$item_key = $_POST['item_key'];
$quantity = (int)$_POST['quantity'];
$woocommerce_store_url = $_POST['woocommerce_store_url'];
$woocommerce_cocart_url_api = $woocommerce_store_url."/wp-json/cocart/v2";
$status = update_woocommerce_quantity_item($cart_key,$item_key,$quantity);
if($status) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status" => "error"));
    exit;
}

function update_woocommerce_quantity_item($cart_key,$item_key,$quantity) {
    global $woocommerce_cocart_url_api;
    $cart_post = array(
        'cart_key' => $cart_key,
        'item_key' => $item_key,
        'quantity' => $quantity,
        'return_cart' => false
    );
    $cart_post = http_build_query($cart_post);
    $curl = curl_init($woocommerce_cocart_url_api.'/cart/item/'.$item_key);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $cart_post);
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