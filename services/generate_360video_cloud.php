<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
ob_start();
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
    if(isset($_GET['video360_cloud_url'])) {
        $video360_cloud_url = $_GET['video360_cloud_url'];
        $mysqli->query("UPDATE svt_settings SET video360_cloud_url='$video360_cloud_url';");
    }
} else {
    $check_req = 0;
}
$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
$path = realpath(dirname(__FILE__) . '/..');
$video360_cloud_url = $settings['video360_cloud_url'];

if (!file_exists(dirname(__FILE__).'/export_tmp/')) {
    mkdir(dirname(__FILE__).'/export_tmp/', 0775);
}
if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
    $check_url = file_get_contents($video360_cloud_url."?check=1", false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
    if(empty($check_url)) {
        $check_url = curl_get_file_contents($video360_cloud_url."?check=1");
    }
} else {
    $check_url = curl_get_file_contents($video360_cloud_url."?check=1");
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

if(!isset($argv[1])) {
    $id_virtualtour = (int)$_POST['id_virtualtour'];
    $params = json_encode($_POST);
    $params = str_replace("'","\'",$params);
    $job_id = 0;
    if (isEnabled('shell_exec')) {
        try {
            $command = 'command -v php 2>&1';
            $output = shell_exec($command);
            if(empty($output)) $output = PHP_BINARY;
            $path_php = trim($output);
            $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
            $path = realpath(dirname(__FILE__) . '/..');
            $result = $mysqli->query("INSERT INTO svt_job_queue(date_time,id_virtualtour,type,params) VALUES(NOW(),$id_virtualtour,'360_video','$params');");
            if($result) {
                $job_id = $mysqli->insert_id;
            }
            $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_360video_cloud.php $job_id > /dev/null &";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."RESULT: ".$result.PHP_EOL,FILE_APPEND);
            }
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
        $resolution = $params['resolution'];
        $audio = $params['audio'];
        $duration = $params['duration'];
        $array_slides = $params['array_slides'];
    }
} else {
    $job_id = $argv[1];
    $result = $mysqli->query("SELECT params FROM svt_job_queue WHERE id=$job_id AND type='360_video' LIMIT 1;");
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $params=$row['params'];
            $params=json_decode($params,true);
            $id_virtualtour = (int)$params['id_virtualtour'];
            $resolution = $params['resolution'];
            $audio = $params['audio'];
            $duration = $params['duration'];
            $array_slides = $params['array_slides'];
        }
    }
}

$vt_name = "";
$vt_author = "";
$query = "SELECT name,author FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $vt_name = $row['name'];
        $vt_author = $row['author'];
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

$zip = new ZipArchive();
$zip_name = $id_virtualtour."_360video_".time().".zip";
$zip_path_file = dirname(__FILE__)."/export_tmp/$zip_name";
$zip->open($zip_path_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$array_tmp_files = array();
foreach ($array_slides as $key => $slide) {
    if($s3_enabled) {
        $exist_original = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/original/'.$slide['panorama_image']);
        $panorama_image = $path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR.$slide['panorama_image'];
        if($exist_original) {
            try {
                $s3Client->getObject(array(
                    'Bucket' => $s3_bucket_name,
                    'Key'    => 'viewer/panoramas/original/'.$slide['panorama_image'],
                    'SaveAs' => $panorama_image
                ));
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                }
            }
        } else {
            try {
                $s3Client->getObject(array(
                    'Bucket' => $s3_bucket_name,
                    'Key'    => 'viewer/panoramas/'.$slide['panorama_image'],
                    'SaveAs' => $panorama_image
                ));
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                }
            }
        }
    } else {
        if(file_exists($path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$slide['panorama_image'])) {
            $panorama_image = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$slide['panorama_image'];
        } else {
            $panorama_image = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.$slide['panorama_image'];
        }
    }
    $zip->addFile($panorama_image, $slide['panorama_image']);
    if($s3_enabled) {
        array_push($array_tmp_files,$panorama_image);
    }
}
if($audio!="") {
    if($s3_enabled) {
        $audio_file = $path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR.$audio;
        try {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/content/'.$audio,
                'SaveAs' => $audio_file
            ));
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
    } else {
        $audio_file = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$audio;
    }
    $zip->addFile($audio_file, $audio);
    if($s3_enabled) {
        array_push($array_tmp_files,$audio_file);
    }
}
$zip->close();

