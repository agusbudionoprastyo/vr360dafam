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
set_language($language,$settings['language_domain']);
$s3_params = check_s3_tour_enabled($id_vt);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$ok_label = _("Ok");
$progress_label = _("In Progress");
$draft_label = _("Draft");
$query = "SELECT svp.id,svp.id_virtualtour,svp.name,svp.date_time,COUNT(svps.id) as count_slides,0 as status,(SELECT id FROM svt_job_queue WHERE svp.id_virtualtour = id_virtualtour and svp.id=id_project) as id_job FROM svt_video_projects as svp
LEFT JOIN svt_video_project_slides svps on svp.id = svps.id_video_project
WHERE svp.id_virtualtour=$id_vt
GROUP BY svp.id,svp.id_virtualtour,svp.name,svp.date_time";
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
    array( 'db' => 'name',  'dt' =>0, 'formatter' => function( $d, $row ) {
        return $d;
    }),
    array( 'db' => 'date_time',  'dt' =>1, 'formatter' => function( $d, $row ) {
        global $language;
        return formatTime("%d %b %Y - %H:%M",$language,strtotime($d));
    }),
    array( 'db' => 'count_slides',  'dt' =>2, 'formatter' => function( $d, $row ) {
        return $d;
    }),
    array( 'db' => 'status',  'dt' =>3, 'formatter' => function( $d, $row ) {
        global $draft_label,$ok_label,$progress_label,$s3_enabled,$s3_bucket_name;
        if($s3_enabled) {
            $path_video = "s3://$s3_bucket_name/video/".$row['id_virtualtour']."_".$row['id'].".mp4";
        } else {
            $path_video = "../../video/".$row['id_virtualtour']."_".$row['id'].".mp4";
        }
        if(file_exists($path_video)) {
            return '<span class="badge badge-success"><i class="fas fa-check"></i>&nbsp;&nbsp;'.$ok_label.'</span>';
        } else {
            if(!empty($row['id_job'])) {
                return '<span class="badge badge-warning"><i class="fas fa-circle-notch fa-spin"></i>&nbsp;&nbsp;'.$progress_label.'</span>';
            } else {
                return '<span class="badge badge-secondary"><i class="fas fa-circle"></i>&nbsp;&nbsp;'.$draft_label.'</span>';
            }
        }
    })
);
$sql_details = array(
    'user' => DATABASE_USERNAME,
    'pass' => DATABASE_PASSWORD,
    'db' => DATABASE_NAME,
    'host' => DATABASE_HOST);
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);