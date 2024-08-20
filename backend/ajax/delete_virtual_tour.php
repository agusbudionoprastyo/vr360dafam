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
$id_virtualtour = (int)$_POST['id_virtualtour'];
$where = "";
switch(get_user_role($id_user)) {
    case 'customer':
        $where = " AND id_user=$id_user ";
        break;
    case 'editor':
        $where = " AND id IN () ";
        $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $ids = $row['ids'];
                $where = " AND id IN ($ids) ";
            }
        }
        break;
}
$code = "";
$name_vt = "";
$query = "SELECT code,name FROM svt_virtualtours WHERE id=$id_virtualtour $where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $name_vt = $row['name'];
    }
}
if(empty($code)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    die();
}
if(get_user_role($id_user)=='administrator') {
    $query = "DELETE FROM svt_virtualtours WHERE id=$id_virtualtour; ";
} else {
    $query = "DELETE FROM svt_virtualtours WHERE id_user=$id_user AND id=$id_virtualtour; ";
}
$result = $mysqli->query($query);
if($result) {
    if(isset($_SESSION['id_virtualtour_sel'])) {
        if($_SESSION['id_virtualtour_sel']==$id_virtualtour) {
            unset($_SESSION['id_virtualtour_sel']);
            unset($_SESSION['name_virtualtour_sel']);
        }
    }
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
            $source_file = $file->getPathname();
            unlink($source_file);
        }
        rmdir($path.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_virtualtour.DIRECTORY_SEPARATOR);
    }
    session_write_close();
    $mysqli->query("ALTER TABLE svt_virtualtours AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_forms_data AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_access_log AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_rooms AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_rooms_alt AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_rooms_access_log AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_gallery AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_icons AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_maps AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_markers AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_pois AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_poi_gallery AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_presentations AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_poi_embedded_gallery AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_poi_objects360 AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_products AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_product_images AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_video_projects AUTO_INCREMENT = 1;");
    $mysqli->query("ALTER TABLE svt_video_project_slides AUTO_INCREMENT = 1;");
    include("../../services/clean_images.php");
    $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
    if(file_exists($path . "favicons" . DIRECTORY_SEPARATOR . "v_$code")) {
        array_map('unlink', glob($path . "favicons" . DIRECTORY_SEPARATOR . "v_$code" . DIRECTORY_SEPARATOR ."*.*"));
        rmdir($path . "favicons" . DIRECTORY_SEPARATOR . "v_$code" . DIRECTORY_SEPARATOR);
    }
    set_user_log($id_user,'delete_virtual_tour',json_encode(array("id"=>$id_virtualtour,"name"=>$name_vt)),date('Y-m-d H:i:s', time()));
    update_user_space_storage($id_user,false);
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}