<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
set_time_limit(9999);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$debug = false;
$mysqli->query("SET session wait_timeout=3600");
if(isset($_GET['check_req'])) {
    $check_req = 1;
    if(isset($_GET['slideshow_cloud_url'])) {
        $slideshow_cloud_url = $_GET['slideshow_cloud_url'];
        $mysqli->query("UPDATE svt_settings SET slideshow_cloud_url='$slideshow_cloud_url';");
    }
} else {
    $check_req = 0;
}
$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
$slideshow_cloud_url = $settings['slideshow_cloud_url'];

if (!file_exists(dirname(__FILE__).'/export_tmp/')) {
    mkdir(dirname(__FILE__).'/export_tmp/', 0775);
}
if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
    $check_url = file_get_contents($slideshow_cloud_url."?check=1", false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
    if(empty($check_url)) {
        $check_url = curl_get_file_contents($slideshow_cloud_url."?check=1");
    }
} else {
    $check_url = curl_get_file_contents($slideshow_cloud_url."?check=1");
}
if($check_url!='ok') {
    try {
        $json = json_decode($check_url,true);
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$json['msg']));
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$check_url));
        exit;
    }
}

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("php zip not enabled")));
    exit;
}

if($check_req)  {
    ob_end_clean();
    echo json_encode(array("status"=>"ok","msg"=>_("All requirements are met.")));
    exit;
}

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

$path = realpath(dirname(__FILE__).'/..');
session_write_close();

if(!isset($argv[1])) {
    $id_virtualtour = (int)$_POST['id_virtualtour'];
    $params = json_encode($_POST);
    $params = str_replace("'","\'",$params);
    unset($_POST['id_virtualtour']);
    $gallery_params = json_encode($_POST);
    $gallery_params = str_replace("'","\'",$gallery_params);
    $mysqli->query("UPDATE svt_virtualtours SET gallery_params='$gallery_params' WHERE id=$id_virtualtour;");
    $job_id = 0;
    if (isEnabled('shell_exec')) {
        try {
            $command = 'command -v php 2>&1';
            $output = shell_exec($command);
            if(empty($output)) $output = PHP_BINARY;
            $path_php = trim($output);
            $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
            $path = realpath(dirname(__FILE__) . '/..');
            $result = $mysqli->query("INSERT INTO svt_job_queue(date_time,id_virtualtour,type,params) VALUES(NOW(),$id_virtualtour,'slideshow','$params');");
            if($result) {
                $job_id = $mysqli->insert_id;
            }
            $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_slideshow_cloud.php $job_id > /dev/null &";
            shell_exec($command);
            ob_end_clean();
            echo json_encode(array("status"=>"ok","background"=>1));
            exit;
        } catch (Exception $e) {
            if($job_id!=0) {
                $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
            }
        }
    } else {
        $params = $_POST;
        $width = $params['width'];
        $height = $params['height'];
        $slide_duration = $params['slide_duration'];
        $fade_duration = $params['fade_duration'];
        $zoom_rate = $params['zoom_rate'];
        $audio = $params['audio'];
        $watermark = $params['watermark'];
        $watermark_opacity = $params['watermark_opacity'];
    }
} else {
    $job_id = $argv[1];
    $result = $mysqli->query("SELECT params FROM svt_job_queue WHERE id=$job_id AND type='slideshow' LIMIT 1;");
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $params=$row['params'];
            $params=json_decode($params,true);
            $id_virtualtour = (int)$params['id_virtualtour'];
            $width = $params['width'];
            $height = $params['height'];
            $slide_duration = $params['slide_duration'];
            $fade_duration = $params['fade_duration'];
            $zoom_rate = $params['zoom_rate'];
            $audio = $params['audio'];
            $watermark = $params['watermark'];
            $watermark_opacity = $params['watermark_opacity'];
        }
    }
}

$s3Client = null;
$s3_params = check_s3_tour_enabled($id_virtualtour);
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

if($s3_enabled) {
    if (!file_exists(dirname(__FILE__).'/import_tmp/slideshow/')) {
        mkdir(dirname(__FILE__).'/import_tmp/slideshow/', 0775);
    }
}

