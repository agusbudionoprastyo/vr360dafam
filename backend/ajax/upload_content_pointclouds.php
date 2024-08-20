<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once(dirname(__FILE__).'/../functions.php');

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    echo 'ERROR: '._("php zip not enabled");
    exit;
}

$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$s3Client = null;
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    if($s3Client==null) {
        $s3Client = init_s3_client_no_wrapper($s3_params);
        if($s3Client==null) {
            $s3_enabled = false;
        } else {
            $s3_enabled = true;
        }
    } else {
        $s3_enabled = true;
    }
}
session_write_close();
if(isset($_FILES) && !empty($_FILES['file']['name'])){
    $allowed_ext = array('zip');
    $filename = $_FILES['file']['name'];
    $ext = explode('.',$filename);
    $ext = strtolower(end($ext));
    if(in_array($ext,$allowed_ext)){
        $name = "content_".time();
        $temp_dest = dirname(__FILE__).'/../../services/import_tmp/'.$name.'.zip';
        if($s3_enabled) {
            $folder_unzip = dirname(__FILE__).'/../../services/import_tmp/'.$name.'/';
        } else {
            $folder_unzip = dirname(__FILE__).'/../../viewer/pointclouds/'.$name.'/';
        }
        $moved = move_uploaded_file($_FILES['file']['tmp_name'],$temp_dest);
        if($moved) {
            $zip = new ZipArchive;
            $res = $zip->open($temp_dest);
            if ($res === TRUE) {
                $zip->extractTo($folder_unzip);
                $zip->close();
                unlink($temp_dest);
            }
            $jsFiles = glob($folder_unzip . '*.js');
            $jsonFiles = glob($folder_unzip . '*.json');
            $files = array_merge($jsFiles, $jsonFiles);
            if (!empty($files)) {
                $firstFile = $files[0];
                $fileName = basename($firstFile);
            } else {
                ob_end_clean();
                echo 'ERROR: '._("No JS or JSON files found in the zip.");
                exit;
            }
            if($s3_enabled) {
                try {
                    $s3Client->uploadDirectory($folder_unzip,$s3_bucket_name,"viewer/pointclouds/$name/");
                    if($settings['aws_s3_type']=='digitalocean') {
                        $objects = $s3Client->listObjects([
                            'Bucket' => $s3_bucket_name,
                            'Prefix' => "viewer/pointclouds/$name/"
                        ])->get('Contents');
                        foreach ($objects as $object) {
                            $s3Client->putObjectAcl([
                                'Bucket' => $s3_bucket_name,
                                'Key' => $object['Key'],
                                'ACL' => 'public-read',
                            ]);
                        }
                    }
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                    exit;
                }
                try {
                    deleteDir($folder_unzip);
                    rmdir($folder_unzip);
                } catch (Exception $e) {}
            }
            ob_end_clean();
            echo "pointclouds/$name/$fileName";
        } else {
            ob_end_clean();
            echo 'ERROR: code:'.$_FILES["file"]["error"];
        }
    }else{
        ob_end_clean();
        echo 'ERROR: '._("Only zip files are supported.");
    }
}else{
    ob_end_clean();
    echo 'ERROR: '._("File not provided.");
}
exit;

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}