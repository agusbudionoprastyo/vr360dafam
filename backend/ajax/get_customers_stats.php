<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if($user_info['role']!='administrator') {
    die();
}
if(!isset($_SESSION['lang'])) {
    if(!empty($user_info['language'])) {
        $language = $user_info['language'];
    } else {
        $language = $settings['language'];
    }
} else {
    $language = $_SESSION['lang'];
}
set_language($language,$settings['language_domain']);
session_write_close();
$stats = array();
$stats['count_customers_active'] = 0;
$stats['count_customers_inactive'] = 0;
$stats['count_customers_total'] = 0;
$stats['last_registered'] = '';
$stats['free_plans'] = '';
$stats['recurring_month'] = 0;
$stats['subscriptions'] = array();
$query = "SELECT active,COUNT(id) as num FROM svt_users WHERE role='customer' GROUP BY active;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if($row['active']==1) {
                $stats['count_customers_active'] = $row['num'];
            } else {
                $stats['count_customers_inactive'] = $row['num'];
            }
        }
        $stats['count_customers_total'] = $stats['count_customers_active']+$stats['count_customers_inactive'];
    }
}
$query = "SELECT COUNT(u.id) as num FROM svt_users as u JOIN svt_plans as p ON p.id=u.id_plan AND p.price=0 WHERE u.active=1 AND u.role='customer';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $stats['free_plans'] = $row['num'];
    }
}
$query = "SELECT registration_date FROM svt_users WHERE active=1 AND role='customer' ORDER BY registration_date DESC LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $stats['last_registered'] = formatTime("%d %b %Y",$language,strtotime($row['registration_date']));
    }
}
$query = "SELECT p.price,p.price2,p.interval_count,p.currency,p.frequency,u.expire_plan_date FROM svt_users as u
            JOIN svt_plans as p ON p.id=u.id_plan
            WHERE u.active=1 AND u.role='customer' 
            AND p.currency = (SELECT currency FROM svt_plans GROUP BY currency ORDER BY COUNT(*) DESC LIMIT 1);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if(empty($row['expire_plan_date'])) {
                $status_ok = true;
            } else {
                if (new DateTime() > new DateTime($row['expire_plan_date'])) {
                    $status_ok = false;
                } else{
                    $status_ok = true;
                }
            }
            if($status_ok) {
                $price = $row['price'];
                $price2 = $row['price2'];
                $currency = $row['currency'];
                $frequency = $row['frequency'];
                $interval_count = $row['interval_count'];
                $month_price = 0;
                switch($frequency) {
                    case 'month_year':
                        if($price2!=0) {
                            $price2 = $price2/12;
                        }
                        if($price!=0) {
                            $month_price = ($price + $price2) / 2;
                        }
                        break;
                    case 'recurring':
                        if($price!=0) {
                            $month_price = $price / $interval_count;
                        }
                        break;
                }
                $stats['recurring_month']=$stats['recurring_month']+$month_price;
            }
        }
        $stats['recurring_month'] = format_currency($currency,$stats['recurring_month']);
    }
}
$query = "SELECT p.name as plan,
            SUM(CASE WHEN u.active = 1 THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN u.active = 0 THEN 1 ELSE 0 END) as inactive_users,
            COUNT(u.id) as total_users,
            FORMAT((SUM(CASE WHEN u.active = 1 THEN 1 ELSE 0 END) /
            (SELECT COUNT(*) FROM svt_users WHERE active = 1 AND role='customer')) * 100, 2) as percentage_active_users
            FROM svt_users as u
            LEFT JOIN svt_plans as p ON p.id = u.id_plan
            WHERE u.role = 'customer'
            GROUP BY p.id, p.name
            ORDER BY percentage_active_users DESC,p.name ASC;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if(empty($row['plan'])) $row['plan']="--";
            $stats['subscriptions'][] = $row;
        }
    }
}
ob_end_clean();
echo json_encode($stats);