<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../functions.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
$m = $_POST['m'];
$html = "";
switch($m) {
    case 'marker':
    case 'poi':
        $html = get_library_icons_v($id_virtualtour,$m);
        break;
    case 'marker_h':
        $html = get_library_icons($id_virtualtour,'marker');
        break;
    case 'poi_h':
        $html = get_library_icons($id_virtualtour,'poi');
        break;
    case 'icons':
        $html = get_library_icons($id_virtualtour,'icons');
        break;
}
ob_end_clean();
echo json_encode(array("html"=>$html));