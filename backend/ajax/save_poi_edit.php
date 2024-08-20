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
$type = strip_tags($_POST['type']);
$content = $_POST['content'];
$title = strip_tags($_POST['title']);
$description = strip_tags($_POST['description']);
if($type=='html_sc') {
    $content = htmlspecialchars_decode($content);
    if(substr( $content, 0, 4 ) != "<div") {
        $content = "<div>".$content."</div>";
    }
}
$target = strip_tags($_POST['target']);
if($target=='') $target=NULL;
$id_room = (int)$_POST['id_room'];
$id_poi_autoopen = $_POST['id_poi_autoopen'];
if(empty($id_poi_autoopen)) $id_poi_autoopen = 'NULL'; else $id_poi_autoopen = (int)$id_poi_autoopen;
$view_type = strip_tags($_POST['view_type']);
$box_pos = strip_tags($_POST['box_pos']);
$box_max_width = $_POST['box_max_width'];
if(empty($box_max_width)) $box_max_width = 350;
$box_max_width = (int)$box_max_width;
$box_maximize = (int)$_POST['box_maximize'];
$box_background = strip_tags($_POST['box_background']);
$box_color = strip_tags($_POST['box_color']);
$song_bg_volume = (float)$_POST['song_bg_volume'];
$params = strip_tags($_POST['params']);
$auto_close = $_POST['auto_close'];
if(empty($auto_close) || $auto_close<0) {
    $auto_close=0;
}
$auto_close = (int)$auto_close;
$mysqli->query("UPDATE svt_rooms SET id_poi_autoopen=$id_poi_autoopen WHERE id=$id_room;");
$query = "UPDATE svt_pois SET content=?,title=?,description=?,target=?,view_type=?,box_pos=?,box_max_width=?,box_maximize=?,song_bg_volume=?,params=?,auto_close=?,box_background=?,box_color=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('ssssisiidsissi',  $content,$title,$description,$target,$view_type,$box_pos,$box_max_width,$box_maximize,$song_bg_volume,$params,$auto_close,$box_background,$box_color,$id_poi);
    $result = $smt->execute();
    if ($result) {
        if(strpos($content, 'content/') === 0) {
            $content_image_gt = str_replace("content/","",$content);
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