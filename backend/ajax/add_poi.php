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
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_room = (int)$_POST['id_room'];
$yaw = (float)$_POST['yaw'];
$pitch = (float)$_POST['pitch'];
$type = strip_tags($_POST['type']);
if(empty($type)) $type=NULL;
$embed_type = strip_tags($_POST['embed_type']);
if(empty($embed_type)) {
    $embed_type=NULL;
    $embed_coords=NULL;
    $embed_size=NULL;
    $embed_content=NULL;
} else {
    $coord_1 = ($pitch+5).",".($yaw-10);
    $coord_2 = ($pitch-5).",".($yaw-10);
    $coord_3 = ($pitch+5).",".($yaw+10);
    $coord_4 = ($pitch-5).",".($yaw+10);
    $embed_coords = "$coord_1|$coord_2|$coord_3|$coord_4";
    $embed_size = "300,150";
    if($_POST['embed_type']=='selection') {
        $embed_content="border-width:3px;";
    } else {
        $embed_content="";
    }
}
$query_v = "SELECT pois_icon,pois_id_icon_library,pois_color,pois_background,pois_style,pois_tooltip_type,pois_icon_type,pois_tooltip_visibility,pois_tooltip_background,pois_tooltip_color,pois_default_scale,pois_default_sound,pois_animation FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result_v = $mysqli->query($query_v);
if($result_v) {
    if ($result_v->num_rows == 1) {
        $row_v = $result_v->fetch_array(MYSQLI_ASSOC);
        $pois_icon = $row_v['pois_icon'];
        $pois_icon_type = $row_v['pois_icon_type'];
        $pois_id_icon_library = $row_v['pois_id_icon_library'];
        if ($_POST['embed_type'] == 'selection') {
            $pois_color = 'rgb(255,255,255)';
            $pois_background = 'rgba(255,255,255,0.1)';
        } else {
            $pois_color = $row_v['pois_color'];
            $pois_background = $row_v['pois_background'];
        }
        $pois_style = $row_v['pois_style'];
        $pois_tooltip_type = $row_v['pois_tooltip_type'];
        $pois_tooltip_visibility = $row_v['pois_tooltip_visibility'];
        $pois_tooltip_background = $row_v['pois_tooltip_background'];
        $pois_tooltip_color = $row_v['pois_tooltip_color'];
        $pois_default_scale = $row_v['pois_default_scale'];
        $pois_default_sound = $row_v['pois_default_sound'];
        $pois_animation = $row_v['pois_animation'];
        if ($_POST['type'] == 'callout') {
            $params = '{"title":"Title","description":"Description","dir":"right","title_font_size":"26","title_margin":"10","description_font_size":"14","main_color":"#ffffff","content_bg_color":"rgba(255, 255, 255, 0)","title_bg_color":"rgba(255, 255, 255, 0.8)","title_font_color":"#000000","description_font_color":"#ffffff","content_height":80,"content_width":300,"line_size":100,"rotate":45,"open":"click"}';
        } else {
            $params = '';
        }
        $query = "INSERT INTO svt_pois(id_room,yaw,pitch,type,icon,icon_type,id_icon_library,color,background,style,tooltip_type,tooltip_visibility,tooltip_background,tooltip_color,embed_type,embed_coords,embed_size,embed_content,scale,params,sound,animation) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('iddsssississssssssisss',$id_room,$yaw,$pitch,$type,$pois_icon,$pois_icon_type,$pois_id_icon_library,$pois_color,$pois_background,$pois_style,$pois_tooltip_type,$pois_tooltip_visibility,$pois_tooltip_background,$pois_tooltip_color,$embed_type,$embed_coords,$embed_size,$embed_content,$pois_default_scale,$params,$pois_default_sound,$pois_animation);
            $result = $smt->execute();
            if ($result) {
                $insert_id = $mysqli->insert_id;
                ob_end_clean();
                echo json_encode(array("status" => "ok", "id" => $insert_id));
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error"));
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}