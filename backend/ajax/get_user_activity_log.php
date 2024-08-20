<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require(__DIR__.'/ssp.class.php');
require(__DIR__.'/../../config/config.inc.php');
require(__DIR__.'/../functions.php');
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
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
$id_user_edit = (int)$_POST['id_user_edit'];
$query = "SELECT * FROM svt_users_log WHERE id_user=$id_user_edit";
$table = "( $query ) t";
$primaryKey = 'id_user';
$columns = array(
    array( 'db' => 'type',  'dt' =>0, 'formatter' => function( $d, $row ) {
        switch($d) {
            case 'register':
                $d = "<span class='badge badge-success'><i class='fas fa-user-plus'></i> "._("register")."</span>";
                break;
            case 'login':
                $d = "<span class='badge badge-primary'><i class='fas fa-sign-in-alt'></i> "._("login")."</span>";
                break;
            case 'add_virtual_tour':
                $d = "<span class='badge badge-success'><i class='fas fa-plus'></i> "._("add tour")."</span>";
                break;
            case 'delete_virtual_tour':
                $d = "<span class='badge badge-danger'><i class='fas fa-minus'></i> "._("delete tour")."</span>";
                break;
            case 'subscribe_plan':
                $d = "<span class='badge badge-warning'><i class='fas fa-exchange-alt'></i> "._("subscribe")."</span>";
                break;
            case 'unsubscribe_plan':
                $d = "<span class='badge badge-dark'><i class='fas fa-exchange-alt'></i> "._("unsubscribe")."</span>";
                break;
            case 'add_room':
                $d = "<span class='badge badge-success'><i class='fas fa-plus'></i> "._("add room")."</span>";
                break;
            case 'delete_room':
                $d = "<span class='badge badge-danger'><i class='fas fa-minus'></i> "._("delete room")."</span>";
                break;
        }
        return $d;
    }),
    array( 'db' => 'params',  'dt' =>1, 'formatter' => function( $d, $row ) {
        switch($row['type']) {
            case 'add_virtual_tour':
            case 'delete_virtual_tour':
            case 'add_room':
            case 'delete_room':
                $params = json_decode($d,true);
                $d = $params['id']." - ".strtoupper($params['name']);
                break;
            case 'subscribe_plan':
            case 'unsubscribe_plan':
                $params = json_decode($d,true);
                $d = strtoupper($params['name']);
                break;
        }
        return $d;
    }),
    array( 'db' => 'date_time',  'dt' =>2, 'formatter' => function( $d, $row ) {
        global $language;
        $date_time = formatTime("%d %b %Y - %H:%M",$language,strtotime($d));
        return $date_time;
    }),
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_POST, $sql_details, $table, $primaryKey, $columns )
);