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
if(isset($_SESSION['library_type'])) {
    $library_type = $_SESSION['library_type'];
} else {
    $library_type = 'tour';
}
$s3_enabled = false;
$s3Client = null;
if($library_type=='tour') {
    $s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
}
session_write_close();
if(isset($_FILES) && !empty($_FILES['file']['name'])){
    $filename = $_FILES['file']['name'];
    $ext = explode('.',$filename);
    $ext = strtolower(end($ext));
    $milliseconds = round(microtime(true) * 1000);
    $name = "media_".$milliseconds.".$ext";
    if($s3_enabled) {
        $path_dest = "s3://$s3_bucket_name/viewer/media/".$name;
    } else {
        $path_dest = dirname(__FILE__).'/../../viewer/media/'.$name;
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
                    'Key' => "viewer/media/$name",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                exit;
            }
        }
        ob_end_clean();
        echo $name;
    } else {
        ob_end_clean();
        echo 'ERROR: code:'.$_FILES["file"]["error"];
    }
}else{
    ob_end_clean();
    echo 'ERROR: '._("File not provided.");
}
exit;
