<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
ob_start();
require_once(__DIR__."/../functions.php");
$id_user = (int)$_POST['id_user'];
$object = $_POST['object'];
$can_create = check_plan($object, $id_user, $_SESSION['id_virtualtour_sel']);
if($can_create) {
    ob_end_clean();
    echo json_encode(array("can_create"=>1));
} else {
    ob_end_clean();
    echo json_encode(array("can_create"=>0));
}