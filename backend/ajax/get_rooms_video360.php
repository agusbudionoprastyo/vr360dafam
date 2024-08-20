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
$s3_params = check_s3_tour_enabled($id_virtualtour);
$s3_enabled = false;
$s3_url = "";
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_region = $s3_params['region'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
if(get_user_role($id_user)=='administrator') {
    $where_user = "";
} else {
    $where_user = " AND v.id_user = $id_user ";
}
$array = array();
$query = "SELECT r.id,r.name,r.panorama_image,r.thumb_image FROM svt_rooms as r 
JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
WHERE r.type='image' AND v.id = $id_virtualtour $where_user
GROUP BY r.id
ORDER BY r.priority ASC, r.id ASC";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if($s3_enabled) {
                $thumb = $s3_url."viewer/panoramas/thumb/".$row['panorama_image'];
                if(file_exists("s3://$s3_bucket_name/viewer/panoramas/preview/".$row['panorama_image'])) {
                    $thumb = $s3_url."viewer/panoramas/preview/".$row['panorama_image'];
                }
                if(!empty($row['thumb_image'])) {
                    if(file_exists("s3://$s3_bucket_name/viewer/panoramas/thumb_custom/".$row['thumb_image'])) {
                        $thumb = $s3_url."viewer/panoramas/thumb_custom/".$row['thumb_image'];
                    }
                }
            } else {
                $thumb = "../viewer/panoramas/thumb/".$row['panorama_image'];
                if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'preview'.DIRECTORY_SEPARATOR.$row['panorama_image'])) {
                    $thumb = "../viewer/panoramas/preview/".$row['panorama_image'];
                }
                if(!empty($row['thumb_image'])) {
                    if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'thumb_custom'.DIRECTORY_SEPARATOR.$row['thumb_image'])) {
                        $thumb = "../viewer/panoramas/thumb_custom/".$row['thumb_image'];
                    }
                }
            }
            $array[]=array("id"=>$row['id'],"name"=>$row['name'],"panorama"=>$row['panorama_image'],"thumb_image"=>$thumb);
        }
    }
}
ob_end_clean();
echo json_encode($array);