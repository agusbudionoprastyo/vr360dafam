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
$duplicate_maps = $_POST['duplicate_maps'];
$duplicate_gallery = $_POST['duplicate_gallery'];
$duplicate_info_box = $_POST['duplicate_info_box'];
$duplicate_presentation = $_POST['duplicate_presentation'];
$duplicate_rooms = $_POST['duplicate_rooms'];
$duplicate_pois = $_POST['duplicate_pois'];
$duplicate_markers = $_POST['duplicate_markers'];
$duplicate_products = $_POST['duplicate_products'];
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
$id_user = (int)$_POST['id_user'];
$id_virtualtour = (int)$_POST['id_virtualtour'];
$s3Client = null;
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    if($s3Client==null) {
        $s3Client = init_s3_client_no_wrapper($s3_params);
        if($s3Client==null) {
            $s3_enabled = false;
        } else {
            $s3_enabled = true;
        }
    } else {
        $s3_enabled = true;
    }
}
if(get_user_role($id_user)=='administrator') {
    $query = "SELECT * FROM svt_virtualtours WHERE id=$id_virtualtour; ";
} else {
    $query = "SELECT * FROM svt_virtualtours WHERE id_user=$id_user AND id=$id_virtualtour; ";
}
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==0) {
        ob_end_clean();
        echo json_encode(array("status"=>"unauthorized"));
        exit;
    }
}
$duplicated_label = _("duplicated");
$mysqli->query("CREATE TEMPORARY TABLE svt_virtualtour_tmp SELECT * FROM svt_virtualtours WHERE id = $id_virtualtour;");
$add_q = "";
if(!$duplicate_info_box) {
    $add_q =",info_box=NULL";
}
$mysqli->query("UPDATE svt_virtualtour_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_virtualtours),name=CONCAT(name,' ($duplicated_label)'),date_created=NOW(),ga_tracking_id=NULL,friendly_url=NULL,show_in_first_page=0 $add_q;");
$mysqli->query("INSERT INTO svt_virtualtours SELECT * FROM svt_virtualtour_tmp;");
$id_virtualtour_new = $mysqli->insert_id;
$code_new = md5($id_virtualtour_new);
$mysqli->query("UPDATE svt_virtualtours SET code='$code_new' WHERE id=$id_virtualtour_new;");
$mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_virtualtours_tmp;");
$result = $mysqli->query("SELECT id_virtualtour FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        $mysqli->query("CREATE TEMPORARY TABLE svt_virtualtours_lang_tmp SELECT * FROM svt_virtualtours_lang WHERE id_virtualtour = $id_virtualtour;");
        $mysqli->query("UPDATE svt_virtualtours_lang_tmp SET id_virtualtour=$id_virtualtour_new;");
        $mysqli->query("INSERT INTO svt_virtualtours_lang SELECT * FROM svt_virtualtours_lang_tmp;");
        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_virtualtours_lang_tmp;");
    }
}
$array_rooms = array();
$array_rooms_alt = array();
$array_measures = array();
$array_maps = array();
$array_products = array();
$id_room_default_mapping = array();
if($duplicate_maps) {
    $result = $mysqli->query("SELECT id,id_room_default FROM svt_maps WHERE id_virtualtour=$id_virtualtour;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_map = $row['id'];
                $id_room_default = $row['id_room_default'];
                if(!empty($id_room_default) && ($duplicate_rooms)) {
                    $id_room_default_mapping[$id_map]=$id_room_default;
                }
                $mysqli->query("CREATE TEMPORARY TABLE svt_map_tmp SELECT * FROM svt_maps WHERE id = $id_map;");
                $mysqli->query("UPDATE svt_map_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_maps),id_virtualtour=$id_virtualtour_new,id_room_default=NULL;");
                $mysqli->query("INSERT INTO svt_maps SELECT * FROM svt_map_tmp;");
                $id_map_new = $mysqli->insert_id;
                $array_maps[$id_map] = $id_map_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_map_tmp;");
                $result_m = $mysqli->query("SELECT id_map FROM svt_maps_lang WHERE id_map=$id_map;");
                if($result_m) {
                    if($result_m->num_rows>0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_maps_lang_tmp SELECT * FROM svt_maps_lang WHERE id_map = $id_map;");
                        $mysqli->query("UPDATE svt_maps_lang_tmp SET id_map=$id_map_new;");
                        $mysqli->query("INSERT INTO svt_maps_lang SELECT * FROM svt_maps_lang_tmp;");
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_maps_lang_tmp;");
                    }
                }
            }
        }
    }
}
if($duplicate_rooms) {
    $result = $mysqli->query("SELECT id,id_map FROM svt_rooms WHERE id_virtualtour=$id_virtualtour;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_room = $row['id'];
                $id_map = $row['id_map'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_room_tmp SELECT * FROM svt_rooms WHERE id = $id_room;");
                if(!empty($id_map) && ($duplicate_maps)) {
                    $id_map_new = $array_maps[$id_map];
                    $mysqli->query("UPDATE svt_room_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms),access_count=0,id_virtualtour=$id_virtualtour_new,id_map=$id_map_new;");
                } else {
                    $mysqli->query("UPDATE svt_room_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms),access_count=0,id_virtualtour=$id_virtualtour_new;");
                }
                $mysqli->query("INSERT INTO svt_rooms SELECT * FROM svt_room_tmp;");
                $id_room_new = $mysqli->insert_id;
                $array_rooms[$id_room] = $id_room_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_room_tmp;");
                $result_r = $mysqli->query("SELECT id_room FROM svt_rooms_lang WHERE id_room=$id_room;");
                if($result_r) {
                    if($result_r->num_rows>0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_rooms_lang_tmp SELECT * FROM svt_rooms_lang WHERE id_room = $id_room;");
                        $mysqli->query("UPDATE svt_rooms_lang_tmp SET id_room=$id_room_new;");
                        $mysqli->query("INSERT INTO svt_rooms_lang SELECT * FROM svt_rooms_lang_tmp;");
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_rooms_lang_tmp;");
                    }
                }
            }
        }
    }
    $result = $mysqli->query("SELECT id,video_end_goto FROM svt_rooms WHERE video_end_goto!=0 AND id_virtualtour=$id_virtualtour_new;");
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $video_end_goto = $row['video_end_goto'];
                if(array_key_exists($video_end_goto,$array_rooms)) {
                    $video_end_goto = $array_rooms[$video_end_goto];
                    $mysqli->query("UPDATE svt_rooms SET video_end_goto=$video_end_goto WHERE id=$id;");
                }
            }
        }
    }
}
foreach ($id_room_default_mapping as $id_map_t => $id_room_default_t) {
    $id_map_new = $array_maps[$id_map_t];
    $id_room_default_new = $array_rooms[$id_room_default_t];
    $mysqli->query("UPDATE svt_maps SET id_room_default=$id_room_default_new WHERE id=$id_map_new;");
}
if($duplicate_products) {
    $result = $mysqli->query("SELECT id FROM svt_products WHERE id_virtualtour=$id_virtualtour;");
    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_product = $row['id'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_products_tmp SELECT * FROM svt_products WHERE id = $id_product;");
                $mysqli->query("UPDATE svt_products_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_products),id_virtualtour=$id_virtualtour_new;");
                $mysqli->query("INSERT INTO svt_products SELECT * FROM svt_products_tmp;");
                $id_product_new = $mysqli->insert_id;
                $array_products[$id_product] = $id_product_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_products_tmp;");
                $result_i = $mysqli->query("SELECT id FROM svt_product_images WHERE id_product=$id_product;");
                if ($result_i) {
                    if ($result_i->num_rows > 0) {
                        while ($row_i = $result_i->fetch_array(MYSQLI_ASSOC)) {
                            $id_product_image = $row_i['id'];
                            $mysqli->query("CREATE TEMPORARY TABLE svt_product_images_tmp SELECT * FROM svt_product_images WHERE id = $id_product_image;");
                            $mysqli->query("UPDATE svt_product_images_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_product_images),id_product=$id_product_new;");
                            $mysqli->query("INSERT INTO svt_product_images SELECT * FROM svt_product_images_tmp;");
                            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_product_images_tmp;");
                        }
                    }
                }
                $result_p = $mysqli->query("SELECT id_product FROM svt_products_lang WHERE id_product=$id_product;");
                if($result_p) {
                    if($result_p->num_rows>0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_products_lang_tmp SELECT * FROM svt_products_lang WHERE id_product = $id_product;");
                        $mysqli->query("UPDATE svt_products_lang_tmp SET id_product=$id_product_new;");
                        $mysqli->query("INSERT INTO svt_products_lang SELECT * FROM svt_products_lang_tmp;");
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_products_lang_tmp;");
                    }
                }
            }
        }
    }
}
$array_pois = array();
foreach ($array_rooms as $id_room=>$id_room_new) {
    if($duplicate_markers) {
        $result = $mysqli->query("SELECT id,id_room_target FROM svt_markers WHERE id_room=$id_room;");
        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $id_marker = $row['id'];
                    $id_room_target = $row['id_room_target'];
                    $id_room_target_new = $array_rooms[$id_room_target];
                    $mysqli->query("CREATE TEMPORARY TABLE svt_marker_tmp SELECT * FROM svt_markers WHERE id = $id_marker;");
                    $mysqli->query("UPDATE svt_marker_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_markers),id_room=$id_room_new,id_room_target=$id_room_target_new;");
                    $mysqli->query("INSERT INTO svt_markers SELECT * FROM svt_marker_tmp;");
                    $id_marker_new = $mysqli->insert_id;
                    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_marker_tmp;");
                    $result_p = $mysqli->query("SELECT id_marker FROM svt_markers_lang WHERE id_marker=$id_marker;");
                    if($result_p) {
                        if($result_p->num_rows>0) {
                            $mysqli->query("CREATE TEMPORARY TABLE svt_markers_lang_tmp SELECT * FROM svt_markers_lang WHERE id_marker = $id_marker;");
                            $mysqli->query("UPDATE svt_markers_lang_tmp SET id_marker=$id_marker_new;");
                            $mysqli->query("INSERT INTO svt_markers_lang SELECT * FROM svt_markers_lang_tmp;");
                            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_markers_lang_tmp;");
                        }
                    }
                }
            }
        }
    }
    if($duplicate_pois) {
        $result = $mysqli->query("SELECT id,type,content FROM svt_pois WHERE id_room=$id_room;");
        if ($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $id_poi = $row['id'];
                    $type = $row['type'];
                    if($type=='product' && !$duplicate_products) continue;
                    $mysqli->query("CREATE TEMPORARY TABLE svt_poi_tmp SELECT * FROM svt_pois WHERE id = $id_poi;");
                    if($type=='product' && !empty($row['content'])) {
                        $id_product = $row['content'];
                        $id_product_new = $array_products[$id_product];
                        $mysqli->query("UPDATE svt_poi_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_pois),access_count=0,id_room=$id_room_new,content='$id_product_new';");
                    } else {
                        $mysqli->query("UPDATE svt_poi_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_pois),access_count=0,id_room=$id_room_new;");
                    }
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
    }
    if($duplicate_rooms) {
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
    }
}
foreach ($array_pois as $id_poi=>$id_poi_new) {
    if($duplicate_pois) {
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
if($duplicate_presentation) {
    $result = $mysqli->query("SELECT id,id_room,action,params FROM svt_presentations WHERE id_virtualtour=$id_virtualtour;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_presentation = $row['id'];
                $id_room = $row['id_room'];
                $action = $row['action'];
                $params = $row['params'];
                $id_room_new = $array_rooms[$id_room];
                $params_new = $array_rooms[$params];
                $mysqli->query("CREATE TEMPORARY TABLE svt_presentation_tmp SELECT * FROM svt_presentations WHERE id = $id_presentation;");
                if($action=='goto') {
                    $mysqli->query("UPDATE svt_presentation_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_presentations),id_virtualtour=$id_virtualtour_new,id_room=$id_room_new,params=$params_new;");
                } else {
                    $mysqli->query("UPDATE svt_presentation_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_presentations),id_virtualtour=$id_virtualtour_new,id_room=$id_room_new;");
                }
                $mysqli->query("INSERT INTO svt_presentations SELECT * FROM svt_presentation_tmp;");
                $id_presentation_new = $mysqli->insert_id;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_presentation_tmp;");
                $result_a = $mysqli->query("SELECT id_presentation FROM svt_presentations_lang WHERE id_presentation=$id_presentation;");
                if($result_a) {
                    if($result_a->num_rows>0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_presentations_lang_tmp SELECT * FROM svt_presentations_lang WHERE id_presentation = $id_presentation;");
                        $mysqli->query("UPDATE svt_presentations_lang_tmp SET id_presentation=$id_presentation_new;");
                        $mysqli->query("INSERT INTO svt_presentations_lang SELECT * FROM svt_presentations_lang_tmp;");
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_presentations_lang_tmp;");
                    }
                }
            }
        }
    }
}
if($duplicate_gallery) {
    $result = $mysqli->query("SELECT id FROM svt_gallery WHERE id_virtualtour=$id_virtualtour;");
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_gallery = $row['id'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_gallery_tmp SELECT * FROM svt_gallery WHERE id = $id_gallery;");
                $mysqli->query("UPDATE svt_gallery_tmp SET id=NULL,id_virtualtour=$id_virtualtour_new;");
                $mysqli->query("INSERT INTO svt_gallery SELECT * FROM svt_gallery_tmp;");
                $id_gallery_new = $mysqli->insert_id;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_gallery_tmp;");
                $result_a = $mysqli->query("SELECT id_gallery FROM svt_gallery_lang WHERE id_gallery=$id_gallery;");
                if($result_a) {
                    if($result_a->num_rows>0) {
                        $mysqli->query("CREATE TEMPORARY TABLE svt_gallery_lang_tmp SELECT * FROM svt_gallery_lang WHERE id_gallery = $id_gallery;");
                        $mysqli->query("UPDATE svt_gallery_lang_tmp SET id_gallery=$id_gallery_new;");
                        $mysqli->query("INSERT INTO svt_gallery_lang SELECT * FROM svt_gallery_lang_tmp;");
                        $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_gallery_lang_tmp;");
                    }
                }
            }
        }
    }
    if($s3_enabled) {
        if ($s3Client->doesObjectExist($s3_bucket_name, 'viewer/gallery/'.$id_virtualtour.'_slideshow.mp4')) {
            try {
                $s3Client->copyObject([
                    'Bucket' => $s3_bucket_name,
                    'CopySource' => $s3_bucket_name.'/viewer/gallery/'.$id_virtualtour.'_slideshow.mp4',
                    'Key' => 'viewer/gallery/'.$id_virtualtour_new.'_slideshow.mp4',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    } else {
        $path = realpath(dirname(__FILE__).'/../..');
        if(file_exists($path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4')) {
            copy($path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4',$path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour_new.'_slideshow.mp4');
        }
    }
}
if($duplicate_rooms) {
    $query = "SELECT list_alt,dollhouse,presentation_stop_id_room FROM svt_virtualtours WHERE id=$id_virtualtour_new LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $list_alt=$row['list_alt'];
            $dollhouse=$row['dollhouse'];
            $presentation_stop_id_room=$row['presentation_stop_id_room'];
            if(!empty($list_alt)) {
                $list_alt_array = json_decode($list_alt, true);
                foreach ($list_alt_array as $key => $item) {
                    switch ($item['type']) {
                        case 'room':
                            $id_room = $item['id'];
                            $list_alt_array[$key]['id'] = $array_rooms[$id_room];
                            break;
                        case 'category':
                            $childrens = array();
                            foreach ($item['children'] as $key_c => $children) {
                                if ($children['type'] == "room") {
                                    $id_room = $children['id'];
                                    $list_alt_array[$key]['children'][$key_c]['id'] = $array_rooms[$id_room];
                                }
                            }
                            break;
                    }
                }
                $list_alt = json_encode($list_alt_array);
                $mysqli->query("UPDATE svt_virtualtours SET list_alt='$list_alt' WHERE id=$id_virtualtour_new;");
            }
            if(!empty($dollhouse)) {
                $dollhouse_array = json_decode($dollhouse, true);
                $rooms_to_delete = array();
                foreach ($dollhouse_array['rooms'] as $key => $room) {
                    $id_room = $room['id'];
                    if(array_key_exists($id_room,$array_rooms)) {
                        $dollhouse_array['rooms'][$key]['id'] = $array_rooms[$id_room];
                    } else {
                        array_push($rooms_to_delete,$key);
                    }
                }
                foreach ($rooms_to_delete as $room_to_delete) {
                    if (isset($dollhouse_array['rooms'][$room_to_delete])) {
                        unset($dollhouse_array['rooms'][$room_to_delete]);
                    }
                }
                $dollhouse_array['rooms'] = array_values($dollhouse_array['rooms']);
                $dollhouse = json_encode($dollhouse_array);
                $mysqli->query("UPDATE svt_virtualtours SET dollhouse='$dollhouse' WHERE id=$id_virtualtour_new;");
            }
            if(array_key_exists($presentation_stop_id_room,$array_rooms)) {
                $presentation_stop_id_room = $array_rooms[$presentation_stop_id_room];
                $mysqli->query("UPDATE svt_virtualtours SET presentation_stop_id_room=$presentation_stop_id_room WHERE id=$id_virtualtour_new;");
            } else {
                $mysqli->query("UPDATE svt_virtualtours SET presentation_stop_id_room=0 WHERE id=$id_virtualtour_new;");
            }
        }
    }
    $query = "SELECT list_alt,language FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour_new;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $list_alt=$row['list_alt'];
                $language=$row['language'];
                if(!empty($list_alt)) {
                    $list_alt_array = json_decode($list_alt, true);
                    foreach ($list_alt_array as $key => $item) {
                        switch ($item['type']) {
                            case 'room':
                                $id_room = $item['id'];
                                $list_alt_array[$key]['id'] = $array_rooms[$id_room];
                                break;
                            case 'category':
                                $childrens = array();
                                foreach ($item['children'] as $key_c => $children) {
                                    if ($children['type'] == "room") {
                                        $id_room = $children['id'];
                                        $list_alt_array[$key]['children'][$key_c]['id'] = $array_rooms[$id_room];
                                    }
                                }
                                break;
                        }
                    }
                    $list_alt = json_encode($list_alt_array);
                    $mysqli->query("UPDATE svt_virtualtours_lang SET list_alt='$list_alt' WHERE id_virtualtour=$id_virtualtour_new AND language='$language';");
                }
            }
        }
    }
}
if($duplicate_pois && $duplicate_rooms) {
    $query = "SELECT id,content FROM svt_pois WHERE type='switch_pano' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_new);";
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
    $query = "SELECT id,visible_multiview_ids FROM svt_pois WHERE visible_multiview_ids!='' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_new);";
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
if($duplicate_rooms) {
    $query = "SELECT id,visible_multiview_ids FROM svt_measures WHERE visible_multiview_ids!='' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour_new);";
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
}

if($s3_enabled) {
    $objects = $s3Client->getIterator('ListObjects', [
        'Bucket' => $s3_bucket_name,
        'Prefix' => 'video360/'.$id_virtualtour.'/',
    ]);
    foreach ($objects as $object) {
        $key = $object['Key'];
        $dest = str_replace('/'.$id_virtualtour.'/', '/'.$id_virtualtour_new.'/', $key);
        try {
            $s3Client->copyObject([
                'Bucket' => $s3_bucket_name,
                'CopySource' => $s3_bucket_name.'/'.$key,
                'Key' => $dest,
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {}
    }
} else {
    $path = realpath(dirname(__FILE__).'/../..');
    $filter = array();
    if(file_exists($path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR)) {
        $files_video360 = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR,RecursiveDirectoryIterator::SKIP_DOTS),
                function ($fileInfo, $key, $iterator) use ($filter) {
                    return true;
                }
            )
        );
        foreach ($files_video360 as $file) {
            $file_name = $file->getFilename();
            $source_file = $file->getPathname();
            $dest_dir = $path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour_new.DIRECTORY_SEPARATOR;
            if(!file_exists($dest_dir)) {
                mkdir($dest_dir, 0775, true);
            }
            $dest_file = $dest_dir.$file_name;
            copy($source_file,$dest_file);
        }
    }
}

$array_icons = array();
$result = $mysqli->query("SELECT id FROM svt_icons WHERE id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_icon = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_icon_tmp SELECT * FROM svt_icons WHERE id=$id_icon;");
            $mysqli->query("UPDATE svt_icon_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_icons),id_virtualtour=$id_virtualtour_new;");
            $mysqli->query("INSERT INTO svt_icons SELECT * FROM svt_icon_tmp;");
            $id_icon_new = $mysqli->insert_id;
            $array_icons[$id_icon] = $id_icon_new;
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_icon_tmp;");
        }
    }
}

