<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$id_room = (int)$_POST['id_room'];
$array = array();
$room = array();
$array_lang = array();
$query = "SELECT * FROM svt_markers_lang WHERE id_marker IN(SELECT id FROM svt_markers WHERE id_room=$id_room);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_marker=$row['id_marker'];
            if(!array_key_exists($id_marker,$array_lang)) $array_lang[$id_marker]=array();
            array_push($array_lang[$id_marker],$row);
        }
    }
}
$query = "SELECT r.panorama_image,r.panorama_video,v.enable_multires,r.yaw,r.pitch,r.h_pitch,r.h_roll,r.allow_pitch,r.min_pitch,r.max_pitch,r.min_yaw,r.max_yaw,r.haov,r.vaov,r.type,r.northOffset FROM svt_rooms as r 
            JOIN svt_virtualtours as v ON v.id=r.id_virtualtour
            WHERE r.id = $id_room LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $room['yaw'] = $row['yaw'];
        $room['pitch'] = $row['pitch'];
        $room['h_pitch'] = $row['h_pitch'];
        $room['h_roll'] = $row['h_roll'];
        $room['min_yaw'] = $row['min_yaw'];
        $room['max_yaw'] = $row['max_yaw'];
        $room['allow_pitch'] = $row['allow_pitch'];
        $room['min_pitch'] = $row['min_pitch'];
        $room['max_pitch'] = $row['max_pitch'];
        $room['haov'] = $row['haov'];
        $room['vaov'] = $row['vaov'];
        $room['panorama_video'] = $row['panorama_video'];
        $room['room_type'] = $row['type'];
        $room['northOffset'] = $row['northOffset'];
        if($row['enable_multires']) {
            $room_pano = str_replace('.jpg','',$row['panorama_image']);
            if($s3_enabled) {
                $multires_config_file = "s3://$s3_bucket_name/viewer/panoramas/multires/$room_pano/config.json";
            } else {
                $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
            }
            $room['multires_config_file']=$multires_config_file;
            if(file_exists($multires_config_file)) {
                $multires_tmp = file_get_contents($multires_config_file);
                $multires_array = json_decode($multires_tmp,true);
                $multires_config = $multires_array['multiRes'];
                if($s3_enabled) {
                    $multires_config['basePath'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                } else {
                    $multires_config['basePath'] = '../viewer/panoramas/multires/'.$room_pano;
                }
                $room['multires']=1;
                $room['multires_config']=json_encode($multires_config);
                if($s3_enabled) {
                    $room['multires_dir'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                } else {
                    $room['multires_dir']='../viewer/panoramas/multires/'.$room_pano;
                }
            } else {
                $room['multires']=0;
                $room['multires_config']='';
                $room['multires_dir']='';
            }
        } else {
            $room['multires']=0;
            $room['multires_config']='';
            $room['multires_dir']='';
        }
    }
}
$query = "SELECT 'marker' as what,m.*,r.name as name_room_target,r.panorama_image as marker_preview,r.id as id_room_target,IFNULL(i.id,0) as id_icon_library, i.image as img_icon_library,i.id_virtualtour as id_vt_library,m.yaw_room_target,m.pitch_room_target FROM svt_markers AS m
          JOIN svt_rooms AS r ON m.id_room_target = r.id 
          JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
          LEFT JOIN svt_icons as i ON i.id=m.id_icon_library
          WHERE m.id_room=$id_room;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_marker=$row['id'];
            $row['array_lang']=array();
            if(!is_numeric($row['yaw_room_target'])) $row['yaw_room_target']='';
            if(!is_numeric($row['pitch_room_target'])) $row['pitch_room_target']='';
            if($row['sound']==null) $row['sound']='';
            if($row['embed_type']==null) $row['embed_type']='';
            if($row['embed_content']==null) $row['embed_content']='';
            if($row['embed_coords']==null) $row['embed_coords']='';
            if($row['embed_size']==null) $row['embed_size']='';
            if($row['embed_params']==null) $row['embed_params']='';
            if($_POST['embed_type']=='selection') {
                $embed_content="'border-width:3px;'";
            } else {
                $embed_content="''";
            }
            if(empty($row['id_vt_library'])) $row['id_vt_library']='';
            if(!empty($row["img_icon_library"])) {
                if($s3_enabled && !empty($row['id_vt_library'])) {
                    $row['base64_icon_library'] = convert_image_to_base64("s3://$s3_bucket_name/viewer/icons/".$row["img_icon_library"]);
                } else {
                    $row['base64_icon_library'] = convert_image_to_base64(dirname(__FILE__).'/../../viewer/icons/'.$row["img_icon_library"]);
                }
            } else {
                if($row["show_room"] == 4) {
                    $row["show_room"] = 0;
                }
                $row["img_icon_library"] = '';
                $row['base64_icon_library'] = '';
            }
            if(!empty($row["marker_preview"])) {
                if($s3_enabled) {
                    $row['base64_marker_preview'] = convert_image_to_base64("s3://$s3_bucket_name/viewer/panoramas/preview/".$row["marker_preview"]);
                } else {
                    $row['base64_marker_preview'] = convert_image_to_base64(dirname(__FILE__).'/../../viewer/panoramas/preview/'.$row["marker_preview"]);
                }
            } else {
                $row['base64_marker_preview'] = '';
            }
            if(!empty($row['sound'])) {
                $row['sound'] = str_replace("content/","",$row['sound']);
            }
            if(array_key_exists($id_marker,$array_lang)) {
                foreach ($array_lang[$id_marker] as $array_l) {
                    $row['array_lang'][] = $array_l;
                }
            }
            $array[]=$row;
        }
    }
}
$query = "SELECT 'poi' as what,p.*,IFNULL(i.id,0) as id_icon_library, i.image as img_icon_library,i.id_virtualtour as id_vt_library FROM svt_pois as p
            LEFT JOIN svt_icons as i ON i.id=p.id_icon_library
            WHERE p.id_room=$id_room;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if($row['type']=='html_sc') {
                $row['content'] = htmlspecialchars($row['content']);
            }
            if($row['label']==null) $row['label']='';
            if($row['params']==null) $row['params']='';
            if($row['embed_type']==null) $row['embed_type']='';
            if($row['embed_content']==null) $row['embed_content']='';
            if($row['embed_params']==null) $row['embed_params']='';
            if($row['embed_coords']==null) $row['embed_coords']='';
            if($row['embed_size']==null) $row['embed_size']='';
            if($row['embed_type']=='gallery') {
                $id_poi = $row['id'];
                $query_g = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi ORDER BY priority LIMIT 1;";
                $result_g = $mysqli->query($query_g);
                if($result_g) {
                    if ($result_g->num_rows == 1) {
                        $row_g = $result_g->fetch_array(MYSQLI_ASSOC);
                        $row['embed_content'] = $row_g['image'];
                    }
                }
            }
            $row['switch_panorama_image'] = '';
            if($row['type']=='switch_pano') {
                $id_room_alt = $row['content'];
                if($id_room_alt!='' && $id_room_alt!=0) {
                    $query_ra = "SELECT panorama_image FROM svt_rooms_alt WHERE id=$id_room_alt LIMIT 1;";
                    $result_ra = $mysqli->query($query_ra);
                    if($result_ra) {
                        if ($result_ra->num_rows == 1) {
                            $row_ra = $result_ra->fetch_array(MYSQLI_ASSOC);
                            $row['switch_panorama_image'] = "panoramas/".$row_ra['panorama_image'];
                        }
                    }
                }
            }
            if(!empty($row["img_icon_library"])) {
                if($s3_enabled && !empty($row['id_vt_library'])) {
                    $row['base64_icon_library'] = convert_image_to_base64("s3://$s3_bucket_name/viewer/icons/".$row["img_icon_library"]);
                } else {
                    $row['base64_icon_library'] = convert_image_to_base64(dirname(__FILE__).'/../../viewer/icons/'.$row["img_icon_library"]);
                }
            } else {
                if($row['style']==1) {
                    $row["style"] = 0;
                }
                $row["img_icon_library"] = '';
                $row['base64_icon_library'] = '';
            }
            if($row['embed_type']=='text') {
                if (strpos($row['embed_content'], 'border-width') === false) {
                    $row['embed_content'] = $row['embed_content']." border-width:0px;";
                }
            }
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode(array("markers"=>$array,"room"=>$room));