<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
ini_set('max_input_time', 9999);
require_once(__DIR__.'/ImageResizeException.php');
require_once(__DIR__.'/ImageResize.php');
use \Gumlet\ImageResize;
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$id_virtualtour = (int)$_POST['id_virtualtour'];
$keep_original_panorama = (int)$_POST['keep_original_panorama'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
$s3Client = null;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
}
session_write_close();
$path = realpath(dirname(__FILE__) . '/..');
$compress_jpg = $_POST['compress_jpg'];
$max_width_compress = $_POST['max_width_compress'];
$enable_multires = $_POST['enable_multires'];
if($compress_jpg=="") $compress_jpg=90;
if($max_width_compress=="") $max_width_compress=0;
$array_panoramas_gt = array();
$mysqli->query("UPDATE svt_virtualtours SET enable_multires=$enable_multires,compress_jpg=$compress_jpg,max_width_compress=$max_width_compress WHERE id=$id_virtualtour;");
$result = $mysqli->query("SELECT id,panorama_image FROM svt_rooms WHERE type='image' AND id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $panorama_image = $row['panorama_image'];
            resize_panorama($id,'svt_rooms',$panorama_image);
        }
    }
}
$result = $mysqli->query("SELECT id,panorama_image FROM svt_rooms_alt WHERE id_room IN (SELECT id FROM svt_rooms WHERE type='image' AND id_virtualtour=$id_virtualtour);");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $panorama_image = $row['panorama_image'];
            resize_panorama($id,'svt_rooms_alt',$panorama_image);
        }
    }
}


function resize_panorama($id,$table,$panorama_image) {
    global $path,$compress_jpg,$max_width_compress,$mysqli,$s3_enabled,$s3_bucket_name,$keep_original_panorama,$array_panoramas_gt,$settings,$s3Client;
    $new_name = "pano_".round(microtime(true) * 1000).".jpg";
    if($s3_enabled) {
        $original_pano = "s3://$s3_bucket_name/viewer/panoramas/original/$panorama_image";
        $resized_pano = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image";
        $original_pano_new = "s3://$s3_bucket_name/viewer/panoramas/original/$new_name";
        $resized_pano_new = "s3://$s3_bucket_name/viewer/panoramas/$new_name";
    } else {
        $original_pano = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."original".DIRECTORY_SEPARATOR.$panorama_image;
        $resized_pano = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR.$panorama_image;
        $original_pano_new = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."original".DIRECTORY_SEPARATOR.$new_name;
        $resized_pano_new = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR.$new_name;
    }
    if(file_exists($original_pano)) {
        copy($original_pano,$original_pano_new);
    } else {
        copy($resized_pano,$original_pano_new);
    }
    if(file_exists($original_pano_new)) {
        if($compress_jpg<100 || $max_width_compress>0) {
            try {
                $image = new ImageResize($original_pano_new);
                $image->quality_jpg = $compress_jpg;
                $image->interlace = 1;
                if ($max_width_compress > 0) {
                    $image->resizeToWidth($max_width_compress, false);
                }
                $image->gamma(false);
                $image->save($resized_pano_new);
            } catch (ImageResizeException $e) {}
        } else {
            copy($original_pano_new,$resized_pano_new);
        }
        if(file_exists($resized_pano_new)) {
            unlink($resized_pano);
            unlink($original_pano);
            if($keep_original_panorama==0) {
                unlink($original_pano_new);
            } else {
                if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
                    try {
                        $s3Client->putObjectAcl([
                            'Bucket' => $s3_bucket_name,
                            'Key' => "viewer/panoramas/original/$new_name",
                            'ACL' => 'public-read',
                        ]);
                    } catch (\Aws\S3\Exception\S3Exception $e) {}
                }
            }
            if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/panoramas/$new_name",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
            $mysqli->query("UPDATE $table SET multires_status=0,panorama_image='$new_name' WHERE id=$id;");
            array_push($array_panoramas_gt,$new_name);
        }
    }
}

include("generate_thumb.php");
include("generate_pano_mobile.php");