<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) {
    //DEMO CHECK
    die();
}
require_once(__DIR__."/../backend/functions.php");
require_once(__DIR__."/../db/connection.php");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if(get_user_role($_SESSION['id_user'])!='administrator') {
    echo json_encode(array("status"=>"error"));
    die();
}

$save = (int)$_POST['save'];
if(isset($_POST['paypal_client_id']) && $save==1) {
    $paypal_client_id = $_POST['paypal_client_id'];
    $paypal_client_secret = $_POST['paypal_client_secret'];
    $paypal_live = (int)$_POST['paypal_live'];
    $mysqli->query("UPDATE svt_settings SET paypal_live=$paypal_live;");
    if($paypal_client_id!="keep_paypal_client_id") {
        $query = "UPDATE svt_settings SET paypal_client_id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$paypal_client_id);
            $smt->execute();
        }
    }
    if($paypal_client_secret!="keep_paypal_client_secret") {
        $query = "UPDATE svt_settings SET paypal_client_secret=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$paypal_client_secret);
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
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
if($settings['paypal_live']) {
    $url_paypal = "api-m.paypal.com";
} else {
    $url_paypal = "api-m.sandbox.paypal.com";
}
$app_name = $settings['name'];
$id_product_paypal = $settings['id_product_paypal'];
$logo = $settings['logo'];
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = str_replace("/payments","",$protocol."://".$hostName.$pathInfo['dirname']);
$url_logo = "";
if(!empty($logo)) {
    $url_logo = $url."/backend/assets/".$logo;
}
$url_webhook = $url."/payments/paypal_webhooks.php";
$client_id = $settings['paypal_client_id'];
$client_secret = $settings['paypal_client_secret'];
if(empty($client_id) || empty($client_secret)) {
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $client_secret);
$headers = array();
$headers[] = 'Accept: application/json';
$headers[] = 'Accept-Language: en_US';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(array("status"=>"error","msg"=>curl_error($ch)));
    die();
} else {
    $response = json_decode($result,true);
    if(isset($response['error'])) {
        echo json_encode(array("status"=>"error","msg"=>$response['error_description']));
        die();
    } else {
        if(isset($response['access_token'])) {
            $access_token = $response['access_token'];
        } else {
            echo json_encode(array("status"=>"error","msg"=>"An error has occurred, please try again later"));
            die();
        }
    }
}
curl_close($ch);

$plans_array = array();
$query = "SELECT * FROM svt_plans WHERE price > 0 AND frequency != 'one_time';";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $id_plan_paypal = $row['id_plan_paypal'];
            $id_plan2_paypal = $row['id_plan2_paypal'];
            $name = $row['name'];
            $price = $row['price'];
            $price2 = $row['price2'];
            $currency = $row['currency'];
            $frequency = $row['frequency'];
            $interval_count = $row['interval_count'];
            array_push($plans_array,array("id"=>$id,"id_plan_paypal"=>$id_plan_paypal,"id_plan2_paypal"=>$id_plan2_paypal,"name"=>$name,"price"=>$price,"price2"=>$price2,"currency"=>$currency,"frequency"=>$frequency,"interval_count"=>$interval_count));
        }
    }
}

if(!check_webhook($access_token,$url_webhook)) {
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
        create_webhook($access_token,$url_webhook);
    }
}

if(empty($id_product_paypal)) {
    //CREATE PRODUCT
    $id_product_paypal = create_product($access_token,$app_name,$url,$url_logo);
    $mysqli->query("UPDATE svt_settings SET id_product_paypal='$id_product_paypal';");
} else {
    //CHECK PRODUCT
    if(!check_if_product_exist($access_token,$id_product_paypal,$app_name)) {
        //CREATE PRODUCT
        $id_product_paypal = create_product($access_token,$app_name,$url,$url_logo);
        $mysqli->query("UPDATE svt_settings SET id_product_paypal='$id_product_paypal';");
    } else {
        //MODIFY PRODUCT
        modify_product($access_token,$id_product_paypal,$url,$url_logo);
    }
}

