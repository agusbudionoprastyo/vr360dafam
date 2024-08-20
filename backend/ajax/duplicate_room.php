<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
ini_set('max_execution_time', 9999);
require_once("../../db/connection.php");
require_once("../functions.php");
$duplicate_pois = $_POST['duplicate_pois'];
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
$id_room = (int)$_POST['id_room'];
$duplicate_target_vt = (int)$_POST['duplicate_target_vt'];
if($duplicate_target_vt!=0) {
    $name_virtualtour_sel = get_virtual_tour($duplicate_target_vt,$_SESSION['id_user'])['name'];
    $_SESSION['id_virtualtour_sel'] = $duplicate_target_vt;
    $_SESSION['name_virtualtour_sel'] = $name_virtualtour_sel;
}
session_write_close();
$array_rooms_alt = array();
$array_measures = array();
$mysqli->query("CREATE TEMPORARY TABLE svt_room_tmp SELECT * FROM svt_rooms WHERE id = $id_room;");
if($duplicate_target_vt==0) {
    $duplicated_label = _("duplicated");
    $mysqli->query("UPDATE svt_room_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms),name=CONCAT(name,' ($duplicated_label)'),id_map=NULL,map_top=NULL,map_left=NULL,lat=NULL,lon=NULL,access_count=0;");
} else {
    $mysqli->query("UPDATE svt_room_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms),id_virtualtour=$duplicate_target_vt,id_map=NULL,map_top=NULL,map_left=NULL,lat=NULL,lon=NULL,access_count=0;");
}
$mysqli->query("INSERT INTO svt_rooms SELECT * FROM svt_room_tmp;");
$id_room_new = $mysqli->insert_id;
$mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_room_tmp;");
$result = $mysqli->query("SELECT id_room FROM svt_rooms_lang WHERE id_room=$id_room;");
if($result) {
    if($result->num_rows>0) {
        $mysqli->query("CREATE TEMPORARY TABLE svt_rooms_lang_tmp SELECT * FROM svt_rooms_lang WHERE id_room = $id_room;");
        $mysqli->query("UPDATE svt_rooms_lang_tmp SET id_room=$id_room_new;");
        $mysqli->query("INSERT INTO svt_rooms_lang SELECT * FROM svt_rooms_lang_tmp;");
        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_rooms_lang_tmp;");
    }
}
$result = $mysqli->query("SELECT id FROM svt_rooms_alt WHERE id_room=$id_room;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_room_alt = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_rooms_alt_tmp SELECT * FROM svt_rooms_alt WHERE id = $id_room_alt;");
            $mysqli->query("UPDATE svt_rooms_alt_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms_alt),id_room=$id_room_new;");
            $mysqli->query("INSERT INTO svt_rooms_alt SELECT * FROM svt_rooms_alt_tmp;");
            $id_room_alt_new = $mysqli->insert_id;
            $array_rooms_alt[$id_room_alt] = $id_room_alt_new;
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_rooms_alt_tmp;");
            $result_a = $mysqli->query("SELECT id_room_alt FROM svt_rooms_alt_lang WHERE id_room_alt=$id_room_alt;");
            if($result_a) {
                if($result_a->num_rows>0) {
                    $mysqli->query("CREATE TEMPORARY TABLE svt_rooms_alt_lang_tmp SELECT * FROM svt_rooms_alt_lang WHERE id_room_alt = $id_room_alt;");
                    $mysqli->query("UPDATE svt_rooms_alt_lang_tmp SET id_room_alt=$id_room_alt_new;");
                    $mysqli->query("INSERT INTO svt_rooms_alt_lang SELECT * FROM svt_rooms_alt_lang_tmp;");
                    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_rooms_alt_lang_tmp;");
                }
            }
        }
    }
}
$result = $mysqli->query("SELECT id FROM svt_measures WHERE id_room=$id_room;");
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_measure = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_measures_tmp SELECT * FROM svt_measures WHERE id = $id_measure;");
            $mysqli->query("UPDATE svt_measures_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_measures),id_room=$id_room_new;");
            $mysqli->query("INSERT INTO svt_measures SELECT * FROM svt_measures_tmp;");
            $id_measure_new = $mysqli->insert_id;
            $array_measures[$id_measure] = $id_measure_new;
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_measures_tmp;");
        }
    }
}
if($duplicate_pois) {
    $array_pois = array();
    $result = $mysqli->query("SELECT id FROM svt_pois WHERE id_room=$id_room;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_poi = $row['id'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_poi_tmp SELECT * FROM svt_pois WHERE id = $id_poi;");
                $mysqli->query("UPDATE svt_poi_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_pois),access_count=0,id_room=$id_room_new;");
                $mysqli->query("INSERT INTO svt_pois SELECT * FROM svt_poi_tmp;");
                $id_poi_new = $mysqli->insert_id;
                $array_pois[$id_poi] = $id_poi_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_poi_tmp;");
                $result_p = $mysqli->query("SELECT id_poi FROM svt_pois_lang WHERE id_poi=$id_poi;");
                if($result_p) {
                    if ($result_p->num_rows > 0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_pois_lang_tmp SELECT * FROM svt_pois_lang WHERE id_poi = $id_poi;");
                        $mysqli->query("UPDATE svt_pois_lang_tmp SET id_poi=$id_poi_new;");
                        $mysqli->query("INSERT INTO svt_pois_lang SELECT * FROM svt_pois_lang_tmp;");
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_pois_lang_tmp;");
                    }
                }
            }
        }
    }
    foreach ($array_pois as $id_poi=>$id_poi_new) {
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
    }
}
if($duplicate_pois) {
    $query = "SELECT id,content FROM svt_pois WHERE type='switch_pano' AND id_room=$id_room_new;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $content = $row['content'];
                if(!empty($content) && $content!='0') {
                    $id_room_alt_new = $array_rooms_alt[$content];
                    $mysqli->query("UPDATE svt_pois SET content='$id_room_alt_new' WHERE id=$id;");
                }
            }
        }
    }
    $query = "SELECT id,visible_multiview_ids FROM svt_pois WHERE visible_multiview_ids!='' AND id_room=$id_room_new;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $visible_multiview_ids = $row['visible_multiview_ids'];
                $array_mi = explode(",",$visible_multiview_ids);
                $visible_multiview_ids_new = '';
                foreach ($array_mi as $mi) {
                    if($mi!=0) {
                        $id_room_alt_new = $array_rooms_alt[$mi];
                        $visible_multiview_ids_new .= $id_room_alt_new.",";
                    } else {
                        $visible_multiview_ids_new .= "0,";
                    }
                }
                $visible_multiview_ids_new = rtrim($visible_multiview_ids_new,",");
                $mysqli->query("UPDATE svt_pois SET visible_multiview_ids='$visible_multiview_ids_new' WHERE id=$id;");
            }
        }
    }
}
$query = "SELECT id,visible_multiview_ids FROM svt_measures WHERE visible_multiview_ids!='' AND id_room=$id_room_new;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $visible_multiview_ids = $row['visible_multiview_ids'];
            $array_mi = explode(",",$visible_multiview_ids);
            $visible_multiview_ids_new = '';
            foreach ($array_mi as $mi) {
                if($mi!=0) {
                    $id_room_alt_new = $array_rooms_alt[$mi];
                    $visible_multiview_ids_new .= $id_room_alt_new.",";
                } else {
                    $visible_multiview_ids_new .= "0,";
                }
            }
            $visible_multiview_ids_new = rtrim($visible_multiview_ids_new,",");
            $mysqli->query("UPDATE svt_measures SET visible_multiview_ids='$visible_multiview_ids_new' WHERE id=$id;");
        }
    }
}
ob_end_clean();
echo json_encode(array("status"=>"ok"));