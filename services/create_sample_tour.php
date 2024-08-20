<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
require_once(__DIR__."/../db/connection.php");
require_once (__DIR__."/../backend/functions.php");
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$id_virtualtour = $settings['id_vt_sample'];
$name = strip_tags($_POST['name']);
$author = strip_tags($_POST['author']);
if($id_virtualtour==0) {
    $_SESSION['sample_data']=1;
    $_SESSION['sample_name']=$name;
    $_SESSION['sample_author']=$author;
    include('import_backend_vt.php');
    $id_vt_return=$id_vt;
} else {
    $mysqli->query("CREATE TEMPORARY TABLE svt_virtualtour_tmp SELECT * FROM svt_virtualtours WHERE id = $id_virtualtour;");
    $query = "UPDATE svt_virtualtour_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_virtualtours),id_user=?,name=?,date_created=NOW(),ga_tracking_id=NULL,friendly_url=NULL;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('is', $id_user,$name);
        $smt->execute();
    }
    $mysqli->query("INSERT INTO svt_virtualtours SELECT * FROM svt_virtualtour_tmp;");
    $id_virtualtour_new = $mysqli->insert_id;
    $id_vt_return = $id_virtualtour_new;
    $code_new = md5($id_virtualtour_new);
    $mysqli->query("UPDATE svt_virtualtours SET code='$code_new' WHERE id=$id_virtualtour_new;");
    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_virtualtours_tmp;");
    $array_rooms = array();
    $array_maps = array();
    $array_products = array();
    $array_rooms_alt = array();
    $id_room_default_mapping = array();
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $result = $mysqli->query("SELECT id,id_room_default FROM svt_maps WHERE id_virtualtour=$id_virtualtour;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_map = $row['id'];
                $id_room_default = $row['id_room_default'];
                if(!empty($id_room_default)) {
                    $id_room_default_mapping[$id_map]=$id_room_default;
                }
                $mysqli->query("CREATE TEMPORARY TABLE svt_map_tmp SELECT * FROM svt_maps WHERE id = $id_map;");
                $mysqli->query("UPDATE svt_map_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_maps),id_virtualtour=$id_virtualtour_new,id_room_default=NULL;");
                $mysqli->query("INSERT INTO svt_maps SELECT * FROM svt_map_tmp;");
                $id_map_new = $mysqli->insert_id;
                $array_maps[$id_map] = $id_map_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_map_tmp;");
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $result = $mysqli->query("SELECT id,id_map FROM svt_rooms WHERE id_virtualtour=$id_virtualtour;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_room = $row['id'];
                $id_map = $row['id_map'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_room_tmp SELECT * FROM svt_rooms WHERE id = $id_room;");
                if(!empty($id_map)) {
                    $id_map_new = $array_maps[$id_map];
                    $mysqli->query("UPDATE svt_room_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms),access_count=0,id_virtualtour=$id_virtualtour_new,id_map=$id_map_new,id_poi_autoopen=NULL;");
                } else {
                    $mysqli->query("UPDATE svt_room_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_rooms),access_count=0,id_virtualtour=$id_virtualtour_new,id_poi_autoopen=NULL;");
                }
                $mysqli->query("INSERT INTO svt_rooms SELECT * FROM svt_room_tmp;");
                $id_room_new = $mysqli->insert_id;
                $array_rooms[$id_room] = $id_room_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_room_tmp;");
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    foreach ($id_room_default_mapping as $id_map_t => $id_room_default_t) {
        $id_map_new = $array_maps[$id_map_t];
        $id_room_default_new = $array_rooms[$id_room_default_t];
        $mysqli->query("UPDATE svt_maps SET id_room_default=$id_room_default_new WHERE id=$id_map_new;");
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
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
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $array_pois = array();
    foreach ($array_rooms as $id_room=>$id_room_new) {
        $result = $mysqli->query("SELECT id,id_room_target FROM svt_markers WHERE id_room=$id_room;");
        if($result) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $id_marker = $row['id'];
                    $id_room_target = $row['id_room_target'];
                    $id_room_target_new = $array_rooms[$id_room_target];
                    $mysqli->query("CREATE TEMPORARY TABLE svt_marker_tmp SELECT * FROM svt_markers WHERE id = $id_marker;");
                    $mysqli->query("UPDATE svt_marker_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_markers),id_room=$id_room_new,id_room_target=$id_room_target_new;");
                    $mysqli->query("INSERT INTO svt_markers SELECT * FROM svt_marker_tmp;");
                    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_marker_tmp;");
                }
            }
        }
        $mysqli->close();
        $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        if (mysqli_connect_errno()) {
            echo mysqli_connect_error();
            exit();
        }
        $mysqli->query("SET NAMES 'utf8';");
        $result = $mysqli->query("SELECT id,type,content FROM svt_pois WHERE id_room=$id_room;");
        if($result) {
            if($result->num_rows>0) {
                while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $id_poi = $row['id'];
                    $type = $row['type'];
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
                }
            }
        }
        $mysqli->close();
        $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        if (mysqli_connect_errno()) {
            echo mysqli_connect_error();
            exit();
        }
        $mysqli->query("SET NAMES 'utf8';");
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
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
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
        $mysqli->close();
        $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        if (mysqli_connect_errno()) {
            echo mysqli_connect_error();
            exit();
        }
        $mysqli->query("SET NAMES 'utf8';");
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
        $mysqli->close();
        $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        if (mysqli_connect_errno()) {
            echo mysqli_connect_error();
            exit();
        }
        $mysqli->query("SET NAMES 'utf8';");
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
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
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
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_presentation_tmp;");
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $mysqli->query("CREATE TEMPORARY TABLE svt_gallery_tmp SELECT * FROM svt_gallery WHERE id_virtualtour = $id_virtualtour;");
    $mysqli->query("UPDATE svt_gallery_tmp SET id=NULL,id_virtualtour=$id_virtualtour_new;");
    $mysqli->query("INSERT INTO svt_gallery SELECT * FROM svt_gallery_tmp;");
    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_gallery_tmp;");
    $path = realpath(dirname(__FILE__).'/..');
    if(file_exists($path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4')) {
        copy($path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour.'_slideshow.mp4',$path.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.$id_virtualtour_new.'_slideshow.mp4');
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $mysqli->query("CREATE TEMPORARY TABLE svt_icon_tmp SELECT * FROM svt_icons WHERE id_virtualtour = $id_virtualtour;");
    $mysqli->query("UPDATE svt_icon_tmp SET id=NULL,id_virtualtour=$id_virtualtour_new;");
    $mysqli->query("INSERT INTO svt_icons SELECT * FROM svt_icon_tmp;");
    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_icon_tmp;");
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT list_alt,dollhouse FROM svt_virtualtours WHERE id=$id_virtualtour_new LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $list_alt=$row['list_alt'];
            $dollhouse=$row['dollhouse'];
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
                foreach ($dollhouse_array['rooms'] as $key => $room) {
                    $id_room = $room['id'];
                    $dollhouse_array['rooms'][$key]['id'] = $array_rooms[$id_room];
                }
                $dollhouse = json_encode($dollhouse_array);
                $mysqli->query("UPDATE svt_virtualtours SET dollhouse='$dollhouse' WHERE id=$id_virtualtour_new;");
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT id,content FROM svt_pois WHERE type='switch_pano';";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $content = $row['content'];
                if(!empty($content) && $content!='0') {
                    $id_room_alt_new = $array_rooms_alt[$content];
                    $mysqli->query("UPDATE svt_pois SET content='$id_room_alt_new' WHERE id=$id_virtualtour_new;");
                }
            }
        }
    }
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
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
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
                $mysqli->close();
                $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
                if (mysqli_connect_errno()) {
                    echo mysqli_connect_error();
                    exit();
                }
                $mysqli->query("SET NAMES 'utf8';");
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
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$_SESSION['id_virtualtour_sel'] = $id_vt_return;
$_SESSION['name_virtualtour_sel'] = $name;
session_write_close();
update_user_space_storage($id_user,false);
ob_end_clean();
echo json_encode(array("id"=>$id_vt_return));

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function mycopy($s1,$s2) {
    $path = pathinfo($s2);
    if (!file_exists($path['dirname'])) {
        mkdir($path['dirname'], 0775, true);
    }
    copy($s1,$s2);
}