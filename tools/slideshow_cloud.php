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

$command = "ruby ".$path.DIRECTORY_SEPARATOR.'slideshow.rb 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'permission denied') !== false) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Permission denied").". "._("Execute the command")." \"chmod +x ".$path.DIRECTORY_SEPARATOR."slideshow.rb"."\" "._("on your server")."."));
    exit;
}

$command = 'dpkg-query -W -f=\'${Status}\' ruby-fastimage 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false) {
    $command = 'rpm -q ruby-fastimage 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'gem list | grep -i \'fastimage\'';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'fastimage') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"ruby-fastimage\". "._("Execute the command")." \"apt-get install ruby-fastimage\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"ruby-fastimage\". "._("Execute the command")." \"apt-get install ruby-fastimage\" "._("on your server")."."));
        exit;
    }
}

if(isset($_GET['check'])) {
    ob_end_clean();
    echo "ok";
    exit;
}

if (!file_exists(dirname(__FILE__).'/slideshow_tmp/')) {
    mkdir(dirname(__FILE__).'/slideshow_tmp/', 0775);
}

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

if(isset($_POST['complete_slideshow'])) {
    if(file_exists(dirname(__FILE__).'/slideshow_tmp/'.$_POST['complete_slideshow'])) {
        unlink(dirname(__FILE__).'/slideshow_tmp/'.$_POST['complete_slideshow']);
    }
    exit;
}

$id_virtualtour = $_POST['id_virtualtour'];
$width = $_POST['width'];
$height = $_POST['height'];
$size = $width."x".$height;
$slide_duration = $_POST['slide_duration'];
$fade_duration = $_POST['fade_duration'];
$zoom_rate = $_POST['zoom_rate'];
$fps = $_POST['fps'];
$audio = $_POST['audio'];
if(!empty($audio)) {
    $audio = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $audio);
    $audio = html_entity_decode($audio, ENT_COMPAT, 'UTF-8');
    $audio = str_replace('&#x', '\u', $audio);
}
$watermark = $_POST['watermark'];
$logo = $_POST['logo'];
if(isset($_POST['watermark_opacity'])) {
    $watermark_opacity = $_POST['watermark_opacity'];
} else {
    $watermark_opacity = 1;
}
$array_images = json_decode($_POST['array_images'],true);

if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." ".'POST: '.serialize($_POST).PHP_EOL,FILE_APPEND);
    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." ".'FILE: '.serialize($_FILES).PHP_EOL,FILE_APPEND);
}

if(isset($_FILES) && !empty($_FILES['file']['name'])) {
    $moved = move_uploaded_file($_FILES['file']['tmp_name'], dirname(__FILE__) . '/slideshow_tmp/' . $_FILES['file']['name']);
    if ($moved) {
        $dir_name = basename($_FILES['file']['name'], ".zip");
        if (!file_exists(dirname(__FILE__) . '/slideshow_tmp/' . $dir_name)) {
            mkdir(dirname(__FILE__) . '/slideshow_tmp/' . $dir_name, 0775);
        }
        $zip = new ZipArchive;
        $res = $zip->open(dirname(__FILE__) . '/slideshow_tmp/' . $_FILES['file']['name']);
        if ($res === TRUE) {
            $zip->extractTo(dirname(__FILE__) . '/slideshow_tmp/' . $dir_name);
            $zip->close();
            unlink(dirname(__FILE__) . '/slideshow_tmp/' . $_FILES['file']['name']);
        }
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing file")));
    exit;
}

$input = "";
foreach ($array_images as $image) {
    $input .= dirname(__FILE__).'/slideshow_tmp/'.$dir_name.'/'.$image.' ';
}

$out_name = $id_virtualtour.'_slideshow_'.time().'.mp4';
$out = $path.DIRECTORY_SEPARATOR.'slideshow_tmp'.DIRECTORY_SEPARATOR.$out_name;
try {
    shell_exec("chmod +x ".$path.DIRECTORY_SEPARATOR.'ffmpeg');
} catch (Exception $e) {}
try {
    shell_exec("chmod +x ".$path.DIRECTORY_SEPARATOR.'slideshow.rb');
} catch (Exception $e) {}
if(!empty($audio)) {
    $audio = "--audio='".$path.DIRECTORY_SEPARATOR.'slideshow_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$audio."'";
} else {
    $audio = "";
}
$command = "ruby ".$path.DIRECTORY_SEPARATOR.'slideshow.rb --fps='.$fps.' '.$audio.' --size='.$size.' --slide-duration='.$slide_duration.' --fade-duration='.$fade_duration.' --zoom-rate='.$zoom_rate.' -y '.$input.' '.$out.' 2>&1';
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
}
$output = shell_exec($command);
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
}
if (strpos(strtolower($output), 'permission denied') !== false) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Permission denied").".\n"._("Execute the command")." \"chmod +x ".$path.DIRECTORY_SEPARATOR."slideshow.rb"."\" "._("on your server")."."));
    exit;
}
if (strpos(strtolower($output), 'fastimage') !== false) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing package")." \"ruby-fastimage\".\n"._("Execute the command")." \"apt-get install ruby-fastimage\" "._("on your server")."."));
    exit;
}
if(file_exists($out)) {
    if($watermark!="none") {
        $logo = $path.DIRECTORY_SEPARATOR.'slideshow_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$logo;
        $out_w = $path.DIRECTORY_SEPARATOR.'slideshow_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow_w.mp4';
        switch($watermark) {
            case 'bottom_left':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=30:H-h-30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'bottom_right':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=W-w-30:H-h-30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'top_left':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=30:30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'top_right':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=W-w-30:30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'center':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=(W-w)/2:(H-h)/2" -c:a copy '.$out_w.' 2>&1';
                break;
        }
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
        }
        $output = shell_exec($command);
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
        }
        if(file_exists($out_w)) {
            unlink($out);
            rename($out_w,$out);
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$output));
            exit;
        }
    }
    $command = "rm -R ".$path.DIRECTORY_SEPARATOR.'slideshow_tmp'.DIRECTORY_SEPARATOR.$dir_name;
    shell_exec($command);
    ob_end_clean();
    echo json_encode(array("status"=>"ok","file_name"=>$out_name));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$output));
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