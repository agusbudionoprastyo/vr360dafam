<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
require_once("../../backend/functions.php");
$code = $_POST['code'];
$export_mode = $_POST['export_mode'];
$preview = $_POST['preview'];
$nostat = $_POST['nostat'];
$ip_visitor = $_POST['ip_visitor'];
$language = $_POST['language'];
$dollhouse = "";
$s3Client = null;
$s3_enabled = false;
$debug = false;
$array_vt_lang = array();
if($debug) {
    file_put_contents('log_get_rooms.txt','CODE:'.$code.PHP_EOL,FILE_APPEND);
    $time_start_all = hrtime(true);
}
$s3_url = "";
$query_vt = "SELECT v.id,v.external,v.external_url,v.name as name_virtualtour,v.id_user,v.form_enable,v.form_icon,v.form_content,v.auto_show_slider,v.nav_slider,v.sameAzimuth,v.arrows_nav,v.autorotate_speed,v.autorotate_inactivity,v.nadir_logo,v.nadir_size,v.song as song_bg,v.song_autoplay,v.song_bg_volume,v.voice_commands,v.compass,v.author,v.hfov as hfov_default,v.min_hfov as min_hfov_default,v.max_hfov as max_hfov_default,IF(v.info_box='' OR v.info_box IS NULL OR v.info_box='<p><br></p>',0,v.show_info) as show_info,IF(v.custom_content='' OR v.custom_content IS NULL OR v.custom_content='<div></div>',0,v.show_custom) as show_custom,IF(v.custom2_content='' OR v.custom2_content IS NULL OR v.custom2_content='<div></div>',0,v.show_custom2) as show_custom2,IF(v.custom3_content='' OR v.custom3_content IS NULL OR v.custom3_content='<div></div>',0,v.show_custom3) as show_custom3,IF(v.custom4_content='' OR v.custom4_content IS NULL OR v.custom4_content='<div></div>',0,v.show_custom4) as show_custom4,IF(v.custom5_content='' OR v.custom5_content IS NULL OR v.custom5_content='<div></div>',0,v.show_custom5) as show_custom5,IF(v.location_content='' OR v.location_content IS NULL,0,v.show_location) as show_location,v.show_gallery,v.fb_messenger,v.show_icons_toggle,v.show_measures_toggle,v.show_autorotation_toggle,v.show_nav_control,v.show_presentation,v.show_main_form,v.show_share,v.show_device_orientation,v.drag_device_orientation,v.show_webvr,v.show_audio,v.show_vt_title,v.show_logo,v.show_fullscreen,v.show_map,v.show_map_tour,v.live_session,v.show_annotations,v.show_list_alt,v.list_alt,v.intro_desktop,v.intro_desktop_hide,v.intro_mobile,v.intro_mobile_hide,v.presentation_inactivity,v.presentation_type,v.presentation_video,v.auto_presentation_speed,v.enable_multires,v.whatsapp_chat,v.whatsapp_number,v.transition_loading as transition_loading_v,v.transition_time as transition_time_v,v.transition_zoom as transition_zoom_v,v.transition_fadeout as transition_fadeout_v,v.transition_effect as transition_effect_v,v.meeting,v.keyboard_mode,v.preload_panoramas,v.click_anywhere,v.hide_markers,v.hover_markers,v.autoclose_menu,v.autoclose_list_alt,v.autoclose_slider,v.autoclose_map,v.pan_speed,v.pan_speed_mobile,v.friction,v.friction_mobile,v.zoom_friction,v.zoom_friction_mobile,v.snipcart_currency,v.enable_visitor_rt,v.enable_views_stat,v.interval_visitor_rt,v.show_dollhouse,v.dollhouse,v.dollhouse_glb,v.gallery_mode,v.presentation_loop,v.presentation_stop_click,v.presentation_stop_id_room,v.mobile_panoramas,v.presentation_view_pois,v.presentation_view_measures,v.initial_feedback,v.mouse_follow_feedback,v.gallery_params,v.flyin_duration,v.shop_type,v.woocommerce_store_url,v.woocommerce_customer_key,v.woocommerce_customer_secret,v.show_language,v.language as language_vt,v.languages_enabled,v.show_poweredby FROM svt_virtualtours AS v WHERE v.code = '$code' LIMIT 1;";
$result_vt = $mysqli->query($query_vt);
if($result_vt) {
    if ($result_vt->num_rows == 1) {
        $row = $result_vt->fetch_array(MYSQLI_ASSOC);
        $id_virtualtour = $row['id'];
        $query_lang = "SELECT * FROM svt_virtualtours_lang WHERE language='$language' AND id_virtualtour=$id_virtualtour";
        $result_lang = $mysqli->query($query_lang);
        if($result_lang) {
            if ($result_lang->num_rows == 1) {
                $row_lang = $result_lang->fetch_array(MYSQLI_ASSOC);
                unset($row_lang['id_virtualtour']);
                unset($row_lang['language']);
                $array_vt_lang=$row_lang;
            }
        }
        $query_lang = "SELECT language FROM svt_settings LIMIT 1;";
        $result_lang = $mysqli->query($query_lang);
        if($result_lang) {
            if ($result_lang->num_rows == 1) {
                $row_lang = $result_lang->fetch_array(MYSQLI_ASSOC);
                $default_language_settings = $row_lang['language'];
            }
        }
        if($debug) $time_start = hrtime(true);
        $s3_params = check_s3_tour_enabled($id_virtualtour);
        if(!empty($s3_params)) {
            $s3_bucket_name = $s3_params['bucket'];
            if($s3Client==null) {
                $s3Client = init_s3_client_no_wrapper($s3_params);
                if($s3Client==null) {
                    $s3_enabled = false;
                } else {
                    if(!empty($s3_params['custom_domain'])) {
                        $s3_url = "https://".$s3_params['custom_domain']."/";
                    } else {
                        try {
                            $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.');
                        } catch (Aws\Exception\S3Exception $e) {}
                    }
                    $s3_enabled = true;
                }
            } else {
                $s3_enabled = true;
            }
        }
        if($debug) {
            $time_end = hrtime(true);
            $execution_time = (($time_end - $time_start)/1e+6)/1000;
            file_put_contents('log_get_rooms.txt','S3 INIT:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
        }
        if(!empty($row['name_virtualtour']) && !empty($array_vt_lang['name'])) {
            $row['name_virtualtour']=$array_vt_lang['name'];
        }
        if(!empty($row['form_content']) && !empty($array_vt_lang['form_content'])) {
            $row['form_content']=$array_vt_lang['form_content'];
        }
        if(!empty($row['list_alt']) && !empty($array_vt_lang['list_alt'])) {
            $row['list_alt']=$array_vt_lang['list_alt'];
        }
        $external = $row['external'];
        $external_url = $row['external_url'];
        $background_color = $row['background_color'];
        $id_user = $row['id_user'];
        $name_virtualtour = $row['name_virtualtour'];
        $author = trim($row['author']);
        $hfov_default = $row['hfov_default'];
        $min_hfov = $row['min_hfov_default'];
        $max_hfov = $row['max_hfov_default'];
        $show_audio = $row['show_audio'];
        if($show_audio) {
            if($row['song']==null) $row['song']='';
            $song = $row['song_bg'];
            if($song==null) $song='';
            $song_autoplay = $row['song_autoplay'];
        } else {
            $row['song'] = '';
            $song='';
            $song_autoplay = false;
        }
        $song_bg_volume = $row['song_bg_volume'];
        $show_vt_title = $row['show_vt_title'];
        $show_logo = $row['show_logo'];
        $show_poweredby = $row['show_poweredby'];
        $nadir_logo = $row['nadir_logo'];
        if(empty($nadir_logo)) $nadir_logo='';
        $nadir_size = $row['nadir_size'];
        $autorotate_speed = $row['autorotate_speed']*2;
        $autorotate_inactivity = $row['autorotate_inactivity'];
        if($autorotate_speed==0) $autorotate_inactivity=0;
        $arrows_nav = $row['arrows_nav'];
        $show_info = $row['show_info'];
        $show_custom = $row['show_custom'];
        $show_custom2 = $row['show_custom2'];
        $show_custom3 = $row['show_custom3'];
        $show_custom4 = $row['show_custom4'];
        $show_custom5 = $row['show_custom5'];
        $show_location = $row['show_location'];
        $show_gallery = $row['show_gallery'];
        $show_facebook = $row['fb_messenger'];
        $show_icons_toggle = $row['show_icons_toggle'];
        $show_measures_toggle = $row['show_measures_toggle'];
        $show_autorotation_toggle = $row['show_autorotation_toggle'];
        $show_nav_control = $row['show_nav_control'];
        $show_presentation = $row['show_presentation'];
        $show_share = $row['show_share'];
        $show_device_orientation = $row['show_device_orientation'];
        $drag_device_orientation = $row['drag_device_orientation'];
        $show_webvr = $row['show_webvr'];
        $show_fullscreen = $row['show_fullscreen'];
        $show_map = $row['show_map'];
        $show_map_tour = $row['show_map_tour'];
        $show_annotations = $row['show_annotations'];
        $live_session = $row['live_session'];
        $show_list_alt = $row['show_list_alt'];
        $list_alt = $row['list_alt'];
        $intro_desktop = $row['intro_desktop'];
        if(empty($intro_desktop)) $intro_desktop = "";
        $intro_mobile = $row['intro_mobile'];
        if(empty($intro_mobile)) $intro_mobile = "";
        $intro_desktop_hide = $row['intro_desktop_hide'];
        $intro_mobile_hide = $row['intro_mobile_hide'];
        $voice_commands = $row['voice_commands'];
        $compass = $row['compass'];
        $sameAzimuth = $row['sameAzimuth'];
        $transition_loading = $row['transition_loading_v'];
        $transition_time = $row['transition_time_v'];
        $transition_zoom = $row['transition_zoom_v'];
        $transition_fadeout = $row['transition_fadeout_v'];
        $transition_effect = $row['transition_effect_v'];
        $auto_show_slider = $row['auto_show_slider'];
        $nav_slider = $row['nav_slider'];
        $presentation_inactivity = $row['presentation_inactivity'];
        $presentation_type = $row['presentation_type'];
        $presentation_video = $row['presentation_video'];
        $auto_presentation_speed = $row['auto_presentation_speed']*2;
        $show_main_form = $row['show_main_form'];
        $whatsapp_chat = $row['whatsapp_chat'];
        $whatsapp_number = $row['whatsapp_number'];
        if(empty($whatsapp_number)) $whatsapp_number='';
        if($show_main_form) {
            $form_enable = $row['form_enable'];
        } else {
            $form_enable = false;
        }
        $form_icon = $row['form_icon'];
        $form_content = $row['form_content'];
        if(empty($form_content)) {
            $form_enable=false;
        } else {
            $form_array = json_decode($form_content,true);
            $form_all_disabled = true;
            for($i=1;$i<=11;$i++) {
                if($form_array[$i]['enabled']) {
                    $form_all_disabled = false;
                }
            }
            if($form_all_disabled) $form_enable=false;
        }
        $enable_multires = $row['enable_multires'];
        $meeting = $row['meeting'];
        $keyboard_mode = $row['keyboard_mode'];
        $preload_panoramas = $row['preload_panoramas'];
        $click_anywhere = $row['click_anywhere'];
        $hide_markers = $row['hide_markers'];
        $hover_markers = $row['hover_markers'];
        $autoclose_menu = $row['autoclose_menu'];
        $autoclose_list_alt = $row['autoclose_list_alt'];
        $autoclose_slider = $row['autoclose_slider'];
        $autoclose_map = $row['autoclose_map'];
        $pan_speed = $row['pan_speed'];
        $pan_speed_mobile = $row['pan_speed_mobile'];
        $friction = $row['friction'];
        $friction_mobile = $row['friction_mobile'];
        $zoom_friction = $row['zoom_friction'];
        $zoom_friction_mobile = $row['zoom_friction_mobile'];
        $snipcart_currency = $row['snipcart_currency'];
        $enable_visitor_rt = $row['enable_visitor_rt'];
        $interval_visitor_rt = $row['interval_visitor_rt'];
        if(!$enable_visitor_rt) {
            $interval_visitor_rt = 20000;
        }
        $enable_views_stat = $row['enable_views_stat'];
        $dollhouse = $row['dollhouse'];
        $dollhouse_glb = (empty($row['dollhouse_glb'])) ? '' : $row['dollhouse_glb'];
        $show_dollhouse = $row['show_dollhouse'];
        if(empty($dollhouse) && empty($dollhouse_glb)) $show_dollhouse=0;
        $gallery_mode = $row['gallery_mode'];
        if($debug) $time_start = hrtime(true);
        if($s3_enabled) {
            if(!$s3Client->doesObjectExist($s3_bucket_name,'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4')) {
                $gallery_mode = 'images';
            }
        } else {
            if(!file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4')) {
                $gallery_mode = 'images';
            }
        }
        if($debug) {
            $time_end = hrtime(true);
            $execution_time = (($time_end - $time_start)/1e+6)/1000;
            file_put_contents('log_get_rooms.txt','GALLERY EXIST:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
        }
        $presentation_loop = $row['presentation_loop'];
        $presentation_stop_click = $row['presentation_stop_click'];
        $presentation_stop_id_room = $row['presentation_stop_id_room'];
        $mobile_panoramas = $row['mobile_panoramas'];
        $presentation_view_pois = $row['presentation_view_pois'];
        $presentation_view_measures = $row['presentation_view_measures'];
        $initial_feedback = $row['initial_feedback'];
        $mouse_follow_feedback = $row['mouse_follow_feedback'];
        $flyin_duration = $row['flyin_duration'];
        if(empty($flyin_duration)) $flyin_duration=2000;
        if(!empty($row['gallery_params'])) {
            $gallery_params = json_decode($row['gallery_params'],true);
            if(!isset($gallery_params['gallery_transition'])) {
                $gallery_params['gallery_transition']='swipe';
            }
            if(!isset($gallery_params['gallery_thumbs'])) {
                $gallery_params['gallery_thumbs']='bottomOverMedia';
            }
            if(!isset($gallery_params['gallery_autoplay'])) {
                $gallery_params['gallery_autoplay']=false;
            }
            if(!isset($gallery_params['gallery_slide_duration'])) {
                $gallery_params['gallery_slide_duration']=4;
            }
        } else {
            $gallery_params = array();
            $gallery_params['gallery_transition']='swipe';
            $gallery_params['gallery_thumbs']='bottomOverMedia';
            $gallery_params['gallery_autoplay']=false;
            $gallery_params['gallery_slide_duration']=4;
        }
        $shop_type = $row['shop_type'];
        $woocommerce_store_url = $row['woocommerce_store_url'];
        $woocommerce_customer_key = $row['woocommerce_customer_key'];
        $woocommerce_customer_secret = $row['woocommerce_customer_secret'];
        if($shop_type=='woocommerce') {
            if(empty($woocommerce_store_url) || empty($woocommerce_customer_key) || empty($woocommerce_customer_secret)) {
                $shop_type = 'snipcart';
            }
        }
        if(empty($row['languages_enabled'])) {
            $row['languages_enabled']=array();
        } else {
            $row['languages_enabled']=json_decode($row['languages_enabled'],true);
        }
        $count_languages_enabled = 0;
        foreach ($row['languages_enabled'] as $lang_enabled) {
            if($lang_enabled==1) {
                $count_languages_enabled++;
            }
        }
        $show_language = $row['show_language'];
        $default_language = $row['language_vt'];
        if(empty($default_language)) {
            $default_language = $default_language_settings;
        }
        if($count_languages_enabled>0) {
            if(array_key_exists($default_language,$row['languages_enabled'])) {
                $virtual_tour['languages_enabled'][$default_language]=1;
            }
        }
        $array_languages = array();
        foreach ($row['languages_enabled'] as $lang=>$enabled) {
            if($enabled==1) {
                array_push($array_languages,$lang);
            }
        }
        if(count($array_languages)<=1) $show_language=0;
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"invalid","error"=>$mysqli->error));
        exit;
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"invalid","error"=>$mysqli->error));
    exit;
}

