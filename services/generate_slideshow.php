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
if(isset($_GET['check_req'])) {
    $check_req = 1;
} else {
    $check_req = 0;
}
$path = realpath(dirname(__FILE__).'/..');
$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
if(!isEnabled('shell_exec')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"php \"shell_exec\" "._("function disabled")));
    exit;
}
$array = array();
$input = "";
try {
    shell_exec("chmod +x ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'ffmpeg');
} catch (Exception $e) {}
try {
    shell_exec("chmod +x ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'slideshow.rb');
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

$command = "ruby ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'slideshow.rb 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'permission denied') !== false) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Permission denied").". "._("Execute the command")." \"chmod +x ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."slideshow.rb"."\" "._("on your server")."."));
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
    $id_user = $_SESSION['id_user'];
    $_POST['id_user']=$id_user;
    $params = json_encode($_POST);
    $params = str_replace("'","\'",$params);
    unset($_POST['id_virtualtour']);
    $gallery_params = json_encode($_POST);
    $gallery_params = str_replace("'","\'",$gallery_params);
    $mysqli->query("UPDATE svt_virtualtours SET gallery_params='$gallery_params' WHERE id=$id_virtualtour;");
    $job_id = 0;
    try {
        $command = 'command -v php 2>&1';
        $output = shell_exec($command);
        if(empty($output)) $output = PHP_BINARY;
        $path_php = trim($output);
        $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
        $result = $mysqli->query("INSERT INTO svt_job_queue(date_time,id_virtualtour,type,params) VALUES(NOW(),$id_virtualtour,'slideshow','$params');");
        if($result) {
            $job_id = $mysqli->insert_id;
        }
        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_slideshow.php $job_id > /dev/null &";
        shell_exec($command);
        echo json_encode(array("status"=>"ok","background"=>1));
        exit;
    } catch (Exception $e) {
        if($job_id!=0) {
            delete_job($job_id);
        }
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
            $id_user = $params['id_user'];
            $width = $params['width'];
            $height = $params['height'];
            $slide_duration = $params['slide_duration'];
            $fade_duration = $params['fade_duration'];
            $zoom_rate = $params['zoom_rate'];
            $audio = $params['audio'];
            if(!empty($audio)) {
                $audio = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $audio);
                $audio = html_entity_decode($audio, ENT_COMPAT, 'UTF-8');
                $audio = str_replace('&#x', '\u', $audio);
            }
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
$fps = 30;
$size = $width."x".$height;
$logo = "";
if($watermark!='none') {
    $virtual_tour = get_virtual_tour($id_virtualtour,$id_user);
    $logo = $virtual_tour['logo'];
    if(empty($logo)) {
        $watermark="none";
    }
}
$array_tmp_files = array();
$query = "SELECT image FROM svt_gallery WHERE id_virtualtour=$id_virtualtour AND visible=1 ORDER BY priority;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
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
            $input .= "$image ";
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
$out = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4';
if(file_exists($out)) {
    unlink($out);
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
    if (!file_exists(dirname(__FILE__).'/import_tmp/slideshow/')) {
        mkdir(dirname(__FILE__).'/import_tmp/slideshow/', 0775);
    }
}
if(!empty($audio)) {
    if($s3_enabled) {
        $path_audio = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'slideshow'.DIRECTORY_SEPARATOR.$audio;
        try {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/content/'.$audio,
                'SaveAs' => $path_audio
            ));
            array_push($array_tmp_files,$path_audio);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        $audio = "--audio='".$path_audio."'";
    } else {
        $audio = "--audio='".$path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$audio."'";
    }
} else {
    $audio = "";
}
$command = "ruby ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'slideshow.rb --fps='.$fps.' '.$audio.' --size='.$size.' --slide-duration='.$slide_duration.' --fade-duration='.$fade_duration.' --zoom-rate='.$zoom_rate.' -y '.$input.' '.$out.' 2>&1';
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
}
$output = shell_exec($command);
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
}
if(file_exists($out)) {
    if($watermark!="none") {
        if($s3_enabled) {
            $logo_path = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'slideshow'.DIRECTORY_SEPARATOR.$logo;
            try {
                $s3Client->getObject(array(
                    'Bucket' => $s3_bucket_name,
                    'Key'    => 'viewer/content/'.$logo,
                    'SaveAs' => $logo_path
                ));
                array_push($array_tmp_files,$logo_path);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                }
            }
        } else {
            $logo_path = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$logo;
        }
        $out_w = $path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow_w.mp4';
        switch($watermark) {
            case 'bottom_left':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo_path.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=30:H-h-30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'bottom_right':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo_path.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=W-w-30:H-h-30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'top_left':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo_path.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=30:30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'top_right':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo_path.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=W-w-30:30" -c:a copy '.$out_w.' 2>&1';
                break;
            case 'center':
                $command = $path_ffmpeg.' -i '.$out.' -i '.$logo_path.' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa='.$watermark_opacity.'[logo1];[video][logo1]overlay=(W-w)/2:(H-h)/2" -c:a copy '.$out_w.' 2>&1';
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
            echo json_encode(array("status"=>"error","msg"=>$output));
            exit;
        }
    }
    if($s3_enabled) {
        try {
            switch($s3_params['type']) {
                case 'digitalocean':
                    $s3Client->putObject(array(
                        'Bucket'     => $s3_bucket_name,
                        'SourceFile' => $out,
                        'Key'        => 'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4',
                        'ACL'        => 'public-read'
                    ));
                    break;
                default:
                    $s3Client->putObject(array(
                        'Bucket'     => $s3_bucket_name,
                        'SourceFile' => $out,
                        'Key'        => 'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4'
                    ));
                    break;
            }
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
            }
        }
        unlink($out);
        foreach ($array_tmp_files as $file) {
            unlink($file);
        }
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
    echo json_encode(array("status"=>"ok","msg"=>$id_virtualtour.'_slideshow.mp4'));
    exit;
} else {
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
    echo json_encode(array("status"=>"error","msg"=>$output));
    exit;
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
            file_put_contents(realpath(dirname(__FILE__))."/log_slideshow.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}