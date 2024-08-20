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
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$id_room = $_SESSION['id_room_sel'];
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$compress_jpg = $_SESSION['compress_jpg'];
$max_width_compress = $_SESSION['max_width_compress'];
$id_user = $_SESSION['id_user'];
$keep_original_panorama = $_SESSION['keep_original_panorama'];
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
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
if($compress_jpg=="") $compress_jpg=90;
if($max_width_compress=="") $max_width_compress=0;
if($keep_original_panorama=="") $keep_original_panorama=1;
if(isset($_FILES) && !empty($_FILES['file']['name'])){
    $allowed_ext = array('png','jpg','jpeg');
    $filename = $_FILES['file']['name'];
    $ext = explode('.',$filename);
    $ext = strtolower(end($ext));
    if(in_array($ext,$allowed_ext)){
        if(strtolower($ext)=='png') {
            png2jpg_alt($_FILES['file']['tmp_name'],$_FILES['file']['tmp_name'],100);
        }
        $name = "pano_".round(microtime(true) * 1000).".jpg";
        if($s3_enabled) {
            $room_path = "s3://$s3_bucket_name/viewer/panoramas/$name";
        } else {
            $room_path = dirname(__FILE__).'/../../viewer/panoramas/'.$name;
        }
        $moved = move_uploaded_file($_FILES['file']['tmp_name'],$room_path);
        if($moved) {
            if($keep_original_panorama) {
                if($s3_enabled) {
                    try {
                        copy($room_path,"s3://$s3_bucket_name/viewer/panoramas/original/$name");
                    } catch (Exception $e) {}
                    if($settings['aws_s3_type']=='digitalocean') {
                        try {
                            $s3Client->putObjectAcl([
                                'Bucket' => $s3_bucket_name,
                                'Key' => "viewer/panoramas/original/$name",
                                'ACL' => 'public-read',
                            ]);
                        } catch (\Aws\S3\Exception\S3Exception $e) {
                            ob_end_clean();
                            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                            exit;
                        }
                    }
                } else {
                    try {
                        copy($room_path, dirname(__FILE__) . '/../../viewer/panoramas/original/' . $name);
                    } catch (Exception $e) {}
                }
            }
            list($width, $height) = getimagesize($room_path);
            $ratio = $width / $height;
            if($compress_jpg<100 || $max_width_compress>0) {
                try {
                    $image = new ImageResize($room_path);
                    $image->quality_jpg = $compress_jpg;
                    $image->interlace = 1;
                    if($max_width_compress>0) {
                        $image->resizeToWidth($max_width_compress,false);
                    }
                    $image->gamma(false);
                    $image->save($room_path);
                } catch (ImageResizeException $e) {}
            }
            if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/panoramas/$name",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                    exit;
                }
            }
            $priority = 0;
            $query = "SELECT MAX(priority)+1 as priority FROM svt_rooms_alt WHERE id_room=$id_room LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    $priority = $row['priority'];
                    if(empty($priority)) $priority=0;
                }
            }
            $mysqli->query("INSERT INTO svt_rooms_alt(id_room,panorama_image,priority) VALUES($id_room,'$name',$priority);");
            $panorama_image_gt = $name;
            include("../../services/generate_thumb.php");
            include("../../services/generate_pano_mobile.php");
            generate_multires(false,$id_virtualtour);
            update_user_space_storage($id_user,false);
            ob_end_clean();
            echo 'ok';
        } else {
            ob_end_clean();
            echo 'ERROR: code:'.$_FILES["file"]["error"];
        }
    }else{
        ob_end_clean();
        echo 'ERROR: '._("Only jpg,png files are supported.");
    }
}else{
    ob_end_clean();
    echo 'ERROR: '._("File not provided.");
}

function png2jpg_alt($originalFile, $outputFile, $quality) {
    $image = imagecreatefrompng($originalFile);
    imagejpeg($image, $outputFile, $quality);
    imagedestroy($image);
}
exit;