<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
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
$id_user = $_SESSION['id_user'];
$settings = get_settings();
$user_info = get_user_info($id_user);
$user_email = $user_info['email'];
$app_name = $settings['name'];
$google2fa = new Google2FA();
$secretKey = $google2fa->generateSecretKey();
$_SESSION['2fa_secretkey']=$secretKey;
session_write_close();
$g2faUrl = $google2fa->getQRCodeUrl(
    $app_name,
    $user_email,
    $secretKey
);
$writer = new Writer(
    new ImageRenderer(
        new RendererStyle(400),
        new ImagickImageBackEnd()
    )
);
$qrcode_image = base64_encode($writer->writeString($g2faUrl));
ob_end_clean();
echo json_encode(array("secretkey"=>$secretKey,"qrcode"=>$qrcode_image));