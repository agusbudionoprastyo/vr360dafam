<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$settings = get_settings();
$id_user = $_SESSION['id_user'];
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
if(!get_user_role($id_user)=='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$id_user_edit = (int)$_POST['id_svt'];
$assign = (int)$_POST['assign'];
if($assign==1) {
    $query = "SELECT id FROM svt_virtualtours;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            $mysqli->query("DELETE FROM svt_assign_virtualtours WHERE id_user=$id_user_edit;");
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_vt = $row['id'];
                $mysqli->query("INSERT INTO svt_assign_virtualtours(id_user, id_virtualtour, edit_virtualtour, edit_virtualtour_ui, edit_3d_view, create_rooms, edit_rooms, delete_rooms, create_markers, edit_markers, delete_markers, create_pois, edit_pois, delete_pois, create_maps, edit_maps, delete_maps, info_box, presentation, video360, video_projects, gallery, icons_library, media_library, music_library, sound_library, publish, landing, forms, leads, shop, measurements) VALUES($id_user_edit,$id_vt,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);");
            }
        }
    }
} else {
    $mysqli->query("DELETE FROM svt_assign_virtualtours WHERE id_user=$id_user_edit;");
}