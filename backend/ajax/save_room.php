<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_room = (int)$_POST['id_room'];
$name = strip_tags($_POST['name']);
$logo = $_POST['logo'];
$logo_height = $_POST['logo_height'];
if(empty($logo_height) || $logo_height<1) $logo_height=16;
$logo_height = (int)$logo_height;
$yaw_pitch = strip_tags($_POST['yaw_pitch']);
$northOffset = (int)$_POST['northOffset'];
$change_image = $_POST['change_image'];
$change_video = $_POST['change_video'];
$change_json = $_POST['change_json'];
$panorama_image = strip_tags($_POST['panorama_image']);
$thumb_image = strip_tags($_POST['thumb_image']);
$panorama_video = strip_tags($_POST['panorama_video']);
$panorama_json = strip_tags($_POST['panorama_json']);
$panorama_url = strip_tags($_POST['panorama_url']);
$song = strip_tags($_POST['song']);
$song_bg_volume = (float)$_POST['song_bg_volume'];
$audio_track_enable = (int)$_POST['audio_track_enable'];
$song_loop = (int)$_POST['song_loop'];
$song_once = (int)$_POST['song_once'];
$annotation_title = strip_tags($_POST['annotation_title']);
$annotation_description = strip_tags($_POST['annotation_description'],"<br><b><u><i><ul><li>");
$allow_pitch = (int)$_POST['allow_pitch'];
$allow_hfov = (int)$_POST['allow_hfov'];
$visible = (int)$_POST['visible'];
$visible_list = (int)$_POST['visible_list'];
$min_pitch = $_POST['min_pitch'];
$max_pitch = $_POST['max_pitch'];
$min_yaw = $_POST['min_yaw'];
$max_yaw = $_POST['max_yaw'];
$haov = $_POST['haov'];
$vaov = $_POST['vaov'];
$hfov = $_POST['hfov'];
$h_pitch = (int)$_POST['h_pitch'];
$h_roll = (int)$_POST['h_roll'];
$protect_type = strip_tags($_POST['protect_type']);
$passcode_title = strip_tags($_POST['passcode_title']);
$passcode_description = strip_tags($_POST['passcode_description'],"<br><b><u><i><ul><li>");
$passcode = strip_tags($_POST['passcode']);
$protect_mc_form = $_POST['protect_mc_form'];
$protect_remember = (int)$_POST['protect_remember'];
$filters = [];
$filters['brightness'] = $_POST['brightness'];
$filters['contrast'] = $_POST['contrast'];
$filters['saturate'] = $_POST['saturate'];
$filters['grayscale'] = $_POST['grayscale'];
$filters = json_encode($filters);
$tmp = explode(",",$yaw_pitch);
$yaw = (float)$tmp[0];
$pitch = (float)$tmp[1];
if(empty($min_pitch)) $min_pitch=90; else $min_pitch = (int)$min_pitch;
if(empty($max_pitch)) $max_pitch=90; else $max_pitch = (int)$max_pitch;
if(empty($min_yaw)) $min_yaw=180; else $min_yaw = (int)$min_yaw;
if(empty($max_yaw)) $max_yaw=180; else $max_yaw = (int)$max_yaw;
if(empty($haov)) $haov=360; else $haov = (int)$haov;
if(empty($vaov)) $vaov=180; else $vaov = (int)$vaov;
if(empty($hfov)) $hfov=0; else $hfov = (int)$hfov;
$min_pitch = $min_pitch*-1;
$min_yaw = $min_yaw*-1;
$transition_time = $_POST['transition_time'];
$transition_fadeout = $_POST['transition_fadeout'];
$transition_zoom = (int)$_POST['transition_zoom'];
$transition_override = (int)$_POST['transition_override'];
$transition_effect = strip_tags($_POST['transition_effect']);
$virtual_staging = strip_tags($_POST['virtual_staging']);
$main_view_tooltip = strip_tags($_POST['main_view_tooltip']);
$background_color = strip_tags($_POST['background_color']);
if(empty($background_color)) $background_color="1,1,1";
$virtual_tour = get_virtual_tour($id_virtualtour,$id_user);
if($transition_override==1) {
    if($transition_time=='') $transition_time = $virtual_tour['transition_time']; else $transition_time = (int)$transition_time;
    if($transition_fadeout=='') $transition_fadeout = $virtual_tour['transition_fadeout']; else $transition_fadeout = (int)$transition_fadeout;
} else {
    $transition_time = $virtual_tour['transition_time'];
    $transition_fadeout = $virtual_tour['transition_fadeout'];
    $transition_zoom = $virtual_tour['transition_zoom'];
    $transition_effect = $virtual_tour['transition_effect'];
}
$effect = strip_tags($_POST['effect']);
$apply_preset_to_vt = $_POST['apply_preset_to_vt'];
$protect_send_email = (int)$_POST['protect_send_email'];
$protect_email = strip_tags($_POST['protect_email']);
$video_end_goto = (int)$_POST['video_end_goto'];
$lp_duration = $_POST['lp_duration'];
if(empty($lp_duration)) {
    $lp_duration = 3000;
} else {
    $lp_duration = (int)$lp_duration;
}
$lp_fade = $_POST['lp_fade'];
if(empty($lp_fade)) {
    $lp_fade = 5000;
} else {
    $lp_fade = (int)$lp_fade;
}
$show_nadir = (int)$_POST['show_nadir'];
$array_lang = json_decode($_POST['array_lang'],true);
$settings = get_settings();
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
$s3Client = null;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
if($change_image==1) {
    $name_image = str_replace("tmp_panoramas/","",$panorama_image);
    $path_source = dirname(__FILE__).'/../tmp_panoramas/'.$name_image;
    if($s3_enabled) {
        $path_dest = "s3://$s3_bucket_name/viewer/panoramas/$name_image";
    } else {
        $path_dest = dirname(__FILE__).'/../../viewer/panoramas/'.$name_image;
    }
    if(copy($path_source,$path_dest)) {
        if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/$name_image",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
                exit;
            }
        }
        unlink($path_source);
        $query = "UPDATE svt_rooms SET panorama_image=?,multires_status=0 WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$name_image,$id_room);
            $smt->execute();
        }
        $panorama_image_gt = $name_image;
        include("../../services/generate_thumb.php");
        include("../../services/generate_pano_mobile.php");
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error image"));
        die();
    }
} else if($change_video==1) {
    $name_image = "pano_".time().".jpg";
    if($s3_enabled) {
        $name_video = str_replace($s3_url."viewer/videos/","",$panorama_video);
        $path_dest = "s3://$s3_bucket_name/viewer/panoramas/$name_image";
    } else {
        $name_video = str_replace("../viewer/videos/","",$panorama_video);
        $path_dest = dirname(__FILE__).'/../../viewer/panoramas/'.$name_image;
    }
    $ifp = fopen($path_dest,'wb');
    $data = explode(',', $panorama_image);
    fwrite($ifp,base64_decode($data[1]));
    fclose( $ifp );
    if($s3_enabled && $settings['aws_s3_type']=='digitalocean') {
        try {
            $s3Client->putObjectAcl([
                'Bucket' => $s3_bucket_name,
                'Key' => "viewer/panoramas/$name_image",
                'ACL' => 'public-read',
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            exit;
        }
    }
    $query = "UPDATE svt_rooms SET panorama_image=?,panorama_video=? WHERE id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('ssi',$name_image,$name_video,$id_room);
        $smt->execute();
    }
    $panorama_image_gt = $name_image;
    include("../../services/generate_thumb.php");
    include("../../services/generate_pano_mobile.php");
}
if($change_json==1) {
    if($s3_enabled) {
        $name_json = str_replace($s3_url."viewer/panoramas/","",$panorama_json);
    } else {
        $name_json = str_replace("../viewer/panoramas/","",$panorama_json);
    }
    $query = "UPDATE svt_rooms SET panorama_json=? WHERE id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('si',$name_json,$id_room);
        $smt->execute();
    }
}
if($passcode!='keep_passcode') {
    if(empty($passcode)) {
        $query = "UPDATE svt_rooms SET passcode=NULL WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('i',$id_room);
            $smt->execute();
        }
    } else {
        $query = "UPDATE svt_rooms SET passcode=MD5(?) WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('si',$passcode,$id_room);
            $smt->execute();
        }
    }
}
if($apply_preset_to_vt==1) {
    $query = "UPDATE svt_rooms SET hfov=?,h_pitch=?,h_roll=?,allow_pitch=?,allow_hfov=?,min_pitch=?,max_pitch=?,min_yaw=?,max_yaw=?,haov=?,vaov=?,background_color=? WHERE id_virtualtour=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('iiiiiiiiiiisi', $hfov,$h_pitch,$h_roll,$allow_pitch,$allow_hfov,$min_pitch,$max_pitch,$min_yaw,$max_yaw,$haov,$vaov,$background_color,$id_virtualtour);
        $smt->execute();
    }
}
$query = "UPDATE svt_rooms SET name=?,logo=?,logo_height=?,yaw=?,pitch=?,hfov=?,h_pitch=?,h_roll=?,allow_pitch=?,allow_hfov=?,visible=?,visible_list=?,min_pitch=?,max_pitch=?,min_yaw=?,max_yaw=?,haov=?,vaov=?,northOffset=?,song=?,song_bg_volume=?,audio_track_enable=?,annotation_title=?,annotation_description=?,protect_type=?,passcode_title=?,passcode_description=?,transition_time=?,transition_zoom=?,transition_fadeout=?,transition_override=?,transition_effect=?,filters=?,effect=?,thumb_image=?,virtual_staging=?,main_view_tooltip=?,background_color=?,protect_send_email=?,protect_email=?,video_end_goto=?,protect_remember=?,lp_duration=?,lp_fade=?,song_loop=?,song_once=?,show_nadir=?,protect_mc_form=?,panorama_url=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssiddiiiiiiiiiiiiiisdisssssiiiissssissisiiiiiiissi',$name,$logo,$logo_height,$yaw,$pitch,$hfov,$h_pitch,$h_roll,$allow_pitch,$allow_hfov,$visible,$visible_list,$min_pitch,$max_pitch,$min_yaw,$max_yaw,$haov,$vaov,$northOffset,$song,$song_bg_volume,$audio_track_enable,$annotation_title,$annotation_description,$protect_type,$passcode_title,$passcode_description,$transition_time,$transition_zoom,$transition_fadeout,$transition_override,$transition_effect,$filters,$effect,$thumb_image,$virtual_staging,$main_view_tooltip,$background_color,$protect_send_email,$protect_email,$video_end_goto,$protect_remember,$lp_duration,$lp_fade,$song_loop,$song_once,$show_nadir,$protect_mc_form,$panorama_url,$id_room);
    $result = $smt->execute();
    if ($result) {
        save_input_langs($array_lang,'svt_rooms_lang','id_room',$id_room);
        generate_multires(false,$id_virtualtour);
        update_user_space_storage($id_user,false);
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}