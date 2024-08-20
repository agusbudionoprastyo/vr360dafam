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
$query = "SELECT ad.id,ad.name,ad.type,ad.auto_assign,COUNT(aa.id_virtualtour) as vt_count FROM svt_advertisements AS ad
LEFT JOIN svt_assign_advertisements AS aa ON aa.id_advertisement=ad.id
GROUP BY ad.id";
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
    array( 'db' => 'name',  'dt' =>0 ),
    array( 'db' => 'type',  'dt' =>1, 'formatter' => function( $d, $row ) {
        switch($d) {
            case 'image':
                $d = _("Image");
                break;
            case 'video':
                $d = _("Mp4 Video");
                break;
            case 'iframe':
                $d = _("Embedded Link");
                break;
        }
        return $d;
    }),
    array( 'db' => 'auto_assign',  'dt' =>2, 'formatter' => function( $d, $row ) {
        if($d) {
            return "<i class='fa fa-check'></i>";
        } else {
            return "<i class='fa fa-times'></i>";
        }
    }),
    array( 'db' => 'vt_count',  'dt' =>3 ),
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);