<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
switch(get_user_role($id_user)) {
    case 'administrator':
        $where = "";
        break;
    case 'customer':
        $where = " AND v.id_user=$id_user ";
        break;
    case 'editor':
        $where = " AND v.id IN () ";
        $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $ids = $row['ids'];
                $where = " AND v.id IN ($ids) ";
            }
        }
        break;
}
$array_rooms = array();
$permissions = array();
if(get_user_role($id_user)=="editor") {
    $editor_permissions = get_editor_permissions($id_user,$id_virtualtour);
    if($editor_permissions['create_rooms']==1) {
        $permissions['create'] = true;
    } else {
        $permissions['create'] = false;
    }
    if($editor_permissions['edit_rooms']==1) {
        $permissions['edit'] = true;
    } else {
        $permissions['edit'] = false;
    }
    if($editor_permissions['delete_rooms']==1) {
        $permissions['delete'] = true;
    } else {
        $permissions['delete'] = false;
    }
} else {
    $permissions['create'] = true;
    $permissions['edit'] = true;
    $permissions['delete'] = true;
}
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
if($_SESSION['full_group_by']===true) {
    $group_by = "r.id,r.priority,r.visible,r.name,r.type,r.panorama_image,r.thumb_image,r.multires_status,v.enable_multires,r.yaw,r.pitch,r.h_pitch,r.h_roll,r.allow_pitch,r.min_pitch,r.max_pitch,r.min_yaw,r.max_yaw,r.haov,r.vaov,r.virtual_staging,r.main_view_tooltip";
} else {
    $group_by = "r.id,r.priority";
}
$query = "SELECT r.id,r.visible,r.name,r.type,r.panorama_image,r.thumb_image,(SELECT COUNT(*) FROM svt_markers WHERE id_room=r.id) as count_markers,(SELECT COUNT(*) FROM svt_pois WHERE id_room=r.id) as count_pois,(SELECT COUNT(*) FROM svt_measures WHERE id_room=r.id) as count_measures,r.multires_status,v.enable_multires,r.yaw,r.pitch,r.h_pitch,r.h_roll,r.allow_pitch,r.min_pitch,r.max_pitch,r.min_yaw,r.max_yaw,r.haov,r.vaov,r.virtual_staging,r.main_view_tooltip,GROUP_CONCAT(DISTINCT ra.panorama_image ORDER BY ra.priority ASC) as panoramas_list FROM svt_rooms as r 
JOIN svt_virtualtours AS v ON v.id = r.id_virtualtour
LEFT JOIN svt_rooms_alt AS ra ON ra.id_room=r.id 
WHERE v.id = $id_virtualtour $where
GROUP BY $group_by
ORDER BY r.priority ASC, r.id ASC";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if($row['enable_multires']) {
                $room_pano = str_replace('.jpg','',$row['panorama_image']);
                if($s3_enabled) {
                    $multires_config_file = "s3://$s3_bucket_name/viewer/panoramas/multires/$room_pano/config.json";
                } else {
                    $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
                }
                $row['multires_config']=$multires_config_file;
                if(file_exists($multires_config_file)) {
                    $row['multires']=1;
                } else {
                    $row['multires']=0;
                }
            } else {
                $row['multires']=0;
            }
            if($s3_enabled) {
                $thumb_image_url = $s3_url."viewer/panoramas/thumb/".$row['panorama_image'];
                $thumb_image_path = "s3://$s3_bucket_name/viewer/panoramas/preview/".$row['panorama_image'];
                $thumb_custom_path = "s3://$s3_bucket_name/viewer/panoramas/thumb_custom/".$row['thumb_image'];
            } else {
                $thumb_image_url = "../viewer/panoramas/thumb/".$row['panorama_image'];
                $thumb_image_path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'preview'.DIRECTORY_SEPARATOR.$row['panorama_image'];
                $thumb_custom_path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'thumb_custom'.DIRECTORY_SEPARATOR.$row['thumb_image'];
            }
            if(file_exists($thumb_image_path)) {
                if($s3_enabled) {
                    $thumb_image_url = $s3_url."viewer/panoramas/preview/".$row['panorama_image'];
                } else {
                    $thumb_image_url = "../viewer/panoramas/preview/".$row['panorama_image'];
                }
            }
            if(!empty($row['thumb_image'])) {
                if(file_exists($thumb_custom_path)) {
                    if($s3_enabled) {
                        $thumb_image_url = $s3_url."viewer/panoramas/thumb_custom/".$row['thumb_image'];
                    } else {
                        $thumb_image_url = "../viewer/panoramas/thumb_custom/".$row['thumb_image'];
                    }
                }
            }
            $row['thumb_image_url']=$thumb_image_url;
            $row['category']='';
            if(empty($row['panoramas_list'])) $row['panoramas_list']='';
            $array_rooms_alt = array();
            $query_alt = "SELECT id,panorama_image,view_tooltip FROM svt_rooms_alt WHERE id_room=".$row['id']." ORDER BY priority;";
            $result_alt = $mysqli->query($query_alt);
            if($result_alt) {
                if ($result_alt->num_rows > 0) {
                    while ($row_alt = $result_alt->fetch_array(MYSQLI_ASSOC)) {
                        array_push($array_rooms_alt,$row_alt);
                    }
                }
            }
            $row['rooms_alt']=$array_rooms_alt;
            if(!empty($s3_params)) {
                $row['aws_s3_url'] = $s3_url;
                $row['aws_s3']=1;
            } else {
                $row['aws_s3']=0;
            }
            $array_rooms[]=$row;
        }
    }
}
$array = array();
$query = "SELECT r.id,r.name FROM svt_rooms as r 
JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
WHERE v.id = $id_virtualtour
GROUP BY r.id
ORDER BY r.priority ASC, r.id ASC";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $array[$row['id']]=$row['name'];
        }
    }
}
$array2 = array();
$array_id_rooms = array();
$query = "SELECT list_alt FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $list_alt = $row['list_alt'];
        if (!empty($list_alt)) {
            $list_alt_array = json_decode($list_alt, true);
            foreach ($list_alt_array as $item) {
                switch ($item['type']) {
                    case 'room':
                        if(array_key_exists($item['id'],$array)) {
                            array_push($array2, ["id" => $item['id'], "type" => "room", "hide"=>$item['hide'], "name" => $array[$item['id']]]);
                        }
                        array_push($array_id_rooms,$item['id']);
                        break;
                    case 'category':
                        $childrens = array();
                        foreach ($item['children'] as $children) {
                            if ($children['type'] == "room") {
                                if(array_key_exists($children['id'],$array)) {
                                    array_push($childrens, ["id" => $children['id'], "type" => "room", "hide" => $children['hide'], "name" => $array[$children['id']]]);
                                    foreach ($array_rooms as $key_t => $room_t) {
                                        if($room_t['id']==$children['id']) {
                                            $array_rooms[$key_t]['category']=$item['cat'];
                                        }
                                    }
                                }
                                array_push($array_id_rooms, $children['id']);
                            }
                        }
                        array_push($array2, ["id" => $item['id'], "type" => "category", "name" => $item['cat'], "childrens" => $childrens]);
                        break;
                }
            }
            foreach ($array as $id=>$name) {
                if(!in_array($id,$array_id_rooms)) {
                    array_push($array2,["id"=>$id,"type"=>"room","hide"=>"0","name"=>$name]);
                }
            }
        }
    }
}
ob_end_clean();
echo json_encode(array("rooms"=>$array_rooms,"permissions"=>$permissions));