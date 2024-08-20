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

if(get_user_role($_SESSION['id_user'])!='administrator') {
    echo json_encode(array("status"=>"error"));
    die();
}

$save = (int)$_POST['save'];
if(isset($_POST['2checkout_merchant']) && $save==1) {
    $merchant_code = $_POST['2checkout_merchant'];
    $secret_key = $_POST['2checkout_secret'];
    if($merchant_code!="keep_2checkout_merchant") {
        $query = "UPDATE svt_settings SET 2checkout_merchant=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$merchant_code);
            $smt->execute();
        }
    }
    if($secret_key!="keep_2checkout_secret") {
        $query = "UPDATE svt_settings SET 2checkout_secret=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$secret_key);
            $smt->execute();
        }
    }
}
if(isset($_POST['id_plan'])) {
    $id_plan_p = $_POST['id_plan'];
} else {
    $id_plan_p = null;
}

$settings = get_settings();
$app_name = $settings['name'];
$logo = $settings['logo'];
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname'];
$url_logo = "";
if(!empty($logo)) {
    $url_logo = str_replace("/payments","",$url)."/backend/assets/".$logo;
}

$url_webhook = $url."/2checkout_webhooks.php";

$merchant_code = $settings['2checkout_merchant'];
$secret_key = $settings['2checkout_secret'];
if(empty($secret_key) || empty($merchant_code)) {
    exit;
}

$plans_array = array();
$query = "SELECT * FROM svt_plans WHERE price > 0;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $id_product_2checkout = $row['id_product_2checkout'];
            $id_product2_2checkout = $row['id_product2_2checkout'];
            $name = $row['name'];
            $price = $row['price'];
            $price2 = $row['price2'];
            $currency = $row['currency'];
            $frequency = $row['frequency'];
            $interval_count = $row['interval_count'];
            array_push($plans_array,array("id"=>$id,"id_product_2checkout"=>$id_product_2checkout,"id_product2_2checkout"=>$id_product2_2checkout,"name"=>$name,"price"=>$price,"price2"=>$price2,"currency"=>$currency,"frequency"=>$frequency,"interval_count"=>$interval_count));
        }
    }
}

$config = array(
    'sellerId' => $merchant_code,
    'secretKey' => $secret_key,
    'jwtExpireTime' => 30,
    'curlVerifySsl' => 0
);
$tco = new TwocheckoutFacade($config);

$ch = curl_init($url_webhook);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
curl_exec($ch);
$hcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if(strpos($hcode,'200')===false) {
    echo json_encode(array("status"=>"error","msg"=>"Webhook url $url_webhook not reachable!"));
    die();
}

