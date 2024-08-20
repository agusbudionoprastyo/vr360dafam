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
require_once(__DIR__ . "/../db/connection.php");
require_once(__DIR__ . "/../backend/functions.php");
$debug = false;
if(isset($_GET['check_req'])) {
    $check_req = 1;
} else {
    $check_req = 0;
}
set_language_force('en_US','default');
session_write_close();
if(!isEnabled('shell_exec')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"php \"shell_exec\" "._("function disabled")));
    exit;
}

$path = realpath(dirname(__FILE__) . '/..');
$command = 'command -v ffmpeg 2>&1';
$output = shell_exec($command);
if($output=="") {
    $path_ffmpeg = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'ffmpeg';
    $command = $path_ffmpeg.' -v 2>&1';
    $output = shell_exec($command);
    if (strpos(strtolower($output), 'permission denied') !== false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=> _("Permission denied") . ". " ._("Execute the command")." \"chmod +x ".$path.DIRECTORY_SEPARATOR."ffmpeg"."\" "._("on your server")."."));
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

require_once('getid3/getid3.php');
$getID3 = new getID3();

$job_id = 0;

if(!isset($argv[1])) {
    $id_virtual_tour = (int)$_POST['id_virtualtour'];
    $id_video_project = (int)$_POST['id_video'];
    $job_id = 0;
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
        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_video.php $job_id > /dev/null &";
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
    $result = $mysqli->query("SELECT id_virtualtour,id_project FROM svt_job_queue WHERE id=$job_id AND type='video' LIMIT 1;");
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
$path_frames = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'frames'.DIRECTORY_SEPARATOR.$id_video_project.DIRECTORY_SEPARATOR;
$path_tmp_videos = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'videos'.DIRECTORY_SEPARATOR.$id_video_project.DIRECTORY_SEPARATOR;
$output_video_t = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$id_virtual_tour."_".$id_video_project."_t.mp4";
$output_video_w = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$id_virtual_tour."_".$id_video_project."_w.mp4";
$output_video = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$id_virtual_tour."_".$id_video_project.".mp4";

if(!file_exists($path_frames)) {
    mkdir($path_frames,0755,true);
}
if(!file_exists($path_tmp_videos)) {
    mkdir($path_tmp_videos,0755,true);
}

$time = time();
if($s3_enabled) {
    if(!file_exists($path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR)) {
        mkdir($path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR,0755,true);
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
            }
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

$array_slides = array();
$query = "SELECT * FROM svt_video_project_slides WHERE id_video_project=$id_video_project AND enabled=1 ORDER BY priority ASC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            switch($row['type']) {
                case 'panorama':
                    $query_r = "SELECT panorama_image FROM svt_rooms WHERE id=".$row['id_room']." LIMIT 1;";
                    $result_r = $mysqli->query($query_r);
                    if($result_r) {
                        if ($result_r->num_rows==1) {
                            $row_r=$result_r->fetch_array(MYSQLI_ASSOC);
                            $row['file']=$row_r['panorama_image'];
                        }
                    }
                    break;
            }
            array_push($array_slides,$row);
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

$index = 1;
foreach ($array_slides as $slide) {
    if($debug) {
        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",serialize($slide).PHP_EOL,FILE_APPEND);
    }
    $index_cmd = str_pad($index, 5, "0", STR_PAD_LEFT);
    $id_room = $slide['id_room'];
    $duration = $slide['duration'];
    $file = $slide['file'];
    $font = $slide['font'];
    $params = $slide['params'];
    switch($slide['type']) {
        case 'text':
            $params = json_decode($params,true);
            $fontsize = (int)$params['font_size'];
            $fontsize = ($fontsize * $resolution_h) / 1080;
            $fontcolor = $params['font_color'];
            $bgcolor = $params['bg_color'];
            $text = $params['text'];
            $image = imagecreatetruecolor($resolution_w, $resolution_h);
            $r = hexdec(substr($bgcolor, 1, 2));
            $g = hexdec(substr($bgcolor, 3, 2));
            $b = hexdec(substr($bgcolor, 5, 2));
            $bgcolor = imagecolorallocate($image, $r, $g, $b);
            imagefill($image, 0, 0, $bgcolor);
            $r = hexdec(substr($fontcolor, 1, 2));
            $g = hexdec(substr($fontcolor, 3, 2));
            $b = hexdec(substr($fontcolor, 5, 2));
            if(!empty($text)) {
                $fontcolor = imagecolorallocate($image, $r, $g, $b);
                $text = wrapText($fontsize, $path_font.$font, $text, $resolution_w-50);
                $text_box = imagettfbbox($fontsize, 0, $path_font.$font, $text);
                $text_width = $text_box[2] - $text_box[0];
                $text_height = abs($text_box[7] - $text_box[1]);
                $xt = ($resolution_w / 2) - ($text_width / 2);
                $yt = ($resolution_h / 2) + ($text_height / 2);
                imagettftextcenter($image, $fontsize, $xt, $yt, $fontcolor, $path_font.$font, $text, 0);
            } else {
                $bottom_padding = 0;
                $text_height = 0;
            }
            imagepng($image, $path_tmp_videos."output_$index_cmd.png");
            if(file_exists($path_tmp_videos."output_$index_cmd.png")) {
                imagedestroy($image);
                $command = "$path_ffmpeg -y -loop 1 -i $path_tmp_videos"."output_$index_cmd.png -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -t $duration -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
                }
                $result = shell_exec($command);
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
                }
                if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                    $command_r = "rm -R ".$path_tmp_videos;
                    shell_exec($command_r);
                    $command_r = "rm -R ".$path_frames;
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
                    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                    exit;
                }
                $command_r = "rm ".$path_tmp_videos."output_$index_cmd.png";
                shell_exec($command_r);
            } else {
                $command_r = "rm -R ".$path_tmp_videos;
                shell_exec($command_r);
                $command_r = "rm -R ".$path_frames;
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
                echo json_encode(array("status"=>"error","msg"=>"imagepng error"));
                exit;
            }
            break;
        case 'logo':
            if(empty($file)) {
                if(!empty($vt_logo)) {
                    if($s3_enabled) {
                        $path_logo = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$vt_logo;
                        try {
                            $s3Client->getObject(array(
                                'Bucket' => $s3_bucket_name,
                                'Key'    => 'viewer/content/'.$vt_logo,
                                'SaveAs' => $path_logo
                            ));
                        } catch (\Aws\S3\Exception\S3Exception $e) {
                            if($debug) {
                                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                            }
                        }
                    } else {
                        $path_logo = $path_content.$vt_logo;
                    }
                }
            } else {
                if($s3_enabled) {
                    $path_logo = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$file;
                    try {
                        $s3Client->getObject(array(
                            'Bucket' => $s3_bucket_name,
                            'Key'    => 'video/assets/'.$id_virtual_tour.'/'.$file,
                            'SaveAs' => $path_logo
                        ));
                    } catch (\Aws\S3\Exception\S3Exception $e) {
                        if($debug) {
                            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                        }
                    }
                } else {
                    $path_logo = $path_assets.$file;
                }
            }
            $params = json_decode($params,true);
            $fontsize = (int)$params['font_size'];
            $fontsize = ($fontsize * $resolution_h) / 1080;
            $fontcolor = $params['font_color'];
            $bgcolor = $params['bg_color'];
            $text = $params['text'];
            $image = imagecreatetruecolor($resolution_w, $resolution_h);
            $r = hexdec(substr($bgcolor, 1, 2));
            $g = hexdec(substr($bgcolor, 3, 2));
            $b = hexdec(substr($bgcolor, 5, 2));
            $bgcolor = imagecolorallocate($image, $r, $g, $b);
            imagefill($image, 0, 0, $bgcolor);
            $logo = imagecreatefrompng($path_logo);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
            $max_width = $resolution_w / 2.5;
            $max_height = $resolution_h / 2.5;
            $ratio = min($max_width / $logo_width, $max_height / $logo_height);
            $logo_width = $ratio * $logo_width;
            $logo_height = $ratio * $logo_height;
            $x = ($resolution_w / 2) - ($logo_width / 2);
            $y = ($resolution_h / 2) - ($logo_height / 2);
            $r = hexdec(substr($fontcolor, 1, 2));
            $g = hexdec(substr($fontcolor, 3, 2));
            $b = hexdec(substr($fontcolor, 5, 2));
            if(!empty($text)) {
                if(isset($params['bottom_padding'])) {
                    $bottom_padding = (int)$params['bottom_padding'];
                    $bottom_padding = ($bottom_padding * $resolution_h) / 1080;
                } else {
                    $bottom_padding = 0;
                }
                $fontcolor = imagecolorallocate($image, $r, $g, $b);
                $text = wrapText($fontsize, $path_font.$font, $text, $resolution_w-50);
                $text_box = imagettfbbox($fontsize, 0, $path_font.$font, $text);
                $text_width = $text_box[2] - $text_box[0];
                $text_height = abs($text_box[7] - $text_box[1]);
                $xt = ($resolution_w / 2) - ($text_width / 2);
                $yt = $resolution_h - $text_height - $bottom_padding;
                imagettftextcenter($image, $fontsize, $xt, $yt, $fontcolor, $path_font.$font, $text, $resolution_h);
            } else {
                $bottom_padding = 0;
                $text_height = 0;
            }
            $x = round($x);
            $y = round($y);
            imagecopyresampled($image, $logo, $x, $y, 0, 0, $logo_width, $logo_height, imagesx($logo), imagesy($logo));
            imagepng($image, $path_tmp_videos."output_$index_cmd.png");
            if(file_exists($path_tmp_videos."output_$index_cmd.png")) {
                imagedestroy($image);
                imagedestroy($logo);
                $command = "$path_ffmpeg -y -loop 1 -i $path_tmp_videos"."output_$index_cmd.png -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -t $duration -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
                }
                $result = shell_exec($command);
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
                }
                if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                    $command_r = "rm -R ".$path_tmp_videos;
                    shell_exec($command_r);
                    $command_r = "rm -R ".$path_frames;
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
                    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                    exit;
                }
                $command_r = "rm ".$path_tmp_videos."output_$index_cmd.png";
                shell_exec($command_r);
            } else {
                $command_r = "rm -R ".$path_tmp_videos;
                shell_exec($command_r);
                $command_r = "rm -R ".$path_frames;
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
                echo json_encode(array("status"=>"error","msg"=>"imagepng error"));
                exit;
            }
            break;
        case 'panorama':
            if($s3_enabled) {
                $exist_original = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/original/'.$file);
                $panorama = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$file;
                if($exist_original) {
                    try {
                        $s3Client->getObject(array(
                            'Bucket' => $s3_bucket_name,
                            'Key'    => 'viewer/panoramas/original/'.$file,
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
                            'Key'    => 'viewer/panoramas/'.$file,
                            'SaveAs' => $panorama
                        ));
                    } catch (\Aws\S3\Exception\S3Exception $e) {
                        if($debug) {
                            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                        }
                    }
                }
            } else {
                $panorama = $path_panorama.$file;
                if(!file_exists($panorama)) {
                    $panorama = $path_panorama2.$file;
                }
            }
            $params = json_decode($params,true);
            $frames=$duration*$fps;
            $initial_yaw=$params['initial_yaw'];
            $initial_pitch=$params['initial_pitch'];
            $initial_hfov=$params['initial_hfov'];
            $end_yaw=$params['end_yaw'];
            $end_pitch=$params['end_pitch'];
            $end_hfov=$params['end_hfov'];
            $inc_yaw=yaw_distance($initial_yaw,$end_yaw)/$frames;
            $inc_pitch=($end_pitch-$initial_pitch)/$frames;
            $inc_hfov=($end_hfov-$initial_hfov)/$frames;
            $yaw=$initial_yaw;
            $pitch=$initial_pitch;
            $hfov=$initial_hfov;
            for($i=0;$i<=$frames;$i++) {
                $vhfov = 2 * atan(tan(deg2rad($hfov) / 2) / (16/9));
                $vhfov = abs(rad2deg($vhfov));
                $command = "$path_ffmpeg -hide_banner -y -i $panorama -preset veryfast -start_number $i -vframes 1 -vf \"v360=equirect:flat:yaw=$yaw:pitch=$pitch:h_fov=$hfov:v_fov=$vhfov:w=$resolution_w:h=$resolution_h\" ".$path_frames."frame_".$index_cmd."_%05d.jpg 2>&1";
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
                }
                $result = shell_exec($command);
                if($debug) {
                    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
                }
                $yaw=$yaw+$inc_yaw;
                if($yaw>=180) {
                    $yaw=-180;
                } else if($yaw<=-180) {
                    $yaw=180;
                }
                $pitch=$pitch+$inc_pitch;
                $hfov=$hfov+$inc_hfov;
            }
            $command = "$path_ffmpeg -hide_banner -y -framerate $fps -i ".$path_frames."frame_".$index_cmd."_%05d.jpg -preset veryfast -c:v libx264 -pix_fmt yuv420p -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_tmp_videos;
                shell_exec($command_r);
                $command_r = "rm -R ".$path_frames;
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
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            break;
        case 'image':
            if($s3_enabled) {
                $path_image = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$file;
                try {
                    $s3Client->getObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video/assets/'.$id_virtual_tour.'/'.$file,
                        'SaveAs' => $path_image
                    ));
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                    }
                }
            } else {
                $path_image = $path_assets.$file;
            }
            $command = "$path_ffmpeg -hide_banner -y -loop 1 -i $path_image -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -t $duration -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_tmp_videos;
                shell_exec($command_r);
                $command_r = "rm -R ".$path_frames;
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
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            break;
        case 'video':
            if($s3_enabled) {
                $path_video = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$file;
                try {
                    $s3Client->getObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'video/assets/'.$id_virtual_tour.'/'.$file,
                        'SaveAs' => $path_video
                    ));
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    if($debug) {
                        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."S3 ERROR: ".$e->getMessage().PHP_EOL,FILE_APPEND);
                    }
                }
            } else {
                $path_video = $path_assets.$file;
            }
            $command = "$path_ffmpeg -hide_banner -y -i $path_video -an -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_tmp_videos;
                shell_exec($command_r);
                $command_r = "rm -R ".$path_frames;
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
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            break;
    }
    $index++;
}

