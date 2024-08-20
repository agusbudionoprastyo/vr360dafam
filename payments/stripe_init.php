<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) {
    //DEMO CHECK
    die();
}
require_once(__DIR__."/../backend/functions.php");
require_once(__DIR__."/../db/connection.php");
require(__DIR__."/../backend/vendor/stripe-php/init.php");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(get_user_role($_SESSION['id_user'])!='administrator') {
    echo json_encode(array("status"=>"error"));
    die();
}

$save = (int)$_POST['save'];
if(isset($_POST['stripe_secret_key']) && $save==1) {
    $stripe_secret_key = $_POST['stripe_secret_key'];
    $stripe_public_key = $_POST['stripe_public_key'];
    if($stripe_public_key!="keep_stripe_public_key") {
        $query = "UPDATE svt_settings SET stripe_public_key=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$stripe_public_key);
            $smt->execute();
        }
    }
    if($stripe_secret_key!="keep_stripe_secret_key") {
        $query = "UPDATE svt_settings SET stripe_secret_key=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$stripe_secret_key);
            $smt->execute();
        }
    }
}
if(isset($_POST['stripe_automatic_tax_rate'])) {
    $stripe_automatic_tax_rate = $_POST['stripe_automatic_tax_rate'];
    $query = "UPDATE svt_settings SET stripe_automatic_tax_rate=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$stripe_automatic_tax_rate);
        $smt->execute();
    }
}

if(isset($_POST['id_plan'])) {
    $id_plan_p = $_POST['id_plan'];
} else {
    $id_plan_p = null;
}

$settings = get_settings();
$stripe_tax_automatic = $settings['stripe_automatic_tax_rate'];
if(empty($stripe_tax_automatic)) $stripe_tax_automatic='unspecified';
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

$url_webhook = $url."/stripe_webhooks.php";

$key = $settings['stripe_secret_key'];
if(empty($key)) {
    exit;
}

$plans_array = array();
$query = "SELECT * FROM svt_plans WHERE price > 0;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $id_product_stripe = $row['id_product_stripe'];
            $id_price_stripe = $row['id_price_stripe'];
            $id_price2_stripe = $row['id_price2_stripe'];
            $name = $row['name'];
            $price = $row['price'];
            $price2 = $row['price2'];
            $currency = $row['currency'];
            $frequency = $row['frequency'];
            $interval_count = $row['interval_count'];
            array_push($plans_array,array("id"=>$id,"id_product_stripe"=>$id_product_stripe,"id_price_stripe"=>$id_price_stripe,"id_price2_stripe"=>$id_price2_stripe,"name"=>$name,"price"=>$price,"price2"=>$price2,"currency"=>$currency,"frequency"=>$frequency,"interval_count"=>$interval_count));
        }
    }
}

$stripe = new \Stripe\StripeClient($key);

if(!check_webhook($url_webhook)) {
    $ch = curl_init($url_webhook);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
    curl_exec($ch);
    $hcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if(strpos($hcode,'200')===false) {
        echo json_encode(array("status"=>"error","msg"=>"Webhook url $url_webhook not reachable!"));
        die();
    } else {
        create_webhook($url_webhook);
    }
}

