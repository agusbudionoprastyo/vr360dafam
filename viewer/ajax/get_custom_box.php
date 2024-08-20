<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = $_POST['id_virtualtour'];
$custom_content = "";
$custom2_content = "";
$custom3_content = "";
$location_content = "";
$query = "SELECT custom_content,custom2_content,custom3_content,custom4_content,custom5_content,show_custom,show_custom2,show_custom3,show_custom4,show_custom5,show_location,location_content FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $show_custom = $row['show_custom'];
        $show_custom2 = $row['show_custom2'];
        $show_custom3 = $row['show_custom3'];
        $show_custom4 = $row['show_custom4'];
        $show_custom5 = $row['show_custom5'];
        $show_location = $row['show_location'];
        if($show_custom) {
            $custom_content = $row['custom_content'];
            if(($custom_content=="<div></div>") || (empty($custom_content))) $custom_content="";
        }
        if($show_custom2) {
            $custom2_content = $row['custom2_content'];
            if(($custom2_content=="<div></div>") || (empty($custom2_content))) $custom2_content="";
        }
        if($show_custom3) {
            $custom3_content = $row['custom3_content'];
            if(($custom3_content=="<div></div>") || (empty($custom3_content))) $custom3_content="";
        }
        if($show_custom4) {
            $custom4_content = $row['custom4_content'];
            if(($custom4_content=="<div></div>") || (empty($custom4_content))) $custom4_content="";
        }
        if($show_custom5) {
            $custom5_content = $row['custom5_content'];
            if(($custom5_content=="<div></div>") || (empty($custom5_content))) $custom5_content="";
        }
        if($show_location) {
            $location_content = $row['location_content'];
            if((empty($location_content))) $location_content="";
        }
    }
}
ob_end_clean();
echo json_encode(array("custom_box"=>$custom_content,"custom2_box"=>$custom2_content,"custom3_box"=>$custom3_content,"custom4_box"=>$custom4_content,"custom5_box"=>$custom5_content,"location_box"=>$location_content));