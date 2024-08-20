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
    if(isset($_GET['video_project_url'])) {
        $video_project_url = $_GET['video_project_url'];
        $mysqli->query("UPDATE svt_settings SET video_project_url='$video_project_url';");
    }
} else {
    $check_req = 0;
}
$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
$video_project_url = $settings['video_project_url'];
if (!file_exists(dirname(__FILE__).'/export_tmp/')) {
    mkdir(dirname(__FILE__).'/export_tmp/', 0775);
}
if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
    $check_url = file_get_contents($video_project_url."?check=1", false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
    if(empty($check_url)) {
        $check_url = curl_get_file_contents($video_project_url."?check=1");
    }
} else {
    $check_url = curl_get_file_contents($video_project_url."?check=1");
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
    $id_virtual_tour = (int)$_POST['id_virtualtour'];
    $id_video_project = (int)$_POST['id_video'];
    $job_id = 0;
    if (isEnabled('shell_exec')) {
        try {
            $command = 'command -v php 2>&1';
            $output = shell_exec($command);
            if(empty($output)) $output = PHP_BINARY;
            $path_php = trim($output);
            $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
            $result = $mysqli->query("INSERT INTO svt_job_queue(date_time,id_virtualtour,id_project,type) VALUES(NOW(),$id_virtual_tour,$id_video_project,'video');");
            if($result) {
                $job_id = $mysqli->insert_id;
            }
            $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_video_cloud.php $job_id > /dev/null &";
            shell_exec($command);
            ob_end_clean();
            echo json_encode(array("status"=>"ok","background"=>1));
            exit;
        } catch (Exception $e) {
            if($job_id!=0) {
                $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
            }
        }
    }
} else {
    $job_id = $argv[1];
    $result = $mysqli->query("SELECT id_virtualtour,id_project,params FROM svt_job_queue WHERE id=$job_id AND type='video' LIMIT 1;");
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_virtual_tour = $row['id_virtualtour'];
            $id_video_project = $row['id_project'];
        }
    }
}

$s3Client = null;
$s3_params = check_s3_tour_enabled($id_virtual_tour);
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

$path_panorama = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR;
$path_panorama2 = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR;
$path_content = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR;
$path_assets = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_virtual_tour.DIRECTORY_SEPARATOR;
$path_font = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR;

$time = time();
if($s3_enabled) {
    if(!file_exists($path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR)) {
        mkdir($path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR,0755,true);
    }
}

$array = array();
$zip_name = $id_virtual_tour."_video_".time().".zip";
$zip_path_file = dirname(__FILE__)."/export_tmp/$zip_name";

$query = "SELECT * FROM svt_video_project_slides WHERE id_video_project=$id_video_project AND enabled=1 ORDER BY priority ASC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        $zip = new ZipArchive();
        $zip->open($zip_path_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            switch($row['type']) {
                case 'panorama':
                    $query_r = "SELECT panorama_image FROM svt_rooms WHERE id=".$row['id_room']." LIMIT 1;";
                    $result_r = $mysqli->query($query_r);
                    if($result_r) {
                        if ($result_r->num_rows==1) {
                            $row_r=$result_r->fetch_array(MYSQLI_ASSOC);
                            $row['file']=$row_r['panorama_image'];
                            if($s3_enabled) {
                                $exist_original = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/original/'.$row['file']);
                                $panorama = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$row['file'];
                                if($exist_original) {
                                    try {
                                        $s3Client->getObject(array(
                                            'Bucket' => $s3_bucket_name,
                                            'Key'    => 'viewer/panoramas/original/'.$row['file'],
                                            'SaveAs' => $panorama
                                        ));
                                    } catch (\Aws\S3\Exception\S3Exception $e) {
                                        if($debug) {
                                            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                                        }
                                    }
                                } else {
                                    try {
                                        $s3Client->getObject(array(
                                            'Bucket' => $s3_bucket_name,
                                            'Key'    => 'viewer/panoramas/'.$row['file'],
                                            'SaveAs' => $panorama
                                        ));
                                    } catch (\Aws\S3\Exception\S3Exception $e) {
                                        if($debug) {
                                            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                                        }
                                    }
                                }
                            } else {
                                $panorama = $path_panorama.$row['file'];
                                if(!file_exists($panorama)) {
                                    $panorama = $path_panorama2.$row['file'];
                                }
                            }
                            $zip->addFile($panorama, $row['file']);
                        }
                    }
                    break;
                default:
                    if(!empty($row['file'])) {
                        if($s3_enabled) {
                            $path_file = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$row['file'];
                            try {
                                $s3Client->getObject(array(
                                    'Bucket' => $s3_bucket_name,
                                    'Key'    => 'video/assets/'.$id_virtual_tour.'/'.$row['file'],
                                    'SaveAs' => $path_file
                                ));
                            } catch (\Aws\S3\Exception\S3Exception $e) {
                                if($debug) {
                                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                                }
                            }
                        } else {
                            $path_file = $path_assets.$row['file'];
                        }
                        $zip->addFile($path_file, $row['file']);
                    }
                    break;
            }
            if(!empty($row['font'])) {
                $zip->addFile($path_font.$row['font'], $row['font']);
            }
            if(!empty($row['params'])) {
                $row['params']=json_decode($row['params'],true);
            }
            array_push($array,$row);
        }
    } else {
        if($job_id!=0) {
            $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
        }
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Error: No video slides")));
        exit;
    }
}