$result = $mysqli->query("SELECT id FROM svt_media_library WHERE id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_media = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_media_library_tmp SELECT * FROM svt_media_library WHERE id=$id_media;");
            $mysqli->query("UPDATE svt_media_library_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_media_library),id_virtualtour=$id_virtualtour_new;");
            $mysqli->query("INSERT INTO svt_media_library SELECT * FROM svt_media_library_tmp;");
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_media_library_tmp;");
        }
    }
}

$result = $mysqli->query("SELECT id FROM svt_music_library WHERE id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_music = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_music_library_tmp SELECT * FROM svt_music_library WHERE id=$id_music;");
            $mysqli->query("UPDATE svt_music_library_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_music_library),id_virtualtour=$id_virtualtour_new;");
            $mysqli->query("INSERT INTO svt_music_library SELECT * FROM svt_music_library_tmp;");
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_music_library_tmp;");
        }
    }
}

$result = $mysqli->query("SELECT id FROM svt_sound_library WHERE id_virtualtour=$id_virtualtour;");
if($result) {
    if($result->num_rows>0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_sound = $row['id'];
            $mysqli->query("CREATE TEMPORARY TABLE svt_sound_library_tmp SELECT * FROM svt_sound_library WHERE id=$id_sound;");
            $mysqli->query("UPDATE svt_sound_library_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_sound_library),id_virtualtour=$id_virtualtour_new;");
            $mysqli->query("INSERT INTO svt_sound_library SELECT * FROM svt_sound_library_tmp;");
            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_sound_library_tmp;");
        }
    }
}

