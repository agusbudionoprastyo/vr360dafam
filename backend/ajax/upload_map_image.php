<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once(dirname(__FILE__).'/../functions.php');
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
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
session_write_close();
if(isset($_FILES) && !empty($_FILES['file']['name'])){
    $allowed_ext = array('png','jpg','jpeg');
    $filename = $_FILES['file']['name'];
    $ext = explode('.',$filename);
    $ext = strtolower(end($ext));
    if(in_array($ext,$allowed_ext)){
        $map = "map_".round(microtime(true) * 1000).".$ext";
        if($s3_enabled) {
            $path_dest = "s3://$s3_bucket_name/viewer/maps/".$map;
        } else {
            $path_dest = dirname(__FILE__).'/../../viewer/maps/'.$map;
        }
        $moved = move_uploaded_file($_FILES['file']['tmp_name'],$path_dest);
        if($moved) {
            if((strtolower($ext)=='jpg') || (strtolower($ext)=='jpeg')) {
                try {
                    $src_img = imagecreatefromjpeg($path_dest);
                    imageinterlace($src_img, true);
                    imagejpeg($src_img, $path_dest);
                } catch (Exception $e) {}
            } elseif (strtolower($ext)=='png') {
                try {
                    $src_img = imagecreatefrompng($path_dest);
                    imageinterlace($src_img, true);
                    imagealphablending($src_img, true);
                    imagesavealpha($src_img, true);
                    imagepng($src_img, $path_dest);
                } catch (Exception $e) {}
            }
            if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/maps/$map",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                    exit;
                }
            }
            ob_end_clean();
            if($s3_enabled) {
                echo $s3_url."viewer/maps/".$map;
            } else {
                echo "../viewer/maps/".$map;
            }
        } else {
            ob_end_clean();
            echo 'ERROR: code:'.$_FILES["file"]["error"];
        }
    }else{
        ob_end_clean();
        echo 'ERROR:'._("Only jpg,png files are supported.");
    }
}else{
    ob_end_clean();
    echo 'ERROR: '._("File not provided.");
}
exit;