if($s3_enabled) {
    foreach ($array_tmp_files as $file) {
        unlink($file);
    }
}

$array_slides = json_encode($array_slides);

$cfile = new CURLFile($zip_path_file,'application/zip',$zip_name);
$post = array('file'=>$cfile,'id_virtualtour'=>$id_virtualtour,"resolution"=>$resolution,"audio"=>$audio,"duration"=>$duration,"array_slides"=>$array_slides,"vt_name"=>$vt_name,"vt_author"=>$vt_author);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$video360_cloud_url);
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
    $file_url = stripFile($video360_cloud_url)."/video360_tmp/".$file_name;
    if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
        $file_video360 = file_get_contents($file_url, false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
        if(empty($file_video360)) {
            $file_video360 = curl_get_file_contents($file_url);
        }
    } else {
        $file_video360 = curl_get_file_contents($file_url);
    }
    $file_url_txt = stripFile($video360_cloud_url)."/video360_tmp/".str_replace('.mp4','.txt',$file_name);
    if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
        $file_video360_txt = file_get_contents($file_url_txt, false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
        if(empty($file_video360_txt)) {
            $file_video360_txt = curl_get_file_contents($file_url_txt);
        }
    } else {
        $file_video360_txt = curl_get_file_contents($file_url_txt);
    }
    if($s3_enabled) {
        try {
            switch($s3_params['type']) {
                case 'digitalocean':
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video360/'.$id_virtualtour.'/',
                        'Body'   => "",
                        'ACL'    => 'public-read'
                    ));
                    break;
                default:
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video360/'.$id_virtualtour.'/',
                        'Body'   => ""
                    ));
                    break;
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        try {
            switch($s3_params['type']) {
                case 'digitalocean':
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video360/'.$id_virtualtour.'/'.$file_name,
                        'Body'   => $file_video360,
                        'ACL'    => 'public-read'
                    ));
                    break;
                default:
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video360/'.$id_virtualtour.'/'.$file_name,
                        'Body'   => $file_video360
                    ));
                    break;
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        try {
            switch($s3_params['type']) {
                case 'digitalocean':
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video360/'.$id_virtualtour.'/'.str_replace('.mp4','.txt',$file_name),
                        'Body'   => $file_video360_txt,
                        'ACL'    => 'public-read'
                    ));
                    break;
                default:
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video360/'.$id_virtualtour.'/'.str_replace('.mp4','.txt',$file_name),
                        'Body'   => $file_video360_txt
                    ));
                    break;
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        $exist_360 = $s3Client->doesObjectExist($s3_bucket_name,'video360/'.$id_virtualtour.'/'.$file_name);
    } else {
        check_directory('/../video360/');
        check_directory('/../video360/'.$id_virtualtour.'/');
        $dest_video360 = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.$file_name;
        file_put_contents($dest_video360,$file_video360);
        $dest_video360_txt = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.str_replace('.mp4','.txt',$file_name);
        file_put_contents($dest_video360_txt,$file_video360_txt);
        $exist_360 = file_exists($dest_video360);
    }
    if($exist_360) {
        $post = array('complete_video360'=>$file_name);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$video360_cloud_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $curl_result = curl_exec($ch);
        if($job_id!=0) {
            $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
        exit;
    }
} else {
    if($job_id!=0) {
        $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$response['msg']));
    exit;
}

if($job_id!=0) {
    $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
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

function convertTo_time($millisec) {
    $secs = $millisec / 1000;
    $hours   = ($secs / 3600);
    $minutes = (($secs / 60) % 60);
    $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
    $seconds = $secs % 60;
    $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
    if ($hours > 1) {
        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
    } else {
        $hours = '00';
    }
    $Time = "$hours:$minutes:$seconds";
    return $Time;
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
            file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}