$query = "SELECT ui_style FROM svt_virtualtours WHERE id=$id_virtualtour_new LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $ui_style = $row['ui_style'];
        if (!empty($ui_style)) {
            $ui_style_array = json_decode($ui_style, true);
            foreach ($ui_style_array['controls'] as $key => $item) {
                if(!empty($item['icon_library']) && $item['icon_library']!=0) {
                    if(array_key_exists($item['icon_library'],$array_icons)) {
                        $ui_style_array['controls'][$key]['icon_library'] = $array_icons[$item['icon_library']];
                    }
                }
            }
            $ui_style = str_replace("'","\'",json_encode($ui_style_array));
            $mysqli->query("UPDATE svt_virtualtours SET ui_style='$ui_style' WHERE id=$id_virtualtour_new;");
        }
    }
}

if($duplicate_rooms) {
    $result = $mysqli->query("SELECT id FROM svt_video_projects WHERE id_virtualtour=$id_virtualtour;");
    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_video_project = $row['id'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_video_projects_tmp SELECT * FROM svt_video_projects WHERE id = $id_video_project;");
                $mysqli->query("UPDATE svt_video_projects_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_video_projects),id_virtualtour=$id_virtualtour_new;");
                $mysqli->query("INSERT INTO svt_video_projects SELECT * FROM svt_video_projects_tmp;");
                $id_video_project_new = $mysqli->insert_id;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_video_projects_tmp;");

                if($s3_enabled) {
                    $objects = $s3Client->getIterator('ListObjects', [
                        'Bucket' => $s3_bucket_name,
                        'Prefix' => 'video/assets/'.$id_virtualtour.'/',
                    ]);
                    foreach ($objects as $object) {
                        $key = $object['Key'];
                        $dest = str_replace('/'.$id_virtualtour.'/', '/'.$id_virtualtour_new.'/', $key);
                        try {
                            $s3Client->copyObject([
                                'Bucket' => $s3_bucket_name,
                                'CopySource' => $s3_bucket_name.'/'.$key,
                                'Key' => $dest,
                            ]);
                        } catch (\Aws\S3\Exception\S3Exception $e) {}
                    }
                    if ($s3Client->doesObjectExist($s3_bucket_name, 'video/'.$id_virtualtour."_".$id_video_project.".mp4")) {
                        try {
                            $s3Client->copyObject([
                                'Bucket' => $s3_bucket_name,
                                'CopySource' => $s3_bucket_name.'/video/'.$id_virtualtour."_".$id_video_project.".mp4",
                                'Key' => 'video/'.$id_virtualtour_new."_".$id_video_project_new.".mp4",
                            ]);
                        } catch (\Aws\S3\Exception\S3Exception $e) {}
                    }
                } else {
                    $path = realpath(dirname(__FILE__).'/../..');
                    $filter = array();
                    if(file_exists($path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR)) {
                        $files_video_assets = new RecursiveIteratorIterator(
                            new RecursiveCallbackFilterIterator(
                                new RecursiveDirectoryIterator($path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR,RecursiveDirectoryIterator::SKIP_DOTS),
                                function ($fileInfo, $key, $iterator) use ($filter) {
                                    return true;
                                }
                            )
                        );
                        foreach ($files_video_assets as $file) {
                            $file_name = $file->getFilename();
                            $source_file = $file->getPathname();
                            $dest_dir = $path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_virtualtour_new.DIRECTORY_SEPARATOR;
                            if(!file_exists($dest_dir)) {
                                mkdir($dest_dir, 0775, true);
                            }
                            $dest_file = $dest_dir.$file_name;
                            copy($source_file,$dest_file);
                        }
                    }
                    if(file_exists($path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$id_virtualtour."_".$id_video_project.".mp4")) {
                        copy($path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$id_virtualtour."_".$id_video_project.".mp4",$path.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$id_virtualtour_new."_".$id_video_project_new.".mp4");
                    }
                }
                $result_i = $mysqli->query("SELECT id,id_room FROM svt_video_project_slides WHERE id_video_project=$id_video_project;");
                if ($result_i) {
                    if ($result_i->num_rows > 0) {
                        while ($row_i = $result_i->fetch_array(MYSQLI_ASSOC)) {
                            $id_slide = $row_i['id'];
                            $id_room = $row_i['id_room'];
                            $mysqli->query("CREATE TEMPORARY TABLE svt_video_project_slides_tmp SELECT * FROM svt_video_project_slides WHERE id = $id_slide;");
                            if(!empty($id_room)) {
                                $id_room_new = $array_rooms[$id_room];
                                $mysqli->query("UPDATE svt_video_project_slides_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_video_project_slides),id_room=$id_room_new,id_video_project=$id_video_project_new;");
                            } else {
                                $mysqli->query("UPDATE svt_video_project_slides_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_video_project_slides),id_video_project=$id_video_project_new;");
                            }
                            $mysqli->query("INSERT INTO svt_video_project_slides SELECT * FROM svt_video_project_slides_tmp;");
                            $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_video_project_slides_tmp;");
                        }
                    }
                }
            }
        }
    }
}

ob_end_clean();
echo json_encode(array("status"=>"ok","id"=>$id_virtualtour_new));