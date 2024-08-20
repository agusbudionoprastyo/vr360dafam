<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_product = (int)$_POST['id_product'];
$product_type = $_POST['product_type'];
$groped_products = $_POST['groped_products'];
$id_variation = (int)$_POST['id_variation'];
$variation = $_POST['variation'];
if(isset($_SESSION['cart_key'])) {
    $cart_key = $_SESSION['cart_key'];
} else {
    $cart_key = "cart_$id_virtualtour".md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
}
$woocommerce_store_url = $_POST['woocommerce_store_url'];
$woocommerce_store_cart = $_POST['woocommerce_store_cart'];
$woocommerce_cocart_url_api = $woocommerce_store_url."/wp-json/cocart/v2";
$url_cart = "";
switch($product_type) {
    case 'simple':
        $result_cart = add_woocommerce_cart_item($cart_key,$id_product,$product_type,null,null,null);
        break;
    case 'grouped':
        $result_cart = add_woocommerce_cart_item($cart_key,$id_product,$product_type,null,null,$groped_products);
        break;
    case 'variable':
        $result_cart = add_woocommerce_cart_item($cart_key,$id_product,$product_type,$id_variation,$variation,null);
        break;
}
$url_cart = $result_cart[0];
$response = $result_cart[1];
if(!empty($url_cart)) {
    ob_end_clean();
    echo json_encode(array("status"=>"ok",'url_cart'=>$url_cart));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","response"=>$response));
    exit;
}

function add_woocommerce_cart_item($cart_key,$product_id,$product_type,$id_variation,$variation,$groped_products) {
    global $woocommerce_cocart_url_api, $woocommerce_store_url, $woocommerce_store_cart;
    $url_cart = "";
    switch($product_type) {
        case 'simple':
            $cart_post = array(
                'cart_key'=>$cart_key,
                'id' => $product_id,
                'quantity' => 1,
            );
            break;
        case 'grouped':
            $array_quantities = array();
            $groped_products = explode(",",$groped_products);
            foreach ($groped_products as $groped_product) {
                $array_quantities[$groped_product]=1;
            }
            $cart_post = array(
                'cart_key'=>$cart_key,
                'id' => $product_id,
                'quantity' => $array_quantities,
            );
            break;
        case 'variable':
            $cart_post = array(
                'cart_key'=>$cart_key,
                'id' => $product_id,
                'variation_id' => $id_variation,
                'variation' => $variation,
                'quantity' => 1,
            );
            break;
    }
    $cart_post = http_build_query($cart_post);
    if($product_type=='grouped') {
        $curl = curl_init($woocommerce_cocart_url_api.'/cart/add-items');
    } else {
        $curl = curl_init($woocommerce_cocart_url_api.'/cart/add-item');
    }
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
    if (isset($result['cart_key'])) {
        $cart_key = $result['cart_key'];
        $_SESSION['cart_key'] = $cart_key;
        $url_cart = "$woocommerce_store_url/$woocommerce_store_cart?cocart-load-cart=$cart_key";
        return [$url_cart,$response];
    } else {
        if($product_type=='variable') {
            $variations = generateVariations($variation);
            foreach ($variations as $variation) {
                $cart_post = array(
                    'cart_key'=>$cart_key,
                    'id' => $product_id,
                    'variation_id' => $id_variation,
                    'variation' => $variation,
                    'quantity' => 1,
                );
                $cart_post = http_build_query($cart_post);
                $curl = curl_init($woocommerce_cocart_url_api.'/cart/add-item');
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
                if (isset($result['cart_key'])) {
                    $cart_key = $result['cart_key'];
                    $_SESSION['cart_key'] = $cart_key;
                    $url_cart = "$woocommerce_store_url/$woocommerce_store_cart?cocart-load-cart=$cart_key";
                    return [$url_cart,$response];
                    exit;
                }
            }
            return [$url_cart,$response];
        } else {
            return [$url_cart,$response];
        }
    }
}

function generateVariations($array) {
    $keys = array_keys($array);
    $combinations = [[]];
    foreach ($keys as $key) {
        $newCombinations = [];
        foreach ($combinations as $combination) {
            $value = strtolower($array[$key]);
            $newCombination = $combination;
            $newCombination[$key] = $value;
            $newCombinations[] = $newCombination;
            $newCombination[$key] = ucfirst($value);
            $newCombinations[] = $newCombination;
        }
        $combinations = $newCombinations;
    }
    $variations = [];
    foreach ($combinations as $combination) {
        $variations[] = $combination;
    }
    return $variations;
}