foreach ($plans_array as $plan) {
    $id_plan = $plan['id'];
    if(($id_plan_p!=0) && ($id_plan!=$id_plan_p)) {
        continue;
    }
    $id_plan_paypal = $plan['id_plan_paypal'];
    $name = $plan['name'];
    $price = $plan['price'];
    $price2 = $plan['price2'];
    $currency = $plan['currency'];
    switch(strtolower($currency)) {
        case 'vnd':
        case 'jpy':
        case 'clp':
        case 'rwf':
        case 'pyg':
            $price = number_format($price,0);
            $price2 = number_format($price2,0);
            break;
    }
    $frequency = $plan['frequency'];
    $interval_count = $plan['interval_count'];
    switch($frequency) {
        case 'recurring':
            if(empty($id_plan_paypal)) {
                //CREATE PLAN
                $id_plan_paypal = create_plan($access_token,$id_product_paypal,$name,$currency,$price,$interval_count,'MONTH');
                $mysqli->query("UPDATE svt_plans SET id_plan_paypal='$id_plan_paypal' WHERE id=$id_plan;");
            } else {
                //CHECK PLAN
                if(!check_if_plan_exist($access_token,$id_plan_paypal,$id_product_paypal,$name,$interval_count,$currency)) {
                    //CREATE PLAN
                    $id_plan_paypal = create_plan($access_token,$id_product_paypal,$name,$currency,$price,$interval_count,'MONTH');
                    $mysqli->query("UPDATE svt_plans SET id_plan_paypal='$id_plan_paypal' WHERE id=$id_plan;");
                } else {
                    //MODIFY PLAN
                    if(check_if_differenct_price($access_token,$id_plan_paypal,$currency,$price)) {
                        modify_plan($access_token,$id_plan_paypal,$currency,$price);
                    }
                }
            }
            break;
        case 'month_year':
            $interval_count = 1;
            if(empty($id_plan_paypal)) {
                //CREATE PLAN
                $id_plan_paypal = create_plan($access_token,$id_product_paypal,$name,$currency,$price,$interval_count,'MONTH');
                $mysqli->query("UPDATE svt_plans SET id_plan_paypal='$id_plan_paypal' WHERE id=$id_plan;");
            } else {
                //CHECK PLAN
                if(!check_if_plan_exist($access_token,$id_plan_paypal,$id_product_paypal,$name,$interval_count,$currency)) {
                    //CREATE PLAN
                    $id_plan_paypal = create_plan($access_token,$id_product_paypal,$name,$currency,$price,$interval_count,'MONTH');
                    $mysqli->query("UPDATE svt_plans SET id_plan_paypal='$id_plan_paypal' WHERE id=$id_plan;");
                } else {
                    //MODIFY PLAN
                    if(check_if_differenct_price($access_token,$id_plan_paypal,$currency,$price)) {
                        modify_plan($access_token,$id_plan_paypal,$currency,$price);
                    }
                }
            }
            if(empty($id_plan2_paypal)) {
                //CREATE PLAN
                $id_plan2_paypal = create_plan($access_token,$id_product_paypal,$name,$currency,$price2,$interval_count,'YEAR');
                $mysqli->query("UPDATE svt_plans SET id_plan2_paypal='$id_plan2_paypal' WHERE id=$id_plan;");
            } else {
                //CHECK PLAN
                if(!check_if_plan_exist($access_token,$id_plan2_paypal,$id_product_paypal,$name,$interval_count,$currency)) {
                    //CREATE PLAN
                    $id_plan2_paypal = create_plan($access_token,$id_product_paypal,$name,$currency,$price2,$interval_count,'YEAR');
                    $mysqli->query("UPDATE svt_plans SET id_plan2_paypal='$id_plan2_paypal' WHERE id=$id_plan;");
                } else {
                    //MODIFY PLAN
                    if(check_if_differenct_price($access_token,$id_plan2_paypal,$currency,$price2)) {
                        modify_plan($access_token,$id_plan2_paypal,$currency,$price2);
                    }
                }
            }
            break;
    }
}

$query = "UPDATE svt_settings SET paypal_enabled=1,stripe_enabled=0,2checkout_enabled=0;";
$result = $mysqli->query($query);
if($result) {
    echo json_encode(array("status"=>"ok"));
} else {
    echo json_encode(array("status"=>"error","msg"=>"An error has occurred, please try again later."));
}

function check_webhook($access_token,$url_webhook) {
    global $url_paypal;
    $return = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/notifications/webhooks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"check_webhook: ".curl_error($ch)));
        die();
    } else {
        $response = json_decode($result,true);
        $webhooks = $response['webhooks'];
        foreach ($webhooks as $webhook) {
            if($webhook['url']==$url_webhook) {
                $return = true;
            }
        }
    }
    curl_close($ch);
    return $return;
}

function create_webhook($access_token,$url_webhook) {
    global $url_paypal;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/notifications/webhooks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
          "url": "'.$url_webhook.'",
          "event_types": [
            {
              "name": "*"
            }
          ]
        }');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"create_webhook: ".curl_error($ch)));
        die();
    }
    curl_close($ch);
}

function check_if_product_exist($access_token,$id,$app_name) {
    global $url_paypal;
    $exist = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/catalogs/products/'.$id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"check_if_product_exist: ".curl_error($ch)));
        die();
    } else {
        $response = json_decode($result,true);
        if(isset($response['id'])) {
            if($response['name']==$app_name) {
                $exist = true;
            }
        }
    }
    curl_close($ch);
    return $exist;
}

