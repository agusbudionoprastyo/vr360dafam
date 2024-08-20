<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
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
require_once("../backend/functions.php");
require_once("../db/connection.php");
require('../backend/vendor/stripe-php/init.php');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$stripe_tax_automatic = $settings['stripe_automatic_tax_rate'];
if(empty($stripe_tax_automatic)) $stripe_tax_automatic='unspecified';
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname'];
$url = str_replace("/payments","",$url);

$key = $settings['stripe_secret_key'];
if(empty($key)) {
    exit;
}
$stripe = new \Stripe\StripeClient($key);
header('Content-Type: application/json');
$endpoint = $_POST['endpoint'];
switch ($endpoint) {
    case 'checkout_session':
        $id_plan = (int)$_POST['id_plan'];
        $second_price = (int)$_POST['second_price'];
        $user = get_user_info($id_user);
        $plan = get_plan($id_plan);
        $id_customer_stripe = $user['id_customer_stripe'];
        if($second_price==1) {
            $id_price_stripe = $plan['id_price2_stripe'];
        } else {
            $id_price_stripe = $plan['id_price_stripe'];
        }
        $frequency = $plan['frequency'];
        if($frequency=='month_year') $frequency="recurring";
        $user_name = $user['username'];
        $user_email = $user['email'];
        if(empty($id_customer_stripe)) {
            $id_customer_stripe = create_customer($user_name,$user_email);
            $mysqli->query("UPDATE svt_users SET id_customer_stripe='$id_customer_stripe' WHERE id=$id_user;");
        } else {
            if(!check_if_customer_exist($id_customer_stripe)) {
                $id_customer_stripe = create_customer($user_name,$user_email);
                $mysqli->query("UPDATE svt_users SET id_customer_stripe='$id_customer_stripe' WHERE id=$id_user;");
            } else {
                modify_customer($id_customer_stripe,$user_name,$user_email);
            }
        }
        $checkout_session = create_checkout($url,$id_customer_stripe,$id_price_stripe,$id_plan,$frequency);
        echo json_encode(array("status"=>"ok","id" => $checkout_session->id));
        break;
    case 'setup_session':
        $user = get_user_info($id_user);
        $id_customer_stripe = $user['id_customer_stripe'];
        $user_name = $user['username'];
        $user_email = $user['email'];
        modify_customer($id_customer_stripe,$user_name,$user_email);
        $id_subscription_stripe = $user['id_subscription_stripe'];
        $setup_session = create_setup($url,$id_customer_stripe,$id_subscription_stripe);
        echo json_encode(array("status"=>"ok","id" => $setup_session->id));
        break;
    case 'cancel_subscription':
        if(!$demo) {
            $user = get_user_info($id_user);
            $id_subscription_stripe = $user['id_subscription_stripe'];
            $subscription = get_subscription($id_subscription_stripe);
            cancel_subscription($id_subscription_stripe);
            $end_date = $subscription->current_period_end;
            $end_date = date('Y-m-d H:i:s',$end_date);
            $result = $mysqli->query("UPDATE svt_users SET expire_plan_date='$end_date' WHERE id=$id_user;");
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

                echo json_encode(array("status"=>"ok"));
            } else {
                echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
            }
        } else {
            echo json_encode(array("status"=>"error","msg"=>"Demo mode, insufficient permission."));
        }
        break;
    case 'change_subscription':
        if(!$demo) {
            $id_plan = (int)$_POST['id_plan'];
            $second_price = (int)$_POST['second_price'];
            $user = get_user_info($id_user);
            $plan = get_plan($id_plan);
            $query = "SELECT id_plan FROM svt_users WHERE id=$id_user LIMIT 1;";
            $result = $mysqli->query($query);
            $old_plan = "";
            if($result) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $id_plan_old = $row['id_plan'];
                $plan_old = get_plan($id_plan_old);
                $old_plan = $plan_old['name'];
            }
            $id_customer_stripe = $user['id_customer_stripe'];
            $user_name = $user['username'];
            $user_email = $user['email'];
            modify_customer($id_customer_stripe,$user_name,$user_email);
            if($second_price==1) {
                $id_price_stripe = $plan['id_price2_stripe'];
            } else {
                $id_price_stripe = $plan['id_price_stripe'];
            }
            $id_subscription_stripe = $user['id_subscription_stripe'];
            $subscription = get_subscription($id_subscription_stripe);
            change_subscription($id_subscription_stripe,$subscription,$id_price_stripe);
            set_user_log($id_user,'subscribe_plan',json_encode(array("id"=>$id_plan,"name"=>$plan['name'])),date('Y-m-d H:i:s', time()));
            $result = $mysqli->query("UPDATE svt_users SET id_plan=$id_plan WHERE id=$id_user;");
            if($result) {
                if($settings['notify_plan_changes']) {
                    $query = "SELECT u.id,u.username,u.email,p.name as plan FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id=$id_user;";
                    $result = $mysqli->query($query);
                    if($result) {
                        if($result->num_rows>0) {
                            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                                $username = $row['username'];
                                $email_u = $row['email'];
                                $plan = $row['plan'];
                                $subject = $settings['mail_plan_changed_subject'];
                                $body = $settings['mail_plan_changed_body'];
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
                echo json_encode(array("status"=>"ok"));
            } else {
                echo json_encode(array("status"=>"error","msg"=>"Error, retry later."));
            }
        } else {
            echo json_encode(array("status"=>"error","msg"=>"Demo mode, insufficient permission."));
        }
        break;
    case 'payment_method':
        $user = get_user_info($id_user);
        $id_subscription_stripe = $user['id_subscription_stripe'];
        $subscription = get_subscription($id_subscription_stripe);
        $id_payment_method = $subscription->default_payment_method;
        $payment_method = get_payment_method($id_payment_method);
        $card = $payment_method->card->last4." (".$payment_method->card->brand.")";
        echo json_encode(array("status"=>"ok","card"=>$card));
        break;
    case 'proration':
        $id_plan = (int)$_POST['id_plan'];
        $second_price = (int)$_POST['second_price'];
        $user = get_user_info($id_user);
        $plan = get_plan($id_plan);
        $id_customer_stripe = $user['id_customer_stripe'];
        if($second_price==1) {
            $id_price_stripe = $plan['id_price2_stripe'];
        } else {
            $id_price_stripe = $plan['id_price_stripe'];
        }
        $id_subscription_stripe = $user['id_subscription_stripe'];
        $interval_count = $plan['interval_count'];
        $price_plan = $plan['price'];
        $price2_plan = $plan['price2'];
        switch($plan['frequency']) {
            case 'recurring':
                if($interval_count==1) {
                    $recurring_label = _("month");
                } elseif($interval_count==12) {
                    $recurring_label = _("year");
                } else {
                    $recurring_label = $interval_count." "._("months");
                }
                break;
            case 'month_year':
                if($second_price==1) {
                    $price_plan = $price2_plan;
                    $recurring_label = _("year");
                } else {
                    $recurring_label = _("month");
                }
                break;
        }
        $subscription = get_subscription($id_subscription_stripe);
        $invoice = get_proration($id_customer_stripe,$id_subscription_stripe,$subscription,$id_price_stripe);
        switch ($plan['currency']) {
            case 'AED':
                $currency = "AED ";
                $price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'ILS':
                $currency = "₪ ";
                $price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'RUB':
                $currency = "₽ ";
                $price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'AUD':
                $currency = "A$ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',' ')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',' ')." / ".$recurring_label;
                break;
            case 'BRL':
                $currency = "R$ ";
                $price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,',','.')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'CAD':
                $currency = "C$ ";
                $price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'CHF':
                $currency = "₣ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,',','.')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'CNY':
                $currency = "¥ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'CZK':
                $currency = "Kč ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,',','.')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'CLP':
                $currency = "$ ";
                $next_price = $currency.number_format($invoice->starting_balance + $invoice->total,0,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,0,'.',',')." / ".$recurring_label;
                break;
            case 'JPY':
                $currency = "¥ ";
                $next_price = $currency.number_format($invoice->starting_balance + $invoice->total,0,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,0,'.',',')." / ".$recurring_label;
                break;
            case 'EUR':
                $currency = "€ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,',','.')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'GBP':
                $currency = "£ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'IDR':
                $currency = "Rp ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'INR':
                $currency = "Rs ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'PLN':
                $currency = "zł ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,',','.')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'SEK':
                $currency = "kr ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,',','.')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'TRY':
                $currency = "₺ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'TJS':
                $currency = "SM ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'USD':
            case 'ARS':
                $currency = "$ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'HKD':
                $currency = "HK$ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'SGD':
                $currency = "S$ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'NGN':
                $currency = "₦ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'MXN':
                $currency = "Mex$ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',','.')." / ".$recurring_label;
                break;
            case 'MYR':
                $currency = "RM ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'PHP':
                $currency = "₱ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'THB':
                $currency = "฿ ";
                $next_price = $currency.number_format($invoice->starting_balance/100 + $invoice->total/100,2,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,'.',',')." / ".$recurring_label;
                break;
            case 'RWF':
                $currency = "FRw ";
                $next_price = $currency.number_format($invoice->starting_balance + $invoice->total,0,'',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,0,'',',')." / ".$recurring_label;
                break;
            case 'VND':
                $currency = "₫ ";
                $next_price = $currency.number_format($invoice->starting_balance + $invoice->total,0,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,0,'.',',')." / ".$recurring_label;
                break;
            case 'PYG':
                $currency = "₲ ";
                $next_price = $currency.number_format($invoice->starting_balance + $invoice->total,0,'.',',')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,0,'.',',')." / ".$recurring_label;
                break;
            case 'ZAR':
                $currency = "R ";
                $next_price = $currency.number_format($invoice->starting_balance + $invoice->total,2,',',' ')." (".date("d M Y").")";
                $subseq_price = $currency.number_format($price_plan,2,',',' ')." / ".$recurring_label;
                break;
        }
        $next_payment_date = $invoice->lines->data[1]->period->end;
        $subseq_price = $subseq_price . " ("._("from ").date("d M Y",$next_payment_date).")";
        echo json_encode(array("status"=>"ok","plan"=>$plan,"next_price"=>$next_price,"subseq_price"=>$subseq_price,"invoice"=>$invoice));
        break;
    case 'reactivate_subscription':
        if(!$demo) {
            $user = get_user_info($id_user);
            $id_customer_stripe = $user['id_customer_stripe'];
            $user_name = $user['username'];
            $user_email = $user['email'];
            modify_customer($id_customer_stripe,$user_name,$user_email);
            $id_subscription_stripe = $user['id_subscription_stripe'];
            $subscription = get_subscription($id_subscription_stripe);
            reactivate_subscription($id_subscription_stripe);
            $result = $mysqli->query("UPDATE svt_users SET expire_plan_date=NULL WHERE id=$id_user;");
            if ($result) {
                echo json_encode(array("status" => "ok"));
            } else {
                echo json_encode(array("status" => "error", "msg" => "Error, retry later."));
            }
        } else {
            echo json_encode(array("status"=>"error","msg"=>"Demo mode, insufficient permission."));
        }
        break;
    case 'subscription_end_date':
        $user = get_user_info($id_user);
        $id_subscription_stripe = $user['id_subscription_stripe'];
        $subscription = get_subscription($id_subscription_stripe);
        $end_date = $subscription->current_period_end;
        $end_date = date('d M Y',$end_date);
        $id_product_stripe = $subscription->items->data[0]->price->product;
        $name_plan = get_name_plan_stripe($id_product_stripe);
        echo json_encode(array("status"=>"ok","end_date"=>$end_date,"name"=>$name_plan));
        break;
}

