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
session_write_close();
$query = "SELECT f.*,r.name as room FROM svt_forms_data as f
LEFT JOIN svt_rooms as r ON r.id=f.id_room
WHERE f.id_virtualtour=$id_vt";
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
    array( 'db' => 'room',  'dt' =>1 ),
    array( 'db' => 'title',  'dt' =>2 ),
    array( 'db' => 'field1',  'dt' =>3 ),
    array( 'db' => 'field2',  'dt' =>4 ),
    array( 'db' => 'field3',  'dt' =>5 ),
    array( 'db' => 'field4',  'dt' =>6 ),
    array( 'db' => 'field5',  'dt' =>7 ),
    array( 'db' => 'field6',  'dt' =>8 ),
    array( 'db' => 'field7',  'dt' =>9 ),
    array( 'db' => 'field8',  'dt' =>10 ),
    array( 'db' => 'field9',  'dt' =>11 ),
    array( 'db' => 'field10',  'dt' =>12 )
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);