$enable_rooms_multiple=true;
$enable_rooms_protect=true;
$query = "SELECT create_landing,create_gallery,create_presentation,enable_live_session,enable_meeting,enable_chat,enable_voice_commands,enable_share,enable_device_orientation,enable_webvr,enable_logo,enable_nadir_logo,enable_song,enable_forms,enable_annotations,enable_rooms_multiple,enable_rooms_protect,enable_info_box,enable_maps,enable_icons_library,enable_password_tour,enable_expiring_dates,enable_auto_rotate,enable_flyin,enable_multires,enable_dollhouse,enable_multilanguage FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        if(!$row['enable_live_session']) $live_session=0;
        if(!$row['enable_meeting']) $meeting=0;
        if(!$row['create_gallery']) $show_gallery=0;
        if(!$row['enable_info_box']) $show_info=0;
        if(!$row['enable_voice_commands']) $voice_commands=0;
        if(!$row['enable_chat']) {
            $show_facebook=0;
            $whatsapp_chat=0;
        }
        if(!$row['enable_song']) {
            $song="";
            $song_autoplay=false;
            $show_audio=false;
        }
        if(!$row['enable_maps']) {
            $show_map=0;
            $show_map_tour=0;
        }
        if(!$row['enable_annotations']) $show_annotations=0;
        if(!$row['enable_forms']) $form_enable=false;
        if(!$row['enable_share']) $show_share=0;
        if(!$row['enable_device_orientation']) $show_device_orientation=0;
        if(!$row['enable_webvr']) $show_webvr=0;
        if(!$row['create_presentation']) $show_presentation=0;
        if(!$row['enable_rooms_multiple']) $enable_rooms_multiple=false;
        if(!$row['enable_rooms_protect']) $enable_rooms_protect=false;
        if(!$row['enable_nadir_logo']) $nadir_logo='';
        if(!$row['enable_auto_rotate']) {
            $autorotate_speed=0;
            $autorotate_inactivity=0;
        }
        if(!$row['enable_multires']) $enable_multires=false;
        if(!$row['enable_dollhouse']) $show_dollhouse=false;
        if(!$row['enable_multilanguage']) $show_language=false;
    }
}

