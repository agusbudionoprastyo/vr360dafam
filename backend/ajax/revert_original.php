<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
ini_set('max_input_time', 9999);
require_once(dirname(__FILE__).'/../../db/connection.php');
require_once(dirname(__FILE__).'/../functions.php');
require_once(dirname(__FILE__).'/ImageResizeException.php');
require_once(dirname(__FILE__).'/ImageResize.php');
use \Gumlet\ImageResize;
$id_user = $_SESSION['id_user'];
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$id_room = (int)$_POST['id_room'];
$panorama_image = $_POST['panorama_image'];
$panorama_image_new = 'pano_'.time().'.jpg';
rename(dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image, dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image_new);
$virtual_tour = get_virtual_tour($id_virtualtour,$id_user);
$compress_jpg = $virtual_tour['compress_jpg'];
$max_width_compress = $virtual_tour['max_width_compress'];
if($compress_jpg=="") $compress_jpg=90;
if($max_width_compress=="") $max_width_compress=0;
copy(dirname(__FILE__).'/../../viewer/panoramas/original/'.$panorama_image_new,dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image_new);
if($compress_jpg<100 || $max_width_compress>0) {
    try {
        $image = new ImageResize(dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image_new);
        $image->quality_jpg = $compress_jpg;
        $image->interlace = 1;
        if($max_width_compress>0) {
            $image->resizeToWidth($max_width_compress,false);
        }
        $image->gamma(false);
        $image->save(dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image_new);
    } catch (ImageResizeException $e) {}
}
if(file_exists(dirname(__FILE__).'/../../viewer/panoramas/'.$panorama_image_new)) {
    $mysqli->query("UPDATE svt_rooms SET panorama_image='$panorama_image_new',multires_status=0,blur=0 WHERE id=$id_room;");
    $panorama_image_gt = $panorama_image_new;
    include("../../services/generate_thumb.php");
    include("../../services/generate_pano_mobile.php");
    generate_multires(false,$id_virtualtour);
}