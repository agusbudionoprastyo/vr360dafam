<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_user = $_SESSION['id_user'];
session_write_close();
$name = strip_tags($_POST['name']);
$name = ucfirst($name);
$panorama_image = strip_tags($_POST['panorama_image']);
if(isset($_POST['panorama_video'])) {
    $panorama_video = strip_tags($_POST['panorama_video']);
} else {
    $panorama_video = '';
}
if(isset($_POST['type_pano'])) {
    $type_pano = strip_tags($_POST['type_pano']);
} else {
    $type_pano = 'image';
}
$panorama_url = strip_tags($_POST['panorama_url']);
$panorama_json = strip_tags($_POST['panorama_json']);
if($type_pano!='hls') $panorama_url="";
$virtual_tour = get_virtual_tour($id_virtualtour,$id_user);
$transition_time = $virtual_tour['transition_time'];
$transition_fadeout = $virtual_tour['transition_fadeout'];
$transition_zoom = $virtual_tour['transition_zoom'];
$transition_effect = $virtual_tour['transition_effect'];
$settings = get_settings();
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
$s3Client = null;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
$path_source = '';
switch($type_pano) {
    case 'image':
    case 'hls':
    case 'lottie':
        $name_image = str_replace("tmp_panoramas/","",$panorama_image);
        $name_video = '';
        $path_source = dirname(__FILE__).'/../tmp_panoramas/'.$name_image;
        if($s3_enabled) {
            if($type_pano=='lottie') {
                $panorama_json = str_replace($s3_url."viewer/panoramas/","",$panorama_json);
            }
            $path_dest = "s3://$s3_bucket_name/viewer/panoramas/$name_image";
        } else {
            if($type_pano=='lottie') {
                $panorama_json = str_replace("../viewer/panoramas/","",$panorama_json);
            }
            $path_dest = dirname(__FILE__).'/../../viewer/panoramas/'.$name_image;
        }
        if(copy($path_source,$path_dest)) {
            if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/panoramas/$name_image",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                    exit;
                }
            }
            $panorama_image_gt = $name_image;
            include("../../services/generate_thumb.php");
            include("../../services/generate_pano_mobile.php");
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error image"));
            die();
        }
        break;
    case 'video':
        $name_image = "pano_".round(microtime(true) * 1000).".jpg";
        if($s3_enabled) {
            $name_video = str_replace($s3_url."viewer/videos/","",$panorama_video);
            $path_dest = "s3://$s3_bucket_name/viewer/panoramas/$name_image";
        } else {
            $name_video = str_replace("../viewer/videos/","",$panorama_video);
            $path_dest = dirname(__FILE__).'/../../viewer/panoramas/'.$name_image;
        }
        $ifp = fopen($path_dest,'wb');
        fwrite($ifp,base64_decode($panorama_image));
        fclose($ifp);
        if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/$name_image",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                exit;
            }
        }
        $panorama_image_gt = $name_image;
        include("../../services/generate_thumb.php");
        include("../../services/generate_pano_mobile.php");
        break;
    case 'ai_room':
        $type_pano = 'image';
        $name_image = "pano_".round(microtime(true) * 1000).".jpg";
        if($s3_enabled) {
            $path_dest = "s3://$s3_bucket_name/viewer/panoramas/$name_image";
        } else {
            $path_dest = dirname(__FILE__).'/../../viewer/panoramas/'.$name_image;
        }
        if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
            file_put_contents($path_dest, file_get_contents($panorama_image));
        } else {
            file_put_contents($path_dest, curl_get_file_contents($panorama_image));
        }
        if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/$name_image",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                exit;
            }
        }
        $panorama_image_gt = $name_image;
        include("../../services/generate_thumb.php");
        include("../../services/generate_pano_mobile.php");
        break;
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$priority = 0;
$query = "SELECT MAX(priority)+1 as priority FROM svt_rooms WHERE id_virtualtour=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $priority = $row['priority'];
        if(empty($priority)) $priority=0;
    }
}
$query = "INSERT INTO svt_rooms(id_virtualtour,name,type,panorama_image,panorama_video,panorama_url,panorama_json,priority,transition_time,transition_fadeout,transition_zoom,transition_effect,protect_email)
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'');";
if ($smt = $mysqli->prepare($query)) {
    $smt->bind_param('issssssiiiis',$id_virtualtour,$name,$type_pano,$name_image,$name_video,$panorama_url,$panorama_json,$priority,$transition_time,$transition_fadeout,$transition_zoom,$transition_effect);
    $result = $smt->execute();
    if ($result) {
        $id_room = $mysqli->insert_id;
        if($type_pano=='image') {
            if($s3_enabled) {
                $path_image = "s3://$s3_bucket_name/viewer/panoramas/$name_image";
            } else {
                $path_image = dirname(__FILE__).'/../../viewer/panoramas/'.$name_image;
            }
            autodetect_panorama_image($path_image,$id_room);
        }
        generate_multires(false,$id_virtualtour);
        $mysqli->query("UPDATE svt_rooms SET type='video' WHERE panorama_video<>'';");
        update_user_space_storage($id_user,false);
        if(!empty($path_source)) {
            unlink($path_source);
        }
        set_user_log($id_user,'add_room',json_encode(array("id"=>$id_room,"name"=>$name)),date('Y-m-d H:i:s', time()));
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id"=>$id_room));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}

function autodetect_panorama_image($imagePath,$id_room) {
    global $mysqli;
    $size = getimagesize($imagePath);
    $width = $size[0];
    $height = $size[1];
    $aspectRatio = $width / $height;
    if ($aspectRatio >= 1.6 && $aspectRatio < 1.9) { //16:9
        $allow_pitch = 0;
        $allow_hfov = 0;
        $vaov = 36;
        $haov = 60;
        $min_yaw = -25;
        $max_yaw = 25;
        $hfov = 60;
        $h_pitch = 0;
        $h_roll = 0;
        $min_pitch = -90;
        $max_pitch = 90;
    } elseif ($aspectRatio >= 1 && $aspectRatio < 1.6) { //4:3
        $allow_pitch = 0;
        $allow_hfov = 0;
        $vaov = 36;
        $haov = 50;
        $min_yaw = -25;
        $max_yaw = 25;
        $hfov = 60;
        $h_pitch = 0;
        $h_roll = 0;
        $min_pitch = -90;
        $max_pitch = 90;
    } elseif ($aspectRatio >= 3 && $aspectRatio <= 5) { //180 horizontal
        $allow_pitch = 0;
        $allow_hfov = 0;
        $vaov = 60;
        $haov = 220;
        $min_yaw = -110;
        $max_yaw = 110;
        $hfov = 90;
        $h_pitch = 0;
        $h_roll = 0;
        $min_pitch = -90;
        $max_pitch = 90;
    } elseif ($aspectRatio > 5) { //360 horizontal
        $allow_pitch = 0;
        $allow_hfov = 0;
        $vaov = 60;
        $haov = 360;
        $min_yaw = -180;
        $max_yaw = 180;
        $hfov = 90;
        $h_pitch = 0;
        $h_roll = 0;
        $min_pitch = -90;
        $max_pitch = 90;
    } else {
        return;
    }
    $query = "UPDATE svt_rooms SET allow_pitch=?,allow_hfov=?,vaov=?,haov=?,min_yaw=?,max_yaw=?,hfov=?,h_pitch=?,h_roll=?,min_pitch=?,max_pitch=? WHERE id=?";
    if ($smt = $mysqli->prepare($query)) {
        $smt->bind_param('iiiiiiiiiiii',$allow_pitch,$allow_hfov,$vaov,$haov,$min_yaw,$max_yaw,$hfov,$h_pitch,$h_roll,$min_pitch,$max_pitch,$id_room);
        $smt->execute();
    }
}