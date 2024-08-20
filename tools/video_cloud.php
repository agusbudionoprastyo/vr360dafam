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

if(isset($_GET['check'])) {
    ob_end_clean();
    echo "ok";
    exit;
}

if (!file_exists(dirname(__FILE__).'/video_tmp/')) {
    mkdir(dirname(__FILE__).'/video_tmp/', 0775);
}

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

if(isset($_POST['complete_video'])) {
    if(file_exists(dirname(__FILE__).'/video_tmp/'.$_POST['complete_video'])) {
        unlink(dirname(__FILE__).'/video_tmp/'.$_POST['complete_video']);
    }
    exit;
}

if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." ".'POST: '.serialize($_POST).PHP_EOL,FILE_APPEND);
    file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." ".'FILE: '.serialize($_FILES).PHP_EOL,FILE_APPEND);
}

$id_virtualtour = $_POST['id_virtualtour'];
$resolution_w = $_POST['resolution_w'];
$resolution_h = $_POST['resolution_h'];
$vt_logo = $_POST['vt_logo'];
$fade_duration = $_POST['fade_duration'];
$watermark = $_POST['watermark'];
$fps = $_POST['fps'];
$audio = $_POST['audio'];
if(!empty($audio)) {
    $audio = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $audio);
    $audio = html_entity_decode($audio, ENT_COMPAT, 'UTF-8');
    $audio = str_replace('&#x', '\u', $audio);
}
$watermark_logo = $_POST['watermark_logo'];
if(empty($watermark_logo)) {
    if(empty($vt_logo)) {
        $watermark = 'none';
    } else {
        $watermark_logo = $vt_logo;
    }
}
if(isset($_POST['watermark_opacity'])) {
    $watermark_opacity = $_POST['watermark_opacity'];
} else {
    $watermark_opacity = 1;
}
$array_slides = json_decode($_POST['array_slides'],true);

if(isset($_FILES) && !empty($_FILES['file']['name'])) {
    $moved = move_uploaded_file($_FILES['file']['tmp_name'], dirname(__FILE__) . '/video_tmp/' . $_FILES['file']['name']);
    if ($moved) {
        $dir_name = basename($_FILES['file']['name'], ".zip");
        if (!file_exists(dirname(__FILE__) . '/video_tmp/' . $dir_name)) {
            mkdir(dirname(__FILE__) . '/video_tmp/' . $dir_name, 0775);
        }
        $zip = new ZipArchive;
        $res = $zip->open(dirname(__FILE__) . '/video_tmp/' . $_FILES['file']['name']);
        if ($res === TRUE) {
            $zip->extractTo(dirname(__FILE__) . '/video_tmp/' . $dir_name);
            $zip->close();
            unlink(dirname(__FILE__) . '/video_tmp/' . $_FILES['file']['name']);
        }
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing file")));
    exit;
}

$out_name = $id_virtualtour.'_video_'.time().'.mp4';
$path_files = dirname(__FILE__).DIRECTORY_SEPARATOR.'video_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR;
$out = $path.DIRECTORY_SEPARATOR.'video_tmp'.DIRECTORY_SEPARATOR.$out_name;
$output_video_t = dirname(__FILE__).DIRECTORY_SEPARATOR.'video_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR."t_".$out_name;
$output_video_w = dirname(__FILE__).DIRECTORY_SEPARATOR.'video_tmp'.DIRECTORY_SEPARATOR.$dir_name.DIRECTORY_SEPARATOR."w_".$out_name;

$path_frames = $path_files.'frames'.DIRECTORY_SEPARATOR;
$path_tmp_videos = $path_files.'videos'.DIRECTORY_SEPARATOR;
if(!file_exists($path_frames)) {
    mkdir($path_frames,0777,true);
}
if(!file_exists($path_tmp_videos)) {
    mkdir($path_tmp_videos,0777,true);
}
try {
    shell_exec("chmod 777 ".$path_frames);
} catch (Exception $e) {}
try {
    shell_exec("chmod 777 ".$path_tmp_videos);
} catch (Exception $e) {}

$index = 1;
foreach ($array_slides as $slide) {
    $index_cmd = str_pad($index, 5, "0", STR_PAD_LEFT);
    $id_room = $slide['id_room'];
    $duration = $slide['duration'];
    $file = $slide['file'];
    $font = $slide['font'];
    $params = $slide['params'];
    switch($slide['type']) {
        case 'text':
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
                $text = wrapText($fontsize, $path_files.$font, $text, $resolution_w-50);
                $text_box = imagettfbbox($fontsize, 0, $path_files.$font, $text);
                $text_width = $text_box[2] - $text_box[0];
                $text_height = abs($text_box[7] - $text_box[1]);
                $xt = ($resolution_w / 2) - ($text_width / 2);
                $yt = ($resolution_h / 2) + ($text_height / 2);
                imagettftextcenter($image, $fontsize, $xt, $yt, $fontcolor, $path_files.$font, $text, 0);
            } else {
                $bottom_padding = 0;
                $text_height = 0;
            }
            imagepng($image, $path_tmp_videos."output_$index_cmd.png");
            imagedestroy($image);
            $command = "$path_ffmpeg -loop 1 -i $path_tmp_videos"."output_$index_cmd.png -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -t $duration -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_files;
                shell_exec($command_r);
                ob_end_clean();
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            $command_r = "rm ".$path_tmp_videos."output_$index_cmd.png";
            shell_exec($command_r);
            break;
        case 'logo':
            if(empty($file)) {
                if(!empty($vt_logo)) {
                    $path_logo = $path_files.$vt_logo;
                }
            } else {
                $path_logo = $path_files.$file;
            }
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
                $text = wrapText($fontsize, $path_files.$font, $text, $resolution_w-50);
                $text_box = imagettfbbox($fontsize, 0, $path_files.$font, $text);
                $text_width = $text_box[2] - $text_box[0];
                $text_height = abs($text_box[7] - $text_box[1]);
                $xt = ($resolution_w / 2) - ($text_width / 2);
                $yt = $resolution_h - $text_height - $bottom_padding;
                imagettftextcenter($image, $fontsize, $xt, $yt, $fontcolor, $path_files.$font, $text, $resolution_h);
            } else {
                $bottom_padding = 0;
                $text_height = 0;
            }
            $x = round($x);
            $y = round($y);
            imagecopyresampled($image, $logo, $x, $y, 0, 0, $logo_width, $logo_height, imagesx($logo), imagesy($logo));
            imagepng($image, $path_tmp_videos."output_$index_cmd.png");
            imagedestroy($image);
            imagedestroy($logo);
            $command = "$path_ffmpeg -loop 1 -i $path_tmp_videos"."output_$index_cmd.png -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -t $duration -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_files;
                shell_exec($command_r);
                ob_end_clean();
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            $command_r = "rm ".$path_tmp_videos."output_$index_cmd.png";
            shell_exec($command_r);
            break;
        case 'panorama':
            $panorama = $path_files.$file;
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
                $command = "$path_ffmpeg -hide_banner -i $panorama -preset veryfast -start_number $i -vframes 1 -vf \"v360=equirect:flat:yaw=$yaw:pitch=$pitch:h_fov=$hfov:v_fov=$vhfov:w=$resolution_w:h=$resolution_h\" ".$path_frames."frame_".$index_cmd."_%05d.jpg 2>&1";
                shell_exec($command);
                $yaw=$yaw+$inc_yaw;
                if($yaw>=180) {
                    $yaw=-180;
                } else if($yaw<=-180) {
                    $yaw=180;
                }
                $pitch=$pitch+$inc_pitch;
                $hfov=$hfov+$inc_hfov;
            }
            $command = "$path_ffmpeg -hide_banner -framerate $fps -y -i ".$path_frames."frame_".$index_cmd."_%05d.jpg -preset veryfast -c:v libx264 -pix_fmt yuv420p -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_files;
                shell_exec($command_r);
                ob_end_clean();
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            break;
        case 'image':
            $image = $file;
            $command = "$path_ffmpeg -hide_banner -loop 1 -i $path_files$image -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -t $duration -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_files;
                shell_exec($command_r);
                ob_end_clean();
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            break;
        case 'video':
            $video = $file;
            $command = "$path_ffmpeg -hide_banner -i $path_files$video -an -preset veryfast -filter_complex \"scale=$resolution_w:$resolution_h:force_original_aspect_ratio=decrease,pad=$resolution_w:$resolution_h:(ow-iw)/2:(oh-ih)/2\" -c:v libx264 -pix_fmt yuv420p -r $fps -s ".$resolution_w."x".$resolution_h." -aspect 16:9 ".$path_tmp_videos."output_$index_cmd.mp4 2>&1";
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
            }
            $result = shell_exec($command);
            if($debug) {
                file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."OUTPUT: ".$result.PHP_EOL,FILE_APPEND);
            }
            if(!file_exists($path_tmp_videos."output_$index_cmd.mp4")) {
                $command_r = "rm -R ".$path_files;
                shell_exec($command_r);
                ob_end_clean();
                echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
                exit;
            }
            break;
    }
    $index++;
}

