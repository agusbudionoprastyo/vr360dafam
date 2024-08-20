<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$s3_params = check_s3_tour_enabled($_SESSION['id_virtualtour_sel']);
$s3_enabled = false;
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    $s3_url = init_s3_client($s3_params);
    if($s3_url!==false) {
        $s3_enabled = true;
    }
}
session_write_close();
$id_poi = (int)$_POST['id_poi'];
$image = strip_tags($_POST['image']);
$priority = 0;
$query = "SELECT MAX(priority)+1 as priority FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $priority = $row['priority'];
        if (empty($priority)) $priority = 0;
    }
}
$query = "INSERT INTO svt_poi_embedded_gallery(id_poi,image,priority) VALUES(?,?,?);";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('isi',  $id_poi,$image,$priority);
    $result = $smt->execute();
    if ($result) {
        $gallery_image_gt = $image;
        require_once("../../services/generate_thumb.php");
        update_user_space_storage($id_user,false);
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}