foreach ($plans_array as $plan) {
    $id_plan = $plan['id'];
    if(($id_plan_p!=0) && ($id_plan!=$id_plan_p)) {
        continue;
    }
    $id_product_2checkout = $plan['id_product_2checkout'];
    $id_product2_2checkout = $plan['id_product2_2checkout'];
    $name = $plan['name'];
    if(!empty($app_name)) {
        $name = $app_name." - ".$name;
    }
    $price = $plan['price'];
    $price2 = $plan['price2'];
    $currency = $plan['currency'];
    $frequency = $plan['frequency'];
    $interval_count = $plan['interval_count'];
    switch($frequency) {
        case 'recurring':
        case 'one_time':
            if(empty($id_product_2checkout)) {
                //CREATE PRODUCT
                $id_product_2checkout = create_product($name,$id_plan,$currency,$price,$frequency,$interval_count,'MONTH');
                $mysqli->query("UPDATE svt_plans SET id_product_2checkout='$id_product_2checkout' WHERE id=$id_plan;");
            } else {
                //CHECK PRODUCT
                if(!check_if_product_exist($id_product_2checkout)) {
                    //CREATE PRODUCT
                    $id_product_2checkout = create_product($name,$id_plan,$currency,$price,$frequency,$interval_count,'MONTH');
                    $mysqli->query("UPDATE svt_plans SET id_product_2checkout='$id_product_2checkout' WHERE id=$id_plan;");
                } else {
                    //MODIFY PRODUCT
                    if(modify_product($id_product_2checkout,$name,$id_plan,$currency,$price,$frequency,$interval_count)) {
                        $id_product_2checkout = create_product($name,$id_plan,$currency,$price,$frequency,$interval_count,'MONTH');
                        $mysqli->query("UPDATE svt_plans SET id_product_2checkout='$id_product_2checkout' WHERE id=$id_plan;");
                    }
                }
            }
            break;
        case 'month_year':
            $interval_count = 1;
            if(empty($id_product_2checkout)) {
                //CREATE PRODUCT
                $id_product_2checkout = create_product($name,$id_plan,$currency,$price,$frequency,$interval_count);
                $mysqli->query("UPDATE svt_plans SET id_product_2checkout='$id_product_2checkout' WHERE id=$id_plan;");
            } else {
                //CHECK PRODUCT
                if(!check_if_product_exist($id_product_2checkout)) {
                    //CREATE PRODUCT
                    $id_product_2checkout = create_product($name,$id_plan,$currency,$price,$frequency,$interval_count);
                    $mysqli->query("UPDATE svt_plans SET id_product_2checkout='$id_product_2checkout' WHERE id=$id_plan;");
                } else {
                    //MODIFY PRODUCT
                    if(modify_product($id_product_2checkout,$name,$id_plan,$currency,$price,$frequency,$interval_count)) {
                        $id_product_2checkout = create_product($name,$id_plan,$currency,$price,$frequency,$interval_count);
                        $mysqli->query("UPDATE svt_plans SET id_product_2checkout='$id_product_2checkout' WHERE id=$id_plan;");
                    }
                }
            }
            $interval_count = 12;
            if(empty($id_product2_2checkout)) {
                //CREATE PRODUCT
                $id_product2_2checkout = create_product($name,$id_plan,$currency,$price2,$frequency,$interval_count);
                $mysqli->query("UPDATE svt_plans SET id_product2_2checkout='$id_product2_2checkout' WHERE id=$id_plan;");
            } else {
                //CHECK PRODUCT
                if(!check_if_product_exist($id_product_2checkout)) {
                    //CREATE PRODUCT
                    $id_product2_2checkout = create_product($name,$id_plan,$currency,$price2,$frequency,$interval_count);
                    $mysqli->query("UPDATE svt_plans SET id_product2_2checkout='$id_product2_2checkout' WHERE id=$id_plan;");
                } else {
                    //MODIFY PRODUCT
                    if(modify_product($id_product2_2checkout,$name,$id_plan,$currency,$price2,$frequency,$interval_count)) {
                        $id_product2_2checkout = create_product($name,$id_plan,$currency,$price2,$frequency,$interval_count);
                        $mysqli->query("UPDATE svt_plans SET id_product2_2checkout='$id_product2_2checkout' WHERE id=$id_plan;");
                    }
                }
            }
            break;
    }
}

$array_products_2checkout = array();
$query = "SELECT * FROM svt_plans WHERE (id_product_2checkout IS NOT NULL OR id_product2_2checkout IS NOT NULL) AND frequency IN('recurring','month_year');";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_product_2checkout = $row['id_product_2checkout'];
            $id_product2_2checkout = $row['id_product2_2checkout'];
            if(!in_array($id_product_2checkout,$array_products_2checkout) && !empty($id_product_2checkout)) {
                array_push($array_products_2checkout,$id_product_2checkout);
            }
            if(!in_array($id_product2_2checkout,$array_products_2checkout) && !empty($id_product2_2checkout)) {
                array_push($array_products_2checkout,$id_product2_2checkout);
            }
        }
    }
}

$associations = array();
foreach ($array_products_2checkout as $id1) {
    foreach ($array_products_2checkout as $id2) {
        if ($id1 != $id2) {
            $associations[$id1][] = $id2;
        }
    }
}

foreach ($associations as $id_product_2checkout => $others_id_product_2checkout) {
    associate_upgrade_product($id_product_2checkout,$others_id_product_2checkout);
}

$query = "UPDATE svt_settings SET 2checkout_enabled=1,paypal_enabled=0,stripe_enabled=0;";
$result = $mysqli->query($query);
if($result) {
    echo json_encode(array("status"=>"ok"));
} else {
    echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
}

