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
$yaw_m = $_POST['yaw_m'];
$pitch_m = $_POST['pitch_m'];
if($yaw_m=='') $yaw_m = NULL; else $yaw_m = (float)$yaw_m;
if($pitch_m=='') $pitch_m = NULL; else $pitch_m = (float)$pitch_m;
$id_room_target = (int)$_POST['id_room_target'];
$rotateX = 0;
$rotateZ = 0;
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
$lookat = (int)$_POST['lookat'];
$backlink = (int)$_POST['backlink'];
$query_v = "SELECT markers_icon,markers_id_icon_library,markers_color,markers_background,markers_show_room,markers_tooltip_type,markers_icon_type,markers_tooltip_visibility,markers_tooltip_background,markers_tooltip_color,markers_default_scale,markers_default_sound,markers_animation FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result_v = $mysqli->query($query_v);
if($result_v) {
    if ($result_v->num_rows == 1) {
        $row_v = $result_v->fetch_array(MYSQLI_ASSOC);
        $markers_icon = $row_v['markers_icon'];
        $markers_icon_type = $row_v['markers_icon_type'];
        $markers_id_icon_library = $row_v['markers_id_icon_library'];
        if ($_POST['embed_type'] == 'selection') {
            $markers_color = 'rgb(255,255,255)';
            $markers_background = 'rgba(255,255,255,0.1)';
        } else {
            $markers_color = $row_v['markers_color'];
            $markers_background = $row_v['markers_background'];
        }
        $markers_show_room = $row_v['markers_show_room'];
        $markers_tooltip_type = $row_v['markers_tooltip_type'];
        $markers_tooltip_visibility = $row_v['markers_tooltip_visibility'];
        $markers_tooltip_background = $row_v['markers_tooltip_background'];
        $markers_tooltip_color = $row_v['markers_tooltip_color'];
        $markers_default_scale = $row_v['markers_default_scale'];
        $markers_default_sound = $row_v['markers_default_sound'];
        $markers_animation = $row_v['markers_animation'];
        $query = "INSERT INTO svt_markers(id_room,yaw,pitch,id_room_target,rotateX,rotateZ,icon,icon_type,id_icon_library,color,background,show_room,tooltip_type,tooltip_visibility,tooltip_background,tooltip_color,yaw_room_target,pitch_room_target,embed_type,embed_coords,embed_size,embed_content,lookat,scale,sound,animation) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
        if ($smt = $mysqli->prepare($query)) {
            $smt->bind_param('iddiiissississssddssssiiss',$id_room,$yaw,$pitch,$id_room_target,$rotateX,$rotateZ,$markers_icon,$markers_icon_type,$markers_id_icon_library,$markers_color,$markers_background,$markers_show_room,$markers_tooltip_type,$markers_tooltip_visibility,$markers_tooltip_background,$markers_tooltip_color,$yaw_m,$pitch_m,$embed_type,$embed_coords,$embed_size,$embed_content,$lookat,$markers_default_scale,$markers_default_sound,$markers_animation);
            $result = $smt->execute();
            if ($result) {
                $insert_id = $mysqli->insert_id;
                if ($backlink == 1) {
                    $yaw = (float)$yaw_m - 180;
                    $yaw_m = NULL;
                    $pitch_m = NULL;
                    $query = "INSERT INTO svt_markers(id_room,yaw,pitch,id_room_target,rotateX,rotateZ,icon,icon_type,id_icon_library,color,background,show_room,tooltip_type,tooltip_visibility,tooltip_background,tooltip_color,yaw_room_target,pitch_room_target,embed_type,embed_coords,embed_size,embed_content,lookat,scale,sound) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
                    if ($smt = $mysqli->prepare($query)) {
                        $smt->bind_param('iddiiissississssddssssiis', $id_room_target, $yaw, $pitch, $id_room, $rotateX, $rotateZ, $markers_icon, $markers_icon_type, $markers_id_icon_library, $markers_color, $markers_background, $markers_show_room, $markers_tooltip_type, $markers_tooltip_visibility, $markers_tooltip_background, $markers_tooltip_color, $yaw_m, $pitch_m, $embed_type, $embed_coords, $embed_size, $embed_content, $lookat, $markers_default_scale,$markers_default_sound);
                        $smt->execute();
                    }
                }
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