foreach ($plans_array as $plan) {
    $id_plan = $plan['id'];
    if(($id_plan_p!=0) && ($id_plan!=$id_plan_p)) {
        continue;
    }
    $id_product_stripe = $plan['id_product_stripe'];
    $id_price_stripe = $plan['id_price_stripe'];
    $id_price2_stripe = $plan['id_price2_stripe'];
    $name = $plan['name'];
    if(!empty($app_name)) {
        $name = $app_name." - ".$name;
    }
    $price = $plan['price'];
    $price2 = $plan['price2'];
    $currency = $plan['currency'];
    $frequency = $plan['frequency'];
    $interval_count = $plan['interval_count'];
    if(empty($id_product_stripe)) {
        //CREATE PRODUCT
        $id_product_stripe = create_product($name);
        $mysqli->query("UPDATE svt_plans SET id_product_stripe='$id_product_stripe' WHERE id=$id_plan;");
    } else {
        //CHECK PRODUCT
        if(!check_if_product_exist($id_product_stripe)) {
            //CREATE PRODUCT
            $id_product_stripe = create_product($name);
            $mysqli->query("UPDATE svt_plans SET id_product_stripe='$id_product_stripe' WHERE id=$id_plan;");
        } else {
            //MODIFY PRODUCT
            modify_product($id_product_stripe,$name);
        }
    }
    switch($frequency) {
        case 'recurring':
        case 'one_time':
            if(empty($id_price_stripe)) {
                //CREATE PRICE
                $id_price_stripe = create_price($id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,'month');
                $mysqli->query("UPDATE svt_plans SET id_price_stripe='$id_price_stripe' WHERE id=$id_plan;");
            } else {
                //CHECK PRICE
                if(!check_if_price_exist($id_price_stripe)) {
                    //CREATE PRICE
                    $id_price_stripe = create_price($id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,'month');
                    $mysqli->query("UPDATE svt_plans SET id_price_stripe='$id_price_stripe' WHERE id=$id_plan;");
                } else {
                    $id_price_stripe_mod = modify_price($id_price_stripe,$id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,'month');
                    if($id_price_stripe_mod!=$id_price_stripe) {
                        $id_price_stripe = $id_price_stripe_mod;
                        $mysqli->query("UPDATE svt_plans SET id_price_stripe='$id_price_stripe' WHERE id=$id_plan;");
                    }
                }
            }
            break;
        case 'month_year':
            $interval_count = 1;
            if(empty($id_price_stripe)) {
                //CREATE PRICE
                $id_price_stripe = create_price($id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,'month');
                $mysqli->query("UPDATE svt_plans SET id_price_stripe='$id_price_stripe' WHERE id=$id_plan;");
            } else {
                //CHECK PRICE
                if(!check_if_price_exist($id_price_stripe)) {
                    //CREATE PRICE
                    $id_price_stripe = create_price($id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,'month');
                    $mysqli->query("UPDATE svt_plans SET id_price_stripe='$id_price_stripe' WHERE id=$id_plan;");
                } else {
                    $id_price_stripe_mod = modify_price($id_price_stripe,$id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,'month');
                    if($id_price_stripe_mod!=$id_price_stripe) {
                        $id_price_stripe = $id_price_stripe_mod;
                        $mysqli->query("UPDATE svt_plans SET id_price_stripe='$id_price_stripe' WHERE id=$id_plan;");
                    }
                }
            }
            if(empty($id_price2_stripe)) {
                //CREATE PRICE
                $id_price2_stripe = create_price($id_product_stripe,$currency,$price2,$frequency,$interval_count,$stripe_tax_automatic,'year');
                $mysqli->query("UPDATE svt_plans SET id_price2_stripe='$id_price2_stripe' WHERE id=$id_plan;");
            } else {
                //CHECK PRICE
                if(!check_if_price_exist($id_price2_stripe)) {
                    //CREATE PRICE
                    $id_price2_stripe = create_price($id_product_stripe,$currency,$price2,$frequency,$interval_count,$stripe_tax_automatic,'year');
                    $mysqli->query("UPDATE svt_plans SET id_price2_stripe='$id_price2_stripe' WHERE id=$id_plan;");
                } else {
                    $id_price2_stripe_mod = modify_price($id_price2_stripe,$id_product_stripe,$currency,$price2,$frequency,$interval_count,$stripe_tax_automatic,'year');
                    if($id_price2_stripe_mod!=$id_price2_stripe) {
                        $id_price2_stripe = $id_price2_stripe_mod;
                        $mysqli->query("UPDATE svt_plans SET id_price2_stripe='$id_price2_stripe' WHERE id=$id_plan;");
                    }
                }
            }
            break;
    }
}

$query = "UPDATE svt_settings SET stripe_enabled=1,paypal_enabled=0,2checkout_enabled=0;";
$result = $mysqli->query($query);
if($result) {
    echo json_encode(array("status"=>"ok"));
} else {
    echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
}