function generateRandomCode($length=5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function check_if_product_exist($id_product_2checkout) {
    global $tco;
    try {
        $result = $tco->apiCore()->call( '/products/'.$id_product_2checkout.'/', array(), 'GET' );
        if(isset($result['ProductCode']) && $result['ProductCode']==$id_product_2checkout) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function create_product($name,$id,$currency,$price,$frequency,$interval_count) {
    global $tco;
    $product_code = generateRandomCode(5)."_".$id;
    try {
        switch ($frequency) {
            case 'one_time':
                $product = array(
                    'ProductCode'=>$product_code,
                    'ProductName'=>$name,
                    'Enabled'=>true,
                    'PurchaseMultipleUnits'=>false,
                    'Tangible'=>false,
                    'ProductTaxCategoryUUID'=>'997391d5-48d2-48d4-8914-6d48307b1307',
                    'PricingConfigurations'=>array(
                        0=>array(
                            'Name'=>'Price for '.$name,
                            'DefaultCurrency'=>$currency,
                            'PricingSchema'=>'FLAT',
                            'PriceType'=>'GROSS',
                            'UseOriginalPrices'=>true,
                            'Prices'=>array(
                                'Regular'=>array(
                                    0=>array(
                                        'Amount'=>$price,
                                        'Currency'=>$currency,
                                        'MinQuantity'=>1,
                                        'MaxQuantity'=>99999
                                    )
                                )
                            )
                        )
                    ),
                    'Fulfillment'=>'NO_DELIVERY',
                    'GeneratesSubscription'=>false
                );
                break;
            case 'recurring':
            case 'month_year':
                $product = array(
                    'ProductCode'=>$product_code,
                    'ProductName'=>$name,
                    'Enabled'=>true,
                    'PurchaseMultipleUnits'=>false,
                    'Tangible'=>false,
                    'ProductTaxCategoryUUID'=>'997391d5-48d2-48d4-8914-6d48307b1307',
                    'PricingConfigurations'=>array(
                        0=>array(
                            'Name'=>'Price for '.$name,
                            'DefaultCurrency'=>$currency,
                            'PricingSchema'=>'FLAT',
                            'PriceType'=>'GROSS',
                            'UseOriginalPrices'=>true,
                            'Prices'=>array(
                                'Regular'=>array(
                                    0=>array(
                                        'Amount'=>$price,
                                        'Currency'=>$currency,
                                        'MinQuantity'=>1,
                                        'MaxQuantity'=>99999
                                    )
                                )
                            )
                        )
                    ),
                    'Fulfillment'=>'NO_DELIVERY',
                    'GeneratesSubscription'=>true,
                    'SubscriptionInformation'=>array(
                        'BundleRenewalManagement'=>'GLOBAL',
                        'BillingCycle'=>$interval_count,
                        'BillingCycleUnits'=>'MONTH'
                    )
                );
                break;
        }
        $result = $tco->apiCore()->call( '/products/', $product, 'POST' );
        if($result) {
            return $product_code;
        }
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function modify_product($id_product_2checkout,$name,$id_plan,$currency,$price,$frequency,$interval_count) {
    global $tco;
    try {
        $product_exist = $tco->apiCore()->call( '/products/'.$id_product_2checkout.'/', array(), 'GET' );
        if(isset($product_exist['ProductCode']) && $product_exist['ProductCode']==$id_product_2checkout) {
            $name_exist = $product_exist['ProductName'];
            $currency_exist = $product_exist['PricingConfigurations'][0]['DefaultCurrency'];
            $price_exist = $product_exist['PricingConfigurations'][0]['Prices']['Regular'][0]['Amount'];
            $frquency_exist = ($product_exist['SubscriptionInformation']['IsOneTimeFee']==1) ? 'one_time' : 'recurring';
            $interval_count_exist = $product_exist['SubscriptionInformation']['BillingCycle'];
            if($frequency=='month_year') $frequency = "recurring";
            if($frequency=='one_time') $interval_count=0;
            if($frquency_exist=='one_time') $interval_count_exist=0;
            if(($name_exist!=$name) || ($currency_exist!=$currency) || ($price_exist!=$price) || ($interval_count_exist!=$interval_count) || ($frquency_exist!=$frequency)) {
                $tco->apiCore()->call( '/products/'.$id_product_2checkout.'/', array(), 'DELETE' );
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function associate_upgrade_product($id_product_2checkout,$others_id_product_2checkout) {
    global $tco;
    try {
        $upgrade_array = array(
            "AllowUpgradeFrom"=>$others_id_product_2checkout,
            "UpgradeSettings"=>array(
                "PricingScheme"=> 3,
                "SubscriptionUpgradeType"=> 3
            )
        );
        $tco->apiCore()->call( '/products/'.$id_product_2checkout.'/upgrade/', $upgrade_array, 'POST' );
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}