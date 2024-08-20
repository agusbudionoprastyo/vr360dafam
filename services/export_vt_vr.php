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
$query = "SELECT name,description,author,code,song,logo,nadir_logo,background_image,loading_background_color FROM svt_virtualtours WHERE id=$id_vt LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
        $song = $row['song'];
        $logo = $row['logo'];
        $background_image = $row['background_image'];
        $name = $row['name'];
        $description = $row['description'];
        $author = $row['author'];
        $loading_background_color = $row['loading_background_color'];
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
$code_vt = $code;
$code = $code."_vr";
if(file_exists(dirname(__FILE__)."/export_tmp/$code/")) {
    deleteDirectory(dirname(__FILE__)."/export_tmp/$code/");
}
check_directory("/export_tmp/");
check_directory("/export_tmp/$code/");
check_directory("/export_tmp/$code/www/www/");
check_directory("/export_tmp/$code/www/favicons/");
check_directory("/export_tmp/$code/www/favicons/v_$code_vt");
check_directory("/export_tmp/$code/www/css/");
check_directory("/export_tmp/$code/www/img/");
check_directory("/export_tmp/$code/www/font/");
check_directory("/export_tmp/$code/www/js/");
check_directory("/export_tmp/$code/www/panoramas/");
check_directory("/export_tmp/$code/www/panoramas/mobile/");
check_directory("/export_tmp/$code/www/videos/");
check_directory("/export_tmp/$code/www/content/");
check_directory("/export_tmp/$code/www/gallery/");
check_directory("/export_tmp/$code/www/gallery/thumb/");
check_directory("/export_tmp/$code/www/media/");
check_directory("/export_tmp/$code/www/media/thumb/");
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
$app_id = "com." . $variable1 . "." . $variable2."_vr";
$capacitor_config = <<<STR
{
  "appId": "$app_id",
  "appName": "VR $name",
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
recursive_copy(dirname(__FILE__)."/../favicons/v_$code_vt",dirname(__FILE__)."/export_tmp/$code/www/favicons/v_$code_vt");
$array_js_files = ['js/aframe-master.min.js','js/aframe-look-at-billboard-component.js','js/progress.min.js'];
$js = '';
foreach($array_js_files as $js_file) {
    $js .= file_get_contents(dirname(__FILE__)."/../vr/$js_file");
    $js .= "\r\n\r\n";
}
copy(dirname(__FILE__)."/../vr/js/index.js",dirname(__FILE__)."/export_tmp/$code/www/js/index.js");
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/js/script.js",$js);
$js = Minifier::minify(file_get_contents(dirname(__FILE__)."/export_tmp/$code/www/js/index.js"),array('flaggedComments' => false));
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/js/index.js",$js);
copy(dirname(__FILE__)."/../vr/font/Roboto-Regular.png",dirname(__FILE__)."/export_tmp/$code/www/font/Roboto-Regular.png");
copy(dirname(__FILE__)."/../vr/font/Roboto-Regular-msdf.json",dirname(__FILE__)."/export_tmp/$code/www/font/Roboto-Regular-msdf.json");
recursive_copy(dirname(__FILE__)."/../vr/img",dirname(__FILE__)."/export_tmp/$code/www/img");
$array_css_files = ['css/index.css','css/progress.css'];
$css = '';
foreach($array_css_files as $css_file) {
    $css .= minify_css(file_get_contents(dirname(__FILE__)."/../vr/$css_file"));
    $css .= "\r\n\r\n";
}
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/css/style.css",$css);
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$song,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$logo,'content');
copy_file($s3_enabled,$s3_bucket_name,$s3Client,$background_image,'content');

$array_id_rooms = array();
$query = "SELECT id,panorama_image,panorama_video FROM svt_rooms WHERE type IN ('image','video') AND id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_room = $row['id'];
            array_push($array_id_rooms,$id_room);
            $panorama_image = $row['panorama_image'];
            $panorama_name = explode(".",$panorama_image)[0];
            $panorama_video = $row['panorama_video'];
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_image,'panoramas/mobile');
            copy_file($s3_enabled,$s3_bucket_name,$s3Client,$panorama_video,'videos');
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
    $query = "SELECT id,type,content FROM svt_pois WHERE content!='' AND id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_poi = $row['id'];
                array_push($array_id_pois,$id_poi);
                $type = $row['type'];
                $content = $row['content'];
                if (strpos($content, 'content/') === 0) {
                    $content_file = basename($content);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'content');
                }
                if (strpos($content, 'media/') === 0) {
                    $content_file = basename($content);
                    copy_file($s3_enabled,$s3_bucket_name,$s3Client,$content_file,'media');
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

$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$url = str_replace('services/','vr/',$url);
$data = file_get_contents($url."index.php?code=$code_vt&export=1&export_s3=".(($s3_enabled) ? 1 : 0), false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
file_put_contents(dirname(__FILE__)."/export_tmp/$code/www/index.html",$data);
$file_name_zip = str_replace(" ","_",$name)."_vr.zip";
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