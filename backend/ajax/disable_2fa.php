<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
require_once("../vendor/google2fa/vendor/autoload.php");
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
$secretKey = $_SESSION['2fa_secretkey'];
$id_user = (int)$_SESSION['id_user'];
$code = $_POST['code'];
$google2fa = new Google2FA();
if ($google2fa->verifyKey($secretKey, $code)) {
    $mysqli->query("UPDATE svt_users SET 2fa_secretkey=NULL WHERE id=$id_user;");
    unset($_SESSION['2fa_secretkey']);
    session_write_close();
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}