function check_if_plan_exist($access_token,$id,$id_product_paypal,$name,$interval_count,$currency) {
    global $url_paypal;
    $exist = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/billing/plans/'.$id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"check_if_plan_exist: ".curl_error($ch)));
        die();
    } else {
        $response = json_decode($result,true);
        if(isset($response['id'])) {
            if(($response['name']==$name) && ($response['product_id']==$id_product_paypal) && ($response['billing_cycles'][0]['frequency']['interval_count']==$interval_count) && ($response['billing_cycles'][0]['pricing_scheme']['fixed_price']['currency_code']==$currency)) {
                $exist = true;
            }
        }
    }
    curl_close($ch);
    return $exist;
}

function check_if_differenct_price($access_token,$id,$currency,$price) {
    global $url_paypal;
    $different = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/billing/plans/'.$id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"check_if_differenct_price: ".curl_error($ch)));
        die();
    } else {
        $response = json_decode($result,true);
        if(isset($response['id'])) {
            if($response['billing_cycles'][0]['pricing_scheme']['fixed_price']['value']!=$price) {
                $different = true;
            }

        }
    }
    curl_close($ch);
    return $different;
}

function create_product($access_token,$name,$url,$url_logo) {
    global $url_paypal;
    $product_id = null;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/catalogs/products');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    if(empty($url_logo)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{
          "name": "'.$name.'",
          "type": "SERVICE",
          "category": "SOFTWARE",
          "home_url": "'.$url.'"
        }');
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{
          "name": "'.$name.'",
          "type": "SERVICE",
          "category": "SOFTWARE",
          "image_url": "'.$url_logo.'",
          "home_url": "'.$url.'"
        }');
    }
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    $headers[] = 'Paypal-Request-Id: PRODUCT-'.strtoupper(str_replace(" ","-",$name));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"create_product: ".curl_error($ch)));
        die();
    } else {
        $response = json_decode($result,true);
        if(isset($response['id'])) {
            $product_id = $response['id'];
        } else {
            if(isset($response['details'][0]['description'])) {
                $error_msg = $response['details'][0]['description'];
            } else {
                $error_msg = "An error has occurred, please try again later";
            }
            echo json_encode(array("status"=>"error","msg"=>"create_product: ".$error_msg));
            die();
        }
    }
    curl_close($ch);
    return $product_id;
}

function modify_product($access_token,$id,$url,$url_logo) {
    global $url_paypal;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/catalogs/products/'.$id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, '[
      {
        "op": "replace",
        "path": "/image_url",
        "value": "'.$url_logo.'"
      },
      {
        "op": "replace",
        "path": "/home_url",
        "value": "'.$url.'"
      }
    ]');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"modify_product: ".curl_error($ch)));
        die();
    }
    curl_close($ch);
}

function modify_plan($access_token,$id,$currency,$price) {
    global $url_paypal;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/billing/plans/'.$id.'/update-pricing-schemes');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
                  "pricing_schemes": [
                    {
                      "billing_cycle_sequence": 1,
                      "pricing_scheme": {
                        "fixed_price": {
                          "value": "'.$price.'",
                          "currency_code": "'.$currency.'"
                        },
                        "roll_out_strategy": {
                          "process_change_from": "NEXT_PAYMENT"
                        }
                      }
                    }
                  ]
                }');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
}

function create_plan($access_token,$id_product_paypal,$name,$currency,$price,$interval_count,$interval_unit='MONTH') {
    global $url_paypal;
    $plan_id = null;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://'.$url_paypal.'/v1/billing/plans');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{
                  "product_id": "'.$id_product_paypal.'",
                  "name": "'.$name.'",
                  "status": "ACTIVE",
                  "billing_cycles": [
                    {
                      "frequency": {
                        "interval_unit": "'.$interval_unit.'",
                        "interval_count": '.$interval_count.'
                      },
                      "tenure_type": "REGULAR",
                      "sequence": 1,
                      "total_cycles": 0,
                      "pricing_scheme": {
                        "fixed_price": {
                          "value": "'.$price.'",
                          "currency_code": "'.$currency.'"
                        }
                      }
                    }
                  ],
                  "payment_preferences": {
                    "auto_bill_outstanding": true,
                    "setup_fee": {
                      "value": "0",
                      "currency_code": "'.$currency.'"
                    },
                    "setup_fee_failure_action": "CANCEL",
                    "payment_failure_threshold": 1
                  }
                }');
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.$access_token;
    $headers[] = 'Paypal-Request-Id: PLAN-'.strtoupper(str_replace(" ","-",$name).'-'.str_replace([',','.'],"",$price).$currency.'-'.$interval_count);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(array("status"=>"error","msg"=>"create_plan: ".curl_error($ch)));
        die();
    } else {
        $response = json_decode($result,true);
        if(isset($response['id'])) {
            $plan_id = $response['id'];
        } else {
            if(isset($response['details'][0]['description'])) {
                $error_msg = $response['details'][0]['description'];
            } else {
                $error_msg = "An error has occurred, please try again later";
            }
            echo json_encode(array("status"=>"error","msg"=>"create_plan: ".$error_msg));
            die();
        }
    }
    curl_close($ch);
    return $plan_id;
}