<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$name = strip_tags($_POST['name']);
$n_virtual_tours = ($_POST['n_virtual_tours']=="") ? -1 : (int)$_POST['n_virtual_tours'];
$n_rooms = ($_POST['n_rooms']=="") ? -1 : (int)$_POST['n_rooms'];
$n_rooms_tour = ($_POST['n_rooms_tour']=="") ? -1 : (int)$_POST['n_rooms_tour'];
$n_markers = ($_POST['n_markers']=="") ? -1 : (int)$_POST['n_markers'];
$n_pois = ($_POST['n_pois']=="") ? -1 : (int)$_POST['n_pois'];
$n_gallery_images = ($_POST['n_gallery_images']=="") ? -1 : (int)$_POST['n_gallery_images'];
$n_ai_generate_month = ($_POST['n_ai_generate_month']=="") ? -1 : (int)$_POST['n_ai_generate_month'];
$days = ($_POST['days']=="") ? -1 : (int)$_POST['days'];
$max_file_size_upload = ($_POST['max_file_size_upload']=="") ? -1 : (int)$_POST['max_file_size_upload'];
$max_storace_space = ($_POST['max_storace_space']=="") ? -1 : (int)$_POST['max_storace_space'];
$create_landing = (int)$_POST['create_landing'];
$create_showcase = (int)$_POST['create_showcase'];
$create_globes = (int)$_POST['create_globes'];
$create_gallery = (int)$_POST['create_gallery'];
$create_presentation = (int)$_POST['create_presentation'];
$create_video360 = (int)$_POST['create_video360'];
$create_video_projects = (int)$_POST['create_video_projects'];
$enable_live_session = (int)$_POST['enable_live_session'];
$enable_meeting = (int)$_POST['enable_meeting'];
$enable_chat = (int)$_POST['enable_chat'];
$enable_voice_commands = (int)$_POST['enable_voice_commands'];
$enable_share = (int)$_POST['enable_share'];
$enable_device_orientation = (int)$_POST['enable_device_orientation'];
$enable_webvr = (int)$_POST['enable_webvr'];
$enable_logo = (int)$_POST['enable_logo'];
$enable_nadir_logo = (int)$_POST['enable_nadir_logo'];
$enable_song = (int)$_POST['enable_song'];
$enable_comments = (int)$_POST['enable_comments'];
$enable_forms = (int)$_POST['enable_forms'];
$enable_annotations = (int)$_POST['enable_annotations'];
$enable_panorama_video = (int)$_POST['enable_panorama_video'];
$enable_ai_room = (int)$_POST['enable_ai_room'];
$enable_rooms_multiple = (int)$_POST['enable_rooms_multiple'];
$enable_rooms_protect = (int)$_POST['enable_rooms_protect'];
$enable_info_box = (int)$_POST['enable_info_box'];
$enable_context_info = (int)$_POST['enable_context_info'];
$enable_maps = (int)$_POST['enable_maps'];
$enable_icons_library = (int)$_POST['enable_icons_library'];
$enable_media_library = (int)$_POST['enable_media_library'];
$enable_music_library = (int)$_POST['enable_music_library'];
$enable_sound_library = (int)$_POST['enable_sound_library'];
$enable_password_tour = (int)$_POST['enable_password_tour'];
$enable_expiring_dates = (int)$_POST['enable_expiring_dates'];
$enable_export_vt = (int)$_POST['enable_export_vt'];
$enable_download_slideshow = (int)$_POST['enable_download_slideshow'];
$enable_statistics = (int)$_POST['enable_statistics'];
$enable_auto_rotate = (int)$_POST['enable_auto_rotate'];
$enable_flyin = (int)$_POST['enable_flyin'];
$enable_multires = (int)$_POST['enable_multires'];
$enable_shop = (int)$_POST['enable_shop'];
$enable_dollhouse = (int)$_POST['enable_dollhouse'];
$enable_editor_ui = (int)$_POST['enable_editor_ui'];
$enable_custom_html = (int)$_POST['enable_custom_html'];
$enable_metatag = (int)$_POST['enable_metatag'];
$enable_loading_iv = (int)$_POST['enable_loading_iv'];
$enable_measurements = (int)$_POST['enable_measurements'];
$enable_multilanguage = (int)$_POST['enable_multilanguage'];
$enable_auto_translation = (int)$_POST['enable_auto_translation'];
$enable_poweredby = (int)$_POST['enable_poweredby'];
$price = str_replace(",",".",strip_tags($_POST['price']));
$price2 = str_replace(",",".",strip_tags($_POST['price2']));
if(empty($price)) $price=0;
if($price<0) $price=0;
$price = (float)$price;
if(empty($price2)) $price2=0;
if($price2<0) $price2=0;
$price2 = (float)$price2;
$currency = strip_tags($_POST['currency']);
$custom_features = strip_tags($_POST['custom_features']);
$visible = (int)$_POST['visible'];
$external_url = strip_tags($_POST['external_url']);
$frequency = strip_tags($_POST['frequency']);
$interval_count = $_POST['interval_count'];
if(empty($interval_count)) $interval_count=1;
if($interval_count<1) $interval_count=1;
if($interval_count>12) $interval_count=12;
$interval_count = (int)$interval_count;
$customize_menu = strip_tags($_POST['customize_menu']);
$expire_tours = (int)$_POST['expire_tours'];
$button_type = $_POST['button_type'];
$button_text = strip_tags($_POST['button_text']);
$button_icon = strip_tags($_POST['button_icon']);
$settings = get_settings();
if(($price>0) && ($settings['stripe_enabled'] || $settings['paypal_enabled']) && ($frequency=='recurring')) {
    $days = -1;
}
$query = "INSERT INTO svt_plans(price,price2,n_virtual_tours,n_rooms,n_rooms_tour,n_markers,n_pois,n_gallery_images,days,create_landing,create_gallery,create_presentation,create_video360,enable_live_session,max_file_size_upload,max_storage_space,enable_chat,enable_voice_commands,enable_share,enable_device_orientation,enable_webvr,enable_logo,enable_nadir_logo,enable_song,enable_forms,enable_annotations,enable_panorama_video,enable_rooms_multiple,enable_rooms_protect,enable_info_box,enable_context_info,enable_maps,enable_icons_library,enable_media_library,enable_music_library,enable_password_tour,enable_expiring_dates,enable_statistics,enable_auto_rotate,enable_flyin,enable_multires,enable_meeting,create_showcase,create_globes,enable_export_vt,enable_shop,enable_dollhouse,enable_editor_ui,enable_custom_html,enable_metatag,enable_loading_iv,enable_measurements,visible,interval_count,expire_tours,name,currency,custom_features,external_url,frequency,customize_menu,enable_download_slideshow,create_video_projects,button_type,button_text,button_icon,enable_comments,enable_ai_room,n_ai_generate_month,enable_sound_library,enable_multilanguage,enable_auto_translation,enable_poweredby) 
VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?); ";
if ($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ddiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiissssssiisssiiiiiii',$price,$price2,$n_virtual_tours,$n_rooms,$n_rooms_tour,$n_markers,$n_pois,$n_gallery_images,$days,$create_landing,$create_gallery,$create_presentation,$create_video360,$enable_live_session,$max_file_size_upload,$max_storace_space,$enable_chat,$enable_voice_commands,$enable_share,$enable_device_orientation,$enable_webvr,$enable_logo,$enable_nadir_logo,$enable_song,$enable_forms,$enable_annotations,$enable_panorama_video,$enable_rooms_multiple,$enable_rooms_protect,$enable_info_box,$enable_context_info,$enable_maps,$enable_icons_library,$enable_media_library,$enable_music_library,$enable_password_tour,$enable_expiring_dates,$enable_statistics,$enable_auto_rotate,$enable_flyin,$enable_multires,$enable_meeting,$create_showcase,$create_globes,$enable_export_vt,$enable_shop,$enable_dollhouse,$enable_editor_ui,$enable_custom_html,$enable_metatag,$enable_loading_iv,$enable_measurements,$visible,$interval_count,$expire_tours,$name,$currency,$custom_features,$external_url,$frequency,$customize_menu,$enable_download_slideshow,$create_video_projects,$button_type,$button_text,$button_icon,$enable_comments,$enable_ai_room,$n_ai_generate_month,$enable_sound_library,$enable_multilanguage,$enable_auto_translation,$enable_poweredby);
    $result = $smt->execute();
    if ($result) {
        $id = $mysqli->insert_id;
        ob_end_clean();
        echo json_encode(array("status"=>"ok","id"=>$id));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}