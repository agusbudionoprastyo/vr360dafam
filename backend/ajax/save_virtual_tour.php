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
$code = "";
$logo_exist = "";
$favicon_ok = 1;
$query = "SELECT code,logo FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $logo_exist = $row['logo'];
    }
}
$name = strip_tags($_POST['name']);
$author = strip_tags($_POST['author']);
$id_user = (int)$_POST['id_user'];
$hfov = (int)$_POST['hfov'];
$min_hfov = (int)$_POST['min_hfov'];
$max_hfov = (int)$_POST['max_hfov'];
$hfov_mobile_ratio = (float)$_POST['hfov_mobile_ratio'];
$pan_speed = (float)$_POST['pan_speed'];
$pan_speed_mobile = (float)$_POST['pan_speed_mobile'];
$friction = (float)$_POST['friction'];
$friction_mobile = (float)$_POST['friction_mobile'];
$zoom_friction = (float)$_POST['zoom_friction'];
$zoom_friction_mobile = (float)$_POST['zoom_friction_mobile'];
$mouse_follow_feedback = (float)$_POST['mouse_follow_feedback'];
$zoom_to_pointer = (int)$_POST['zoom_to_pointer'];
$quality_viewer = (float)$_POST['quality_viewer'];
$song = strip_tags($_POST['song']);
$flyin = (int)$_POST['flyin'];
$flyin_duration = (int)$_POST['flyin_duration'];
if(empty($flyin_duration)) $flyin_duration=2000;
$logo = strip_tags($_POST['logo']);
$link_logo = strip_tags($_POST['link_logo']);
$background_image = strip_tags($_POST['background_image']);
$background_video = strip_tags($_POST['background_video']);
$background_image_mobile = strip_tags($_POST['background_image_mobile']);
$background_video_mobile = strip_tags($_POST['background_video_mobile']);
$background_video_delay = $_POST['background_video_delay'];
if(empty($background_video_delay)) $background_video_delay=0;
$background_video_delay = (int)$background_video_delay;
$background_video_delay_mobile = $_POST['background_video_delay_mobile'];
if(empty($background_video_delay_mobile)) $background_video_delay_mobile=0;
$background_video_delay_mobile = (int)$background_video_delay_mobile;
$nadir_logo = strip_tags($_POST['nadir_logo']);
$intro_desktop = strip_tags($_POST['intro_desktop']);
$intro_mobile = strip_tags($_POST['intro_mobile']);
$intro_desktop_hide = $_POST['intro_desktop_hide'];
$intro_mobile_hide = $_POST['intro_mobile_hide'];
if(empty($intro_desktop_hide) || $intro_desktop_hide<0) $intro_desktop_hide=0;
if(empty($intro_mobile_hide) || $intro_mobile_hide<0) $intro_mobile_hide=0;
$intro_desktop_hide = (int)$intro_desktop_hide;
$intro_mobile_hide = (int)$intro_mobile_hide;
$auto_start = (int)$_POST['auto_start'];
$hide_loading = (int)$_POST['hide_loading'];
$sameAzimuth = (int)$_POST['sameAzimuth'];
$description = strip_tags($_POST['description']);
$ga_tracking_id = strip_tags($_POST['ga_tracking_id']);
$compress_jpg = $_POST['compress_jpg'];
if($compress_jpg=="") $compress_jpg=90;
$compress_jpg = (int)$compress_jpg;
$max_width_compress = $_POST['max_width_compress'];
if($max_width_compress=="") $max_width_compress=0;
$max_width_compress = (int)$max_width_compress;
$enable_multires = (int)$_POST['enable_multires'];
$preload_panoramas = (int)$_POST['preload_panoramas'];
$mobile_panoramas = (int)$_POST['mobile_panoramas'];
$keep_original_panorama = (int)$_POST['keep_original_panorama'];
$transition_time = $_POST['transition_time'];
if($transition_time=='') $transition_time = 250;
$transition_time = (int)$transition_time;
$transition_fadeout = $_POST['transition_fadeout'];
if($transition_fadeout=='') $transition_fadeout = 400;
$transition_fadeout = (int)$transition_fadeout;
$transition_zoom = (int)$_POST['transition_zoom'];
$transition_loading = (int)$_POST['transition_loading'];
$transition_effect = strip_tags($_POST['transition_effect']);
$markers_default_lookat = (int)$_POST['markers_default_lookat'];
$markers_default_backlink = (int)$_POST['markers_default_backlink'];
$note = strip_tags($_POST['note']);
$language = strip_tags($_POST['language']);
$external_url = strip_tags($_POST['external_url']);
$id_categories = $_POST['id_categories'];
$keyboard_mode = (int)$_POST['keyboard_mode'];
$shop_type = strip_tags($_POST['shop_type']);
$woocommerce_store_url = rtrim(strip_tags($_POST['woocommerce_store_url']),'/');
$woocommerce_store_cart = strip_tags($_POST['woocommerce_store_cart']);
$woocommerce_store_checkout = strip_tags($_POST['woocommerce_store_checkout']);
$woocommerce_customer_key = strip_tags($_POST['woocommerce_customer_key']);
$woocommerce_customer_secret = strip_tags($_POST['woocommerce_customer_secret']);
$snipcart_api_key = strip_tags($_POST['snipcart_api_key']);
$snipcart_currency = strip_tags($_POST['snipcart_currency']);
$enable_visitor_rt = (int)$_POST['enable_visitor_rt'];
$enable_views_stat = (int)$_POST['enable_views_stat'];
$interval_visitor_rt = $_POST['interval_visitor_rt'];
if($interval_visitor_rt=="") $interval_visitor_rt=1000;
if($interval_visitor_rt<0) $interval_visitor_rt=0;
$interval_visitor_rt = (int)$interval_visitor_rt;
$ar_camera_align = (int)$_POST['ar_camera_align'];
$click_anywhere = (int)$_POST['click_anywhere'];
$hide_markers = (int)$_POST['hide_markers'];
$hover_markers = (int)$_POST['hover_markers'];
$initial_feedback = $_POST['initial_feedback'];
if(empty($initial_feedback)) $initial_feedback=0;
$initial_feedback = (int)$initial_feedback;
$loading_background_color = strip_tags($_POST['loading_background_color']);
$loading_text_color = strip_tags($_POST['loading_text_color']);
$custom_html = htmlspecialchars_decode($_POST['custom_html']);
$context_info = $_POST['context_info'];
$mouse_zoom = (int)$_POST['mouse_zoom'];
if($context_info=='<p><br></p>') $context_info="";
$xist_background = get_virtual_tour($id_virtualtour,$id_user)['background_image'];
$form_content = $_POST['form_content'];
$array_lang = json_decode($_POST['array_lang'],true);
$poweredby_type = strip_tags($_POST['poweredby_type']);
$poweredby_link = strip_tags($_POST['poweredby_link']);
$poweredby_image = strip_tags($_POST['poweredby_image']);
$poweredby_text = strip_tags($_POST['poweredby_text'],'<br>');
$query = "UPDATE svt_virtualtours SET name=?,id_user=?,author=?,hfov=?,min_hfov=?,max_hfov=?,hfov_mobile_ratio=?,pan_speed=?,pan_speed_mobile=?,friction=?,friction_mobile=?,zoom_friction=?,zoom_friction_mobile=?,zoom_to_pointer=?,song=?,logo=?,background_image=?,background_video=?,background_video_delay=?,nadir_logo=?,auto_start=?,hide_loading=?,sameAzimuth=?,description=?,ga_tracking_id=?,compress_jpg=?,link_logo=?,max_width_compress=?,quality_viewer=?,intro_desktop=?,intro_mobile=?,intro_desktop_hide=?,intro_mobile_hide=?,enable_multires=?,transition_time=?,transition_zoom=?,transition_loading=?,transition_effect=?,transition_fadeout=?,markers_default_lookat=?,markers_default_backlink=?,note=?,flyin=?,language=?,external_url=?,keyboard_mode=?,preload_panoramas=?,mobile_panoramas=?,keep_original_panorama=?,click_anywhere=?,hide_markers=?,hover_markers=?,snipcart_currency=?,enable_visitor_rt=?,enable_views_stat=?,interval_visitor_rt=?,custom_html=?,context_info=?,ar_camera_align=?,initial_feedback=?,mouse_follow_feedback=?,background_video_delay_mobile=?,background_image_mobile=?,background_video_mobile=?,loading_background_color=?,loading_text_color=?,mouse_zoom=?,flyin_duration=?,shop_type=?,woocommerce_store_url=?,woocommerce_store_cart=?,woocommerce_store_checkout=?,form_content=?,poweredby_type=?,poweredby_image=?,poweredby_text=?,poweredby_link=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sisiiidddddddissssisiiissisidssiiiiiisiiisissiiiiiiisiiissiidissssiisssssssssi',$name,$id_user,$author,$hfov,$min_hfov,$max_hfov,$hfov_mobile_ratio,$pan_speed,$pan_speed_mobile,$friction,$friction_mobile,$zoom_friction,$zoom_friction_mobile,$zoom_to_pointer,$song,$logo,$background_image,$background_video,$background_video_delay,$nadir_logo,$auto_start,$hide_loading,$sameAzimuth,$description,$ga_tracking_id,$compress_jpg,$link_logo,$max_width_compress,$quality_viewer,$intro_desktop,$intro_mobile,$intro_desktop_hide,$intro_mobile_hide,$enable_multires,$transition_time,$transition_zoom,$transition_loading,$transition_effect,$transition_fadeout,$markers_default_lookat,$markers_default_backlink,$note,$flyin,$language,$external_url,$keyboard_mode,$preload_panoramas,$mobile_panoramas,$keep_original_panorama,$click_anywhere,$hide_markers,$hover_markers,$snipcart_currency,$enable_visitor_rt,$enable_views_stat,$interval_visitor_rt,$custom_html,$context_info,$ar_camera_align,$initial_feedback,$mouse_follow_feedback,$background_video_delay_mobile,$background_image_mobile,$background_video_mobile,$loading_background_color,$loading_text_color,$mouse_zoom,$flyin_duration,$shop_type,$woocommerce_store_url,$woocommerce_store_cart,$woocommerce_store_checkout,$form_content,$poweredby_type,$poweredby_image,$poweredby_text,$poweredby_link,$id_virtualtour);
    $result = $smt->execute();
    if ($result) {
        $mysqli->query("DELETE FROM svt_category_vt_assoc WHERE id_virtualtour=$id_virtualtour;");
        foreach ($id_categories as $id_category) {
            $id_category = (int)$id_category;
            $mysqli->query("INSERT INTO svt_category_vt_assoc(id_virtualtour,id_category) VALUES($id_virtualtour,$id_category);");
        }
        if($snipcart_api_key!="keep_snipcart_public_key") {
            $query = "UPDATE svt_virtualtours SET snipcart_api_key=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si',$snipcart_api_key,$id_virtualtour);
                $smt->execute();
            }
        }
        if($woocommerce_customer_key!="keep_woocommerce_customer_key") {
            $query = "UPDATE svt_virtualtours SET woocommerce_customer_key=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si',$woocommerce_customer_key,$id_virtualtour);
                $smt->execute();
            }
        }
        if($woocommerce_customer_secret!="keep_woocommerce_customer_secret") {
            $query = "UPDATE svt_virtualtours SET woocommerce_customer_secret=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si',$woocommerce_customer_secret,$id_virtualtour);
                $smt->execute();
            }
        }
        $query = "UPDATE svt_rooms SET transition_time=?,transition_zoom=?,transition_fadeout=? WHERE id_virtualtour=? AND transition_override=0;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('iiii',$transition_time,$transition_zoom,$transition_fadeout,$id_virtualtour);
            $smt->execute();
        }
        save_input_langs($array_lang,'svt_virtualtours_lang','id_virtualtour',$id_virtualtour);
        $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
        if($logo!=$logo_exist) {
            if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "v_$code")) {
                array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "v_$code" . DIRECTORY_SEPARATOR ."*.*"));
                rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "v_$code" . DIRECTORY_SEPARATOR);
            }
        }
        $favicon_ok = generate_favicons('vt',$id_virtualtour);
        $s3_params = check_s3_tour_enabled($id_virtualtour);
        $s3_enabled = false;
        if(!empty($s3_params)) {
            $s3_bucket_name = $s3_params['bucket'];
            $s3_url = init_s3_client($s3_params);
            if($s3_url!==false) {
                $s3_enabled = true;
            }
        }
        generate_multires(false,$id_virtualtour);
        update_user_space_storage($id_user,false);
        if(!empty($background_image) && $xist_background!=$background_image) {
            $content_image_gt = $background_image;
            require_once("../../services/generate_thumb.php");
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok","favicon"=>$favicon_ok));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}