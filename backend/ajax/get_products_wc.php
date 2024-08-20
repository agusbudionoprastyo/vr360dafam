<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once(__DIR__."/../functions.php");
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
$id_vt = (int)$_POST['id_vt'];
$virtual_tour = get_virtual_tour($id_vt,$_SESSION['id_user']);
if($virtual_tour!==false) {
    $woocommerce_store_url = $virtual_tour['woocommerce_store_url'];
    $woocommerce_customer_key = $virtual_tour['woocommerce_customer_key'];
    $woocommerce_customer_secret = $virtual_tour['woocommerce_customer_secret'];
    $woocommerce_client = init_woocommerce_api($woocommerce_store_url,$woocommerce_customer_key,$woocommerce_customer_secret);
    $products = get_woocommerce_products($woocommerce_client);
    $html = "";
    foreach ($products as $product) {
        if(isset($product['images'][0])) {
            $thumb_image = "<img style='width:20px;height:20px;border-radius:50%;vertical-align:sub;' src='".$product['images'][0]."' /> ";
        } else {
            $thumb_image = "";
        }
        switch($product['stock_status']) {
            case 'instock':
                $stock_label = "green";
                break;
            case 'onbackorder':
                $stock_label = "orange";
                break;
            case 'outofstock':
                $stock_label = "red";
                break;
            default:
                $stock_label = "black";
                break;
        }
        $html .= "<tr>";
        $html .= "<td>".$thumb_image.$product['name']."&nbsp;&nbsp;<a target='_blank' href='".$product['link']."'><i class='fas fa-external-link-square-alt'></i></a></td>";
        $html .= "<td>".$product['type']."</td>";
        $html .= "<td>".$product['sku']."</td>";
        $html .= "<td>".$product['price']."</td>";
        $html .= "<td style='color: $stock_label'>".$product['stock_status']."</td>";
        $html .= "</tr>";
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok","products"=>$html));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}