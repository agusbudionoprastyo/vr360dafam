<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require(__DIR__.'/ssp.class.php');
require(__DIR__.'/../../config/config.inc.php');
require_once(__DIR__."/../functions.php");
$id_user = $_SESSION['id_user'];
$id_vt = (int)$_GET['id_vt'];
$settings = get_settings();
$user_info = get_user_info($id_user);
if(!isset($_SESSION['lang'])) {
    if(!empty($user_info['language'])) {
        $language = $user_info['language'];
    } else {
        $language = $settings['language'];
    }
} else {
    $language = $_SESSION['lang'];
}
$s3_params = check_s3_tour_enabled($id_vt);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
set_language($language,$settings['language_domain']);
session_write_close();
$none_label = _("None");
$link_label = _("Link");
$popup_label = _("Popup");
$cart_label = _("Cart");
$buy_label = _("BUY");
$add_to_cart_label = _("ADD TO CART");
$query = "SELECT p.id,p.name,p.purchase_type,CONCAT(p.price,'|',IFNULL(p.custom_currency,'')) as price,MIN(spi.image) as image,v.snipcart_currency as currency,CONCAT(p.button_text,'|',p.button_icon) as button FROM svt_products as p
LEFT JOIN svt_product_images spi on p.id = spi.id_product
JOIN svt_virtualtours as v ON v.id=p.id_virtualtour
WHERE p.id_virtualtour=$id_vt
GROUP BY p.id";
$table = "( $query ) t";
$primaryKey = 'id';
$columns = array(
    array(
        'db' => 'id',
        'dt' => 'DT_RowId',
        'formatter' => function( $d, $row ) {
            return $d;
        }
    ),
    array( 'db' => 'name',  'dt' =>0, 'formatter' => function( $d, $row ) {
        global $s3_enabled,$s3_url;
        if(!empty($row['image'])) {
            if($s3_enabled) {
                $image = $s3_url.'viewer/products/thumb/'.$row['image'];
            } else {
                $image = '../viewer/products/thumb/'.$row['image'];
            }
            return "<img style='width:20px;height:20px;border-radius:50%;vertical-align:sub;' src='$image' /> ".$d;
        } else {
            return $d;
        }
    }),
    array( 'db' => 'price',  'dt' =>1, 'formatter' => function( $d, $row ) {
        $tmp = explode("|",$d);
        $price = $tmp[0];
        $custom_currency = $tmp[1];
        if($row['purchase_type']!='cart' && !empty($custom_currency)) {
            $price = $custom_currency." ".$price;
        } else {
            $price = format_currency($row['currency'],$price);
        }
        return $price;
    }),
    array( 'db' => 'purchase_type',  'dt' =>2, 'formatter' => function( $d, $row ) {
        global $none_label,$link_label,$popup_label,$cart_label;
        switch($d) {
            case 'none':
                $d = $none_label;
                break;
            case 'link':
                $d = $link_label;
                break;
            case 'popup':
                $d = $popup_label;
                break;
            case 'cart':
                $d = $cart_label;
                break;
        }
        return $d;
    }),
    array( 'db' => 'button',  'dt' =>3, 'formatter' => function( $d, $row ) {
        global $buy_label,$add_to_cart_label;
        $tmp = explode("|",$d);
        if($row['purchase_type']!='none') {
            if(empty($row['button_text'])) {
                switch($row['purchase_type']) {
                    case 'cart':
                        return "<i class='".$tmp[1]."'></i>&nbsp;&nbsp;".$add_to_cart_label;
                        break;
                    default:
                        return "<i class='".$tmp[1]."'></i>&nbsp;&nbsp;".$buy_label;
                        break;
                }
            } else {
                return "<i class='".$tmp[1]."'></i>&nbsp;&nbsp;".$tmp[0];
            }
        } else {
            return "";
        }
    })
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);