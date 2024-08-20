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
require_once(dirname(__FILE__).'/ImageResizeException.php');
require_once(dirname(__FILE__).'/ImageResize.php');
use \Gumlet\ImageResize;
$debug = false;
if(isset($_GET['check_req'])) {
    $check_req = 1;
} else {
    $check_req = 0;
}
$time = time();
$path = realpath(dirname(__FILE__).'/..');
$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
if(!isEnabled('shell_exec')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"php \"shell_exec\" "._("function disabled")));
    exit;
}
$command = 'dpkg-query -W -f=\'${Status}\' python3 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false || strpos(strtolower($output), 'commande introuvable') !== false) {
    $command = 'rpm -q python3 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'command -v python3 2>&1';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'python3') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"python3\".\n"._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"python3\".\n"._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
        exit;
    }
}
$command = 'command -v python3 2>&1';
$output = shell_exec($command);
if($output=="") {
    $command = 'command -v python 2>&1';
    $output = shell_exec($command);
    if($output=="") {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing command")." python3."));
        exit;
    } else {
        $path_python = trim($output);
    }
} else {
    $path_python = trim($output);
}

try {
    shell_exec("chmod +x ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'ffmpeg');
} catch (Exception $e) {}

$command = 'command -v ffmpeg 2>&1';
$output = shell_exec($command);
if($output=="") {
    $path_ffmpeg = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'ffmpeg';
    $command = $path_ffmpeg.' -v 2>&1';
    $output = shell_exec($command);
    if (strpos(strtolower($output), 'permission denied') !== false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Permission denied").". "._("Execute the command")." \"chmod +x ".$path.DIRECTORY_SEPARATOR."ffmpeg"."\" "._("on your server")."."));
        exit;
    }
} else {
    $path_ffmpeg = trim($output);
}

