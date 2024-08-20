<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$id_user = $_SESSION['id_user'];
session_write_close();
$array = array();
$permissions = array();
if(get_user_role($id_user)=="editor") {
    $editor_permissions = get_editor_permissions($id_user,$id_virtualtour);
    if($editor_permissions['edit_maps']==1) {
        $permissions['edit'] = true;
    } else {
        $permissions['edit'] = false;
    }
    if($editor_permissions['delete_maps']==1) {
        $permissions['delete'] = true;
    } else {
        $permissions['delete'] = false;
    }
} else {
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
$query = "SELECT m.*,IF(m.map_type='map',(SELECT COUNT(*) FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND lat IS NOT NULL AND lat !=''),(SELECT COUNT(*) FROM svt_rooms WHERE id_map=m.id)) as count_rooms FROM svt_maps as m 
WHERE m.id_virtualtour=$id_virtualtour ORDER BY m.map_type DESC,m.priority ASC, m.id ASC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if($row['map_type']=='floorplan') {
                if($s3_enabled) {
                    $row['map_image'] = $s3_url."viewer/maps/thumb/".$row['map'];
                } else {
                    $row['map_image'] = "../viewer/maps/thumb/".$row['map'];
                }
            }
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode(array("maps"=>$array,"permissions"=>$permissions));