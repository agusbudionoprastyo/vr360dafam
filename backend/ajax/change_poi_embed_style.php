<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id = (int)$_POST['id'];
$yaw = (float)$_POST['yaw'];
$pitch = (float)$_POST['pitch'];
$embed_type = strip_tags($_POST['embed_type']);
$current_embed_type = strip_tags($_POST['embed_type_current']);
if(empty($embed_type)) {
    $content_q_add = '';
    if($current_embed_type=='callout') {
        $content_q_add = ",type=''";
    }
    $query = "UPDATE svt_pois SET embed_type=NULL $content_q_add WHERE id=$id;";
} else {
    $coord_1 = ($pitch+5).",".($yaw-10);
    $coord_2 = ($pitch-5).",".($yaw-10);
    $coord_3 = ($pitch+5).",".($yaw+10);
    $coord_4 = ($pitch-5).",".($yaw+10);
    $embed_coords = "$coord_1|$coord_2|$coord_3|$coord_4";
    $embed_size = "300,150";
    $content_q_add = "";
    $params = '';
    if($current_embed_type=='callout') {
        $content_q_add = ",type=''";
    }
    switch($_POST['embed_type']) {
        case 'gallery':
        case 'video':
        case 'video_chroma':
        case 'video_transparent':
        case 'link':
            $embed_content="";
            $content_q_add = ",type=NULL,content=NULL";
            break;
        case 'selection':
            $embed_content="border-width:3px;";
            break;
        case "callout":
            $embed_content="";
            $embed_type="";
            $embed_size="";
            $embed_coords="";
            $params = '{"title":"Title","description":"Description","dir":"right","title_font_size":"26","title_margin":"10","description_font_size":"14","main_color":"#ffffff","content_bg_color":"rgba(255, 255, 255, 0)","title_bg_color":"rgba(255, 255, 255, 0.8)","title_font_color":"#000000","description_font_color":"#ffffff","content_height":80,"content_width":300,"line_size":100,"rotate":45,"open":"click"}';
            $content_q_add = ",type='callout'";
            break;
        default:
            $embed_content="";
            break;
    }
    $query = "UPDATE svt_pois SET params='$params',embed_type='$embed_type',embed_size='$embed_size',embed_coords='$embed_coords',embed_content='$embed_content' $content_q_add WHERE id=$id;";
}
$result=$mysqli->query($query);
if($result) {
    $mysqli->query("UPDATE svt_pois_lang SET embed_content=NULL WHERE id_poi=$id;");
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","q"=>$mysqli->error));
}