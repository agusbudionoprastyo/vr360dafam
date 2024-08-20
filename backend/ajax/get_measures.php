<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$id_room = (int)$_POST['id_room'];
$array=array();
$room=array();
$query = "SELECT r.panorama_image,r.panorama_video,v.enable_multires,r.yaw,r.pitch,r.h_pitch,r.h_roll,r.allow_pitch,r.min_pitch,r.max_pitch,r.min_yaw,r.max_yaw,r.haov,r.vaov,r.type,r.id_poi_autoopen FROM svt_rooms as r 
            JOIN svt_virtualtours as v ON v.id=r.id_virtualtour
            WHERE r.id = $id_room LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $room['id_poi_autoopen'] = $row['id_poi_autoopen'];
        $room['yaw'] = $row['yaw'];
        $room['pitch'] = $row['pitch'];
        $room['h_pitch'] = $row['h_pitch'];
        $room['h_roll'] = $row['h_roll'];
        $room['min_yaw'] = $row['min_yaw'];
        $room['max_yaw'] = $row['max_yaw'];
        $room['allow_pitch'] = $row['allow_pitch'];
        $room['min_pitch'] = $row['min_pitch'];
        $room['max_pitch'] = $row['max_pitch'];
        $room['haov'] = $row['haov'];
        $room['vaov'] = $row['vaov'];
        $room['panorama_video'] = $row['panorama_video'];
        $room['room_type'] = $row['type'];
        if($row['enable_multires']) {
            $room_pano = str_replace('.jpg','',$row['panorama_image']);
            if($s3_enabled) {
                $multires_config_file = "s3://$s3_bucket_name/viewer/panoramas/multires/$room_pano/config.json";
            } else {
                $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
            }
            if(file_exists($multires_config_file)) {
                $multires_tmp = file_get_contents($multires_config_file);
                $multires_array = json_decode($multires_tmp,true);
                $multires_config = $multires_array['multiRes'];
                if($s3_enabled) {
                    $multires_config['basePath'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                } else {
                    $multires_config['basePath'] = '../viewer/panoramas/multires/'.$room_pano;
                }
                $room['multires']=1;
                $room['multires_config']=json_encode($multires_config);
                if($s3_enabled) {
                    $room['multires_dir'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                } else {
                    $room['multires_dir']='../viewer/panoramas/multires/'.$room_pano;
                }
            } else {
                $room['multires']=0;
                $room['multires_config']='';
                $room['multires_dir']='';
            }
        } else {
            $room['multires']=0;
            $room['multires_config']='';
            $room['multires_dir']='';
        }
    }
}
$query = "SELECT * FROM svt_measures WHERE id_room=$id_room;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode(array("measures"=>$array,"room"=>$room));