function check_if_customer_exist($id) {
    global $stripe;
    try {
        $response = $stripe->customers->retrieve($id, []);
        if($response['deleted']==1) {
            return false;
        } else {
            return true;
        }
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

function create_customer($name,$email) {
    global $stripe;
    try {
        $response = $stripe->customers->create([
            'name' => $name,
            'email' => $email
        ]);
        $id = $response['id'];
        return $id;
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

function modify_customer($id_customer_stripe,$name,$email) {
    global $stripe;
    try {
        $stripe->customers->update($id_customer_stripe,
            ['name' => $name, 'email' => $email]
        );
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

function create_checkout($url,$id_customer_stripe,$id_price_stripe,$id_plan,$frequency) {
    global $stripe, $stripe_tax_automatic;
    $mode = "subscription";
    if($frequency=="one_time") {
        $mode = "payment";
    }
    $stripe_price = $stripe->prices->retrieve($id_price_stripe, []);
    $tax_behavior_exist = trim($stripe_price['tax_behavior']);
    if(($tax_behavior_exist!='unspecified') && ($stripe_tax_automatic!='unspecified')) {
        try {
            $response = $stripe->checkout->sessions->create([
                'success_url' => $url.'/backend/index.php?p=change_plan&response=success',
                'cancel_url' => $url.'/backend/index.php?p=change_plan&response=cancel',
                'customer' => $id_customer_stripe,
                'line_items' => [
                    [
                        'price' => $id_price_stripe,
                        'quantity' => 1,
                    ],
                ],
                'mode' => $mode,
                'billing_address_collection' => 'required',
                'metadata' => [
                    'id_plan' => $id_plan
                ],
                'automatic_tax' => [
                    'enabled' => true
                ],
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto'
                ],
                "tax_id_collection" => [
                    "enabled" => true
                ]
            ]);
            return $response;
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
    } else {
        try {
            $response = $stripe->checkout->sessions->create([
                'success_url' => $url.'/backend/index.php?p=change_plan&response=success',
                'cancel_url' => $url.'/backend/index.php?p=change_plan&response=cancel',
                'customer' => $id_customer_stripe,
                'line_items' => [
                    [
                        'price' => $id_price_stripe,
                        'quantity' => 1,
                    ],
                ],
                'mode' => $mode,
                'billing_address_collection' => 'required',
                'metadata' => [
                    'id_plan' => $id_plan
                ],
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto'
                ],
                "tax_id_collection" => [
                    "enabled" => true
                ]
            ]);
            return $response;
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
}

function create_setup($url,$id_customer_stripe,$id_subscription_stripe) {
    global $stripe;
    try {
        $response = $stripe->checkout->sessions->create([
            'mode' => 'setup',
            'customer' => $id_customer_stripe,
            'setup_intent_data' => [
                'metadata' => [
                    'customer_id' => $id_customer_stripe,
                    'subscription_id' => $id_subscription_stripe,
                ],
            ],
            'billing_address_collection' => 'required',
            'success_url' => $url.'/backend/index.php',
            'cancel_url' => $url.'/backend/index.php',
        ]);
        return $response;
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

function get_subscription($id) {
    global $stripe;
    try {
        $response = $stripe->subscriptions->retrieve($id);
        return $response;
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

function cancel_subscription($id) {
    global $stripe;
    try {
        $stripe->subscriptions->update($id, [
            "cancel_at_period_end"=> true
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

function change_subscription($id,$subscription,$id_price_stripe) {
    global $stripe;
    try {
        $stripe->subscriptions->update($id, [
                'cancel_at_period_end' => false,
                'proration_behavior' => 'always_invoice',
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $id_price_stripe,
                    ],
                ]
            ]
        );
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

function get_payment_method($id) {
    global $stripe;
    try {
        $response = $stripe->paymentMethods->retrieve($id, []);
        return $response;
        return $response;
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

function get_proration($id_customer_stripe,$id_subscription_stripe,$subscription,$id_price_stripe) {
    global $stripe;
    try {
        $response = $stripe->invoices->upcoming([
            "customer" => $id_customer_stripe,
            "subscription" => $id_subscription_stripe,
            "subscription_proration_behavior" => "always_invoice",
            "subscription_items" => [
                [
                    'id' => $subscription->items->data[0]->id,
                    'price' => $id_price_stripe,
                ],
            ]
        ]);
        return $response;
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

function reactivate_subscription($id) {
    global $stripe;
    try {
        $stripe->subscriptions->update($id, [
            "cancel_at"=> ""
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