require_once('getid3/getid3.php');
$getID3 = new getID3();

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
    $command = "$path_ffmpeg -hide_banner -y $input_list -stream_loop -1 -i \"$path_files$audio\" -shortest -preset veryfast $filter_complex_cmd ".$output_video_t." 2>&1";
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
    $command_r = "rm -R ".$path_files;
    shell_exec($command_r);
    ob_end_clean();
    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
    exit;
}

if($watermark!="none") {
    switch ($watermark) {
        case 'bottom_left':
            $command = $path_ffmpeg . ' -i ' . $output_video_t. ' -i ' . $path_files.$watermark_logo . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=30:H-h-30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'bottom_right':
            $command = $path_ffmpeg . ' -i ' . $output_video_t. ' -i ' . $path_files.$watermark_logo . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=W-w-30:H-h-30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'top_left':
            $command = $path_ffmpeg . ' -i ' . $output_video_t. ' -i ' . $path_files.$watermark_logo . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=30:30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'top_right':
            $command = $path_ffmpeg . ' -i ' . $output_video_t. ' -i ' . $path_files.$watermark_logo . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=W-w-30:30" -c:a copy ' . $output_video_w . ' 2>&1';
            break;
        case 'center':
            $command = $path_ffmpeg . ' -i ' . $output_video_t. ' -i ' . $path_files.$watermark_logo . ' -preset veryfast -filter_complex "[1][0]scale2ref=w=oh*mdar:h=ih*0.1[logo][video];[logo]format=argb,colorchannelmixer=aa=' . $watermark_opacity . '[logo1];[video][logo1]overlay=(W-w)/2:(H-h)/2" -c:a copy ' . $output_video_w . ' 2>&1';
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
    rename($output_video_w,$out);
} else {
    rename($output_video_t,$out);
}
if(!file_exists($out)) {
    $command_r = "rm -R ".$path_files;
    shell_exec($command_r);
    ob_end_clean();
    echo json_encode(array("status"=>"error","command"=>$command,"msg"=>$result));
    exit;
}

$command_r = "rm -R ".$path_files;
shell_exec($command_r);
ob_end_clean();
echo json_encode(array("status"=>"ok","file_name"=>$out_name));
exit;



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
            file_put_contents(realpath(dirname(__FILE__))."/log_video.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}

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