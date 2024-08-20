<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
ini_set('max_execution_time', 9999);
require_once("../../db/connection.php");
session_write_close();
$id_poi = (int)$_POST['id_poi'];
$id_room_target = (int)$_POST['id_room_target'];
$mysqli->query("CREATE TEMPORARY TABLE svt_poi_tmp SELECT * FROM svt_pois WHERE id = $id_poi;");
$mysqli->query("UPDATE svt_poi_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_pois),access_count=0,id_room=$id_room_target;");
$mysqli->query("INSERT INTO svt_pois SELECT * FROM svt_poi_tmp;");
$id_poi_new = $mysqli->insert_id;
$mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_poi_tmp;");
$result = $mysqli->query("SELECT id FROM svt_poi_gallery WHERE id_poi=$id_poi;");
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi_gallery = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_poi_gallery_tmp SELECT * FROM svt_poi_gallery WHERE id = $id_poi_gallery;");
            $mysqli->query("UPDATE svt_poi_gallery_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_poi_gallery),id_poi=$id_poi_new;");
            $mysqli->query("INSERT INTO svt_poi_gallery SELECT * FROM svt_poi_gallery_tmp;");
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_poi_gallery_tmp;");
        }
    }
}
$result = $mysqli->query("SELECT id_poi FROM svt_pois_lang WHERE id_poi=$id_poi;");
if($result) {
    if ($result->num_rows > 0) {
        $mysqli->query("CREATE TEMPORARY TABLE svt_pois_lang_tmp SELECT * FROM svt_pois_lang WHERE id_poi = $id_poi;");
        $mysqli->query("UPDATE svt_pois_lang_tmp SET id_poi=$id_poi_new;");
        $mysqli->query("INSERT INTO svt_pois_lang SELECT * FROM svt_pois_lang_tmp;");
        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_pois_lang_tmp;");
    }
}
$result = $mysqli->query("SELECT id FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi;");
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi_embedded_gallery = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_poi_embedded_gallery_tmp SELECT * FROM svt_poi_embedded_gallery WHERE id = $id_poi_embedded_gallery;");
            $mysqli->query("UPDATE svt_poi_embedded_gallery_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_poi_embedded_gallery),id_poi=$id_poi_new;");
            $mysqli->query("INSERT INTO svt_poi_embedded_gallery SELECT * FROM svt_poi_embedded_gallery_tmp;");
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_poi_embedded_gallery_tmp;");
        }
    }
}
$result = $mysqli->query("SELECT id FROM svt_poi_objects360 WHERE id_poi=$id_poi;");
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi_object360 = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_poi_objects360_tmp SELECT * FROM svt_poi_objects360 WHERE id = $id_poi_object360;");
            $mysqli->query("UPDATE svt_poi_objects360_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_poi_objects360),id_poi=$id_poi_new;");
            $mysqli->query("INSERT INTO svt_poi_objects360 SELECT * FROM svt_poi_objects360_tmp;");
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_poi_objects360_tmp;");
        }
    }
}
$result = $mysqli->query("SELECT id,content FROM svt_pois WHERE type='pointclouds' AND id=$id_poi_new LIMIT 1;");
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $content = $row['content'];
        $path = dirname($content);
        if(file_exists('../../viewer/'.$path.'/settings_'.$id_poi.'.json')) {
            copy('../../viewer/'.$path.'/settings_'.$id_poi.'.json','../../viewer/'.$path.'/settings_'.$id_poi_new.'.json');
        }
    }
}
ob_end_clean();
echo json_encode(array("status"=>"ok","id"=>$id_poi_new));