if($check_req) {
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
    try {
        $command = 'command -v php 2>&1';
        $output = shell_exec($command);
        if(empty($output)) $output = PHP_BINARY;
        $path_php = trim($output);
        $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
        $result = $mysqli->query("INSERT INTO svt_job_queue(date_time,id_virtualtour,type,params) VALUES(NOW(),$id_virtualtour,'360_video','$params');");
        if($result) {
            $job_id = $mysqli->insert_id;
        }
        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_360video.php $job_id > /dev/null &";
        shell_exec($command);
        echo json_encode(array("status"=>"ok","background"=>1));
        exit;
    } catch (Exception $e) {
        if($job_id!=0) {
            $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
        }
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
            if(!empty($audio)) {
                $audio = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $audio);
                $audio = html_entity_decode($audio, ENT_COMPAT, 'UTF-8');
                $audio = str_replace('&#x', '\u', $audio);
            }
            $duration = $params['duration'];
            $array_slides = $params['array_slides'];
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
check_directory("/export_tmp/");
$list_file_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'export_tmp'.DIRECTORY_SEPARATOR.$time."_list_360video_$id_virtualtour.txt";
$metadata_file_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'export_tmp'.DIRECTORY_SEPARATOR.$time."_metadata_360video_$id_virtualtour.txt";
if(file_exists($list_file_path)) {
    unlink($list_file_path);
}
if(file_exists($metadata_file_path)) {
    unlink($metadata_file_path);
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
$metadata_content = ";FFMETADATA1\ntitle=$vt_name\nartist=$vt_author";
file_put_contents($metadata_file_path,$metadata_content,FILE_APPEND);
$start_ms = 0;
$end_ms = 0;
$time = time();
check_directory('/../video360/');
check_directory('/../video360/'.$id_virtualtour.'/');
check_directory('/../video360/'.$id_virtualtour.'/tmp/');
check_directory('/../video360/'.$id_virtualtour.'/tmp/'.$time);
$output_file_name = "video360_".$time."_tmp.mp4";
$output_file_name_m = "video360_".$time."_tmp_m.mp4";
$output_file_name_f = "video360_".$time.".mp4";
$output_file_name_dc = "video360_".$time.".txt";
$output_file_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'export_tmp'.DIRECTORY_SEPARATOR.$output_file_name;
$output_file_path_m = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'export_tmp'.DIRECTORY_SEPARATOR.$output_file_name_m;
$output_file_path_f = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.$output_file_name_f;
$output_file_path_dc = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.$output_file_name_dc;
foreach ($array_slides as $slide) {
    $room_name = $slide['name'];
    $duration_slide = $slide['duration'];
    if($s3_enabled) {
        $exist_original = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/original/'.$slide['panorama_image']);
        $panorama_image = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$slide['panorama_image'];
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
    $panorama_tmp_image = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$slide['panorama_image'];
    $resolution_split = explode("x",$resolution);
    try {
        $image = new ImageResize($panorama_image);
        $image->quality_jpg = 90;
        $image->interlace = 1;
        $image->resize($resolution_split[0],$resolution_split[1],true);
        $image->gamma(false);
        $image->save($panorama_tmp_image);
    } catch (ImageResizeException $e) {}
    if(file_exists($panorama_tmp_image)) {
        $panorama_image = $panorama_tmp_image;
    }
    $file_content = "file '$panorama_image'\noutpoint $duration_slide\n";
    file_put_contents($list_file_path,$file_content,FILE_APPEND);
    $end_ms = $start_ms + ($duration_slide*1000)-1;
    $metadata_content = "\n\n[CHAPTER]\nTIMEBASE=1/1000\nSTART=$start_ms\nEND=$end_ms\ntitle=$room_name";
    $description_yt_content = convertTo_time($start_ms)." $room_name\n";
    file_put_contents($metadata_file_path,$metadata_content,FILE_APPEND);
    file_put_contents($output_file_path_dc,$description_yt_content,FILE_APPEND);
    $start_ms = $start_ms + $duration_slide*1000;
}
if(!empty($audio)) {
    if($s3_enabled) {
        $path_audio = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$audio;
        try {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/content/'.$audio,
                'SaveAs' => $path_audio
            ));
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        $audio = "'".$path_audio."'";
    } else {
        $audio = "'".$path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$audio."'";
    }
    $command = $path_ffmpeg." -f concat -safe 0 -i $list_file_path -i $audio -preset veryfast -sn -c:v libx264 -c:a aac -pix_fmt yuv420p -aspect 2:1 -s $resolution -r 30 -t $duration $output_file_path 2>&1";
} else {
    $command = $path_ffmpeg." -f concat -safe 0 -i $list_file_path -preset veryfast -sn -c:v libx264 -pix_fmt yuv420p -aspect 2:1 -s $resolution -r 30 -t $duration $output_file_path 2>&1";
}
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
}
$output = shell_exec($command);
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
}
if(file_exists($output_file_path)) {
    $command = $path_ffmpeg." -i $output_file_path -i $metadata_file_path -map_metadata 1 -codec copy $output_file_path_m 2>&1";
    if($debug) {
        file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
    }
    $output = shell_exec($command);
    if($debug) {
        file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
    }
    if(file_exists($output_file_path_m)) {
        $command = $path_python." ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'spatialmedia'.DIRECTORY_SEPARATOR." -i $output_file_path_m $output_file_path_f 2>&1";
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
        }
        $output = shell_exec($command);
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
        }
        if(file_exists($output_file_path_f)) {
            unlink($list_file_path);
            unlink($metadata_file_path);
            unlink($output_file_path);
            unlink($output_file_path_m);
            $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time;
            shell_exec($command_r);
            if($s3_enabled) {
                switch($s3_params['type']) {
                    case 'digitalocean':
                        $s3Client->putObject(array(
                            'Bucket' => $s3_bucket_name,
                            'Key'    => 'video360/'.$id_virtualtour.'/',
                            'Body'   => "",
                            'ACL'    => 'public-read'
                        ));
                        $s3Client->putObject(array(
                            'Bucket'     => $s3_bucket_name,
                            'SourceFile' => $output_file_path_f,
                            'Key'        => 'video360/'.$id_virtualtour.'/'.$output_file_name_f,
                            'ACL'        => 'public-read'
                        ));
                        $s3Client->putObject(array(
                            'Bucket'     => $s3_bucket_name,
                            'SourceFile' => $output_file_path_dc,
                            'Key'        => 'video360/'.$id_virtualtour.'/'.$output_file_name_dc,
                            'ACL'        => 'public-read'
                        ));
                        break;
                    default:
                        $s3Client->putObject(array(
                            'Bucket' => $s3_bucket_name,
                            'Key'    => 'video360/'.$id_virtualtour.'/',
                            'Body'   => ""
                        ));
                        $s3Client->putObject(array(
                            'Bucket'     => $s3_bucket_name,
                            'SourceFile' => $output_file_path_f,
                            'Key'        => 'video360/'.$id_virtualtour.'/'.$output_file_name_f
                        ));
                        $s3Client->putObject(array(
                            'Bucket'     => $s3_bucket_name,
                            'SourceFile' => $output_file_path_dc,
                            'Key'        => 'video360/'.$id_virtualtour.'/'.$output_file_name_dc
                        ));
                        break;
                }
                unlink($output_file_path_f);
                unlink($output_file_path_dc);
            }
            if($job_id!=0) {
                $mysqli->close();
                $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
                if (mysqli_connect_errno()) {
                    echo mysqli_connect_error();
                    exit();
                }
                $mysqli->query("SET NAMES 'utf8';");
                $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
            }
            ob_end_clean();
            echo json_encode(array("status"=>"ok","background"=>0,"msg"=>$output_file_name_f));
            exit;
        } else {
            unlink($list_file_path);
            unlink($metadata_file_path);
            unlink($output_file_path);
            unlink($output_file_path_m);
            $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time;
            shell_exec($command_r);
            if($job_id!=0) {
                $mysqli->close();
                $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
                if (mysqli_connect_errno()) {
                    echo mysqli_connect_error();
                    exit();
                }
                $mysqli->query("SET NAMES 'utf8';");
                $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
            }
            ob_end_clean();
            echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$output));
            exit;
        }
    } else {
        copy($output_file_path,$output_file_path_f);
        unlink($list_file_path);
        unlink($metadata_file_path);
        unlink($output_file_path);
        unlink($output_file_path_m);
        $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time;
        shell_exec($command_r);
        if($job_id!=0) {
            $mysqli->close();
            $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
            if (mysqli_connect_errno()) {
                echo mysqli_connect_error();
                exit();
            }
            $mysqli->query("SET NAMES 'utf8';");
            $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok","background"=>0,"msg"=>$output_file_name_f));
        exit;
    }
} else {
    unlink($list_file_path);
    $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$time;
    shell_exec($command_r);
    if($job_id!=0) {
        $mysqli->close();
        $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        if (mysqli_connect_errno()) {
            echo mysqli_connect_error();
            exit();
        }
        $mysqli->query("SET NAMES 'utf8';");
        $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$output));
    exit;
}

function check_directory($path) {
    try {
        if (!file_exists(dirname(__FILE__).$path)) {
            mkdir(dirname(__FILE__).$path, 0775,true);
        }
    } catch (Exception $e) {}
    try {
        shell_exec("chmod 775 ".dirname(__FILE__).$path);
    } catch (Exception $e) {}
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
            file_put_contents(realpath(dirname(__FILE__))."/log_360video.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}