$videos = glob($path_tmp_videos."*.mp4");
$input_list = "";
$array_durations = array();
foreach ($videos as $video) {
    $input_list .= "-i $video ";
    $video_file = $getID3->analyze($video);
    $seconds = $video_file['playtime_seconds'];
    array_push($array_durations,$seconds);
}

$num_videos = count($videos);
$prev_offset = 0;
$filter_complex = "";
if($num_videos==1) {
    if(!empty($audio)) {
        $filter_complex_cmd = "-map 0:v:0 -map 1:a:0";
    } else {
        $filter_complex_cmd = "";
    }
} else {
    for ($i = 0; $i < $num_videos-1; $i++) {
        $duration_sec = $array_durations[$i];
        $offset = $duration_sec+$prev_offset-$fade_duration;
        $prev_offset = $offset;
        if ($i > 0) {
            $filter_complex .= "[vfade$i]";
        } else {
            $filter_complex .= "[0]fade=in:st=0:d=1[vfade0];[vfade0]";
        }
        if($i==$num_videos-2) {
            $filter_complex .= "[".($i+1).":v]xfade=transition=fade:duration=$fade_duration:offset=".$offset."[vfade".($i+1)."];[vfade".($i+1)."]fade=out:st=".($offset+$array_durations[$i+1]-1).":d=1,format=yuv420p";
        } else {
            $filter_complex .= "[".($i+1).":v]xfade=transition=fade:duration=$fade_duration:offset=".$offset."[vfade".($i+1)."];";
        }
    }
    if(!empty($audio)) {
        $filter_complex_cmd = "-filter_complex \"$filter_complex\" -map ".($num_videos).":a:0";
    } else {
        $filter_complex_cmd = "-filter_complex \"$filter_complex\"";
    }
}
if(!empty($audio)) {
    if($s3_enabled) {
        $path_audio = $path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR.$audio;
    } else {
        $path_audio = $path_content.$audio;
    }
    $command = "$path_ffmpeg -hide_banner -y $input_list -stream_loop -1 -i \"$path_audio\" -shortest -preset veryfast $filter_complex_cmd ".$output_video_t." 2>&1";
} else {
    $command = "$path_ffmpeg -hide_banner -y $input_list -preset veryfast $filter_complex_cmd ".$output_video_t." 2>&1";
}
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
}
$result = shell_exec($command);
if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
}
if(!file_exists($output_video_t)) {
    $command_r = "rm -R ".$path_tmp_videos;
    shell_exec($command_r);
    $command_r = "rm -R ".$path_frames;
    shell_exec($command_r);
    if($job_id!=0) {
        $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
    exit;
}

if($watermark!="none") {
    switch ($watermark) {
        case 'bottom_left':
            $command = $path_ffmpeg . ' -y -i ' . $output_video_t. ' -i ' . $watermark_path . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=30:H-h-30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'bottom_right':
            $command = $path_ffmpeg . ' -y -i ' . $output_video_t. ' -i ' . $watermark_path . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=W-w-30:H-h-30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'top_left':
            $command = $path_ffmpeg . ' -y -i ' . $output_video_t. ' -i ' . $watermark_path . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=30:30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'top_right':
            $command = $path_ffmpeg . ' -y -i ' . $output_video_t. ' -i ' . $watermark_path . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=W-w-30:30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'center':
            $command = $path_ffmpeg . ' -y -i ' . $output_video_t. ' -i ' . $watermark_path . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=(W-w)/2:(H-h)/2" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
    }
    if($debug) {
        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
    }
    $result = shell_exec($command);
    if($debug) {
        file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
    }
    unlink($output_video_t);
    rename($output_video_w,$output_video);
} else {
    rename($output_video_t,$output_video);
}
if(!file_exists($output_video)) {
    $command_r = "rm -R ".$path_tmp_videos;
    shell_exec($command_r);
    $command_r = "rm -R ".$path_frames;
    shell_exec($command_r);
    if($job_id!=0) {
        $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
    }
    ob_end_clean();
    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
    exit;
}

$command_r = "rm -R ".$path_tmp_videos;
shell_exec($command_r);
$command_r = "rm -R ".$path_frames;
shell_exec($command_r);

if($s3_enabled) {
    switch($s3_params['type']) {
        case 'digitalocean':
            $s3Client->putObject(array(
                'Bucket'     => $s3_bucket_name,
                'SourceFile' => $output_video,
                'Key'        => 'video/'.$id_virtual_tour."_".$id_video_project.".mp4",
                'ACL'        => 'public-read'
            ));
            break;
        default:
            $s3Client->putObject(array(
                'Bucket'     => $s3_bucket_name,
                'SourceFile' => $output_video,
                'Key'        => 'video/'.$id_virtual_tour."_".$id_video_project.".mp4"
            ));
            break;
    }
    unlink($output_video);
    $command_r = "rm -R ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'import_tmp'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$time.DIRECTORY_SEPARATOR;
    shell_exec($command_r);
}

if($job_id!=0) {
    $mysqli->query("DELETE FROM svt_job_queue WHERE id=$job_id;");
}
$mysqli->query("UPDATE svt_video_projects SET date_time=NOW() WHERE id_virtualtour=$id_virtual_tour AND id=$id_video_project;");
ob_end_clean();
echo json_encode(array("status"=>"ok","msg"=>$id_virtual_tour.'_'.$id_video_project.".mp4"));
exit;

function imagettftextcenter($image, $size, $x, $y, $color, $fontfile, $text, $resolution_h){
    $rect = imagettfbbox($size, 0, $fontfile, "Tq");
    $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7]));
    $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7]));
    $h1 = $maxY - $minY;
    $rect = imagettfbbox($size, 0, $fontfile, "Tq\nTq");
    $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7]));
    $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7]));
    $h2 = $maxY - $minY;
    if($resolution_h!=0) {
        $vpadding = $h2 - $h1 - $h1 + (20 * $resolution_h) / 1080;
    } else {
        $vpadding = $h2 - $h1 - $h1;
    }
    $frect = imagettfbbox($size, 0, $fontfile, $text);
    $minX = min(array($frect[0],$frect[2],$frect[4],$frect[6]));
    $maxX = max(array($frect[0],$frect[2],$frect[4],$frect[6]));
    $text_width = $maxX - $minX;
    $text = explode("\n", $text);
    foreach($text as $txt){
        $rect = imagettfbbox($size, 0, $fontfile, $txt);
        $minX = min(array($rect[0],$rect[2],$rect[4],$rect[6]));
        $maxX = max(array($rect[0],$rect[2],$rect[4],$rect[6]));
        $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7]));
        $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7]));
        $width = $maxX - $minX;
        $height = $maxY - $minY;
        $_x = $x + (($text_width - $width) / 2);
        imagettftext($image, $size, 0, $_x, $y, $color, $fontfile, $txt);
        $y += ($height + $vpadding);
    }
    return $rect;
}

function wrapText($fontSize, $fontFace, $string, $width){
    $string = str_replace("<br>","\n",$string);
    $ret = "";
    $arr = explode(" ", $string);
    foreach($arr as $word){
        $testboxWord = imagettfbbox($fontSize, 0, $fontFace, $word);
        $len = strlen($word);
        while($testboxWord[2] > $width && $len > 0){
            $word = substr($word, 0, $len);
            $len--;
            $testboxWord = imagettfbbox($fontSize, 0, $fontFace, $word);
        }
        $teststring = $ret . ' ' . $word;
        $testboxString = imagettfbbox($fontSize, 0, $fontFace, $teststring);
        if($testboxString[2] > $width){
            $ret.=($ret == "" ? "" : "\n") . $word;
        }else{
            $ret.=($ret == "" ? "" : ' ') . $word;
        }
    }
    return $ret;
}

function yaw_distance($yaw1, $yaw2) {
    $distance = $yaw2 - $yaw1;
    if ($distance > 180) {
        $distance -= 360;
    } elseif ($distance < -180) {
        $distance += 360;
    }
    return $distance;
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