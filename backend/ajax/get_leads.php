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
session_write_close();
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
$query = "SELECT * FROM svt_leads WHERE id_virtualtour=$id_vt";
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
    array( 'db' => 'datetime',  'dt' =>0, 'formatter' => function( $d, $row ) {
        global $language;
        if(empty($d)) {
            return "--";
        } else {
            return "<span style='display:none;'>".strtotime($d)."</span>".formatTime("%d %b %Y",$language,strtotime($d));
        }
    }),
    array( 'db' => 'name',  'dt' =>1 ),
    array( 'db' => 'email',  'dt' =>2 ),
    array( 'db' => 'phone',  'dt' =>3 )
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);