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
$id_new_preset = (int)$_POST['id_preset'];
$settings = get_settings();
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
$user_role = get_user_role($id_user);
if($user_role=='administrator') {
    $query = "SELECT id,id_user,name,public FROM svt_editor_ui_presets ORDER BY name;";
} else {
    $query = "SELECT id,id_user,name,public FROM svt_editor_ui_presets WHERE id_user=$id_user OR public=1 ORDER BY name;";
}
$html = '<option data-name="" data-public="0" data-delete="0" data-update="1" id="0">'._("Add new preset").'</option>';
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_preset = $row['id'];
            $id_user_preset = $row['id_user'];
            $name_preset = $row['name'];
            $public_preset = $row['public'];
            $delete=0;
            $update=0;
            if($user_role=='administrator') {
                $delete=1;
                $update=1;
            } else if($id_user==$id_user_preset) {
                if($public_preset==0) {
                    $delete=1;
                }
                $update=1;
            }
            if($id_preset==$id_new_preset) {
                $html .= '<option selected data-name="'.$name_preset.'" data-public="'.$public_preset.'" data-delete="'.$delete.'" data-update="'.$update.'" id="'.$id_preset.'">'.$name_preset.'</option>';
            } else {
                $html .= '<option data-name="'.$name_preset.'" data-public="'.$public_preset.'" data-delete="'.$delete.'" data-update="'.$update.'" id="'.$id_preset.'">'.$name_preset.'</option>';
            }
        }
    }
}
ob_end_clean();
echo json_encode(array("html"=>$html));