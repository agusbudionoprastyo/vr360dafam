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
$id_user = (int)$_POST['id_user'];
$id_virtualtour = $_POST['id_virtualtour'];
$stats = array();
$array_sizes = get_disk_size_stat($id_user,$id_virtualtour);
$size = '0 B';
if($array_sizes[1]!='0 B' && $array_sizes[2]!='0 B') {
    $size = $array_sizes[0]."&nbsp;&nbsp;<span style='font-size:12px;vertical-align:middle;'><i class='far fa-folder'></i> ".$array_sizes[1]."&nbsp;&nbsp;<i class='fas fa-cloud'></i> ".$array_sizes[2]."</span>";
} else if($array_sizes[1]=='0 B' && $array_sizes[2]!='0 B') {
    $size = $array_sizes[0]."&nbsp;&nbsp;<span style='font-size:12px'><i style='vertical-align:text-top' class='fas fa-cloud'></i></span>";
} else if($array_sizes[1]!='0 B' && $array_sizes[2]=='0 B') {
    $size = $array_sizes[0]."&nbsp;&nbsp;<span style='font-size:12px'><i style='vertical-align:text-top' class='far fa-folder'></i></span>";
}
$stats['disk_space_used'] = $size;
ob_end_clean();
echo json_encode($stats);