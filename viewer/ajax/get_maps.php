<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
require_once("../../backend/functions.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$language = $_POST['language'];
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
            if(!empty($s3_params['custom_domain'])) {
                $s3_url = "https://".$s3_params['custom_domain']."/";
            } else {
                try {
                    $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.');
                } catch (Aws\Exception\S3Exception $e) {}
            }
            $s3_enabled = true;
        }
    } else {
        $s3_enabled = true;
    }
}
$map_tour = array();
$map_tour_points = array();
$maps = array();
$array_maps_lang = array();
$array_rooms_lang = array();
$query = "SELECT * FROM svt_maps_lang WHERE language='$language' AND id_map IN (SELECT id FROM svt_maps WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_map = $row['id_map'];
            unset($row['id_map']);
            unset($row['language']);
            $array_maps_lang[$id_map]=$row;
        }
    }
}
$query = "SELECT * FROM svt_rooms_lang WHERE language='$language' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_room = $row['id_room'];
            unset($row['id_room']);
            unset($row['language']);
            $array_rooms_lang[$id_room]=$row;
        }
    }
}
$query = "SELECT * FROM svt_maps WHERE id_virtualtour=$id_virtualtour AND map_type='map' LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $row['point_size'] = $row['point_size']*2;
        $map_tour = $row;
        $query_m = "SELECT id,name,lat,lon,thumb_image,panorama_image FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND visible=1 AND lat IS NOT NULL AND lon IS NOT NULL;";
        $result_m = $mysqli->query($query_m);
        if($result_m) {
            if ($result_m->num_rows > 0) {
                while ($row_m = $result_m->fetch_array(MYSQLI_ASSOC)) {
                    if($s3_enabled) {
                        $thumb_exist = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/thumb_custom/'.$row_m['thumb_image']);
                        $èreview_exist = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/preview/'.$row_m['panorama_image']);
                    } else {
                        $thumb_exist = file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'thumb_custom'.DIRECTORY_SEPARATOR.$row_m['thumb_image']);
                        $èreview_exist = file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'preview'.DIRECTORY_SEPARATOR.$row_m['panorama_image']);
                    }
                    $id_room = $row_m['id'];
                    if(array_key_exists($id_room,$array_rooms_lang)) {
                        if (!empty($row_m['name']) && !empty($array_rooms_lang[$id_room]['name'])) {
                            $row_m['name'] = $array_rooms_lang[$id_room]['name'];
                        }
                    }
                    if(!empty($row['thumb_image']) && $thumb_exist) {
                        $row_m['icon'] = 'panoramas/thumb_custom/'.$row_m['thumb_image'];
                    } else if($èreview_exist) {
                        $row_m['icon'] = 'panoramas/preview/'.$row_m['panorama_image'];
                    } else {
                        $row_m['icon'] = 'panoramas/thumb/'.$row_m['panorama_image'];
                    }
                    unset($row_m['thumb_image']);
                    unset($row_m['panorama_image']);
                    $map_tour_points[] = $row_m;
                }
            }
        }
    }
}
$query = "SELECT * FROM svt_maps WHERE id_virtualtour=$id_virtualtour AND map_type='floorplan' ORDER BY priority ASC, id ASC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_map = $row['id'];
            if(array_key_exists($id_map,$array_maps_lang)) {
                if (!empty($row['name']) && !empty($array_maps_lang[$id_map]['name'])) {
                    $row['name'] = $array_maps_lang[$id_map]['name'];
                }
            }
            if($s3_enabled) {
                $map = $s3_url."viewer/maps/".$row['map'];
                $map_exist = $s3Client->doesObjectExist($s3_bucket_name,'viewer/maps/'.$row['map']);
            } else {
                $map = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'maps'.DIRECTORY_SEPARATOR.$row['map'];
                $map_exist = file_exists($map);
            }
            if($map_exist) {
                list($width, $height) = getimagesize($map);
                $row['map_ratio'] = $width/$height;
            } else {
                $row['map_ratio'] = 1;
            }
            if(empty($row['info_link'])) $row['info_link']='';
            if(empty($row['id_room_default'])) $row['id_room_default']='';
            $maps[] = $row;
        }
    }
}
ob_end_clean();
echo json_encode(array("status"=>"ok","maps"=>$maps,"map_tour"=>$map_tour,"map_tour_points"=>$map_tour_points));