function check_webhook($url_webhook) {
    global $stripe;
    try {
        $response = $stripe->webhookEndpoints->all();
        foreach ($response['data'] as $wh) {
            $events = $wh['enabled_events'];
            $url = $wh['url'];
            if(($url==$url_webhook) && (in_array('checkout.session.completed',$events)) && (in_array('invoice.payment_failed',$events)) && (in_array('customer.subscription.deleted',$events)) && (in_array('customer.subscription.updated',$events))) {
                return true;
            }
        }
        return false;
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function create_webhook($url_webhook) {
    global $stripe;
    try {
        $stripe->webhookEndpoints->create([
            'url' => $url_webhook,
            'enabled_events' => [
                'checkout.session.completed',
                'invoice.payment_failed',
                'customer.subscription.deleted',
                'customer.subscription.updated'
            ],
        ]);
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function check_if_product_exist($id) {
    global $stripe;
    try {
        $stripe->products->retrieve($id, []);
        return true;
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        return false;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function check_if_price_exist($id) {
    global $stripe;
    try {
        $stripe->prices->retrieve($id, []);
        return true;
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        return false;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function create_product($name) {
    global $stripe,$url_logo;
    try {
        if(!empty($url_logo)) {
            $response = $stripe->products->create([
                'name' => $name, 'images'=>[$url_logo]
            ]);
        } else {
            $response = $stripe->products->create([
                'name' => $name,
            ]);
        }
        $id = $response['id'];
        return $id;
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create product: ".$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function modify_product($id_product_stripe,$name) {
    global $stripe,$url_logo;
    try {
        if(!empty($url_logo)) {
            $stripe->products->update($id_product_stripe, [
                'name' => $name, 'images' => [$url_logo]
            ]);
        } else {
            $stripe->products->update($id_product_stripe, [
                'name' => $name
            ]);
        }
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify product: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify product: ".$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function create_price($id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,$interval_unit='month') {
    global $stripe;
    $currency = strtolower($currency);
    switch($currency) {
        case 'vnd':
        case 'clp':
        case 'jpy':
        case 'rwf':
        case 'pyg':
            $price = strval($price);
            break;
        default:
            $price = strval($price*100);
            break;
    }
    try {
        switch ($frequency) {
            case 'one_time':
                $response = $stripe->prices->create([
                    'unit_amount' => $price,
                    'currency' => $currency,
                    'product' => $id_product_stripe,
                    'tax_behavior' => $stripe_tax_automatic
                ]);
                break;
            case 'recurring':
            case 'month_year':
                $response = $stripe->prices->create([
                    'unit_amount' => $price,
                    'currency' => $currency,
                    'recurring' => ['interval' => $interval_unit, 'interval_count' => $interval_count],
                    'product' => $id_product_stripe,
                    'tax_behavior' => $stripe_tax_automatic
                ]);
                break;
        }
        $id = $response['id'];
        return $id;
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>"create price: ".$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}

function modify_price($id_price_stripe,$id_product_stripe,$currency,$price,$frequency,$interval_count,$stripe_tax_automatic,$interval_unit='month') {
    global $stripe;
    $currency = strtolower($currency);
    switch($currency) {
        case 'vnd':
        case 'clp':
        case 'jpy':
        case 'rwf':
        case 'pyg':
            $price = strval($price);
            break;
        default:
            $price = strval($price*100);
            break;
    }
    try {
        $stripe_price = $stripe->prices->retrieve($id_price_stripe, []);
        $currency_exist = trim($stripe_price['currency']);
        $price_exist = trim($stripe_price['unit_amount']);
        $frquency_exist = trim($stripe_price['type']);
        $interval_count_exist = trim($stripe_price['recurring']['interval_count']);
        $tax_behavior_exist = trim($stripe_price['tax_behavior']);
        if($frequency=='month_year') $frequency='recurring';
        if($frquency_exist=='one_time') $interval_count_exist=1;
        if(($currency_exist!=$currency) || ($price_exist!=$price) || ($interval_count_exist!=$interval_count) || ($frquency_exist!=$frequency) || ($tax_behavior_exist!=$stripe_tax_automatic)) {
            switch ($frequency) {
                case 'one_time':
                    $response = $stripe->prices->create([
                        'unit_amount' => $price,
                        'currency' => $currency,
                        'product' => $id_product_stripe,
                        'tax_behavior' => $stripe_tax_automatic
                    ]);
                    break;
                case 'recurring':
                case 'month_year':
                    $response = $stripe->prices->create([
                        'unit_amount' => $price,
                        'currency' => $currency,
                        'recurring' => ['interval' => $interval_unit, 'interval_count' => $interval_count],
                        'product' => $id_product_stripe,
                        'tax_behavior' => $stripe_tax_automatic
                    ]);
                    break;
            }
            $id = $response['id'];
            return $id;
        } else {
            return $id_price_stripe;
        }
    } catch(\Stripe\Exception\CardException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify price: ".$e->getError()->message));
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo json_encode(array("status"=>"error","msg"=>"modify price: ".$e->getError()->message));
        exit;
    } catch (Exception $e) {
        echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
        exit;
    }
}