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
unset($_SESSION['lang']);
session_write_close();
if(!get_user_role($id_user)=='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$logo_exist = "";
$small_logo_exist = "";
$favicon_ok = 1;
$query = "SELECT logo,small_logo FROM svt_settings LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $logo_exist = $row['logo'];
        $small_logo_exist = $row['small_logo'];
    }
}
$purchase_code = strip_tags($_POST['purchase_code']);
$name = strip_tags($_POST['name']);
$theme_color = strip_tags($_POST['theme_color']);
if(empty($theme_color)) $theme_color='#0b5394';
$sidebar = strip_tags($_POST['sidebar']);
$theme_color_dark = strip_tags($_POST['theme_color_dark']);
if(empty($theme_color_dark)) $theme_color_dark=null;
$dark_mode = (int)$_POST['dark_mode'];
$sidebar_color_1 = strip_tags($_POST['sidebar_color_1']);
if(empty($sidebar_color_1)) $sidebar_color_1=null;
$sidebar_color_2 = strip_tags($_POST['sidebar_color_2']);
if(empty($sidebar_color_2)) $sidebar_color_2=null;
$sidebar_color_1_dark = strip_tags($_POST['sidebar_color_1_dark']);
if(empty($sidebar_color_1_dark)) $sidebar_color_1_dark=null;
$sidebar_color_2_dark = strip_tags($_POST['sidebar_color_2_dark']);
if(empty($sidebar_color_2_dark)) $sidebar_color_2_dark=null;
$font_backend = strip_tags($_POST['font_backend']);
if(empty($font_backend)) $font_backend='Nunito';
$welcome_msg = $_POST['welcome_msg'];
if($welcome_msg=='<p><br></p>' || $welcome_msg=='<p></p>') $welcome_msg="";
$terms_and_conditions = $_POST['terms_and_conditions'];
if($terms_and_conditions=='<p><br></p>' || $terms_and_conditions=='<p></p>') $terms_and_conditions="";
$privacy_policy = $_POST['privacy_policy'];
if($privacy_policy=='<p><br></p>' || $privacy_policy=='<p></p>') $privacy_policy="";
$furl_blacklist = strip_tags($_POST['furl_blacklist']);
$furl_blacklist = strtolower($furl_blacklist);
$furl_blacklist = str_replace(" ","",$furl_blacklist);
$logo = strip_tags($_POST['logo']);
$small_logo = strip_tags($_POST['small_logo']);
$background = strip_tags($_POST['background']);
$background_reg = strip_tags($_POST['background_reg']);
$style_login = (int)$_POST['style_login'];
$style_register = (int)$_POST['style_register'];
$smtp_server = strip_tags($_POST['smtp_server']);
$smtp_port = $_POST['smtp_port'];
if($smtp_port=='') $smtp_port=0;
$smtp_port = (int)$smtp_port;
$smtp_secure = strip_tags($_POST['smtp_secure']);
$smtp_auth = (int)$_POST['smtp_auth'];
$smtp_username = strip_tags($_POST['smtp_username']);
$smtp_password = strip_tags($_POST['smtp_password']);
$smtp_from_email = strip_tags($_POST['smtp_from_email']);
$smtp_from_name = strip_tags($_POST['smtp_from_name']);
$language = strip_tags($_POST['language']);
$language_domain = strip_tags($_POST['language_domain']);
$language_domain = str_replace("_lang","",$language_domain);
$languages_enabled = $_POST['languages_enabled'];
$languages_viewer_enabled = $_POST['languages_viewer_enabled'];
$css_array = json_decode($_POST['css_array'],true);
$js_array = json_decode($_POST['js_array'],true);
$head_array = json_decode($_POST['head_array'],true);
$contact_mail = strip_tags($_POST['contact_mail']);
$help_url = strip_tags($_POST['help_url']);
$website_url = strip_tags($_POST['website_url']);
$website_name = strip_tags($_POST['website_name']);
$enable_external_vt = (int)$_POST['enable_external_vt'];
$enable_ar_vt = (int)$_POST['enable_ar_vt'];
$enable_wizard = (int)$_POST['enable_wizard'];
$enable_sample = (int)$_POST['enable_sample'];
$id_vt_sample = (int)$_POST['id_vt_sample'];
if($id_vt_sample==0) $id_vt_sample=NULL;
$id_vt_template = (int)$_POST['id_vt_template'];
if($id_vt_template==0) $id_vt_template=NULL;
$social_google_enable = (int)$_POST['social_google_enable'];
$social_facebook_enable = (int)$_POST['social_facebook_enable'];
$social_twitter_enable = (int)$_POST['social_twitter_enable'];
$social_wechat_enable = (int)$_POST['social_wechat_enable'];
$social_qq_enable = (int)$_POST['social_qq_enable'];
$social_google_id = strip_tags($_POST['social_google_id']);
$social_google_secret = strip_tags($_POST['social_google_secret']);
$social_facebook_id = strip_tags($_POST['social_facebook_id']);
$social_facebook_secret = strip_tags($_POST['social_facebook_secret']);
$social_twitter_id = strip_tags($_POST['social_twitter_id']);
$social_twitter_secret = strip_tags($_POST['social_twitter_secret']);
$social_wechat_id = strip_tags($_POST['social_wechat_id']);
$social_wechat_secret = strip_tags($_POST['social_wechat_secret']);
$social_qq_id = strip_tags($_POST['social_qq_id']);
$social_qq_secret = strip_tags($_POST['social_qq_secret']);
$enable_registration = (int)$_POST['enable_registration'];
$default_id_plan = (int)$_POST['default_id_plan'];
$change_plan = (int)$_POST['change_plan'];
$validate_email = (int)$_POST['validate_email'];
$stripe_enabled = (int)$_POST['stripe_enabled'];
$stripe_automatic_tax_rate = strip_tags($_POST['stripe_automatic_tax_rate']);
$stripe_secret_key = strip_tags($_POST['stripe_secret_key']);
$stripe_public_key = strip_tags($_POST['stripe_public_key']);
$paypal_enabled = (int)$_POST['paypal_enabled'];
$paypal_live = (int)$_POST['paypal_live'];
$paypal_client_id = strip_tags($_POST['paypal_client_id']);
$paypal_client_secret = strip_tags($_POST['paypal_client_secret']);
$mail_activate_subject = $_POST['mail_activate_subject'];
$mail_activate_body = $_POST['mail_activate_body'];
$mail_user_add_subject = $_POST['mail_user_add_subject'];
$mail_user_add_body = $_POST['mail_user_add_body'];
$mail_forgot_subject = $_POST['mail_forgot_subject'];
$mail_forgot_body = $_POST['mail_forgot_body'];
$mail_plan_expiring_subject = $_POST['mail_plan_expiring_subject'];
$mail_plan_expiring_body = $_POST['mail_plan_expiring_body'];
$mail_plan_expired_subject = $_POST['mail_plan_expired_subject'];
$mail_plan_expired_body = $_POST['mail_plan_expired_body'];
$mail_plan_changed_subject = $_POST['mail_plan_changed_subject'];
$mail_plan_changed_body = $_POST['mail_plan_changed_body'];
$mail_plan_canceled_subject = $_POST['mail_plan_canceled_subject'];
$mail_plan_canceled_body = $_POST['mail_plan_canceled_body'];
$first_name_enable = (int)$_POST['first_name_enable'];
$last_name_enable = (int)$_POST['last_name_enable'];
$company_enable = (int)$_POST['company_enable'];
$tax_id_enable = (int)$_POST['tax_id_enable'];
$street_enable = (int)$_POST['street_enable'];
$city_enable = (int)$_POST['city_enable'];
$province_enable = (int)$_POST['province_enable'];
$postal_code_enable = (int)$_POST['postal_code_enable'];
$country_enable = (int)$_POST['country_enable'];
$tel_enable = (int)$_POST['tel_enable'];
$first_name_mandatory = (int)$_POST['first_name_mandatory'];
$last_name_mandatory = (int)$_POST['last_name_mandatory'];
$company_mandatory = (int)$_POST['company_mandatory'];
$tax_id_mandatory = (int)$_POST['tax_id_mandatory'];
$street_mandatory = (int)$_POST['street_mandatory'];
$city_mandatory = (int)$_POST['city_mandatory'];
$province_mandatory = (int)$_POST['province_mandatory'];
$postal_code_mandatory = (int)$_POST['postal_code_mandatory'];
$country_mandatory = (int)$_POST['country_mandatory'];
$tel_mandatory = (int)$_POST['tel_mandatory'];
$peerjs_host = strip_tags($_POST['peerjs_host']);
$peerjs_port = strip_tags($_POST['peerjs_port']);
$peerjs_path = strip_tags($_POST['peerjs_path']);
$turn_host = strip_tags($_POST['turn_host']);
$turn_port = strip_tags($_POST['turn_port']);
$turn_username = strip_tags($_POST['turn_username']);
$turn_password = strip_tags($_POST['turn_password']);
$jitsi_domain = strip_tags($_POST['jitsi_domain']);
$leaflet_street_basemap = strip_tags($_POST['url_street']);
$leaflet_satellite_basemap = strip_tags($_POST['url_sat']);
$leaflet_street_subdomain = strip_tags($_POST['sub_street']);
$leaflet_satellite_subdomain = strip_tags($_POST['sub_sat']);
$leaflet_street_maxzoom = strip_tags($_POST['zoom_street']);
$leaflet_satellite_maxzoom = strip_tags($_POST['zoom_sat']);
if(empty($peerjs_host)) $peerjs_host='svtpeerjs.simpledemo.it';
if(empty($peerjs_port)) $peerjs_port='443';
if(empty($peerjs_path)) $peerjs_path='/svt';
if(empty($jitsi_domain)) $jitsi_domain='meet.jit.si';
if(empty($leaflet_street_basemap)) $leaflet_street_basemap='https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}';
if(empty($leaflet_satellite_basemap)) $leaflet_satellite_basemap='https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}';
if(empty($leaflet_street_maxzoom)) $leaflet_street_maxzoom='20';
if(empty($leaflet_satellite_maxzoom)) $leaflet_satellite_maxzoom='20';
$footer_link_1 = strip_tags($_POST['footer_link_1']);
$footer_link_2 = strip_tags($_POST['footer_link_2']);
$footer_link_3 = strip_tags($_POST['footer_link_3']);
$footer_value_1 = $_POST['footer_value_1'];
$footer_value_2 = $_POST['footer_value_2'];
$footer_value_3 = $_POST['footer_value_3'];
if(strpos(get_string_between($footer_value_1, '<p>', '</p>'), 'http') === 0) {
    $footer_value_1 = str_replace(['<p>','</p>'],'',$footer_value_1);
}
if(strpos(get_string_between($footer_value_2, '<p>', '</p>'), 'http') === 0) {
    $footer_value_2 = str_replace(['<p>','</p>'],'',$footer_value_2);
}
if(strpos(get_string_between($footer_value_3, '<p>', '</p>'), 'http') === 0) {
    $footer_value_3 = str_replace(['<p>','</p>'],'',$footer_value_3);
}
$multires = strip_tags($_POST['multires']);
$multires_cloud_url = strip_tags($_POST['multires_cloud_url']);
$video360 = strip_tags($_POST['video360']);
$video360_cloud_url = strip_tags($_POST['video360_cloud_url']);
$slideshow = strip_tags($_POST['slideshow']);
$slideshow_cloud_url = strip_tags($_POST['slideshow_cloud_url']);
$video_project = strip_tags($_POST['video_project']);
$video_project_url = strip_tags($_POST['video_project_url']);
$enable_screencast = (int)$_POST['enable_screencast'];
$url_screencast = $_POST['url_screencast'];
$notify_email = strip_tags($_POST['notify_email']);
$notify_registrations = (int)$_POST['notify_registrations'];
$notify_useradd = (int)$_POST['notify_useradd'];
$notify_plan_expires = (int)$_POST['notify_plan_expires'];
$notify_plan_expiring = (int)$_POST['notify_plan_expiring'];
$notify_plan_changes = (int)$_POST['notify_plan_changes'];
$notify_plan_cancels = (int)$_POST['notify_plan_cancels'];
$notify_vt_create = (int)$_POST['notify_vt_create'];
$captcha_login = (int)$_POST['captcha_login'];
$captcha_register = (int)$_POST['captcha_register'];
$twofa_enable = (int)$_POST['twofa_enable'];
$vr_button = strip_tags($_POST['vr_button']);
$share_providers = strip_tags($_POST['share_providers']);
$disqus_shortname = strip_tags($_POST['disqus_shortname']);
$disqus_public_key = strip_tags($_POST['disqus_public_key']);
$disqus_allow_tour = (int)$_POST['disqus_allow_tour'];
$font_provider = strip_tags($_POST['font_provider']);
$days_expire_notification = (int)$_POST['days_expire_notification'];
if($days_expire_notification<=1) $days_expire_notification=1;
$enable_ai_room = (int)$_POST['enable_ai_room'];
$ai_key = strip_tags($_POST['ai_key']);
$enable_deepl = (int)$_POST['enable_deepl'];
$deepl_api_key = strip_tags($_POST['deepl_api_key']);
$aws_s3_enabled = (int)$_POST['aws_s3_enabled'];
$aws_s3_vt_auto = (int)$_POST['aws_s3_vt_auto'];
$aws_s3_type = strip_tags($_POST['aws_s3_type']);
$aws_s3_secret = strip_tags($_POST['aws_s3_secret']);
$aws_s3_key = strip_tags($_POST['aws_s3_key']);
$aws_s3_region = strip_tags($_POST['aws_s3_region']);
$aws_s3_bucket = strip_tags($_POST['aws_s3_bucket']);
$aws_s3_accountid = strip_tags($_POST['aws_s3_accountid']);
$aws_s3_custom_domain = strip_tags($_POST['aws_s3_custom_domain']);
$globe_ion_token = strip_tags($_POST['globe_ion_token']);
$globe_arcgis_token = strip_tags($_POST['globe_arcgis_token']);
$globe_googlemaps_key = strip_tags($_POST['globe_googlemaps_key']);
$maintenance_backend = (int)$_POST['maintenance_backend'];
$maintenance_viewer = (int)$_POST['maintenance_viewer'];
$maintenance_ip = strip_tags($_POST['maintenance_ip']);
if(empty($maintenance_ip)) {
    $maintenance_backend=0;
    $maintenance_viewer=0;
}
$query = "UPDATE svt_settings SET purchase_code=?,name=?,theme_color=?,sidebar=?,sidebar_color_1=?,sidebar_color_2=?,font_backend=?,welcome_msg=?,logo=?,small_logo=?,background=?,background_reg=?,smtp_server=?,smtp_port=?,smtp_secure=?,smtp_auth=?,smtp_username=?,smtp_from_email=?,smtp_from_name=?,furl_blacklist=?,language=?,language_domain=?,languages_enabled=?,contact_email=?,help_url=?,enable_external_vt=?,enable_ar_vt=?,enable_wizard=?,social_google_enable=?,social_facebook_enable=?,social_twitter_enable=?,enable_registration=?,default_id_plan=?,change_plan=?,validate_email=?,stripe_enabled=?,stripe_automatic_tax_rate=?,paypal_enabled=?,paypal_live=?,mail_activate_subject=?,mail_activate_body=?,mail_user_add_subject=?,mail_user_add_body=?,mail_forgot_subject=?,mail_forgot_body=?,mail_plan_expiring_subject=?,mail_plan_expiring_body=?,mail_plan_expired_subject=?,mail_plan_expired_body=?,mail_plan_changed_subject=?,mail_plan_changed_body=?,mail_plan_canceled_subject=?,mail_plan_canceled_body=?,first_name_enable=?,last_name_enable=?,company_enable=?,tax_id_enable=?,street_enable=?,city_enable=?,province_enable=?,postal_code_enable=?,country_enable=?,tel_enable=?,first_name_mandatory=?,last_name_mandatory=?,company_mandatory=?,tax_id_mandatory=?,street_mandatory=?,city_mandatory=?,province_mandatory=?,postal_code_mandatory=?,country_mandatory=?,tel_mandatory=?,peerjs_host=?,peerjs_port=?,peerjs_path=?,turn_host=?,turn_port=?,turn_username=?,turn_password=?,jitsi_domain=?,leaflet_street_basemap=?,leaflet_satellite_basemap=?,leaflet_street_subdomain=?,leaflet_street_maxzoom=?,leaflet_satellite_subdomain=?,leaflet_satellite_maxzoom=?,enable_sample=?,id_vt_sample=?,id_vt_template=?,footer_link_1=?,footer_link_2=?,footer_link_3=?,footer_value_1=?,footer_value_2=?,footer_value_3=?,multires=?,multires_cloud_url=?,video360=?,video360_cloud_url=?,slideshow=?,slideshow_cloud_url=?,enable_screencast=?,url_screencast=?,notify_email=?,notify_registrations=?,notify_useradd=?,notify_plan_expires=?,notify_plan_expiring=?,notify_plan_changes=?,notify_plan_cancels=?,notify_vt_create=?,captcha_login=?,captcha_register=?,vr_button=?,social_wechat_enable=?,social_qq_enable=?,style_login=?,style_register=?,share_providers=?,video_project_url=?,video_project=?,theme_color_dark=?,dark_mode=?,sidebar_color_1_dark=?,sidebar_color_2_dark=?,website_url=?,website_name=?,disqus_shortname=?,disqus_allow_tour=?,font_provider=?,days_expire_notification=?,enable_ai_room=?,aws_s3_enabled=?,aws_s3_vt_auto=?,aws_s3_region=?,aws_s3_bucket=?,aws_s3_type=?,aws_s3_accountid=?,aws_s3_custom_domain=?,`2fa_enable`=?,terms_and_conditions=?,maintenance_backend=?,maintenance_viewer=?,maintenance_ip=?,privacy_policy=?,enable_deepl=?,languages_viewer_enabled=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sssssssssssssisisssssssssiiiiiiiiiiisiissssssssssssssiiiiiiiiiiiiiiiiiiiissssssssssssssiiissssssssssssissiiiiiiiiisiiiissssisssssisiiiisssssisiissis',$purchase_code,$name,$theme_color,$sidebar,$sidebar_color_1,$sidebar_color_2,$font_backend,$welcome_msg,$logo,$small_logo,$background,$background_reg,$smtp_server,$smtp_port,$smtp_secure,$smtp_auth,$smtp_username,$smtp_from_email,$smtp_from_name,$furl_blacklist,$language,$language_domain,$languages_enabled,$contact_mail,$help_url,$enable_external_vt,$enable_ar_vt,$enable_wizard,$social_google_enable,$social_facebook_enable,$social_twitter_enable,$enable_registration,$default_id_plan,$change_plan,$validate_email,$stripe_enabled,$stripe_automatic_tax_rate,$paypal_enabled,$paypal_live,$mail_activate_subject,$mail_activate_body,$mail_user_add_subject,$mail_user_add_body,$mail_forgot_subject,$mail_forgot_body,$mail_plan_expiring_subject,$mail_plan_expiring_body,$mail_plan_expired_subject,$mail_plan_expired_body,$mail_plan_changed_subject,$mail_plan_changed_body,$mail_plan_canceled_subject,$mail_plan_canceled_body,$first_name_enable,$last_name_enable,$company_enable,$tax_id_enable,$street_enable,$city_enable,$province_enable,$postal_code_enable,$country_enable,$tel_enable,$first_name_mandatory,$last_name_mandatory,$company_mandatory,$tax_id_mandatory,$street_mandatory,$city_mandatory,$province_mandatory,$postal_code_mandatory,$country_mandatory,$tel_mandatory,$peerjs_host,$peerjs_port,$peerjs_path,$turn_host,$turn_port,$turn_username,$turn_password,$jitsi_domain,$leaflet_street_basemap,$leaflet_satellite_basemap,$leaflet_street_subdomain,$leaflet_street_maxzoom,$leaflet_satellite_subdomain,$leaflet_satellite_maxzoom,$enable_sample,$id_vt_sample,$id_vt_template,$footer_link_1,$footer_link_2,$footer_link_3,$footer_value_1,$footer_value_2,$footer_value_3,$multires,$multires_cloud_url,$video360,$video360_cloud_url,$slideshow,$slideshow_cloud_url,$enable_screencast,$url_screencast,$notify_email,$notify_registrations,$notify_useradd,$notify_plan_expires,$notify_plan_expiring,$notify_plan_changes,$notify_plan_cancels,$notify_vt_create,$captcha_login,$captcha_register,$vr_button,$social_wechat_enable,$social_qq_enable,$style_login,$style_register,$share_providers,$video_project_url,$video_project,$theme_color_dark,$dark_mode,$sidebar_color_1_dark,$sidebar_color_2_dark,$website_url,$website_name,$disqus_shortname,$disqus_allow_tour,$font_provider,$days_expire_notification,$enable_ai_room,$aws_s3_enabled,$aws_s3_vt_auto,$aws_s3_region,$aws_s3_bucket,$aws_s3_type,$aws_s3_accountid,$aws_s3_custom_domain,$twofa_enable,$terms_and_conditions,$maintenance_backend,$maintenance_viewer,$maintenance_ip,$privacy_policy,$enable_deepl,$languages_viewer_enabled);
    $result = $smt->execute();
}
if($stripe_public_key!="keep_stripe_public_key") {
    $query = "UPDATE svt_settings SET stripe_public_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$stripe_public_key);
        $smt->execute();
    }
}
if($stripe_secret_key!="keep_stripe_secret_key") {
    $query = "UPDATE svt_settings SET stripe_secret_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$stripe_secret_key);
        $smt->execute();
    }
}
if($paypal_client_id!="keep_paypal_client_id") {
    $query = "UPDATE svt_settings SET paypal_client_id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$paypal_client_id);
        $smt->execute();
    }
}
if($paypal_client_secret!="keep_paypal_client_secret") {
    $query = "UPDATE svt_settings SET paypal_client_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$paypal_client_secret);
        $smt->execute();
    }
}
if($aws_s3_secret!="keep_aws_s3_secret") {
    $query = "UPDATE svt_settings SET aws_s3_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$aws_s3_secret);
        $smt->execute();
    }
}
if($aws_s3_key!="keep_aws_s3_key") {
    $query = "UPDATE svt_settings SET aws_s3_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$aws_s3_key);
        $smt->execute();
    }
}
if($disqus_public_key!="keep_disqus_public_key") {
    $query = "UPDATE svt_settings SET disqus_public_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$disqus_public_key);
        $smt->execute();
    }
}
if($globe_ion_token!="keep_globe_ion_token") {
    $query = "UPDATE svt_settings SET globe_ion_token=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$globe_ion_token);
        $smt->execute();
    }
}
if($globe_arcgis_token!="keep_globe_arcgis_token") {
    $query = "UPDATE svt_settings SET globe_arcgis_token=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$globe_arcgis_token);
        $smt->execute();
    }
}
if($globe_googlemaps_key!="keep_globe_googlemaps_key") {
    $query = "UPDATE svt_settings SET globe_googlemaps_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$globe_googlemaps_key);
        $smt->execute();
    }
}
if($smtp_password!='keep_password') {
    $query = "UPDATE svt_settings SET smtp_password=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$smtp_password);
        $smt->execute();
    }
}
if($social_google_id!='keep_password') {
    $query = "UPDATE svt_settings SET social_google_id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_google_id);
        $smt->execute();
    }
}
if($social_google_secret!='keep_password') {
    $query = "UPDATE svt_settings SET social_google_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_google_secret);
        $smt->execute();
    }
}
if($social_facebook_id!='keep_password') {
    $query = "UPDATE svt_settings SET social_facebook_id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_facebook_id);
        $smt->execute();
    }
}
if($social_facebook_secret!='keep_password') {
    $query = "UPDATE svt_settings SET social_facebook_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_facebook_secret);
        $smt->execute();
    }
}
if($social_twitter_id!='keep_password') {
    $query = "UPDATE svt_settings SET social_twitter_id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_twitter_id);
        $smt->execute();
    }
}
if($social_twitter_secret!='keep_password') {
    $query = "UPDATE svt_settings SET social_twitter_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_twitter_secret);
        $smt->execute();
    }
}
if($social_wechat_id!='keep_password') {
    $query = "UPDATE svt_settings SET social_wechat_id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_wechat_id);
        $smt->execute();
    }
}
if($social_wechat_secret!='keep_password') {
    $query = "UPDATE svt_settings SET social_wechat_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_wechat_secret);
        $smt->execute();
    }
}
if($social_qq_id!='keep_password') {
    $query = "UPDATE svt_settings SET social_qq_id=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_qq_id);
        $smt->execute();
    }
}
if($social_qq_secret!='keep_password') {
    $query = "UPDATE svt_settings SET social_qq_secret=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$social_qq_secret);
        $smt->execute();
    }
}
if($ai_key!='keep_ai_key') {
    $query = "UPDATE svt_settings SET ai_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$ai_key);
        $smt->execute();
    }
}
if($deepl_api_key!='keep_deepl_api_key') {
    $query = "UPDATE svt_settings SET deepl_api_key=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$deepl_api_key);
        $smt->execute();
    }
}
foreach ($css_array as $name=>$content) {
    if($name=='custom_b') {
        $url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_b.css';
    } else {
        $url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$name.'.css';
    }
    if(file_exists($url_css) && $content=='') {
        @unlink($url_css);
    } else {
        if($content!='') {
            @file_put_contents($url_css,$content);
        }
    }
}
foreach ($js_array as $name=>$content) {
    if($name=='custom_b') {
        $url_js = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'custom_b.js';
    } else {
        $url_js = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.$name.'.js';
    }
    if(file_exists($url_js) && $content=='') {
        @unlink($url_js);
    } else {
        if($content!='') {
            @file_put_contents($url_js,$content);
        }
    }
}
foreach ($head_array as $name=>$content) {
    if($name=='custom_b') {
        $url_head = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'header'.DIRECTORY_SEPARATOR.'custom_b.php';
    } else {
        $url_head = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'header'.DIRECTORY_SEPARATOR.$name.'.php';
    }
    if(file_exists($url_head) && $content=='') {
        @unlink($url_head);
    } else {
        if($content!='') {
            @file_put_contents($url_head,$content);
        }
    }
}
$query_vc = "UPDATE svt_voice_commands SET ";
$array_vc_values = array();
foreach($_POST['voice_commands'] as $key=>$value){
    $value = strip_tags($value);
    array_push($array_vc_values,$value);
    $query_vc .= $key."=?,";
}
$query_vc = rtrim($query_vc,", ").";";
if($smt = $mysqli->prepare($query_vc)) {
    $smt->bind_param(str_repeat('s', count($array_vc_values)),...$array_vc_values);
    $smt->execute();
}
if($result) {
    $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
    if(empty($logo) && empty($small_logo)) {
        if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "custom")) {
            array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR ."*.*"));
            rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR);
        }
    } else {
        if($logo!=$logo_exist || $small_logo!=$small_logo_exist) {
            if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "custom")) {
                array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR ."*.*"));
                rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR);
            }
        }
    }
    if(!empty($logo) || !empty($small_logo)) {
        $favicon_ok = generate_favicons('backend',0);
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok","favicon"=>$favicon_ok));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error $query"));
}