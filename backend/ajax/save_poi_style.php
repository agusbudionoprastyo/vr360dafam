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
$id_poi = (int)$_POST['id'];
$color = strip_tags($_POST['color']);
$background = strip_tags($_POST['background']);
$icon = strip_tags($_POST['icon']);
$label = strip_tags($_POST['label']);
$style = strip_tags($_POST['style']);
$id_icon_library = (int)$_POST['id_icon_library'];
$tooltip_type = strip_tags($_POST['tooltip_type']);
$tooltip_visibility = strip_tags($_POST['tooltip_visibility']);
$tooltip_background = strip_tags($_POST['tooltip_background']);
$tooltip_color = strip_tags($_POST['tooltip_color']);
$tooltip_text = strip_tags($_POST['tooltip_text'],['p','b','strong','i','s','br','ul','li','h1','h2','h3','h4','h5','h6']);
$css_class = strip_tags($_POST['css_class']);
$embed_content = $_POST['embed_content'];
if(empty($embed_content)) $embed_content=NULL;
$embed_video_autoplay = (int)$_POST['embed_video_autoplay'];
$embed_video_loop = (int)$_POST['embed_video_loop'];
$embed_video_muted = (int)$_POST['embed_video_muted'];
$embed_gallery_autoplay = $_POST['embed_gallery_autoplay'];
if(empty($embed_gallery_autoplay)) $embed_gallery_autoplay=0;
$embed_gallery_autoplay = (int)$embed_gallery_autoplay;
$animation = strip_tags($_POST['animation']);
$icon_type = strip_tags($_POST['icon_type']);
$embed_params = strip_tags($_POST['embed_params']);
$sound = strip_tags($_POST['sound']);
if(empty($sound)) {
    $sound = NULL;
} else {
    $sound = "content/$sound";
}
$array_lang = json_decode($_POST['array_lang'],true);
$query = "UPDATE svt_pois SET color=?,background=?,icon=?,label=?,style=?,id_icon_library=?,tooltip_type=?,tooltip_visibility=?,tooltip_text=?,css_class=?,embed_content=?,embed_video_autoplay=?,embed_video_loop=?,embed_video_muted=?,embed_gallery_autoplay=?,animation=?,icon_type=?,embed_params=?,tooltip_background=?,tooltip_color=?,sound=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssssiisssssiiiissssssi',$color,$background,$icon,$label,$style,$id_icon_library,$tooltip_type,$tooltip_visibility,$tooltip_text,$css_class,$embed_content,$embed_video_autoplay,$embed_video_loop,$embed_video_muted,$embed_gallery_autoplay,$animation,$icon_type,$embed_params,$tooltip_background,$tooltip_color,$sound,$id_poi);
    $result = $smt->execute();
    if ($result) {
        save_input_langs($array_lang,'svt_pois_lang','id_poi',$id_poi);
        if(strpos($embed_content, 'content/') === 0) {
            $content_image_gt = str_replace("content/","",$embed_content);
            include("../../services/generate_thumb.php");
        }
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