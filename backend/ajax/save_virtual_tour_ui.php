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
$arrows_nav = (int)$_POST['arrows_nav'];
$voice_commands = (int)$_POST['voice_commands'];
$compass = (int)$_POST['compass'];
$auto_show_slider = (int)$_POST['auto_show_slider'];
$nav_slider = (int)$_POST['nav_slider'];
$show_list_alt = (int)$_POST['show_list_alt'];
$show_info = (int)$_POST['show_info'];
$info_box_type = strip_tags($_POST['info_box_type']);
$show_dollhouse = (int)$_POST['show_dollhouse'];
$show_custom = (int)$_POST['show_custom'];
$show_custom2 = (int)$_POST['show_custom2'];
$show_custom3 = (int)$_POST['show_custom3'];
$show_custom4 = (int)$_POST['show_custom4'];
$show_custom5 = (int)$_POST['show_custom5'];
$show_location = (int)$_POST['show_location'];
$show_gallery = (int)$_POST['show_gallery'];
$show_icons_toggle = (int)$_POST['show_icons_toggle'];
$show_measures_toggle = (int)$_POST['show_measures_toggle'];
$show_autorotation_toggle = (int)$_POST['show_autorotation_toggle'];
$show_nav_control = (int)$_POST['show_nav_control'];
$show_presentation = (int)$_POST['show_presentation'];
$show_main_form = (int)$_POST['show_main_form'];
$show_share = (int)$_POST['show_share'];
$share_providers = strip_tags($_POST['share_providers']);
$show_device_orientation = (int)$_POST['show_device_orientation'];
$drag_device_orientation = (int)$_POST['drag_device_orientation'];
$show_webvr = (int)$_POST['show_webvr'];
$show_audio = (int)$_POST['show_audio'];
$show_vt_title = (int)$_POST['show_vt_title'];
$show_fullscreen = (int)$_POST['show_fullscreen'];
$show_map = (int)$_POST['show_map'];
$show_map_tour = (int)$_POST['show_map_tour'];
$show_language = (int)$_POST['show_language'];
$live_session = (int)$_POST['live_session'];
$meeting = (int)$_POST['meeting'];
$show_annotations = (int)$_POST['show_annotations'];
$autoclose_menu = (int)$_POST['autoclose_menu'];
$autoclose_list_alt = (int)$_POST['autoclose_list_alt'];
$autoclose_slider = (int)$_POST['autoclose_slider'];
$autoclose_map = (int)$_POST['autoclose_map'];
$fb_messenger = (int)$_POST['fb_messenger'];
$whatsapp_chat = (int)$_POST['whatsapp_chat'];
$show_logo = (int)$_POST['show_logo'];
$array_colors = $_POST['array_colors'];
$array_positions = $_POST['array_positions'];
$array_orders = $_POST['array_orders'];
$array_icons = $_POST['array_icons'];
$array_library_icons = $_POST['array_library_icons'];
$annotation_position = strip_tags($_POST['annotation_position']);
$map_position = strip_tags($_POST['map_position']);
$logo_position = strip_tags($_POST['logo_position']);
$logo_height = $_POST['logo_height'];
if(empty($logo_height)) $logo_height=40;
$logo_height = (int)$logo_height;
$logo_padding_left = $_POST['logo_padding_left'];
if(empty($logo_padding_left)) $logo_padding_left=0;
$logo_padding_left = (int)$logo_padding_left;
$logo_padding_top = $_POST['logo_padding_top'];
if(empty($logo_padding_top)) $logo_padding_top=0;
$logo_padding_top = (int)$logo_padding_top;
$logo_padding_right = $_POST['logo_padding_right'];
if(empty($logo_padding_right)) $logo_padding_right=0;
$logo_padding_right = (int)$logo_padding_right;
$form_enable = (int)$_POST['form_enable'];
$custom_title = strip_tags($_POST['custom_title']);
$custom_content = $_POST['custom_content'];
$custom_content = htmlspecialchars_decode($custom_content);
$custom2_title = strip_tags($_POST['custom2_title']);
$custom2_content = $_POST['custom2_content'];
$custom2_content = htmlspecialchars_decode($custom2_content);
$custom3_title = strip_tags($_POST['custom3_title']);
$custom3_content = $_POST['custom3_content'];
$custom3_content = htmlspecialchars_decode($custom3_content);
$custom4_title = strip_tags($_POST['custom4_title']);
$custom4_content = $_POST['custom4_content'];
$custom4_content = htmlspecialchars_decode($custom4_content);
$custom5_title = strip_tags($_POST['custom5_title']);
$custom5_content = $_POST['custom5_content'];
$custom5_content = htmlspecialchars_decode($custom5_content);
$location_title = strip_tags($_POST['location_title']);
$location_content = $_POST['location_content'];
$markers_icon = strip_tags($_POST['markers_icon']);
$markers_icon_type = strip_tags($_POST['markers_icon_type']);
$markers_id_icon_library = (int)$_POST['markers_id_icon_library'];
$markers_color = strip_tags($_POST['markers_color']);
$markers_background = strip_tags($_POST['markers_background']);
$markers_show_room = (int)$_POST['markers_show_room'];
if($markers_show_room!=4) $markers_id_icon_library=0;
$markers_tooltip_type = strip_tags($_POST['markers_tooltip_type']);
$markers_tooltip_visibility = strip_tags($_POST['markers_tooltip_visibility']);
$markers_tooltip_background = strip_tags($_POST['markers_tooltip_background']);
$markers_tooltip_color = strip_tags($_POST['markers_tooltip_color']);
$markers_default_scale = (int)$_POST['markers_default_scale'];
$markers_default_sound = strip_tags($_POST['markers_default_sound']);
$markers_animation = strip_tags($_POST['markers_animation']);
$pois_icon = strip_tags($_POST['pois_icon']);
$pois_icon_type = strip_tags($_POST['pois_icon_type']);
$pois_id_icon_library = (int)$_POST['pois_id_icon_library'];
$pois_color = strip_tags($_POST['pois_color']);
$pois_background = strip_tags($_POST['pois_background']);
$pois_style = strip_tags($_POST['pois_style']);
if($pois_style!=1) $pois_id_icon_library=0;
$pois_tooltip_type = strip_tags($_POST['pois_tooltip_type']);
$pois_tooltip_visibility = strip_tags($_POST['pois_tooltip_visibility']);
$pois_tooltip_background = strip_tags($_POST['pois_tooltip_background']);
$pois_tooltip_color = strip_tags($_POST['pois_tooltip_color']);
$pois_default_scale = (int)$_POST['pois_default_scale'];
$pois_default_sound = strip_tags($_POST['pois_default_sound']);
$pois_animation = strip_tags($_POST['pois_animation']);
$position_list = $array_positions['position_list'];
$preview_room_slider = (int)$_POST['preview_room_slider'];
$vt_title_height = (int)$_POST['vt_title_height'];
if($position_list!='default') {
    $tmp = explode("_",$position_list);
    $type_list = $tmp[0];
    $position_list = $tmp[1];
} else {
    $type_list = "default";
    $position_list = "";
}
$position_arrows = $array_positions['position_arrows'];
if($position_arrows!='default') {
    $tmp = explode("_",$position_arrows);
    $type_arrows = $tmp[0];
    $position_arrows = $tmp[1];
} else {
    $type_arrows = "default";
    $position_arrows = "";
}
foreach($array_orders as $key => $value) {
    $array_orders[str_replace(['_left','_center','_right','_menu'], '', $key)] = $value;
    unset($array_orders[$key]);
}
if(!isset($array_orders['controls_arrow'])) $array_orders['controls_arrow']=0;
if(!isset($array_colors['title_background'])) $array_colors['title_background']='';
$font_viewer = strip_tags($_POST['font_viewer']);
$id_preset = $_POST['id_preset'];
$name_preset = strip_tags($_POST['name_preset']);
$preset_public = (int)$_POST['preset_public'];
$apply_preset = (int)$_POST['apply_preset'];
$nadir_size = strip_tags($_POST['nadir_size']);
$icons_tooltips = (int)$_POST['icons_tooltips'];
$autorotate_speed = $_POST['autorotate_speed'];
if($autorotate_speed=="") $autorotate_speed=0;
if($autorotate_speed>=10) $autorotate_speed=10;
if($autorotate_speed<=-10) $autorotate_speed=-10;
$autorotate_speed = (int)$autorotate_speed;
$autorotate_inactivity = $_POST['autorotate_inactivity'];
if($autorotate_inactivity=="") $autorotate_inactivity=0;
$autorotate_inactivity = (int)$autorotate_inactivity;
$song_autoplay = (int)$_POST['song_autoplay'];
$fb_page_id = strip_tags($_POST['fb_page_id']);
$whatsapp_number = strip_tags($_POST['whatsapp_number']);
$password_meeting = strip_tags($_POST['password_meeting']);
$password_livesession = strip_tags($_POST['password_livesession']);
$show_comments = (int)$_POST['show_comments'];
$disqus_shortname = strip_tags($_POST['disqus_shortname']);
$disqus_public_key = strip_tags($_POST['disqus_public_key']);
$song_bg_volume = (float)$_POST['song_bg_volume'];
$language = strip_tags($_POST['language']);
$languages_enabled = $_POST['languages_enabled'];
$show_poweredby = (int)$_POST['show_poweredby'];
$poweredby_height = (int)$_POST['poweredby_height'];
$poweredby_font_size = (int)$_POST['poweredby_font_size'];
$poweredby_font_color = strip_tags($_POST['poweredby_font_color']);
$poweredby_position = strip_tags($_POST['poweredby_position']);
$ui_style = [
    'icons_tooltips'=>$icons_tooltips,
    'preview_room_slider'=>$preview_room_slider,
    'items'=>[
        'list'=>[
            'background_initial'=>'',
            'background'=>$array_colors['slider_background']
        ],
        'annotation'=>[
            'position'=>$annotation_position,
            'color'=>$array_colors['annotation_color'],
            'background'=>$array_colors['annotation_background']
        ],
        'title'=>[
            'color'=>$array_colors['title_color'],
            'background'=>rtrim(str_replace("rgb","rgba",$array_colors['title_background']), ")"),
            'background_height'=>$vt_title_height
        ],
        'comments'=>[
            'color'=>$array_colors['comments_color']
        ],
        'nav_control'=>[
            'color'=>$array_colors['nav_control_color'],
            'color_hover'=>$array_colors['nav_control_color_hover'],
            'background'=>$array_colors['nav_background']
        ],
        'logo'=>[
            'position'=>$logo_position,
            'height'=>$logo_height,
            'padding_top'=>$logo_padding_top,
            'padding_left'=>$logo_padding_left,
            'padding_right'=>$logo_padding_right
        ],
        'map'=>[
            'position'=>$map_position,
            'color'=>$array_colors['map_bar_color'],
            'color_hover'=>$array_colors['map_bar_color_hover'],
            'background'=>$array_colors['map_bar_background']
        ],
        'poweredby'=>[
            'position'=>$poweredby_position,
            'image_height'=>$poweredby_height,
            'font_size'=>$poweredby_font_size,
            'font_color'=>$poweredby_font_color,
        ],
    ],
    'icons'=>[
        'menu'=>[
            'color'=>$array_colors['menu_color'],
            'color_hover'=>$array_colors['menu_color_hover']
        ],
        'list_alt'=>[
            'color'=>$array_colors['list_alt_color'],
            'color_hover'=>$array_colors['list_alt_color_hover']
        ],
        'audio'=>[
            'color'=>$array_colors['audio_color'],
            'color_hover'=>$array_colors['audio_color_hover']
        ],
        'floorplan'=>[
            'color'=>$array_colors['floorplan_color'],
            'color_hover'=>$array_colors['floorplan_color_hover']
        ],
        'map'=>[
            'color'=>$array_colors['map_color'],
            'color_hover'=>$array_colors['map_color_hover']
        ],
        'fullscreen'=>[
            'color'=>$array_colors['fullscreen_color'],
            'color_hover'=>$array_colors['fullscreen_color_hover']
        ]
    ],
    'controls'=>[
        'list_alt_menu'=>[
            'style'=>'background-color:'.$array_colors['list_alt_menu_background'].';color:'.$array_colors['list_alt_menu_color'].';',
            'style_hover'=>'background-color:'.$array_colors['list_alt_menu_background_hover'].';color:'.$array_colors['list_alt_menu_color_hover'].';',
            'icon_color'=>$array_colors['list_alt_menu_icon_color'],
            'icon_color_hover'=>$array_colors['list_alt_menu_icon_color_hover']
        ],
        'list'=>[
            'type'=>explode("_",$array_positions['position_list'])[0],
            'position'=>explode("_",$array_positions['position_list'])[1],
            'order'=>$array_orders['controls_arrow'],
            'style'=>'background-color:'.$array_colors['list_background'].';color:'.$array_colors['list_color'].';',
            'style_hover'=>'background-color:'.$array_colors['list_background_hover'].';color:'.$array_colors['list_color_hover'].';'
        ],
        'arrows'=>[
            'type'=>explode("_",$array_positions['position_arrows'])[0],
            'position'=>explode("_",$array_positions['position_arrows'])[1],
            'order'=>$array_orders['controls_arrow'],
            'style'=>'background-color:'.$array_colors['arrows_background'].';color:'.$array_colors['arrows_color'].';',
            'style_hover'=>'background-color:'.$array_colors['arrows_background_hover'].';color:'.$array_colors['arrows_color_hover'].';'
        ],
        'nav_arrows'=>[
            'style'=>'background-color:transparent;color:'.$array_colors['nav_arrows_color'].';',
            'style_hover'=>'background-color:transparent;color:'.$array_colors['nav_arrows_color_hover'].';'
        ],
        'voice'=>[
            'type'=>'button',
            'position'=>'left',
            'order'=>0
        ],
        'custom'=>[
            'type'=>explode("_",$array_positions['position_custom'])[0],
            'position'=>explode("_",$array_positions['position_custom'])[1],
            'order'=>$array_orders['custom_control'],
            'style'=>'background-color:'.$array_colors['custom_background'].';color:'.$array_colors['custom_color'].';',
            'style_hover'=>'background-color:'.$array_colors['custom_background_hover'].';color:'.$array_colors['custom_color_hover'].';',
            'icon'=>$array_icons['custom'],
            'icon_library'=>$array_library_icons['custom'],
            'label'=>$custom_title
        ],
        'custom2'=>[
            'type'=>explode("_",$array_positions['position_custom2'])[0],
            'position'=>explode("_",$array_positions['position_custom2'])[1],
            'order'=>$array_orders['custom2_control'],
            'style'=>'background-color:'.$array_colors['custom2_background'].';color:'.$array_colors['custom2_color'].';',
            'style_hover'=>'background-color:'.$array_colors['custom2_background_hover'].';color:'.$array_colors['custom2_color_hover'].';',
            'icon'=>$array_icons['custom2'],
            'icon_library'=>$array_library_icons['custom2'],
            'label'=>$custom2_title
        ],
        'custom3'=>[
            'type'=>explode("_",$array_positions['position_custom3'])[0],
            'position'=>explode("_",$array_positions['position_custom3'])[1],
            'order'=>$array_orders['custom3_control'],
            'style'=>'background-color:'.$array_colors['custom3_background'].';color:'.$array_colors['custom3_color'].';',
            'style_hover'=>'background-color:'.$array_colors['custom3_background_hover'].';color:'.$array_colors['custom3_color_hover'].';',
            'icon'=>$array_icons['custom3'],
            'icon_library'=>$array_library_icons['custom3'],
            'label'=>$custom3_title
        ],
        'custom4'=>[
            'type'=>explode("_",$array_positions['position_custom4'])[0],
            'position'=>explode("_",$array_positions['position_custom4'])[1],
            'order'=>$array_orders['custom4_control'],
            'style'=>'background-color:'.$array_colors['custom4_background'].';color:'.$array_colors['custom4_color'].';',
            'style_hover'=>'background-color:'.$array_colors['custom4_background_hover'].';color:'.$array_colors['custom4_color_hover'].';',
            'icon'=>$array_icons['custom4'],
            'icon_library'=>$array_library_icons['custom4'],
            'label'=>$custom4_title
        ],
        'custom5'=>[
            'type'=>explode("_",$array_positions['position_custom5'])[0],
            'position'=>explode("_",$array_positions['position_custom5'])[1],
            'order'=>$array_orders['custom5_control'],
            'style'=>'background-color:'.$array_colors['custom5_background'].';color:'.$array_colors['custom5_color'].';',
            'style_hover'=>'background-color:'.$array_colors['custom5_background_hover'].';color:'.$array_colors['custom5_color_hover'].';',
            'icon'=>$array_icons['custom5'],
            'icon_library'=>$array_library_icons['custom5'],
            'label'=>$custom5_title
        ],
        'info'=>[
            'type'=>explode("_",$array_positions['position_info'])[0],
            'position'=>explode("_",$array_positions['position_info'])[1],
            'order'=>$array_orders['info_control'],
            'style'=>'background-color:'.$array_colors['info_background'].';color:'.$array_colors['info_color'].';',
            'style_hover'=>'background-color:'.$array_colors['info_background_hover'].';color:'.$array_colors['info_color_hover'].';',
            'icon'=>$array_icons['info'],
            'icon_library'=>$array_library_icons['info'],
        ],
        'dollhouse'=>[
            'type'=>explode("_",$array_positions['position_dollhouse'])[0],
            'position'=>explode("_",$array_positions['position_dollhouse'])[1],
            'order'=>$array_orders['dollhouse_control'],
            'style'=>'background-color:'.$array_colors['dollhouse_background'].';color:'.$array_colors['dollhouse_color'].';',
            'style_hover'=>'background-color:'.$array_colors['dollhouse_background_hover'].';color:'.$array_colors['dollhouse_color_hover'].';',
            'icon'=>$array_icons['dollhouse'],
            'icon_library'=>$array_library_icons['dollhouse'],
        ],
        'gallery'=>[
            'type'=>explode("_",$array_positions['position_gallery'])[0],
            'position'=>explode("_",$array_positions['position_gallery'])[1],
            'order'=>$array_orders['gallery_control'],
            'style'=>'background-color:'.$array_colors['gallery_background'].';color:'.$array_colors['gallery_color'].';',
            'style_hover'=>'background-color:'.$array_colors['gallery_background_hover'].';color:'.$array_colors['gallery_color_hover'].';',
            'icon'=>$array_icons['gallery'],
            'icon_library'=>$array_library_icons['gallery'],
        ],
        'facebook'=>[
            'type'=>explode("_",$array_positions['position_facebook'])[0],
            'position'=>explode("_",$array_positions['position_facebook'])[1],
            'order'=>$array_orders['facebook_control'],
            'style'=>'background-color:'.$array_colors['facebook_background'].';color:'.$array_colors['facebook_color'].';',
            'style_hover'=>'background-color:'.$array_colors['facebook_background_hover'].';color:'.$array_colors['facebook_color_hover'].';',
            'icon'=>$array_icons['facebook'],
            'icon_library'=>$array_library_icons['facebook'],
        ],
        'whatsapp'=>[
            'type'=>explode("_",$array_positions['position_whatsapp'])[0],
            'position'=>explode("_",$array_positions['position_whatsapp'])[1],
            'order'=>$array_orders['whatsapp_control'],
            'style'=>'background-color:'.$array_colors['whatsapp_background'].';color:'.$array_colors['whatsapp_color'].';',
            'style_hover'=>'background-color:'.$array_colors['whatsapp_background_hover'].';color:'.$array_colors['whatsapp_color_hover'].';',
            'icon'=>$array_icons['whatsapp'],
            'icon_library'=>$array_library_icons['whatsapp'],
        ],
        'presentation'=>[
            'type'=>explode("_",$array_positions['position_presentation'])[0],
            'position'=>explode("_",$array_positions['position_presentation'])[1],
            'order'=>$array_orders['presentation_control'],
            'style'=>'background-color:'.$array_colors['presentation_background'].';color:'.$array_colors['presentation_color'].';',
            'style_hover'=>'background-color:'.$array_colors['presentation_background_hover'].';color:'.$array_colors['presentation_color_hover'].';',
            'icon'=>$array_icons['presentation'],
            'icon_library'=>$array_library_icons['presentation'],
        ],
        'share'=>[
            'type'=>explode("_",$array_positions['position_share'])[0],
            'position'=>explode("_",$array_positions['position_share'])[1],
            'order'=>$array_orders['share_control'],
            'style'=>'background-color:'.$array_colors['share_background'].';color:'.$array_colors['share_color'].';',
            'style_hover'=>'background-color:'.$array_colors['share_background_hover'].';color:'.$array_colors['share_color_hover'].';',
            'icon'=>$array_icons['share'],
            'icon_library'=>$array_library_icons['share'],
            'providers'=>$share_providers
        ],
        'form'=>[
            'type'=>explode("_",$array_positions['position_form'])[0],
            'position'=>explode("_",$array_positions['position_form'])[1],
            'order'=>$array_orders['form_control'],
            'style'=>'background-color:'.$array_colors['form_background'].';color:'.$array_colors['form_color'].';',
            'style_hover'=>'background-color:'.$array_colors['form_background_hover'].';color:'.$array_colors['form_color_hover'].';',
            'icon'=>$array_icons['form'],
            'icon_library'=>$array_library_icons['form'],
        ],
        'live'=>[
            'type'=>explode("_",$array_positions['position_live'])[0],
            'position'=>explode("_",$array_positions['position_live'])[1],
            'order'=>$array_orders['live_control'],
            'style'=>'background-color:'.$array_colors['live_background'].';color:'.$array_colors['live_color'].';',
            'style_hover'=>'background-color:'.$array_colors['live_background_hover'].';color:'.$array_colors['live_color_hover'].';',
            'icon'=>$array_icons['live'],
            'icon_library'=>$array_library_icons['live'],
        ],
        'meeting'=>[
            'type'=>explode("_",$array_positions['position_meeting'])[0],
            'position'=>explode("_",$array_positions['position_meeting'])[1],
            'order'=>$array_orders['meeting_control'],
            'style'=>'background-color:'.$array_colors['meeting_background'].';color:'.$array_colors['meeting_color'].';',
            'style_hover'=>'background-color:'.$array_colors['meeting_background_hover'].';color:'.$array_colors['meeting_color_hover'].';',
            'icon'=>$array_icons['meeting'],
            'icon_library'=>$array_library_icons['meeting'],
        ],
        'vr'=>[
            'type'=>explode("_",$array_positions['position_vr'])[0],
            'position'=>explode("_",$array_positions['position_vr'])[1],
            'order'=>$array_orders['vr_control'],
            'style'=>'background-color:'.$array_colors['vr_background'].';color:'.$array_colors['vr_color'].';',
            'style_hover'=>'background-color:'.$array_colors['vr_background_hover'].';color:'.$array_colors['vr_color_hover'].';',
            'icon'=>$array_icons['vr'],
            'icon_library'=>$array_library_icons['vr'],
        ],
        'compass'=>[
            'type'=>explode("_",$array_positions['position_compass'])[0],
            'position'=>explode("_",$array_positions['position_compass'])[1],
            'order'=>$array_orders['compass_control'],
            'style'=>'background-color:'.$array_colors['compass_background'].';color:'.$array_colors['compass_color'].';',
            'style_hover'=>'background-color:'.$array_colors['compass_background_hover'].';color:'.$array_colors['compass_color_hover'].';'
        ],
        'icons'=>[
            'type'=>explode("_",$array_positions['position_icons'])[0],
            'position'=>explode("_",$array_positions['position_icons'])[1],
            'order'=>$array_orders['icons_control'],
            'style'=>'background-color:'.$array_colors['icons_background'].';color:'.$array_colors['icons_color'].';',
            'style_hover'=>'background-color:'.$array_colors['icons_background_hover'].';color:'.$array_colors['icons_color_hover'].';',
            'icon'=>$array_icons['icons'],
            'icon_library'=>$array_library_icons['icons'],
        ],
        'measures'=>[
            'type'=>explode("_",$array_positions['position_measures'])[0],
            'position'=>explode("_",$array_positions['position_measures'])[1],
            'order'=>$array_orders['measures_control'],
            'style'=>'background-color:'.$array_colors['measures_background'].';color:'.$array_colors['measures_color'].';',
            'style_hover'=>'background-color:'.$array_colors['measures_background_hover'].';color:'.$array_colors['measures_color_hover'].';',
            'icon'=>$array_icons['measures'],
            'icon_library'=>$array_library_icons['measures'],
        ],
        'autorotate'=>[
            'type'=>explode("_",$array_positions['position_autorotate'])[0],
            'position'=>explode("_",$array_positions['position_autorotate'])[1],
            'order'=>$array_orders['autorotate_control'],
            'style'=>'background-color:'.$array_colors['autorotate_background'].';color:'.$array_colors['autorotate_color'].';',
            'style_hover'=>'background-color:'.$array_colors['autorotate_background_hover'].';color:'.$array_colors['autorotate_color_hover'].';',
            'icon'=>$array_icons['autorotate'],
            'icon_library'=>$array_library_icons['autorotate'],
        ],
        'orient'=>[
            'type'=>explode("_",$array_positions['position_orient'])[0],
            'position'=>explode("_",$array_positions['position_orient'])[1],
            'order'=>$array_orders['orient_control'],
            'style'=>'background-color:'.$array_colors['orient_background'].';color:'.$array_colors['orient_color'].';',
            'style_hover'=>'background-color:'.$array_colors['orient_background_hover'].';color:'.$array_colors['orient_color_hover'].';',
            'icon'=>$array_icons['orient'],
            'icon_library'=>$array_library_icons['orient'],
        ],
        'annotations'=>[
            'type'=>explode("_",$array_positions['position_annotations'])[0],
            'position'=>explode("_",$array_positions['position_annotations'])[1],
            'order'=>$array_orders['annotations_control'],
            'style'=>'background-color:'.$array_colors['annotations_background'].';color:'.$array_colors['annotations_color'].';',
            'style_hover'=>'background-color:'.$array_colors['annotations_background_hover'].';color:'.$array_colors['annotations_color_hover'].';',
            'icon'=>$array_icons['annotations'],
            'icon_library'=>$array_library_icons['annotations'],
        ],
        'location'=>[
            'type'=>explode("_",$array_positions['position_location'])[0],
            'position'=>explode("_",$array_positions['position_location'])[1],
            'order'=>$array_orders['location_control'],
            'style'=>'background-color:'.$array_colors['location_background'].';color:'.$array_colors['location_color'].';',
            'style_hover'=>'background-color:'.$array_colors['location_background_hover'].';color:'.$array_colors['location_color_hover'].';',
            'icon'=>$array_icons['location'],
            'icon_library'=>$array_library_icons['location'],
            'label'=>$location_title
        ],
    ]
];
$ui_style = json_encode($ui_style,JSON_UNESCAPED_UNICODE);
if($id_preset!=null && $apply_preset==0) {
    if($id_preset==0) {
        $query = "INSERT INTO svt_editor_ui_presets(id_user,name,public,ui_style) VALUES(?,?,?,?);";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('isis',$id_user,$name_preset,$preset_public,$ui_style);
            $result = $smt->execute();
            if($result) {
                $id_new_preset = $mysqli->insert_id;
            }
        }
    } else {
        $query = "UPDATE svt_editor_ui_presets SET name=?,public=?,ui_style=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('sisi',$name_preset,$preset_public,$ui_style,$id_preset);
            $smt->execute();
        }
        $id_new_preset = 0;
    }
    ob_end_clean();
    echo json_encode(array("status"=>"ok","id_preset"=>$id_new_preset));
    exit;
}
if($apply_preset==1) {
    $query_p = "SELECT ui_style FROM svt_editor_ui_presets WHERE id=$id_preset LIMIT 1;";
    $result_p = $mysqli->query($query_p);
    if($result_p) {
        $row_p = $result_p->fetch_array(MYSQLI_ASSOC);
        $ui_style = $row_p['ui_style'];
        if (!empty($ui_style)) {
            $ui_style_array = json_decode($ui_style, true);
            foreach ($ui_style_array['controls'] as $key => $item) {
                if(!empty($item['icon_library']) && $item['icon_library']!=0) {
                    $id_icon = $item['icon_library'];
                    $query_check_icon = "SELECT * FROM svt_icons WHERE id=$id_icon AND id_virtualtour=$id_virtualtour";
                    $result_check_icon = $mysqli->query($query_check_icon);
                    if($result_check_icon->num_rows==0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_icon_tmp SELECT * FROM svt_icons WHERE id=$id_icon;");
                        $mysqli->query("UPDATE svt_icon_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_icons),id_virtualtour=$id_virtualtour;");
                        $mysqli->query("INSERT INTO svt_icons SELECT * FROM svt_icon_tmp;");
                        $id_icon_new = $mysqli->insert_id;
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_icon_tmp;");
                    } else {
                        $id_icon_new = $id_icon;
                    }
                    $ui_style_array['controls'][$key]['icon_library'] = $id_icon_new;
                }
            }
            $ui_style = json_encode($ui_style_array);
        }
    }
}
$query = "UPDATE svt_virtualtours SET font_viewer=?,arrows_nav=?,voice_commands=?,compass=?,auto_show_slider=?,nav_slider=?,show_custom=?,show_custom2=?,show_custom3=?,show_custom4=?,show_custom5=?,show_info=?,info_box_type=?,show_gallery=?,show_icons_toggle=?,show_measures_toggle=?,show_autorotation_toggle=?,show_nav_control=?,show_presentation=?,show_main_form=?,show_share=?,show_device_orientation=?,drag_device_orientation=?,show_webvr=?,show_audio=?,show_vt_title=?,show_fullscreen=?,show_map=?,show_map_tour=?,live_session=?,show_annotations=?,show_list_alt=?,fb_messenger=?,whatsapp_chat=?,meeting=?,autoclose_menu=?,autoclose_list_alt=?,autoclose_slider=?,autoclose_map=?,show_logo=?,ui_style=?,form_enable=?,custom_content=?,custom2_content=?,custom3_content=?,markers_icon=?,markers_icon_type=?,markers_id_icon_library=?,markers_color=?,markers_background=?,markers_show_room=?,pois_icon=?,pois_icon_type=?,pois_id_icon_library=?,pois_color=?,pois_background=?,pois_style=?,show_dollhouse=?,markers_tooltip_type=?,markers_tooltip_visibility=?,markers_tooltip_background=?,markers_tooltip_color=?,markers_default_scale=?,pois_tooltip_type=?,pois_tooltip_visibility=?,pois_tooltip_background=?,pois_tooltip_color=?,pois_default_scale=?,nadir_size=?,autorotate_speed=?,autorotate_inactivity=?,song_autoplay=?,fb_page_id=?,whatsapp_number=?,location_content=?,show_location=?,show_comments=?,disqus_shortname=?,song_bg_volume=?,markers_default_sound=?,pois_default_sound=?,language=?,languages_enabled=?,show_language=?,custom4_content=?,custom5_content=?,markers_animation=?,pois_animation=?,show_poweredby=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('siiiiiiiiiiisiiiiiiiiiiiiiiiiiiiiiiiiiiisisssssissississiissssissssisiiisssiisdssssissssii',$font_viewer,$arrows_nav,$voice_commands,$compass,$auto_show_slider,$nav_slider,$show_custom,$show_custom2,$show_custom3,$show_custom4,$show_custom5,$show_info,$info_box_type,$show_gallery,$show_icons_toggle,$show_measures_toggle,$show_autorotation_toggle,$show_nav_control,$show_presentation,$show_main_form,$show_share,$show_device_orientation,$drag_device_orientation,$show_webvr,$show_audio,$show_vt_title,$show_fullscreen,$show_map,$show_map_tour,$live_session,$show_annotations,$show_list_alt,$fb_messenger,$whatsapp_chat,$meeting,$autoclose_menu,$autoclose_list_alt,$autoclose_slider,$autoclose_map,$show_logo,$ui_style,$form_enable,$custom_content,$custom2_content,$custom3_content,$markers_icon,$markers_icon_type,$markers_id_icon_library,$markers_color,$markers_background,$markers_show_room,$pois_icon,$pois_icon_type,$pois_id_icon_library,$pois_color,$pois_background,$pois_style,$show_dollhouse,$markers_tooltip_type,$markers_tooltip_visibility,$markers_tooltip_background,$markers_tooltip_color,$markers_default_scale,$pois_tooltip_type,$pois_tooltip_visibility,$pois_tooltip_background,$pois_tooltip_color,$pois_default_scale,$nadir_size,$autorotate_speed,$autorotate_inactivity,$song_autoplay,$fb_page_id,$whatsapp_number,$location_content,$show_location,$show_comments,$disqus_shortname,$song_bg_volume,$markers_default_sound,$pois_default_sound,$language,$languages_enabled,$show_language,$custom4_content,$custom5_content,$markers_animation,$pois_animation,$show_poweredby,$id_virtualtour);
    $result = $smt->execute();
    if($result) {
        if($password_meeting!="keep_password") {
            $query = "UPDATE svt_virtualtours SET password_meeting=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si',$password_meeting,$id_virtualtour);
                $smt->execute();
            }
        }
        if($password_livesession!="keep_password") {
            $query = "UPDATE svt_virtualtours SET password_livesession=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si',$password_livesession,$id_virtualtour);
                $smt->execute();
            }
        }
        if($disqus_public_key!="keep_disqus_public_key") {
            $query = "UPDATE svt_virtualtours SET disqus_public_key=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('si',$disqus_public_key,$id_virtualtour);
                $smt->execute();
            }
        }
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