$fps = 30;
$size = $width."x".$height;
$logo = "";
if($watermark!='none') {
    $virtual_tour = get_virtual_tour($id_virtualtour,$_SESSION['id_user']);
    $logo = $virtual_tour['logo'];
    if(empty($logo)) {
        $watermark="none";
    }
}
$array = array();
$zip_name = $id_virtualtour."_slideshow_".time().".zip";
$zip_path_file = dirname(__FILE__)."/export_tmp/$zip_name";
$query = "SELECT image FROM svt_gallery WHERE id_virtualtour=$id_virtualtour AND visible=1 ORDER BY priority;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        $zip = new ZipArchive();
        $zip->open($zip_path_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if($s3_enabled) {
                $image = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'slideshow'.DIRECTORY_SEPARATOR.$row['image'];
                try {
                    $s3Client->getObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'viewer/gallery/'.$row['image'],
                        'SaveAs' => $image
                    ));
                    array_push($array_tmp_files,$image);
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                    }
                }
            } else {
                $image = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$row['image'];
            }
            array_push($array,$row['image']);
            $zip->addFile($image, $row['image']);
        }
    } else {
        if($job_id!=0) {
            $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
        }
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Error: No gallery images")));
        exit;
    }
}
if($s3_enabled) {
    try {
        $s3Client->deleteObject(array(
            'Bucket' => $s3_bucket_name,
            'Key'    => 'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4'
        ));
    } catch (\Aws\S3\Exception\S3Exception $e) {
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
        }
    }
} else {
    $out = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4';
    if(file_exists($out)) {
        unlink($out);
    }
}

if(!empty($audio)) {
    if($s3_enabled) {
        $audio_file = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'slideshow'.DIRECTORY_SEPARATOR.$audio;
        try {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/content/'.$audio,
                'SaveAs' => $audio_file
            ));
            array_push($array_tmp_files,$audio);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
    } else {
        $audio_file = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$audio;
    }
    $zip->addFile($audio_file, $audio);
}

if($watermark!="none") {
    if($s3_enabled) {
        $logo_file = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'slideshow'.DIRECTORY_SEPARATOR.$logo;
        try {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/content/'.$logo,
                'SaveAs' => $logo_file
            ));
            array_push($array_tmp_files,$audio);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
    } else {
        $logo_file = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$logo;
    }
    $zip->addFile($logo_file, $logo);
}

$zip->close();

if($s3_enabled) {
    foreach ($array_tmp_files as $file) {
        unlink($file);
    }
}

$array_images = json_encode($array);

$cfile = new CURLFile($zip_path_file,'application/zip',$zip_name);
$post = array('file'=>$cfile,'id_virtualtour'=>$id_virtualtour,"width"=>$width,"height"=>$height,"slide_duration"=>$slide_duration,"fade_duration"=>$fade_duration,"zoom_rate"=>$zoom_rate,"fps"=>$fps,"audio"=>$audio,"watermark"=>$watermark,"watermark_opacity"=>$watermark_opacity,"logo"=>$logo,"array_images"=>$array_images);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$slideshow_cloud_url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$curl_result = curl_exec($ch);
curl_close ($ch);
$response = json_decode($curl_result,true);
unlink($zip_path_file);
if($response['status']=='ok') {
    $file_name = $response['file_name'];
    $file_url = stripFile($slideshow_cloud_url)."/slideshow_tmp/".$file_name;
    if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
        $file_slideshow = file_get_contents($file_url, false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
        if(empty($file_slideshow)) {
            $file_slideshow = curl_get_file_contents($file_url);
        }
    } else {
        $file_slideshow = curl_get_file_contents($file_url);
    }
    if($s3_enabled) {
        try {
            switch($s3_params['type']) {
                case 'digitalocean':
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4',
                        'Body'   => $file_slideshow,
                        'ACL'    => 'public-read'
                    ));
                    break;
                default:
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4',
                        'Body'   => $file_slideshow
                    ));
                    break;
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        $exist_slideshow = $s3Client->doesObjectExist($s3_bucket_name,'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4');
    } else {
        check_directory('/../viewer/gallery/');
        $dest_slideshow = $out;
        file_put_contents($dest_slideshow,$file_slideshow);
        $exist_slideshow = file_exists($dest_slideshow);
    }
    if($exist_slideshow) {
        $post = array('complete_slideshow'=>$file_name);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$slideshow_cloud_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_exec($ch);
    }
    if($job_id!=0) {
        $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok","msg"=>$id_virtualtour.'_slideshow.mp4'));
    exit;
} else {
    if($job_id!=0) {
        $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$response['msg']));
    exit;
}

function check_directory($path) {
    try {
        if (!file_exists(dirname(__FILE__).$path)) {
            mkdir(dirname(__FILE__).$path, 0775,true);
        }
    } catch (Exception $e) {}
    try {
        if (isEnabled('shell_exec')) {
            shell_exec("chmod 775 ".dirname(__FILE__).$path);
        }
    } catch (Exception $e) {}
}

function stripFile($in){
    $pieces = explode("/", $in);
    if(strpos(end($pieces), ".") !== false){
        array_pop($pieces);
    }elseif(end($pieces) !== ""){
        $pieces[] = "";
    }
    return implode("/", $pieces);
}

function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function fatal_handler() {
    global $debug,$date,$ip;
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;
    $error = error_get_last();
    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}