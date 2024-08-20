<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require(__DIR__.'/ssp.class.php');
require(__DIR__.'/../../config/config.inc.php');
require(__DIR__.'/../functions.php');
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) {
    $demo = true;
} else {
    $demo = false;
}
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
$customer_label = _("Customer");
$administrator_label = _("Administrator");
$super_admin_label = _("Super Administrator");
$editor_label = _("Editor");
$day_label = _("day");
$days_label = _("days");
$today_label = _("today");
$query = "SELECT u.*,COALESCE(p.name, '--') as plan_name,(SELECT COUNT(*) FROM svt_virtualtours WHERE id_user=u.id) as count_vt FROM svt_users as u LEFT JOIN svt_plans as p ON p.id = u.id_plan";
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
    array( 'db' => 'username',  'dt' =>0, 'formatter' => function( $d, $row ) {
        if(empty($row['avatar'])) {
            $avatar='img/avatar1.png';
        } else {
            $avatar='assets/'.$row['avatar'];
        }
        return "<span style='white-space: nowrap;'><img style='width:20px;height:20px;border-radius:50%;margin-bottom:2px;' src='$avatar' /> ".$d."</span>";
    }),
    array( 'db' => 'email',  'dt' =>1, 'formatter' => function( $d, $row ) {
        global $demo;
        if($demo) {
            return obfuscateEmail($d);
        } else {
            return $d;
        }
    }),
    array( 'db' => 'role',  'dt' =>2, 'formatter' => function( $d, $row ) {
        global $customer_label,$editor_label,$administrator_label,$super_admin_label;
        $role = '';
        switch($d) {
            case 'administrator':
                if($row['super_admin']) {
                    $role = $super_admin_label;
                } else {
                    $role = $administrator_label;
                }
                break;
            case 'editor':
                $role = $editor_label;
                break;
            case 'customer':
                $role = $customer_label;
                break;
        }
        return ucfirst($role);
    }),
    array( 'db' => 'plan_name',  'dt' =>3, 'formatter' => function( $d, $row ) {
        if(($row['role']!='editor') && ($row['id_plan']!=0)) {
            if((!empty($row['id_subscription_stripe']) && ($row['status_subscription_stripe']==0)) || (!empty($row['id_subscription_paypal']) && ($row['status_subscription_paypal']==0)) || (!empty($row['id_subscription_2checkout']) && ($row['status_subscription_2checkout']==0))) {
                return "<i class='fa fa-circle' style='color: red'></i> " . $d;
            } else {
                if(empty($row['expire_plan_date'])) {
                    return "<i class='fa fa-circle' style='color: green'></i> " . $d;
                } else {
                    if (new DateTime() > new DateTime($row['expire_plan_date'])) {
                        return "<i class='fa fa-circle' style='color: red'></i> " . $d;
                    } else{
                        return "<i class='fa fa-circle' style='color: darkorange'></i> " . $d;
                    }
                }
            }
        } else {
            return "";
        }
    }),
    array( 'db' => 'registration_date',  'dt' =>4, 'formatter' => function( $d, $row ) {
        global $language;
        $reg_date = formatTime("%d %b %Y",$language,strtotime($d));
        return $reg_date;
    }),
    array( 'db' => 'expire_plan_date',  'dt' =>5, 'formatter' => function( $d, $row ) {
        global $day_label,$days_label,$today_label;
        $diff_days = dateDiffInDays(date('Y-m-d',strtotime($d)),date('Y-m-d',strtotime('today')));
        if($diff_days==0) {
            return $today_label;
        } else if($diff_days==-1) {
            return "1 ".$day_label;
        } else if ($diff_days>0) {
            return "--";
        } else {
             return abs($diff_days)." ".$days_label;
        }
    }),
    array( 'db' => 'active',  'dt' =>6, 'formatter' => function( $d, $row ) {
        if($d) {
            return "<i class='fa fa-check'></i>";
        } else {
            return "<i class='fa fa-times'></i>";
        }
    }),
    array( 'db' => 'count_vt',  'dt' =>7, 'formatter' => function( $d, $row ) {
        return $d;
    }),
    array( 'db' => 'first_name',  'dt' =>8, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'last_name',  'dt' =>9, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'company',  'dt' =>10, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'tax_id',  'dt' =>11, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'street',  'dt' =>12, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'city',  'dt' =>13, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'province',  'dt' =>14, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'postal_code',  'dt' =>15, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'country',  'dt' =>16, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
    array( 'db' => 'tel',  'dt' =>17, 'formatter' => function( $d, $row ) {
        return "<span class='hidden_td'>$d</span>";
    }),
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);