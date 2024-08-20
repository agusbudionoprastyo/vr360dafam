<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$views = 0;
$query = "SELECT COUNT(id) as count FROM svt_access_log WHERE id_virtualtour=$id_virtualtour;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $views = $row['count'];
    }
}
$mysqli->close();
ob_end_clean();
echo json_encode(array("views"=>$views));
exit;