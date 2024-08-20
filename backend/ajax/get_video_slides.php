<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
require_once("../../services/getid3/getid3.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$getID3 = new getID3();
$path = realpath(dirname(__FILE__) . '/../..');
if($s3_enabled) {
    $path_assets = "s3://$s3_bucket_name/video/assets/$id_virtualtour/";
} else {
    $path_assets = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR;
}
$id_video = (int)$_POST['id_video'];
$query_w = "SELECT logo FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result_w = $mysqli->query($query_w);
if($result_w) {
    if ($result_w->num_rows == 1) {
        $row_w = $result_w->fetch_array(MYSQLI_ASSOC);
        $vt_logo = $row_w['logo'];
    }
}
$count_slides = 0;
$total_duration = 0;
$html = "";
$array_slides = array();
$query = "SELECT vps.id,vps.type,vps.id_room,vps.file,vps.font,vps.duration,vps.params,vps.priority,vps.enabled,sr.panorama_image FROM svt_video_project_slides as vps
LEFT JOIN svt_rooms sr on vps.id_room = sr.id
WHERE vps.id_video_project=$id_video
ORDER BY vps.priority ASC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if(empty($row['file'])) $row['file']='';
            $id = $row['id'];
            $type = $row['type'];
            $file = $row['file'];
            if(empty($file)) {
                if(!empty($vt_logo)) {
                    if($s3_enabled) {
                        $path_logo = $s3_url."viewer/content/".$vt_logo;
                    } else {
                        $path_logo = "../viewer/content/".$vt_logo;
                    }
                } else {
                    $path_logo = "";
                }
            } else {
                if($s3_enabled) {
                    $path_logo = $s3_url."video/assets/$id_virtualtour/$file";
                } else {
                    $path_logo = "../video/assets/$id_virtualtour/$file";
                }
            }
            $panorama_image = $row['panorama_image'];
            $id_room = $row['id_room'];
            $params = $row['params'];
            if($row['enabled']) {
                $checked = "checked";
                $opacity = 1;
            } else {
                $checked = "";
                $opacity = 0.5;
            }
            if(!empty($params)) $params = json_decode($params,true);
            $tools = "<div class='video_edit_div'><div class='edit_icons'><i onclick='edit_video_slide(\"$type\",$id)' class='fas fa-edit'></i><i onclick='duplicate_video_slide($id)' class='fas fa-clone'></i><i onclick='delete_video_slide($id)' class='fas fa-trash-alt'></i></div><div class='visible_video_slide'><input id='visible_video_slide_$id' onclick='toggle_video_slide_visibility($id);' $checked type='checkbox' /></div></div>";
            switch($type) {
                case 'panorama':
                    if($s3_enabled) {
                        $panorama_url = $s3_url."viewer/panoramas/preview/$panorama_image";
                    } else {
                        $panorama_url = "../viewer/panoramas/preview/$panorama_image";
                    }
                    $html .= "<div data-id='$id' style='opacity:$opacity' class='video_slide sort-item noselect'>$tools<i class='icon_type_slide fas fa-drum-steelpan'></i><img class='video_image' src='$panorama_url' /></div>";
                    break;
                case 'image':
                    if($s3_enabled) {
                        $image_url = $s3_url."video/assets/$id_virtualtour/$file";
                    } else {
                        $image_url = "../video/assets/$id_virtualtour/$file";
                    }
                    $html .= "<div data-id='$id' style='opacity:$opacity' class='video_slide sort-item noselect'>$tools<i class='icon_type_slide fas fa-image'></i><img class='video_image' src='$image_url' /></div>";
                    break;
                case 'video':
                    if($s3_enabled) {
                        $video_content = file_get_contents("s3://$s3_bucket_name/video/assets/$id_virtualtour/$file");
                        if(empty($video_content)) {
                            $video_content = curl_get_file_contents($s3_url."video/assets/$id_virtualtour/$file");
                        }
                        $tmpfname = tempnam(sys_get_temp_dir(), "video_");
                        rename($tmpfname, $tmpfname .= '.mp4');
                        file_put_contents($tmpfname,$video_content);
                        $video_file = $getID3->analyze($tmpfname);
                        unlink($tmpfname);
                    } else {
                        $video_file = $getID3->analyze($path_assets.$file);
                    }
                    $seconds = floor($video_file['playtime_seconds']);
                    $row['duration'] = $seconds;
                    if($s3_enabled) {
                        $video_url = $s3_url."video/assets/$id_virtualtour/$file#t=2";
                    } else {
                        $video_url = "../video/assets/$id_virtualtour/$file#t=2";
                    }
                    $html .= "<div data-id='$id' style='opacity:$opacity' class='video_slide sort-item noselect'>$tools<i class='icon_type_slide fas fa-video'></i><video playsinline preload='metadata' src='$video_url'></video></div>";
                    break;
                case 'logo':
                    $bottom_padding = $params['bottom_padding'];
                    $fontsize = $params['font_size'];
                    $fontsize = ($fontsize * 78) / 1080;
                    $bottom_padding = ($bottom_padding * 78) / 1080;
                    $html .= "<div data-id='$id' style='opacity:$opacity;background-color: ".$params['bg_color']."' class='video_slide sort-item video_logo_bg noselect'>$tools<i class='icon_type_slide fas fa-certificate'></i><img class='video_logo' src='$path_logo' /><span style='color:".$params['font_color'].";font-size:".$fontsize."px;top:unset;bottom:".$bottom_padding."px' class='video_text'>".nl2br($params['text'])."</span></div>";
                    break;
                case 'text':
                    $fontsize = $params['font_size'];
                    $fontsize = ($fontsize * 78) / 1080;
                    $html .= "<div data-id='$id' style='opacity:$opacity;background-color: ".$params['bg_color']."' class='video_slide sort-item video_logo_bg noselect'>$tools<i class='icon_type_slide fas fa-heading'></i><span style='color:".$params['font_color'].";font-size:".$fontsize."px' class='video_text'>".$params['text']."</span></div>";
                    break;
            }
            $array_slides[] = $row;
            $count_slides++;
        }
    }
}
$html .= '<div data-toggle="modal" data-target="#modal_new_slide" class="video_slide sort-disabled new_video_slide noselect"><i class="fas fa-plus-circle"></i></div>';
ob_end_clean();
echo json_encode(array('html'=>$html,'count'=>$count_slides,"array_slides"=>$array_slides));