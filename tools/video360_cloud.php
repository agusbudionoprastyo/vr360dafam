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
$debug = false;
$path = realpath(dirname(__FILE__));
require_once(dirname(__FILE__).'/ImageResizeException.php');
require_once(dirname(__FILE__).'/ImageResize.php');
use \Gumlet\ImageResize;

if(!isEnabled('shell_exec')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"php \"shell_exec\" "._("function disabled")));
    exit;
}

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("php zip not enabled")));
    exit;
}

$command = 'dpkg-query -W -f=\'${Status}\' python3 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false) {
    $command = 'rpm -q python3 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'command -v python3 2>&1';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'python3') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"python3\". "._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"python3\". "._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
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

$command = 'command -v ffmpeg 2>&1';
$output = shell_exec($command);
if($output=="") {
    $path_ffmpeg = $path.DIRECTORY_SEPARATOR.'ffmpeg';
    if(file_exists($path_ffmpeg)) {
        try {
            shell_exec("chmod +x ".$path_ffmpeg);
        } catch (Exception $e) {}
        $command = $path_ffmpeg.' -v 2>&1';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'permission denied') !== false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Permission denied").". "._("Execute the command")." \"chmod +x ".$path.DIRECTORY_SEPARATOR."ffmpeg"."\" "._("on your server")."."));
            exit;
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"ffmpeg\". "._("Execute the command")." \"apt-get install ffmpeg\" "._("on your server")."."));
        exit;
    }
} else {
    $path_ffmpeg = trim($output);
}

if(isset($_GET['check'])) {
    ob_end_clean();
    echo "ok";
    exit;
}

if (!file_exists(dirname(__FILE__).'/video360_tmp/')) {
    mkdir(dirname(__FILE__).'/video360_tmp/', 0775);
}

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

if(isset($_POST['complete_video360'])) {
    if(file_exists(dirname(__FILE__).'/video360_tmp/'.$_POST['complete_video360'])) {
        unlink(dirname(__FILE__).'/video360_tmp/'.$_POST['complete_video360']);
        unlink(dirname(__FILE__).'/video360_tmp/'.str_replace('.mp4','.txt',$_POST['complete_video360']));
    }
    exit;
}

$id_virtualtour = $_POST['id_virtualtour'];
$resolution = $_POST['resolution'];
$audio = $_POST['audio'];
if(!empty($audio)) {
    $audio = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $audio);
    $audio = html_entity_decode($audio, ENT_COMPAT, 'UTF-8');
    $audio = str_replace('&#x', '\u', $audio);
}
$duration = $_POST['duration'];
$array_slides = json_decode($_POST['array_slides'],true);
$vt_name = $_POST['vt_name'];
$vt_author = $_POST['vt_author'];
if(empty($id_virtualtour) || empty($resolution) || empty($duration) || empty($array_slides)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing parameters")));
    exit;
}

if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." ".'POST: '.serialize($_POST).PHP_EOL,FILE_APPEND);
    file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." ".'FILE: '.serialize($_FILES).PHP_EOL,FILE_APPEND);
}

if(isset($_FILES) && !empty($_FILES['file']['name'])) {
    $moved = move_uploaded_file($_FILES['file']['tmp_name'],dirname(__FILE__).'/video360_tmp/'.$_FILES['file']['name']);
    if($moved) {
        $dir_name = basename($_FILES['file']['name'], ".zip");
        if (!file_exists(dirname(__FILE__).'/video360_tmp/'.$dir_name)) {
            mkdir(dirname(__FILE__).'/video360_tmp/'.$dir_name, 0775);
        }
        $zip = new ZipArchive;
        $res = $zip->open(dirname(__FILE__).'/video360_tmp/'.$_FILES['file']['name']);
        if ($res === TRUE) {
            $zip->extractTo(dirname(__FILE__).'/video360_tmp/'.$dir_name);
            $zip->close();
            unlink(dirname(__FILE__).'/video360_tmp/'.$_FILES['file']['name']);
            $list_file_path = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR."list_360video_$id_virtualtour.txt";
            $metadata_file_path = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR."metadata_360video_$id_virtualtour.txt";
            $description_file_path = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR."description_360video_$id_virtualtour.txt";
            if(file_exists($list_file_path)) {
                unlink($list_file_path);
            }
            if(file_exists($metadata_file_path)) {
                unlink($metadata_file_path);
            }
            if(file_exists($description_file_path)) {
                unlink($description_file_path);
            }
            $metadata_content = ";FFMETADATA1\ntitle=$vt_name\nartist=$vt_author";
            file_put_contents($metadata_file_path,$metadata_content,FILE_APPEND);
            $start_ms = 0;
            $end_ms = 0;
            foreach ($array_slides as $slide) {
                $room_name = $slide['name'];
                $duration_slide = $slide['duration'];
                $panorama_image = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$slide['panorama_image'];
                $panorama_tmp_image = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR."t_".$slide['panorama_image'];
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
                file_put_contents($description_file_path,$description_yt_content,FILE_APPEND);
                $start_ms = $start_ms + $duration_slide*1000;
            }
            $time = time();
            $output_file_name = "video360_".$time."_tmp.mp4";
            $output_file_name_m = "video360_".$time."_tmp_m.mp4";
            $output_file_name_f = "video360_".$time.".mp4";
            $description_file_path_new = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR."video360_$time.txt";
            rename($description_file_path,$description_file_path_new);
            $output_file_path = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$output_file_name;
            $output_file_path_m = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$output_file_name_m;
            $output_file_path_f = $path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$output_file_name_f;
            if(!empty($audio)) {
                $audio = "'".$path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$audio."'";
                $command = $path_ffmpeg." -f concat -safe 0 -i $list_file_path -i $audio -preset veryfast -sn -c:v libx264 -c:a aac -pix_fmt yuv420p -aspect 2:1 -s $resolution -r 30 -t $duration $output_file_path 2>&1";
            } else {
                $command = $path_ffmpeg." -f concat -safe 0 -i $list_file_path -preset veryfast -sn -c:v libx264 -pix_fmt yuv420p -aspect 2:1 -s $resolution -r 30 -t $duration $output_file_path 2>&1";
            }
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $output = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
            }
            if(file_exists($output_file_path)) {
                $command = $path_ffmpeg." -i $output_file_path -i $metadata_file_path -map_metadata 1 -codec copy $output_file_path_m 2>&1";
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
                }
                $output = shell_exec($command);
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
                }
                if(file_exists($output_file_path_m)) {
                    $command = $path_python." ".$path.DIRECTORY_SEPARATOR.'spatialmedia'.DIRECTORY_SEPARATOR." -i $output_file_path_m $output_file_path_f 2>&1";
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
                    }
                    $output = shell_exec($command);
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
                    }
                    if(file_exists($output_file_path_f)) {
                        $command = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name;
                        shell_exec($command);
                        ob_end_clean();
                        echo json_encode(array("status"=>"ok","file_name"=>$output_file_name_f));
                        exit;
                    } else {
                        $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name;
                        shell_exec($command_r);
                        ob_end_clean();
                        echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$output));
                        exit;
                    }
                } else {
                    copy($output_file_path,$output_file_path_f);
                    $command = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name;
                    shell_exec($command);
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok","file_name"=>$output_file_name_f));
                    exit;
                }
            } else {
                $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'video360_tmp'.DIRECTORY_SEPARATOR.$dir_name;
                shell_exec($command_r);
                ob_end_clean();
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$output));
                exit;
            }
        }
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing file")));
    exit;
}

function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
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
            file_put_contents(realpath(dirname(__FILE__))."/log_video360.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}