if($export_mode==1) {
    $enable_rooms_protect=false;
    $form_enable=false;
    $live_session=0;
}

$array_base64 = array();
$product_attributes = array();
$currency_settings = null;
$woocommerce_client = null;
$array_rooms = array();
$array_rooms_lang = array();
$array_rooms_alt_lang = array();
$array_markers_lang = array();
$array_pois_lang = array();
$array_products_lang = array();
if($external==0) {
    $query = "SELECT * FROM svt_rooms_lang WHERE language='$language' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_room = $row['id_room'];
                unset($row['id_room']);
                unset($row['language']);
                $array_rooms_lang[$id_room]=$row;
            }
        }
    }
    $query = "SELECT * FROM svt_rooms_alt_lang WHERE language='$language' AND id_room_alt IN (SELECT id FROM svt_rooms_alt WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1))";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_room_alt = $row['id_room_alt'];
                unset($row['id_room_alt']);
                unset($row['language']);
                $array_rooms_alt_lang[$id_room_alt]=$row;
            }
        }
    }
    $query = "SELECT * FROM svt_markers_lang WHERE language='$language' AND id_marker IN (SELECT id FROM svt_markers WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1));";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_marker = $row['id_marker'];
                unset($row['id_marker']);
                unset($row['language']);
                $array_markers_lang[$id_marker]=$row;
            }
        }
    }
    $query = "SELECT * FROM svt_pois_lang WHERE language='$language' AND id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1));";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_poi = $row['id_poi'];
                unset($row['id_poi']);
                unset($row['language']);
                $array_pois_lang[$id_poi]=$row;
            }
        }
    }
    $query = "SELECT * FROM svt_products_lang WHERE language='$language' AND id_product IN (SELECT id FROM svt_products WHERE id_virtualtour=$id_virtualtour);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_product = $row['id_product'];
                unset($row['id_product']);
                unset($row['language']);
                $array_products_lang[$id_product]=$row;
            }
        }
    }
    $query = "SELECT * FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1 ORDER BY priority ASC, id ASC";
    $result = $mysqli->query($query);
    $rooms = array();
    $array = array();
    $has_annotation = false;
    $has_measures = false;
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_room = $row['id'];
                array_push($array_rooms,$id_room);
                if(array_key_exists($id_room,$array_rooms_lang)) {
                    if(!empty($row['name']) && !empty($array_rooms_lang[$id_room]['name'])) {
                        $row['name']=$array_rooms_lang[$id_room]['name'];
                    }
                    if(!empty($row['annotation_title']) && !empty($array_rooms_lang[$id_room]['annotation_title'])) {
                        $row['annotation_title']=$array_rooms_lang[$id_room]['annotation_title'];
                    }
                    if(!empty($row['annotation_description']) && !empty($array_rooms_lang[$id_room]['annotation_description'])) {
                        $row['annotation_description']=$array_rooms_lang[$id_room]['annotation_description'];
                    }
                    if(!empty($row['passcode_title']) && !empty($array_rooms_lang[$id_room]['passcode_title'])) {
                        $row['passcode_title']=$array_rooms_lang[$id_room]['passcode_title'];
                    }
                    if(!empty($row['passcode_description']) && !empty($array_rooms_lang[$id_room]['passcode_description'])) {
                        $row['passcode_description']=$array_rooms_lang[$id_room]['passcode_description'];
                    }
                    if(!empty($row['main_view_tooltip']) && !empty($array_rooms_lang[$id_room]['main_view_tooltip'])) {
                        $row['main_view_tooltip']=$array_rooms_lang[$id_room]['main_view_tooltip'];
                    }
                }
                if(empty($row['annotation_title'])) $row['annotation_title']='';
                if(empty($row['annotation_description'])) {
                    $row['annotation_description']='';
                } else {
                    $row['annotation_description'] = nl2br($row['annotation_description']);
                }
                if($row['annotation_title']!='' || $row['annotation_description']!='') {
                    $has_annotation = true;
                }
                if(empty($row['logo'])) $row['logo']='';
                $query_m = "SELECT m.*,r.name as name_room_target,r.id as id_room_target, 'marker' as type,'marker' as object,i.id as id_icon_library, i.image as img_icon_library,i.id_virtualtour as id_vt_library,r.panorama_image FROM svt_markers AS m
                        JOIN svt_rooms AS r ON m.id_room_target = r.id
                        LEFT JOIN svt_icons as i ON i.id=m.id_icon_library
                        WHERE m.id_room = $id_room AND r.visible=1";
                $result_m = $mysqli->query($query_m);
                $markers = array();
                if($result_m) {
                    if ($result_m->num_rows > 0) {
                        while ($row_m = $result_m->fetch_array(MYSQLI_ASSOC)) {
                            $id_marker = $row_m['id'];
                            if(array_key_exists($id_marker,$array_markers_lang)) {
                                if (!empty($row_m['tooltip_text']) && !empty($array_markers_lang[$id_marker]['tooltip_text'])) {
                                    $row_m['tooltip_text'] = $array_markers_lang[$id_marker]['tooltip_text'];
                                }
                            }
                            $id_room_target = $row_m['id_room_target'];
                            if(array_key_exists($id_room_target,$array_rooms_lang)) {
                                if(!empty($row_m['name_room_target']) && !empty($array_rooms_lang[$id_room_target]['name'])) {
                                    $row_m['name_room_target']=$array_rooms_lang[$id_room_target]['name'];
                                }
                            }
                            if($row_m['embed_type']==null) $row_m['embed_type']='';
                            if($row_m['embed_params']==null) $row_m['embed_params']='';
                            if($row_m['sound']==null) $row_m['sound']='';
                            $img_icon_library = $row_m["img_icon_library"];
                            if(!empty($img_icon_library)) {
                                if($debug) $time_start = hrtime(true);
                                if($s3_enabled) {
                                    if(empty($row_p['id_vt_library'])) {
                                        $base64_img = convert_image_to_base64(dirname(__FILE__).'/../icons/'.$img_icon_library);
                                    } else {
                                        $base64_img = convert_image_to_base64($s3_url.'viewer/icons/'.$img_icon_library);
                                    }
                                } else {
                                    $base64_img = convert_image_to_base64(dirname(__FILE__).'/../icons/'.$img_icon_library);
                                }
                                if($debug) {
                                    $time_end = hrtime(true);
                                    $execution_time = (($time_end - $time_start)/1e+6)/1000;
                                    file_put_contents('log_get_rooms.txt','COVERT IMAGE BASE64:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                                }
                            } else {
                                if($row_m['show_room']==4) {
                                    $row_m['show_room']=0;
                                }
                                $row_m['img_icon_library']='';
                                $base64_img = '';
                            }
                            if(!array_key_exists($img_icon_library,$array_base64) && !empty($img_icon_library) && !empty($base64_img)) {
                                $array_base64[$img_icon_library] = $base64_img;
                            }
                            $markers[] = $row_m;
                        }
                    }
                }
                $array_woocommerce_ids = array();
                $array_woocommerce_products = array();
                if($shop_type=='woocommerce') {
                    $query_p = "SELECT DISTINCT p.content FROM svt_pois AS p WHERE p.type='product' AND p.id_room=$id_room;";
                    $result_p = $mysqli->query($query_p);
                    if($result_p) {
                        if ($result_p->num_rows > 0) {
                            while ($row_p = $result_p->fetch_array(MYSQLI_ASSOC)) {
                                if(!empty($row_p['content'])) {
                                    array_push($array_woocommerce_ids,$row_p['content']);
                                }
                            }
                        }
                    }
                    if(count($array_woocommerce_ids)>0) {
                        if($woocommerce_client==null) {
                            $woocommerce_client = init_woocommerce_api($woocommerce_store_url,$woocommerce_customer_key,$woocommerce_customer_secret);
                            if($woocommerce_client!==null) {
                                $product_attributes = get_woocommerce_attributes($woocommerce_client);
                                $currency_settings = get_woocommerce_currency($woocommerce_client);
                            }
                        }
                        $array_woocommerce_products = get_woocommerce_products_vt($woocommerce_client, $array_woocommerce_ids, $currency_settings);
                    }
                }
                $query_p = "SELECT p.*,'poi' as object,i.id as id_icon_library, i.image as img_icon_library,i.id_virtualtour as id_vt_library FROM svt_pois AS p 
                        LEFT JOIN svt_icons as i ON i.id=p.id_icon_library
                        WHERE p.id_room = $id_room";
                $result_p = $mysqli->query($query_p);
                if($result_p) {
                    if ($result_p->num_rows > 0) {
                        while ($row_p = $result_p->fetch_array(MYSQLI_ASSOC)) {
                            $id_poi = $row_p['id'];
                            if(array_key_exists($id_poi,$array_pois_lang)) {
                                if (!empty($row_p['embed_content']) && !empty($array_pois_lang[$id_poi]['embed_content'])) {
                                    $row_p['embed_content'] = $array_pois_lang[$id_poi]['embed_content'];
                                }
                                if (!empty($row_p['label']) && !empty($array_pois_lang[$id_poi]['label'])) {
                                    $row_p['label'] = $array_pois_lang[$id_poi]['label'];
                                }
                                if (!empty($row_p['tooltip_text']) && !empty($array_pois_lang[$id_poi]['tooltip_text'])) {
                                    $row_p['tooltip_text'] = $array_pois_lang[$id_poi]['tooltip_text'];
                                }
                                if (!empty($row_p['title']) && !empty($array_pois_lang[$id_poi]['title'])) {
                                    $row_p['title'] = $array_pois_lang[$id_poi]['title'];
                                }
                                if (!empty($row_p['description']) && !empty($array_pois_lang[$id_poi]['description'])) {
                                    $row_p['description'] = $array_pois_lang[$id_poi]['description'];
                                }
                                if (!empty($row_p['content']) && !empty($array_pois_lang[$id_poi]['content'])) {
                                    $row_p['content'] = $array_pois_lang[$id_poi]['content'];
                                }
                                if (!empty($row_p['params']) && !empty($array_pois_lang[$id_poi]['params'])) {
                                    $row_p['params'] = $array_pois_lang[$id_poi]['params'];
                                }
                            }
                            $img_icon_library = $row_p["img_icon_library"];
                            if(!empty($img_icon_library)) {
                                if($debug) $time_start = hrtime(true);
                                if($s3_enabled) {
                                    if(empty($row_p['id_vt_library'])) {
                                        $base64_img = convert_image_to_base64(dirname(__FILE__).'/../icons/'.$img_icon_library);
                                    } else {
                                        $base64_img = convert_image_to_base64($s3_url.'viewer/icons/'.$img_icon_library);
                                    }
                                } else {
                                    $base64_img = convert_image_to_base64(dirname(__FILE__).'/../icons/'.$img_icon_library);
                                }
                                if($debug) {
                                    $time_end = hrtime(true);
                                    $execution_time = (($time_end - $time_start)/1e+6)/1000;
                                    file_put_contents('log_get_rooms.txt','COVERT IMAGE BASE64:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                                }
                            } else {
                                if($row_p['style']==1) {
                                    $row_p["style"] = 0;
                                }
                                $row_p["img_icon_library"]='';
                                $base64_img = '';
                            }
                            if(!array_key_exists($img_icon_library,$array_base64) && !empty($img_icon_library) && !empty($base64_img)) {
                                $array_base64[$img_icon_library] = $base64_img;
                            }
                            if(empty($row_p['schedule'])) $row_p['schedule']='';
                            if($row_p['label']==null) $row_p['label']='';
                            if($row_p['embed_type']==null) $row_p['embed_type']='';
                            if($row_p['embed_params']==null) $row_p['embed_params']='';
                            if($row_p['sound']==null) $row_p['sound']='';
                            switch($row_p['type']) {
                                case 'gallery':
                                    $id_poi = $row_p['id'];
                                    $array_images = array();
                                    $gallery_order = 'sort';
                                    if(!empty($row_p['params'])) {
                                        try {
                                            $params_json = json_decode($row_p['params'],true);
                                            $gallery_order = $params_json['order'];
                                        } catch (Exception $e) {}
                                    }
                                    if($gallery_order=='random') {
                                        $query_g = "SELECT image,title,description FROM svt_poi_gallery WHERE id_poi=$id_poi ORDER BY RAND();";
                                    } else {
                                        $query_g = "SELECT image,title,description FROM svt_poi_gallery WHERE id_poi=$id_poi ORDER BY priority;";
                                    }
                                    $result_g = $mysqli->query($query_g);
                                    if($result_g) {
                                        if ($result_g->num_rows > 0) {
                                            $index_g = 1;
                                            while ($row_g = $result_g->fetch_array(MYSQLI_ASSOC)) {
                                                if((!empty($row_g['title'])) || (!empty($row_g['description']))) {
                                                    $array_images[] = array("ID"=>$index_g,"kind"=>"image","src"=>(($s3_enabled) ? $s3_url.'viewer/' : '')."gallery/".$row_g['image'],"srct"=>(($s3_enabled) ? $s3_url.'viewer/' : '')."gallery/thumb/".$row_g['image'],"title"=>"<div><h4>".$row_g['title']."</h4><p>".$row_g['description']."</p></div>");
                                                } else {
                                                    $array_images[] = array("ID"=>$index_g,"kind"=>"image","src"=>(($s3_enabled) ? $s3_url.'viewer/' : '')."gallery/".$row_g['image'],"srct"=>(($s3_enabled) ? $s3_url.'viewer/' : '')."gallery/thumb/".$row_g['image']);
                                                }
                                                $index_g++;
                                            }
                                        }
                                    }
                                    $row_p['content'] = $array_images;
                                    $markers[] = $row_p;
                                    break;
                                case 'object360':
                                    $id_poi = $row_p['id'];
                                    $array_object360 = array();
                                    $query_g = "SELECT MAX(image) as image,COUNT(*) as count_images FROM svt_poi_objects360 WHERE id_poi=$id_poi LIMIT 1;";
                                    $result_g = $mysqli->query($query_g);
                                    if($result_g) {
                                        if ($result_g->num_rows == 1) {
                                            $row_g = $result_g->fetch_array(MYSQLI_ASSOC);
                                            $array_object360['count_images'] = $row_g['count_images'];
                                            $tmp = explode(".",$row_g['image']);
                                            $ext = end($tmp);
                                            $tmp = explode("_",$tmp[0]);
                                            $array_object360['name_images'] = $tmp[0]."_".$tmp[1]."_{index}.".$ext;
                                        }
                                    }
                                    $row_p['content'] = $array_object360;
                                    $markers[] = $row_p;
                                    break;
                                case 'product':
                                    $id_product = $row_p['content'];
                                    if(!empty($id_product)) {
                                        $row_product = array();
                                        $array_images = array();
                                        switch($shop_type) {
                                            case 'snipcart':
                                                $query_product = "SELECT * FROM svt_products WHERE id=$id_product LIMIT 1;";
                                                $result_product = $mysqli->query($query_product);
                                                if($result_product) {
                                                    if ($result_product->num_rows == 1) {
                                                        $row_product = $result_product->fetch_array(MYSQLI_ASSOC);
                                                        $id_product = $row_product['id'];
                                                        if(empty($row_product['description'])) $row_product['description']='';
                                                        if(array_key_exists($id_product,$array_products_lang)) {
                                                            if (!empty($row_product['name']) && !empty($array_products_lang[$id_product]['name'])) {
                                                                $row_product['name'] = $array_products_lang[$id_product]['name'];
                                                            }
                                                            if (!empty($row_product['description']) && !empty($array_products_lang[$id_product]['description'])) {
                                                                $row_product['description'] = $array_products_lang[$id_product]['description'];
                                                            }
                                                            if (!empty($array_products_lang[$id_product]['button_text'])) {
                                                                $row_product['button_text'] = $array_products_lang[$id_product]['button_text'];
                                                            }
                                                        }
                                                        IF($row_product['purchase_type']!='cart' && !empty($row_product['custom_currency'])) {
                                                            $price = $row_product['custom_currency']." ".$row_product['price'];
                                                        } else {
                                                            switch ($snipcart_currency) {
                                                                case 'AED':
                                                                    $currency = "AED ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'ILS':
                                                                    $currency = "₪ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'RUB':
                                                                    $currency = "₽ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'AUD':
                                                                    $currency = "A$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',' ');
                                                                    break;
                                                                case 'BRL':
                                                                    $currency = "R$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'CAD':
                                                                    $currency = "C$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'CHF':
                                                                    $currency = "₣ ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'CNY':
                                                                    $currency = "¥ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'CZK':
                                                                    $currency = "Kč ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'CLP':
                                                                    $currency = "$ ";
                                                                    $price = $currency.number_format($row_product['price'],0,'.',',');
                                                                    break;
                                                                case 'JPY':
                                                                    $currency = "¥ ";
                                                                    $price = $currency.number_format($row_product['price'],0,'.',',');
                                                                    break;
                                                                case 'EUR':
                                                                    $currency = "€ ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'GBP':
                                                                    $currency = "£ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'IDR':
                                                                    $currency = "Rp ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'INR':
                                                                    $currency = "Rs ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'PLN':
                                                                    $currency = "zł ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'SEK':
                                                                    $currency = "kr ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'TRY':
                                                                    $currency = "₺ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'TJS':
                                                                    $currency = "SM ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'USD':
                                                                case 'ARS':
                                                                    $currency = "$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'HKD':
                                                                    $currency = "HK$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'SGD':
                                                                    $currency = "S$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'NGN':
                                                                    $currency = "₦ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'MXN':
                                                                    $currency = "Mex$ ";
                                                                    $price = $currency.number_format($row_product['price'],2,',','.');
                                                                    break;
                                                                case 'MYR':
                                                                    $currency = "RM ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'PHP':
                                                                    $currency = "₱ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'THB':
                                                                    $currency = "฿ ";
                                                                    $price = $currency.number_format($row_product['price'],2,'.',',');
                                                                    break;
                                                                case 'RWF':
                                                                    $currency = "FRw ";
                                                                    $price = $currency.number_format($row_product['price'],0,'',',');
                                                                    break;
                                                                case 'VND':
                                                                    $currency = "₫ ";
                                                                    $price = $currency.number_format($row_product['price'],0,'.',',');
                                                                    break;
                                                                case 'PYG':
                                                                    $currency = "₲ ";
                                                                    $price = $currency.number_format($row_product['price'],0,'.',',');
                                                                    break;
                                                                case 'ZAR':
                                                                    $currency = "R ";
                                                                    $price = $currency.number_format($row_product['price'],2,',',' ');
                                                                    break;
                                                            }
                                                        }
                                                        $row_product['price_html']=$price;
                                                    }
                                                }
                                                $row_product['source']='snipcart';
                                                $query_product_images = "SELECT image FROM svt_product_images WHERE id_product=$id_product ORDER BY priority;";
                                                $result_product_images = $mysqli->query($query_product_images);
                                                if($result_product_images) {
                                                    if ($result_product_images->num_rows > 0) {
                                                        while ($row_product_images = $result_product_images->fetch_array(MYSQLI_ASSOC)) {
                                                            $array_images[] = array("src"=>"products/".$row_product_images['image'],"src_thumb"=>"products/thumb/".$row_product_images['image']);
                                                        }
                                                    }
                                                }
                                                break;
                                            case 'woocommerce':
                                                if($woocommerce_client!==null) {
                                                    if(array_key_exists($id_product,$array_woocommerce_products)) {
                                                        $product = $array_woocommerce_products[$id_product];
                                                        $row_product = array();
                                                        $row_product['id']=$id_product;
                                                        $row_product['name']=$product['name'];
                                                        $row_product['source']='woocommerce';
                                                        $row_product['type']=$product['type'];
                                                        $row_product['attributes']=$product['attributes'];
                                                        $row_product['variations']=$product['variations'];
                                                        $row_product['description']=$product['description'];
                                                        $row_product['price_html']=$product['price'];
                                                        $row_product['external_url']=$product['external_url'];
                                                        $row_product['button_text']=$product['button_text'];
                                                        $row_product['grouped_products']=$product['grouped_products'];
                                                        $row_product['status']=$product['stock_status'];
                                                        foreach ($product['images'] as $image) {
                                                            $array_images[] = array("src"=>$image,"src_thumb"=>$image);
                                                        }
                                                    }
                                                }
                                                break;
                                        }
                                        $row_p['product'] = $row_product;
                                        $row_p['product_images'] = $array_images;
                                        $markers[] = $row_p;
                                    }
                                    break;
                                case 'html_sc':
                                    $row_p['content'] = htmlspecialchars_decode($row_p['content']);
                                    $markers[] = $row_p;
                                    break;
                                default:
                                    switch ($row_p['embed_type']) {
                                        case 'image':
                                        case 'video':
                                            if(!empty($row_p['embed_content'])) {
                                                $markers[] = $row_p;
                                            }
                                            break;
                                        case 'gallery':
                                            $id_poi = $row_p['id'];
                                            $array_images = array();
                                            $gallery_order = 'sort';
                                            if(!empty($row_p['embed_params'])) {
                                                try {
                                                    $params_json = json_decode($row_p['embed_params'],true);
                                                    $gallery_order = $params_json['order'];
                                                } catch (Exception $e) {}
                                            }
                                            if($gallery_order=='random') {
                                                $query_g = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi ORDER BY RAND();";
                                            } else {
                                                $query_g = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi ORDER BY priority;";
                                            }
                                            $result_g = $mysqli->query($query_g);
                                            if($result_g) {
                                                if ($result_g->num_rows > 0) {
                                                    while ($row_g = $result_g->fetch_array(MYSQLI_ASSOC)) {
                                                        $array_images[] = "gallery/".$row_g['image'];
                                                    }
                                                }
                                            }
                                            $row_p['embed_content'] = $array_images;
                                            $markers[] = $row_p;
                                            break;
                                        case 'text':
                                            if($row_p['embed_type']=='text') {
                                                if (strpos($row_p['embed_content'], 'border-width') === false) {
                                                    $row_p['embed_content'] = $row_p['embed_content']." border-width:0px;";
                                                }
                                            }
                                            $markers[] = $row_p;
                                            break;
                                        default:
                                            $markers[] = $row_p;
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                }
                $query_ms = "SELECT * FROM svt_measures WHERE id_room = $id_room";
                $result_ms = $mysqli->query($query_ms);
                $measures = array();
                if($result_ms) {
                    if ($result_ms->num_rows > 0) {
                        $has_measures = true;
                        while ($row_ms = $result_ms->fetch_array(MYSQLI_ASSOC)) {
                            $measures[] = $row_ms;
                        }
                    }
                }
                $row['markers'] = $markers;
                $row['measures'] = $measures;
                $array_rooms_alt = array();
                if($enable_rooms_multiple) {
                    $query_ra = "SELECT * FROM svt_rooms_alt WHERE id_room = $id_room ORDER BY priority;";
                    $result_ra = $mysqli->query($query_ra);
                    if($result_ra) {
                        if ($result_ra->num_rows > 0) {
                            while ($row_ra = $result_ra->fetch_array(MYSQLI_ASSOC)) {
                                $id_room_alt = $row_ra['id'];
                                if(array_key_exists($id_room_alt,$array_rooms_alt_lang)) {
                                    if (!empty($row_ra['view_tooltip']) && !empty($array_rooms_alt_lang[$id_room_alt]['view_tooltip'])) {
                                        $row_ra['view_tooltip'] = $array_rooms_alt_lang[$id_room_alt]['view_tooltip'];
                                    }
                                }
                                $room_pano_ra = str_replace('.jpg','',$row_ra['panorama_image']);
                                if($enable_multires) {
                                    if(!empty($row_ra['multires_config'])) {
                                        $exist_multires_config = true;
                                    } else {
                                        if($debug) $time_start = hrtime(true);
                                        if($s3_enabled) {
                                            $multires_config_file = $s3_url.'viewer/panoramas/multires/'.$room_pano_ra.'/config.json';
                                            $exist_multires_config = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/multires/'.$room_pano_ra.'/config.json');
                                        } else {
                                            $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano_ra.DIRECTORY_SEPARATOR.'config.json';
                                            $exist_multires_config = file_exists($multires_config_file);
                                        }
                                        if($debug) {
                                            $time_end = hrtime(true);
                                            $execution_time = (($time_end - $time_start)/1e+6)/1000;
                                            file_put_contents('log_get_rooms.txt','MULTIRES CONFIG EXIST:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                                        }
                                    }
                                    if($exist_multires_config) {
                                        if(!empty($row_ra['multires_config'])) {
                                            $multires_array=json_decode($row_ra['multires_config'],true);
                                        } else {
                                            if($debug) $time_start = hrtime(true);
                                            $multires_tmp = file_get_contents($multires_config_file);
                                            $multires_tmp = str_replace("'","\'",$multires_tmp);
                                            $mysqli->query("UPDATE svt_rooms_alt SET multires_config='$multires_tmp' WHERE id=".$row_ra['id'].";");
                                            $multires_array = json_decode($multires_tmp,true);
                                            if($debug) {
                                                $time_end = hrtime(true);
                                                $execution_time = (($time_end - $time_start)/1e+6)/1000;
                                                file_put_contents('log_get_rooms.txt','MULTIRES CONFIG LOAD:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                                            }
                                        }
                                        $multires_config = $multires_array['multiRes'];
                                        if($s3_enabled) {
                                            $multires_config['basePath'] = $s3_url.'viewer/panoramas/multires/'.$room_pano_ra;
                                        } else {
                                            $multires_config['basePath'] = 'panoramas/multires/'.$room_pano_ra;
                                        }
                                        $row_ra['multires']=1;
                                        $row_ra['multires_config']=$multires_config;
                                        if($s3_enabled) {
                                            $row_ra['multires_dir'] = $s3_url.'viewer/panoramas/multires/'.$room_pano_ra;
                                        } else {
                                            $row_ra['multires_dir']='panoramas/multires/'.$room_pano_ra;
                                        }
                                    } else {
                                        $row_ra['multires']=0;
                                        $row_ra['multires_config']='';
                                        $row_ra['multires_dir']='';
                                    }
                                } else {
                                    $row_ra['multires']=0;
                                    $row_ra['multires_config']='';
                                    $row_ra['multires_dir']='';
                                }
                                if($debug) $time_start = hrtime(true);
                                if($s3_enabled) {
                                    $exist_mobile_pano_ra = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/mobile/'.$row_ra['panorama_image']);
                                } else {
                                    $pano_mobile_ra = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'mobile'.DIRECTORY_SEPARATOR.$row_ra['panorama_image'];
                                    $exist_mobile_pano_ra = file_exists($pano_mobile_ra);
                                }
                                if($debug) {
                                    $time_end = hrtime(true);
                                    $execution_time = (($time_end - $time_start)/1e+6)/1000;
                                    file_put_contents('log_get_rooms.txt','PANO MOBILE EXIST:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                                }
                                if($exist_mobile_pano_ra && $mobile_panoramas) {
                                    $row_ra['pano_mobile']=1;
                                } else {
                                    $row_ra['pano_mobile']=0;
                                }
                                $array_rooms_alt[] = $row_ra;
                            }
                        }
                    }
                }
                $row['array_rooms_alt'] = $array_rooms_alt;
                if(count($array_rooms_alt)==0) $row['virtual_staging']=0;
                $room_pano = str_replace('.jpg','',$row['panorama_image']);
                if($enable_multires) {
                    if(!empty($row['multires_config'])) {
                        $exist_multires_config = true;
                    } else {
                        if($debug) $time_start = hrtime(true);
                        if($s3_enabled) {
                            $multires_config_file = $s3_url.'viewer/panoramas/multires/'.$room_pano.'/config.json';
                            $exist_multires_config = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/multires/'.$room_pano.'/config.json');
                        } else {
                            $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
                            $exist_multires_config = file_exists($multires_config_file);
                        }
                        if($debug) {
                            $time_end = hrtime(true);
                            $execution_time = (($time_end - $time_start)/1e+6)/1000;
                            file_put_contents('log_get_rooms.txt','MULTIRES CONFIG EXIST:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                        }
                    }
                    if($exist_multires_config) {
                        if(!empty($row['multires_config'])) {
                            $multires_array=json_decode($row['multires_config'],true);
                        } else {
                            if($debug) $time_start = hrtime(true);
                            $multires_tmp=file_get_contents($multires_config_file);
                            $multires_tmp = str_replace("'","\'",$multires_tmp);
                            $mysqli->query("UPDATE svt_rooms SET multires_config='$multires_tmp' WHERE id=".$row['id'].";");
                            $multires_array=json_decode($multires_tmp,true);
                            if($debug) {
                                $time_end = hrtime(true);
                                $execution_time = (($time_end - $time_start)/1e+6)/1000;
                                file_put_contents('log_get_rooms.txt','MULTIRES CONFIG LOAD:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                            }
                        }
                        $multires_config=$multires_array['multiRes'];
                        if($s3_enabled) {
                            $multires_config['basePath'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                        } else {
                            $multires_config['basePath'] = 'panoramas/multires/'.$room_pano;
                        }
                        $row['multires']=1;
                        $row['multires_config']=$multires_config;
                        if($s3_enabled) {
                            $row['multires_dir'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                        } else {
                            $row['multires_dir']='panoramas/multires/'.$room_pano;
                        }
                    } else {
                        $row['multires']=0;
                        $row['multires_config']='';
                        $row['multires_dir']='';
                    }
                } else {
                    $row['multires']=0;
                    $row['multires_config']='';
                    $row['multires_dir']='';
                }
                $row['protect_pc']="";
                if($enable_rooms_protect) {
                    switch($row['protect_type']) {
                        case 'none':
                            $row['protected'] = 0;
                            break;
                        case 'passcode':
                            if(empty($row['passcode'])) {
                                $row['protected'] = 0;
                            } else {
                                $row['protect_pc'] = substr($row['passcode'], 0, 3).substr($row['passcode'], -3);
                                $row['protected'] = 1;
                            }
                            break;
                        case 'leads':
                            $row['protected'] = 1;
                            break;
                        case 'mailchimp':
                            if(empty($row['protect_mc_form'])) {
                                $row['protected'] = 0;
                            } else {
                                $row['protect_mc_form'] = preg_replace('/fnames\[(\d+)\]=(\w+);/', "fnames[$1]='$2';", $row['protect_mc_form']);
                                $row['protect_mc_form'] = preg_replace('/ftypes\[(\d+)\]=(\w+);/', "ftypes[$1]='$2';", $row['protect_mc_form']);
                                $row['protect_mc_form'] = str_replace(';,',';',$row['protect_mc_form']);
                                $row['protect_mc_form'] .= '<script>
                                        const targetNode_mc_room = document.querySelector(\'#lead_mc_form_room #mce-success-response\');
                                        var change_mc_room = false;
                                        const observer_mc_room = new MutationObserver(function(mutationsList, observer) {
                                            mutationsList.forEach(mutation => {
                                                if (mutation.attributeName === \'style\' && targetNode_mc_room.style.display === \'block\') {
                                                    if(!change_mc_room) {
                                                        setTimeout(function() {
                                                            check_mc_subscribe_room();
                                                        },1000);
                                                        observer.disconnect();
                                                        change_mc_room = true;
                                                    }
                                                }
                                            });
                                        });
                                        const config_mc_room = { attributes: true };
                                        observer_mc_room.observe(targetNode_mc_room, config_mc_room);
                                    </script>';
                                $row['protected'] = 1;
                            }
                            break;
                    }
                } else {
                    $row['protect_type'] = 'none';
                    $row['protected'] = 0;
                }
                unset($row['passcode']);
                if($show_audio) {
                    if(empty($row['song'])) $row['song']='';
                } else {
                    $row['song']='';
                }
                if(empty($row['filters'])) $row['filters']='';
                if(empty($row['thumb_image'])) $row['thumb_image']='';
                if($debug) $time_start = hrtime(true);
                if($s3_enabled) {
                    $exist_mobile_pano = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/mobile/'.$row['panorama_image']);
                } else {
                    $pano_mobile = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'mobile'.DIRECTORY_SEPARATOR.$row['panorama_image'];
                    $exist_mobile_pano = file_exists($pano_mobile);
                }
                if($debug) {
                    $time_end = hrtime(true);
                    $execution_time = (($time_end - $time_start)/1e+6)/1000;
                    file_put_contents('log_get_rooms.txt','PANO MOBILE EXIST:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                }
                if($exist_mobile_pano && $mobile_panoramas) {
                    $row['pano_mobile']=1;
                } else {
                    $row['pano_mobile']=0;
                }
                if($debug) $time_start = hrtime(true);
                if($s3_enabled) {
                    $exist_lowres_pano = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/lowres/'.$row['panorama_image']);
                } else {
                    $pano_lowres = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'lowres'.DIRECTORY_SEPARATOR.$row['panorama_image'];
                    $exist_lowres_pano = file_exists($pano_lowres);
                }
                if($debug) {
                    $time_end = hrtime(true);
                    $execution_time = (($time_end - $time_start)/1e+6)/1000;
                    file_put_contents('log_get_rooms.txt','PANO LOWRES EXIST:'.$execution_time.' s'.PHP_EOL,FILE_APPEND);
                }
                if($exist_lowres_pano) {
                    $row['panorama_3d']='lowres/'.$row['panorama_image'];
                } else {
                    $row['panorama_3d']=$row['panorama_image'];
                }
                if($row['hfov']==0) {
                    $row['hfov']=$hfov_default;
                } else if($row['hfov']>$max_hfov) {
                    $row['hfov']=$max_hfov;
                } else if($row['hfov']<$min_hfov) {
                    $row['hfov']=$min_hfov;
                }
                $rooms[] = $row;
                $array[$row['id']]=$row['name'];
            }
            if(!$has_annotation) $show_annotations=0;
            if(!$has_measures) $show_measures_toggle=0;
            $array_list_alt = array();
            $array_id_rooms = array();
            if ($list_alt == '') {
                foreach ($rooms as $room) {
                    array_push($array_list_alt,["id"=>$room['id'],"type"=>"room","hide"=>"0","name"=>$room['name']]);
                }
            } else {
                $list_alt_array = json_decode($list_alt, true);
                foreach ($list_alt_array as $item) {
                    switch ($item['type']) {
                        case 'room':
                            if(array_key_exists($item['id'],$array)) {
                                array_push($array_list_alt, ["id" => $item['id'], "type" => "room", "hide" => $item['hide'], "name" => $array[$item['id']]]);
                            }
                            array_push($array_id_rooms,$item['id']);
                            break;
                        case 'category':
                            $childrens = array();
                            foreach ($item['children'] as $children) {
                                if ($children['type'] == "room") {
                                    if(array_key_exists($children['id'],$array)) {
                                        array_push($childrens, ["id" => $children['id'], "type" => "room", "hide" => $children['hide'], "name" => $array[$children['id']]]);
                                    }
                                    array_push($array_id_rooms,$children['id']);
                                }
                            }
                            array_push($array_list_alt, ["id" => $item['id'], "type" => "category", "name" => $item['cat'], "childrens" => $childrens]);
                            break;
                    }
                }
                foreach ($rooms as $room) {
                    $id_room = $room['id'];
                    if(!in_array($id_room,$array_id_rooms)) {
                        array_push($array_list_alt,["id"=>$room['id'],"type"=>"room","hide"=>"0","name"=>$room['name']]);
                    }
                }
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"invalid","error"=>$mysqli->error));
            exit;
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"invalid","error"=>$mysqli->error));
        exit;
    }
} else {
    $rooms = array();
    $array_rooms_alt = array();
    $array_list_alt = array();
}
if(!empty($dollhouse)) {
    $dollhouse_array = json_decode($dollhouse, true);
    $rooms_to_delete = array();
    foreach ($dollhouse_array['rooms'] as $key => $room) {
        $id_room = $room['id'];
        if(!in_array($id_room,$array_rooms)) {
            array_push($rooms_to_delete,$key);
        }
    }
    foreach ($rooms_to_delete as $room_to_delete) {
        if (isset($dollhouse_array['rooms'][$room_to_delete])) {
            unset($dollhouse_array['rooms'][$room_to_delete]);
        }
    }
    $dollhouse_array['rooms'] = array_values($dollhouse_array['rooms']);
    if(empty($dollhouse_glb) && count($dollhouse_array['rooms'])==0) $show_dollhouse=0;
    $dollhouse = json_encode($dollhouse_array);
}
if($preview==0 && $nostat==0) {
    $mysqli->query("INSERT INTO svt_access_log(id_virtualtour,date_time,ip) VALUES($id_virtualtour,NOW(),'$ip_visitor');");
}
if($debug) {
    $time_end_all = hrtime(true);
    $execution_time = (($time_end_all - $time_start_all)/1e+6)/1000;
    file_put_contents('log_get_rooms.txt','TIME SCRIPT:'.$execution_time.' s'.PHP_EOL.PHP_EOL,FILE_APPEND);
}
ob_end_clean();
echo json_encode(array("status"=>"ok",
    "rooms"=>$rooms,
    "id_virtualtour"=>$id_virtualtour,
    "array_base64"=>$array_base64,
    "external"=>$external,
    "external_url"=>$external_url,
    "name_virtualtour"=>$name_virtualtour,
    "song"=>$song,
    "song_bg_volume"=>$song_bg_volume,
    "song_autoplay"=>$song_autoplay,
    "nadir_logo"=>$nadir_logo,
    "nadir_size"=>$nadir_size,
    "autorotate_inactivity"=>$autorotate_inactivity,
    "autorotate_speed"=>$autorotate_speed,
    "arrows_nav"=>$arrows_nav,
    "voice_commands"=>$voice_commands,
    "compass"=>$compass,
    "sameAzimuth"=>$sameAzimuth,
    "auto_show_slider"=>$auto_show_slider,
    "nav_slider"=>$nav_slider,
    "form_enable"=>$form_enable,
    "form_icon"=>$form_icon,
    "form_content"=>$form_content,
    "author"=>$author,
    "hfov"=>$hfov_default,
    "min_hfov"=>$min_hfov,
    "max_hfov"=>$max_hfov,
    "show_audio"=>$show_audio,
    "show_logo"=>$show_logo,
    "show_poweredby"=>$show_poweredby,
    "show_vt_title"=>$show_vt_title,
    "show_gallery"=>$show_gallery,
    "show_info"=>$show_info,
    "show_dollhouse"=>$show_dollhouse,
    "show_custom"=>$show_custom,
    "show_custom2"=>$show_custom2,
    "show_custom3"=>$show_custom3,
    "show_custom4"=>$show_custom4,
    "show_custom5"=>$show_custom5,
    "show_location"=>$show_location,
    "show_facebook"=>$show_facebook,
    "show_icons_toggle"=>$show_icons_toggle,
    "show_measures_toggle"=>$show_measures_toggle,
    "show_autorotation_toggle"=>$show_autorotation_toggle,
    "show_nav_control"=>$show_nav_control,
    "show_presentation"=>$show_presentation,
    "show_share"=>$show_share,
    "show_device_orientation"=>$show_device_orientation,
    "drag_device_orientation"=>$drag_device_orientation,
    "show_webvr"=>$show_webvr,
    "show_fullscreen"=>$show_fullscreen,
    "show_map"=>$show_map,
    "show_map_tour"=>$show_map_tour,
    "show_language"=>$show_language,
    "live_session"=>$live_session,
    "meeting"=>$meeting,
    "show_annotations"=>$show_annotations,
    "show_list_alt"=>$show_list_alt,
    "list_alt"=>$array_list_alt,
    "intro_desktop"=>$intro_desktop,
    "intro_mobile"=>$intro_mobile,
    "intro_desktop_hide"=>$intro_desktop_hide,
    "intro_mobile_hide"=>$intro_mobile_hide,
    "presentation_inactivity"=>$presentation_inactivity,
    "presentation_type"=>$presentation_type,
    "presentation_video"=>$presentation_video,
    "auto_presentation_speed"=>$auto_presentation_speed,
    "presentation_loop"=>$presentation_loop,
    "presentation_stop_click"=>$presentation_stop_click,
    "presentation_stop_id_room"=>$presentation_stop_id_room,
    "presentation_view_pois"=>$presentation_view_pois,
    "presentation_view_measures"=>$presentation_view_measures,
    "whatsapp_chat"=>$whatsapp_chat,
    "whatsapp_number"=>$whatsapp_number,
    "transition_loading"=>$transition_loading,
    "transition_time"=>$transition_time,
    "transition_zoom"=>$transition_zoom,
    "transition_fadeout"=>$transition_fadeout,
    "transition_effect"=>$transition_effect,
    "keyboard_mode"=>$keyboard_mode,
    "preload_panoramas"=>$preload_panoramas,
    "click_anywhere"=>$click_anywhere,
    "hide_markers"=>$hide_markers,
    "hover_markers"=>$hover_markers,
    "autoclose_menu"=>$autoclose_menu,
    "autoclose_list_alt"=>$autoclose_list_alt,
    "autoclose_slider"=>$autoclose_slider,
    "autoclose_map"=>$autoclose_map,
    "pan_speed"=>$pan_speed,
    "pan_speed_mobile"=>$pan_speed_mobile,
    "friction"=>$friction,
    "friction_mobile"=>$friction_mobile,
    "zoom_friction"=>$zoom_friction,
    "zoom_friction_mobile"=>$zoom_friction_mobile,
    "enable_visitor_rt"=>$enable_visitor_rt,
    "enable_views_stat"=>$enable_views_stat,
    "interval_visitor_rt"=>$interval_visitor_rt,
    "dollhouse"=>$dollhouse,
    "dollhouse_glb"=>$dollhouse_glb,
    "gallery_mode"=>$gallery_mode,
    "gallery_params"=>$gallery_params,
    "initial_feedback"=>$initial_feedback,
    "mouse_follow_feedback"=>$mouse_follow_feedback,
    "flyin_duration"=>$flyin_duration,
    "shop_type"=>$shop_type,
    "product_attributes"=>$product_attributes,
    "array_languages"=>$array_languages,
    "default_language"=>$default_language
));