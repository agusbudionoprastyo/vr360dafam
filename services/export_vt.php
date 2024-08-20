<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) {
    //DEMO CHECK
    die();
}
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
ini_set('max_input_time', 9999);
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
require_once(__DIR__."/Minifier.php");
use \JShrink\Minifier;
$plan_permissions = get_plan_permission($_SESSION['id_user']);
if($plan_permissions['enable_export_vt']==0) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}

$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
if (!class_exists('ZipArchive')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("php zip not enabled")));
    exit;
}
$id_vt = $_POST['id_virtualtour'];
$s3Client = null;
$s3_params = check_s3_tour_enabled($id_vt);
$s3_enabled = false;
$s3_bucket_name = "";
if(!empty($s3_params)) {
    $s3_bucket_name = $s3_params['bucket'];
    if($s3Client==null) {
        $s3Client = init_s3_client_no_wrapper($s3_params);
        if($s3Client==null) {
            $s3_enabled = false;
        } else {
            $s3_enabled = true;
        }
    } else {
        $s3_enabled = true;
    }
}
$code = '';
$query = "SELECT name,description,author,code,song,logo,nadir_logo,loading_background_color,background_image,background_image_mobile,background_video,background_video_mobile,intro_desktop,intro_mobile,presentation_video,dollhouse_glb,language,languages_enabled,poweredby_image FROM svt_virtualtours WHERE id=$id_vt LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $song = $row['song'];
        $logo = $row['logo'];
        $poweredby_image = $row['poweredby_image'];
        $nadir_logo = $row['nadir_logo'];
        $background_image = $row['background_image'];
        $background_video = $row['background_video'];
        $background_image_mobile = $row['background_image_mobile'];
        $background_video_mobile = $row['background_video_mobile'];
        $intro_desktop = $row['intro_desktop'];
        $intro_mobile = $row['intro_mobile'];
        $presentation_video = $row['presentation_video'];
        if($presentation_video!='') $presentation_video = basename($presentation_video);
        $dollhouse_glb = $row['dollhouse_glb'];
        $name = $row['name'];
        $description = $row['description'];
        $author = $row['author'];
        $loading_background_color = $row['loading_background_color'];
        $vt_language = $row['language'];
        if(empty($vt_language)) $vt_language='';
        if(!empty($row['languages_enabled'])) {
            $vt_languages_enabled=json_decode($row['languages_enabled'],true);
        }
        $query_s = "SELECT language FROM svt_settings LIMIT 1;";
        $result_s = $mysqli->query($query_s);
        if($result_s) {
            if ($result_s->num_rows == 1) {
                $row_s = $result_s->fetch_array(MYSQLI_ASSOC);
                if(!empty($vt_language)) {
                    $language = $vt_language;
                } else {
                    $language = $row_s['language'];
                }
                $default_language = $language;
                if(array_key_exists($language,$vt_languages_enabled)) {
                    $vt_languages_enabled[$language]=1;
                }
            }
        }
    }
}
if(empty($code)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
if(file_exists(dirname(__FILE__)."/export_tmp/$code/")) {
    deleteDirectory(dirname(__FILE__)."/export_tmp/$code/");
}
check_directory("/export_tmp/");
check_directory("/export_tmp/$code/");
check_directory("/export_tmp/$code/www/");
check_directory("/export_tmp/$code/www/favicons/");
check_directory("/export_tmp/$code/www/favicons/v_$code");
check_directory("/export_tmp/$code/www/css/");
check_directory("/export_tmp/$code/www/css/flags_lang/");
check_directory("/export_tmp/$code/www/css/images/");
check_directory("/export_tmp/$code/www/css/font/");
check_directory("/export_tmp/$code/www/js/");
check_directory("/export_tmp/$code/www/webfonts/");
check_directory("/export_tmp/$code/www/panoramas/");
check_directory("/export_tmp/$code/www/panoramas/lowres/");
check_directory("/export_tmp/$code/www/panoramas/mobile/");
check_directory("/export_tmp/$code/www/panoramas/multires/");
check_directory("/export_tmp/$code/www/panoramas/original/");
check_directory("/export_tmp/$code/www/panoramas/preview/");
check_directory("/export_tmp/$code/www/panoramas/thumb/");
check_directory("/export_tmp/$code/www/panoramas/thumb_custom/");
check_directory("/export_tmp/$code/www/videos/");
check_directory("/export_tmp/$code/www/content/");
check_directory("/export_tmp/$code/www/gallery/");
check_directory("/export_tmp/$code/www/gallery/thumb/");
check_directory("/export_tmp/$code/www/icons/");
check_directory("/export_tmp/$code/www/maps/");
check_directory("/export_tmp/$code/www/maps/thumb/");
check_directory("/export_tmp/$code/www/media/");
check_directory("/export_tmp/$code/www/media/thumb/");
check_directory("/export_tmp/$code/www/objects360/");
check_directory("/export_tmp/$code/www/products/");
check_directory("/export_tmp/$code/www/products/thumb/");
check_directory("/export_tmp/$code/www/pointclouds/");
check_directory("/export_tmp/$code/www/vendor/");
check_directory("/export_tmp/$code/www/vendor/pdf_viewer/");
check_directory("/export_tmp/$code/www/vendor/potree/");
check_directory("/export_tmp/$code/www/ajax/");
$readme = <<<STR
Due to browser security restrictions, a web server must be used locally as well.
To see the tour you can use one of these 4 methods:
1) upload the www directory contents to a web server and access the corresponding url (http: //server.domain/folder/index.html)
2) use a local web server (like xampp, mamp, python http server, ...) and access the corresponding url (http://127.0.0.1:port/index.html)
3) the files are ready to be packaged as desktop application (mac, linux, windows) with Electron (https://www.electronjs.org/)
3) the files are ready to be packaged as mobile application (android, ios) with Capacitor (https://capacitorjs.com/docs/getting-started#add-capacitor-to-your-web-app)
STR;
file_put_contents(dirname(__FILE__)."/export_tmp/$code/readme.txt",$readme);
$electron_js = <<<STR
const {app, BrowserWindow} = require('electron')
const url = require('url')
const path = require('path')
let win
function createWindow() {
   win = new BrowserWindow({width: 1280, height: 720})
   win.loadURL(url.format ({
      pathname: path.join(__dirname, 'www/index.html'),
      protocol: 'file:',
      slashes: true
   }))
}
app.on('ready', createWindow)
STR;
file_put_contents(dirname(__FILE__)."/export_tmp/$code/main.js",$electron_js);
$eletron_package = <<<STR
{
  "name": "$name",
  "version": "1.0.0",
  "description": "$description",
  "main": "main.js",
  "author": "$author",
  "scripts": {
    "start": "electron ."
  }
}
STR;
file_put_contents(dirname(__FILE__)."/export_tmp/$code/package.json",$eletron_package);
$variable1 = str_replace(' ', '', strtolower($settings['name']));
$variable2 = str_replace(' ', '', strtolower($name));
$app_id = "com." . $variable1 . "." . $variable2;
$capacitor_config = <<<STR
{
  "appId": "$app_id",
  "appName": "$name",
  "webDir": "www",
  "server": {
    "androidScheme": "https"
  },
  "backgroundColor": "$loading_background_color",
  "ios": {
  	"contentInset":"always"
  }
}
STR;
file_put_contents(dirname(__FILE__)."/export_tmp/$code/capacitor.config.json",$capacitor_config);
copy(dirname(__FILE__)."/../favicons/android-chrome-192x192.png",dirname(__FILE__)."/export_tmp/$code/www/favicons/android-chrome-192x192.png");
copy(dirname(__FILE__)."/../favicons/android-chrome-256x256.png",dirname(__FILE__)."/export_tmp/$code/www/favicons/android-chrome-256x256.png");
copy(dirname(__FILE__)."/../favicons/apple-touch-icon.png",dirname(__FILE__)."/export_tmp/$code/www/favicons/apple-touch-icon.png");
copy(dirname(__FILE__)."/../favicons/browserconfig.xml",dirname(__FILE__)."/export_tmp/$code/www/favicons/browserconfig.xml");
copy(dirname(__FILE__)."/../favicons/favicon.ico",dirname(__FILE__)."/export_tmp/$code/www/favicons/favicon.ico");
copy(dirname(__FILE__)."/../favicons/favicon-16x16.png",dirname(__FILE__)."/export_tmp/$code/www/favicons/favicon-16x16.png");
copy(dirname(__FILE__)."/../favicons/favicon-32x32.png",dirname(__FILE__)."/export_tmp/$code/www/favicons/favicon-32x32.png");
copy(dirname(__FILE__)."/../favicons/mstile-150x150.png",dirname(__FILE__)."/export_tmp/$code/www/favicons/mstile-150x150.png");
copy(dirname(__FILE__)."/../favicons/safari-pinned-tab.svg",dirname(__FILE__)."/export_tmp/$code/www/favicons/safari-pinned-tab.svg");
recursive_copy(dirname(__FILE__)."/../favicons/v_$code",dirname(__FILE__)."/export_tmp/$code/www/favicons/v_$code");
$array_js_files = ['js/jquery-ui.min.js','js/libpannellum.js','js/pannellum.js','vendor/videojs/video.min.js','js/videojs-pannellum-plugin.js','vendor/videojs/youtube.min.js','vendor/fancybox/jquery.fancybox.min.js','js/sly.min.js','js/jquery.floating-social-share.js','vendor/tooltipster/js/tooltipster.bundle.min.js','js/mobile-detect.min.js','js/typed.min.js','vendor/nanogallery2/jquery.nanogallery2.core.min.js','vendor/SpeechKITT/annyang.js','vendor/SpeechKITT/speechkitt.min.js','vendor/jquery-confirm/jquery-confirm.min.js','js/peerjs.min.js','vendor/clipboard.js/clipboard.min.js','js/pixi.min.js','js/jquery.ui.touch-punch.min.js','vendor/leaflet/leaflet.js','vendor/leaflet/L.Control.Locate.min.js','js/numeric.min.js','vendor/simplebar/simplebar.min.js','vendor/glide/glide.min.js','js/effects.js','js/360-view.min.js','js/lottie.min.js','js/progress.min.js','js/panzoom.min.js','vendor/leaderLine/leader-line.min.js','js/moment.js'];
$js = '';
foreach($array_js_files as $js_file) {
    $js .= file_get_contents(dirname(__FILE__)."/../viewer/$js_file");
    $js .= "\r\n\r\n";
}
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/js/script.js",$js);
copy_file(false,'',null,'jquery.min.js','js');
copy_file(false,'',null,'bootstrap.min.js','js');
copy_file(false,'',null,'jquery.touchSwipe.min.js','js');
copy(dirname(__FILE__)."/../viewer/vendor/threejs/three.min.js",dirname(__FILE__)."/export_tmp/$code/www/js/three.min.js");
copy(dirname(__FILE__)."/../viewer/vendor/threejs/Tween.js",dirname(__FILE__)."/export_tmp/$code/www/js/Tween.js");
copy(dirname(__FILE__)."/../viewer/vendor/threejs/OrbitControls.js",dirname(__FILE__)."/export_tmp/$code/www/js/OrbitControls.js");
copy(dirname(__FILE__)."/../viewer/vendor/threejs/CSS2DRenderer.js",dirname(__FILE__)."/export_tmp/$code/www/js/CSS2DRenderer.js");
copy(dirname(__FILE__)."/../viewer/vendor/threejs/threex.domevents.js",dirname(__FILE__)."/export_tmp/$code/www/js/threex.domevents.js");
copy(dirname(__FILE__)."/../viewer/vendor/sweet-dropdown/jquery.sweet-dropdown.min.js",dirname(__FILE__)."/export_tmp/$code/www/js/jquery.sweet-dropdown.min.js");
copy(dirname(__FILE__)."/../viewer/vendor/videojs/videojs-vr.min.js",dirname(__FILE__)."/export_tmp/$code/www/js/videojs-vr.min.js");
copy_file(false,'',null,'index.js','js');
copy_file(false,'',null,'model-viewer.min.js','js');
copy_file(false,'',null,'hls.min.js','js');
copy_file(false,'',null,'howler.min.js','js');
$js = Minifier::minify(file_get_contents(dirname(__FILE__)."/export_tmp/$code/www/js/index.js"),array('flaggedComments' => false));
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/js/index.js",$js);
if(file_exists(dirname(__FILE__)."/../viewer/js/custom.js")) {
    copy_file(false,'',null,'custom.js','js');
}
if(file_exists(dirname(__FILE__)."/../viewer/js/custom_$code.js")) {
    copy_file(false,'',null,"custom_$code.js",'js');
}
copy_file(false,'',null,'icomoon.eot','css');
copy_file(false,'',null,'icomoon.svg','css');
copy_file(false,'',null,'icomoon.ttf','css');
copy_file(false,'',null,'icomoon.woff','css');
copy_file(false,'',null,'compass.eot','css');
copy_file(false,'',null,'compass.svg','css');
copy_file(false,'',null,'compass.ttf','css');
copy_file(false,'',null,'compass.woff','css');
copy_file(false,'',null,'Smoke10.png','css');
copy_file(false,'',null,'transparent.png','css');
copy_file(false,'',null,'dots_loading.gif','css');
copy(dirname(__FILE__)."/../viewer/vendor/leaflet/images/layers.png",dirname(__FILE__)."/export_tmp/$code/www/css/images/layers.png");
copy(dirname(__FILE__)."/../viewer/vendor/SpeechKITT/themes/flat.css",dirname(__FILE__)."/export_tmp/$code/www/css/skflat.css");
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/css/skflat.css",minify_css(file_get_contents(dirname(__FILE__)."/export_tmp/$code/www/css/skflat.css")));
$array_css_files = ['css/jquery-ui.min.css','vendor/fontawesome-free/css/all.min.css','css/pannellum.css','vendor/fancybox/jquery.fancybox.min.css','css/jquery.floating-social-share.css','css/progress.css','vendor/tooltipster/css/tooltipster.bundle.min.css','vendor/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-borderless.min.css','vendor/nanogallery2/css/nanogallery2.min.css','vendor/videojs/video-js.min.css','vendor/videojs/themes/city/index.css','vendor/jquery-confirm/jquery-confirm.min.css','css/bootstrap-iso.css','vendor/leaflet/leaflet.css','vendor/leaflet/L.Control.Locate.min.css','vendor/simplebar/simplebar.css','vendor/glide/glide.core.min.css','vendor/glide/glide.theme.min.css','css/effects.css','css/index.css','css/animate.min.css','vendor/sweet-dropdown/jquery.sweet-dropdown.min.css','css/woocommerce.css'];
$css = '';
foreach($array_css_files as $css_file) {
    $css .= minify_css(file_get_contents(dirname(__FILE__)."/../viewer/$css_file"));
    $css .= "\r\n\r\n";
}
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/css/style.css",$css);
if(file_exists(dirname(__FILE__)."/../viewer/css/custom.css")) {
    copy_file(false,'',null,'custom.css','css');
    $css = minify_css(file_get_contents(dirname(__FILE__)."/export_tmp/$code/www/css/custom.css"));
    file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/css/custom.css",$css);
}
if(file_exists(dirname(__FILE__)."/../viewer/css/custom_$code.css")) {
    copy_file(false,'',null,"custom_$code.css",'css');
    $css = minify_css(file_get_contents(dirname(__FILE__)."/export_tmp/$code/www/css/custom_$code.css"));
    file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/css/custom_$code.css",$css);
}
recursive_copy(dirname(__FILE__)."/../viewer/vendor/nanogallery2/css/font",dirname(__FILE__)."/export_tmp/$code/www/css/font");
recursive_copy(dirname(__FILE__)."/../viewer/vendor/fontawesome-free/webfonts",dirname(__FILE__)."/export_tmp/$code/www/webfonts");
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$song,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$logo,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$poweredby_image,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$nadir_logo,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$background_image,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$background_video,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$background_image_mobile,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$background_video_mobile,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$intro_desktop,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$intro_mobile,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$presentation_video,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$dollhouse_glb,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$id_vt."_slideshow.mp4",'gallery');
$query = "SELECT id FROM svt_virtualtours WHERE shop_type='woocommerce' AND id=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        copy(dirname(__FILE__)."/../viewer/ajax/add_to_cart_wc.php",dirname(__FILE__)."/export_tmp/$code/www/ajax/add_to_cart_wc.php");
        copy(dirname(__FILE__)."/../viewer/ajax/get_total_cart_wc.php",dirname(__FILE__)."/export_tmp/$code/www/ajax/get_total_cart_wc.php");
        copy(dirname(__FILE__)."/../viewer/ajax/remove_to_cart_wc.php",dirname(__FILE__)."/export_tmp/$code/www/ajax/remove_to_cart_wc.php");
        copy(dirname(__FILE__)."/../viewer/ajax/update_quantity_wc.php",dirname(__FILE__)."/export_tmp/$code/www/ajax/update_quantity_wc.php");
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT a.image,a.video FROM svt_advertisements as a JOIN svt_assign_advertisements saa on a.id = saa.id_advertisement WHERE saa.id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            $video = $row['video'];
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'content');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$video,'content');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT image FROM svt_gallery WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'gallery');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'gallery/thumb');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT map FROM svt_maps WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $map = $row['map'];
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$map,'maps');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$map,'maps/thumb');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$array_id_rooms = array();
$query = "SELECT id,type,panorama_image,panorama_video,thumb_image,logo,song FROM svt_rooms WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_room = $row['id'];
            array_push($array_id_rooms,$id_room);
            $panorama_image = $row['panorama_image'];
            $panorama_name = explode(".",$panorama_image)[0];
            $panorama_video = $row['panorama_video'];
            $thumb_image = $row['thumb_image'];
            $logo = $row['logo'];
            $song = $row['song'];
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/lowres');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/mobile');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/original');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/preview');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/thumb');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_video,'videos');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$thumb_image,'panoramas/thumb_custom');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$logo,'content');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$song,'content');
            if($s3_enabled) {
                try {
                    $s3Client->downloadBucket(dirname(__FILE__)."/export_tmp/$code/www/panoramas/multires/$panorama_name/",$s3_bucket_name,"viewer/panoramas/multires/$panorama_name/");
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            } else {
                if(file_exists(dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name/")) {
                    recursive_copy(dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name",dirname(__FILE__)."/export_tmp/$code/www/panoramas/multires/$panorama_name");
                }
            }
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$array_id_pois = array();
$array_id_products = array();
$id_rooms = implode(",",$array_id_rooms);
if(!empty($id_rooms)) {
    $query = "SELECT panorama_image FROM svt_rooms_alt WHERE id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $panorama_image = $row['panorama_image'];
                $panorama_name = explode(".",$panorama_image)[0];
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/lowres');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/mobile');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/original');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/preview');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/thumb');
                if($s3_enabled) {
                    try {
                        $s3Client->downloadBucket(dirname(__FILE__)."/export_tmp/$code/www/panoramas/multires/$panorama_name/",$s3_bucket_name,"viewer/panoramas/multires/$panorama_name/");
                    } catch (\Aws\S3\Exception\S3Exception $e) {}
                } else {
                    if(file_exists(dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name/")) {
                        recursive_copy(dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name",dirname(__FILE__)."/export_tmp/$code/www/panoramas/multires/$panorama_name");
                    }
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT COUNT(*) as num FROM svt_pois WHERE type IN ('pdf','link') AND content LIKE '%.pdf%' AND id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if($row['num']>0) {
                recursive_copy(dirname(__FILE__)."/../viewer/vendor/pdf_viewer",dirname(__FILE__)."/export_tmp/$code/www/vendor/pdf_viewer");
                unlink(dirname(__FILE__)."/export_tmp/$code/www/vendor/pdf_viewer/index.php");
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT COUNT(*) as num FROM svt_pois WHERE type IN ('pointclouds') AND id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if($row['num']>0) {
                recursive_copy(dirname(__FILE__)."/../viewer/vendor/potree",dirname(__FILE__)."/export_tmp/$code/www/vendor/potree");
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT sound FROM svt_markers WHERE id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $sound = $row['sound'];
                if (strpos($sound, 'content/') === 0) {
                    $content_file = basename($sound);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT id,type,content,embed_type,embed_content,sound FROM svt_pois WHERE id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_poi = $row['id'];
                array_push($array_id_pois,$id_poi);
                $type = $row['type'];
                $content = $row['content'];
                $embed_type = $row['embed_type'];
                $embed_content = $row['embed_content'];
                $sound = $row['sound'];
                if (strpos($content, 'content/') === 0) {
                    $content_file = basename($content);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                }
                if (strpos($sound, 'content/') === 0) {
                    $content_file = basename($sound);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                }
                if (strpos($content, 'media/') === 0) {
                    $content_file = basename($content);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                }
                if (strpos($content, 'pointclouds/') === 0) {
                    $path_pc = dirname($content);
                    if($s3_enabled) {
                        try {
                            $s3Client->downloadBucket(dirname(__FILE__)."/export_tmp/$code/www/$path_pc/",$s3_bucket_name,"viewer/$path_pc/");
                        } catch (\Aws\S3\Exception\S3Exception $e) {}
                    } else {
                        if(file_exists(dirname(__FILE__)."/../viewer/$path_pc/")) {
                            recursive_copy(dirname(__FILE__)."/../viewer/$path_pc/",dirname(__FILE__)."/export_tmp/$code/www/$path_pc/");
                        }
                    }
                }
                if($type=='product') {
                    if(!empty($content)) array_push($array_id_products,$content);
                }
                switch($embed_type) {
                    case 'image':
                    case 'video':
                    case 'video_chroma':
                    case 'object3d':
                        if (strpos($embed_content, 'content') === 0) {
                            $content_file = basename($embed_content);
                            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                        }
                        if (strpos($embed_content, 'media') === 0) {
                            $content_file = basename($embed_content);
                            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                        }
                        break;
                    case 'video_transparent':
                        if (strpos($embed_content, ',') !== false) {
                            $array_contents = explode(",",$embed_content);
                            foreach ($array_contents as $content) {
                                if (strpos($content, 'content') === 0) {
                                    $content_file = basename($content);
                                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                                }
                                if (strpos($content, 'media') === 0) {
                                    $content_file = basename($content);
                                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                                }
                            }
                        } else {
                            if (strpos($embed_content, 'content') === 0) {
                                $content_file = basename($embed_content);
                                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                            }
                            if (strpos($embed_content, 'media') === 0) {
                                $content_file = basename($embed_content);
                                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                            }
                        }
                        break;
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT p.id,p.type,pl.content,p.embed_type,pl.embed_content FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_poi = $row['id'];
                array_push($array_id_pois,$id_poi);
                $type = $row['type'];
                $content = $row['content'];
                $embed_type = $row['embed_type'];
                $embed_content = $row['embed_content'];
                if (strpos($content, 'content/') === 0) {
                    $content_file = basename($content);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                }
                if (strpos($content, 'media/') === 0) {
                    $content_file = basename($content);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                }
                if (strpos($content, 'pointclouds/') === 0) {
                    $path_pc = dirname($content);
                    if($s3_enabled) {
                        try {
                            $s3Client->downloadBucket(dirname(__FILE__)."/export_tmp/$code/www/$path_pc/",$s3_bucket_name,"viewer/$path_pc/");
                        } catch (\Aws\S3\Exception\S3Exception $e) {}
                    } else {
                        if(file_exists(dirname(__FILE__)."/../viewer/$path_pc/")) {
                            recursive_copy(dirname(__FILE__)."/../viewer/$path_pc/",dirname(__FILE__)."/export_tmp/$code/www/$path_pc/");
                        }
                    }
                }
                if($type=='product') {
                    if(!empty($content)) array_push($array_id_products,$content);
                }
                switch($embed_type) {
                    case 'image':
                    case 'video':
                    case 'video_chroma':
                    case 'object3d':
                        if (strpos($embed_content, 'content') === 0) {
                            $content_file = basename($embed_content);
                            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                        }
                        if (strpos($embed_content, 'media') === 0) {
                            $content_file = basename($embed_content);
                            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                        }
                        break;
                    case 'video_transparent':
                        if (strpos($embed_content, ',') !== false) {
                            $array_contents = explode(",",$embed_content);
                            foreach ($array_contents as $content) {
                                if (strpos($content, 'content') === 0) {
                                    $content_file = basename($content);
                                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                                }
                                if (strpos($content, 'media') === 0) {
                                    $content_file = basename($content);
                                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                                }
                            }
                        } else {
                            if (strpos($embed_content, 'content') === 0) {
                                $content_file = basename($embed_content);
                                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                            }
                            if (strpos($embed_content, 'media') === 0) {
                                $content_file = basename($embed_content);
                                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
                            }
                        }
                        break;
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT image,id_virtualtour as id_vt_library FROM svt_icons WHERE id IN (SELECT id_icon_library FROM svt_markers WHERE id_icon_library!=0 AND id_room IN ($id_rooms));";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                if(empty($row['id_vt_library'])) {
                    copy_file(false,$s3_bucket_name,$s3Client,$image,'icons');
                } else {
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'icons');
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");

    $query = "SELECT image,id_virtualtour as id_vt_library FROM svt_icons WHERE id IN (SELECT id_icon_library FROM svt_pois WHERE id_icon_library!=0 AND id_room IN ($id_rooms));";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                if(empty($row['id_vt_library'])) {
                    copy_file(false,$s3_bucket_name,$s3Client,$image,'icons');
                } else {
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'icons');
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
}
$id_pois = implode(",",$array_id_pois);
if(!empty($id_pois)) {
    $query = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi IN ($id_pois);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'gallery');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'gallery/thumb');
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT image FROM svt_poi_gallery WHERE id_poi IN ($id_pois);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'gallery');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'gallery/thumb');
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT image FROM svt_poi_objects360 WHERE id_poi IN ($id_pois);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'objects360');
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
}
$id_products = implode(",",$array_id_products);
if(!empty($id_products)) {
    $query = "SELECT image FROM svt_product_images WHERE id_product IN ($id_products);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'products');
                copy_file($s3_enabled,$s3_bucket_name,$s3Client,$image,'products/thumb');
            }
        }
    }
}
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$url = str_replace('services/','viewer/',$url);
$data = file_get_contents($url."index.php?code=$code&export=1", false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/index.html",$data);
foreach ($vt_languages_enabled as $lang=>$lang_enabled) {
    if($lang_enabled==1) copy_file(false,'',null,"$lang.png",'css/flags_lang');
    if($lang_enabled==1 && $lang!=$default_language) {
        $data = file_get_contents($url."index.php?code=$code&lang=$lang&export=1", false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
        file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/$lang.html",$data);
    }
}
$file_name_zip = str_replace(" ","_",$name).".zip";
RemoveEmptySubFolders(dirname(__FILE__)."/export_tmp/$code/");
zip_folder($code,$file_name_zip);
if(file_exists(dirname(__FILE__)."/export_tmp/$file_name_zip")) {
    deleteDirectory(dirname(__FILE__)."/export_tmp/$code/");
    ob_end_clean();
    echo json_encode(array("status"=>"ok","zip"=>"$file_name_zip"));
    exit;
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}

function check_directory($path) {
    try {
        if (!file_exists(dirname(__FILE__).$path)) {
            mkdir(dirname(__FILE__).$path, 0775,true);
        }
    } catch (Exception $e) {}
}

function copy_file($s3_enabled,$s3_bucket_name,$s3Client,$file_name,$dir) {
    global $code;
    if(!empty($file_name)) {
        if($s3_enabled) {
            try {
                $exist = $s3Client->doesObjectExist($s3_bucket_name,'viewer/'.$dir.'/'.$file_name);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                $exist = false;
            }
            if($exist) {
                try {
                    $s3Client->getObject(array(
                        'Bucket' => $s3_bucket_name,
                        'Key'    => 'viewer/'.$dir.'/'.$file_name,
                        'SaveAs' => dirname(__FILE__)."/export_tmp/$code/www/$dir/$file_name"
                    ));
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        } else {
            $source = dirname(__FILE__)."/../viewer/$dir/$file_name";
            if(file_exists($source)) {
                $dest = dirname(__FILE__)."/export_tmp/$code/www/$dir/$file_name";
                @copy($source,$dest);
            }
        }
    }
}

function recursive_copy($src,$dst) {
    $dir = opendir($src);
    if($dir!=false) {
        @mkdir($dst,0775,true);
        while(( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file)) {
                    recursive_copy($src .'/'. $file, $dst .'/'. $file);
                } else {
                    @copy($src .'/'. $file,$dst .'/'. $file);
                }
            }
        }
        closedir($dir);
    }
}


function minify_css($input) {
    if(trim($input) === "") return $input;
    $input = preg_replace('!/\*.*?\*/!s', '', $input);
    $input = preg_replace('/\n\s*\n/', "\n", $input);
    return preg_replace(
        array(
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
        ),
        array(
            '$1',
            '$1$2$3$4$5$6$7',
        ),
        $input);
}

function RemoveEmptySubFolders($path) {
    $empty=true;
    foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file) {
        if (is_dir($file)) {
            if (!RemoveEmptySubFolders($file)) $empty=false;
        } else {
            $empty=false;
        }
    }
    if ($empty) rmdir($path);
    return $empty;
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

function zip_folder($code,$file_name_zip) {
    $rootPath = realpath(dirname(__FILE__)."/export_tmp/$code/");
    $zip = new ZipArchive();
    $zip->open(dirname(__FILE__)."/export_tmp/$file_name_zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
}