$output_video = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$id_virtual_tour."_".$id_video_project.".mp4";
if($s3_enabled) {
    if($s3_enabled) {
        try {
            $s3Client->deleteObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'video/'.$id_virtual_tour.'_'.$id_video_project.'.mp4'
            ));
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
    }
} else {
    if(file_exists($output_video)) {
        unlink($output_video);
    }
}

$vt_logo = "";
$query_w = "SELECT logo FROM svt_virtualtours WHERE id=$id_virtual_tour LIMIT 1;";
$result_w = $mysqli->query($query_w);
if($result_w) {
    if ($result_w->num_rows == 1) {
        $row_w = $result_w->fetch_array(MYSQLI_ASSOC);
        if(!empty($row_w['logo'])) {
            $vt_logo=$row_w['logo'];
        }
    }
}

$query = "SELECT * FROM svt_video_projects WHERE id=$id_video_project LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $resolution_w=$row['resolution_w'];
        $resolution_h=$row['resolution_h'];
        $fade_duration=$row['fade'];
        $watermark=$row['watermark_pos'];
        $watermark_opacity=$row['watermark_opacity'];
        $watermark_logo=$row['watermark_logo'];
        $fps=$row['fps'];
        if(empty($watermark_logo)) {
            if(!empty($vt_logo)) {
                if($s3_enabled) {
                    $watermark_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$vt_logo;
                    try {
                        $s3Client->getObject(array(
                            'Bucket' => $s3_bucket_name,
                            'Key'    => 'viewer/content/'.$vt_logo,
                            'SaveAs' => $watermark_path
                        ));
                    } catch (\Aws\S3\Exception\S3Exception $e) {
                        if($debug) {
                            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                        }
                    }
                } else {
                    $watermark_path=$path_content.$vt_logo;
                }
                $zip->addFile($watermark_path, $vt_logo);
            } else {
                $watermark='none';
            }
        } else {
            if($s3_enabled) {
                $watermark_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$watermark_logo;
                try {
                    $s3Client->getObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video/assets/'.$id_virtual_tour.'/'.$watermark_logo,
                        'SaveAs' => $watermark_path
                    ));
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                    }
                }
            } else {
                $watermark_path=$path_assets.$watermark_logo;
            }
            $zip->addFile($watermark_path, $watermark_logo);
        }
        $audio=$row['audio'];
        if(!empty($audio)) {
            $audio = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $audio);
            $audio = html_entity_decode($audio, ENT_COMPAT, 'UTF-8');
            $audio = str_replace('&#x', '\u', $audio);
            if($s3_enabled) {
                $audio_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$audio;
                try {
                    $s3Client->getObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'viewer/content/'.$audio,
                        'SaveAs' => $audio_path
                    ));
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                    }
                }
            } else {
                $audio_path = $path_content.$audio;
            }
            $zip->addFile($audio_path, $audio);
        }
    } else {
        if($job_id!=0) {
            $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
        }
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Error: No video project")));
        exit;
    }
}

$zip->close();

if($s3_enabled) {
    $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR;
    shell_exec($command_r);
}

$array_slides = json_encode($array);

$cfile = new CURLFile($zip_path_file,'application/zip',$zip_name);
$post = array('file'=>$cfile,'id_virtualtour'=>$id_virtual_tour,"resolution_w"=>$resolution_w,"resolution_h"=>$resolution_h,"fade_duration"=>$fade_duration,"watermark"=>$watermark,"watermark_opacity"=>$watermark_opacity,"fps"=>$fps,"audio"=>$audio,"vt_logo"=>$vt_logo,"watermark_logo"=>$watermark_logo,"array_slides"=>$array_slides);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$video_project_url);
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
    $file_url = stripFile($video_project_url)."/video_tmp/".$file_name;
    if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
        $file_video = file_get_contents($file_url, false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
        if(empty($file_video)) {
            $file_video = curl_get_file_contents($file_url);
        }
    } else {
        $file_video = curl_get_file_contents($file_url);
    }
    if($s3_enabled) {
        try {
            switch($s3_params['type']) {
                case 'digitalocean':
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video/'.$id_virtual_tour."_".$id_video_project.".mp4",
                        'Body'   => $file_video,
                        'ACL'    => 'public-read'
                    ));
                    break;
                default:
                    $s3Client->putObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video/'.$id_virtual_tour."_".$id_video_project.".mp4",
                        'Body'   => $file_video
                    ));
                    break;
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        $exist_video = $s3Client->doesObjectExist($s3_bucket_name,'video/'.$id_virtual_tour."_".$id_video_project.".mp4");
    } else {
        check_directory('/../video/');
        $dest_video = $output_video;
        file_put_contents($dest_video,$file_video);
        $exist_video = file_exists($dest_video);
    }
    if($exist_video) {
        $post = array('complete_video'=>$file_name);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$video_project_url);
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
    $mysqli->query("UPDATE svt_video_projects SET date_time=NOW() WHERE id_virtualtour=$id_virtual_tour AND id=$id_video_project;");
    ob_end_clean();
    echo json_encode(array("status"=>"ok","msg"=>$id_virtual_tour.'_'.$id_video_project.'.mp4'));
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
            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}