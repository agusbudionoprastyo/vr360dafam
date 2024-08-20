<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
if(isset($_POST['version']) && !empty($_POST['version'])) {
    $version = strip_tags($_POST['version']);
    require_once("../../db/connection.php");
    require_once("../../services/check_update.php");
    $query = "UPDATE svt_settings SET `version`=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('s',$version);
        $result = $smt->execute();
    }
}