<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id_globe = (int)$_POST['id_globe'];
$id_user = $_SESSION['id_user'];
$all_points = true;
$array = array();
$where = "";
switch(get_user_role($id_user)) {
    case 'administrator';
        $where = "";
        break;
    case 'customer':
        $where = "WHERE v.id_user=$id_user";
        break;
    case 'editor':
        return '';
        break;
}
$s3Client = null;
$s3_url = "";
$query = "SELECT v.id,v.name,v.author,v.background_image,s.id_virtualtour as id_g,s.lat,s.lon FROM svt_virtualtours AS v
                LEFT JOIN svt_globe_list AS s ON s.id_virtualtour=v.id AND s.id_globe=$id_globe
                $where
                ORDER BY v.date_created;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if($row['lat']==null) {
                $all_points = false;
            }
            $s3_params = check_s3_tour_enabled($row['id']);
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
            if(empty($row['background_image'])) {
                $row['background_image']='';
            } else {
                if($s3_enabled) {
                    $row['background_image'] = $s3_url."viewer/content/".$row['background_image'];
                } else {
                    $row['background_image'] = "../viewer/content/".$row['background_image'];
                }
            }
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode(array("map_points"=>$array,"all_points"=>$all_points));