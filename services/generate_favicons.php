<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
set_time_limit(9999);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$debug = false;

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

$settings = get_settings();
if(!empty($settings['small_logo'])) {
    $logo_backend = $settings['small_logo'];
} else {
    $logo_backend = $settings['logo'];
}
$api_key = "ef6e1fd351061564ebe63d780cffa9e3cfc29a40";

if(isset($_GET['url'])) {
    $url = $_GET['url'];
    $url = str_replace("/backend/ajax","",$url);
} else {
    if(isset($argv[1])) {
        $url = $argv[1];
        $url = str_replace("/backend/ajax","",$url);
    } else {
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        $hostName = $_SERVER['HTTP_HOST'];
        if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
        $url = $protocol."://".$hostName.$pathInfo['dirname'];
        $url = str_replace("/services","",$url);
    }
}

if(isset($_GET['what'])) {
    $what = $_GET['what'];
} else {
    if(isset($argv[2])) {
        $what = $argv[2];
    } else {
        $what = 'all';
    }
}

if(isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    if(isset($argv[3])) {
        $id = $argv[3];
    } else {
        $id = 0;
    }
}

if($debug) {
    if(isset($argv[1])) {
        file_put_contents(realpath(dirname(__FILE__))."/log_favicons.txt",$date." - ".$ip." "."ARGV: ".serialize($argv).PHP_EOL,FILE_APPEND);
    }
    file_put_contents(realpath(dirname(__FILE__))."/log_favicons.txt",$date." - ".$ip." "."GET: ".serialize($_GET).PHP_EOL,FILE_APPEND);
}

$path = realpath(dirname(__FILE__) . '/..');

if($what=='backend' || $what=='all') {
    if(!empty($logo_backend)) {
        if (!file_exists($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR)) {
            mkdir($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR, 0775);
            chmod($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR, 0775);
        }
        $url_backend_logo = $url.'/backend/assets/'.$logo_backend;
        $lang = $settings['language'];
        $lang = strtok($lang, '_');
        $description = $settings['name']." - "."BACKEND";
        generate_favicons_api($api_key,$settings['name'],'../../backend/',$url_backend_logo,$path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR);
        fix_manifest($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR,'../../backend/',$settings['name'],$lang,$description);
    }
}

$s3Client = null;
if($what=='vt' || $what=='all') {
    if($what=='all') {
        $query = "SELECT id,code,logo,name,description,meta_description,language FROM svt_virtualtours;";
    } else {
        $query = "SELECT id,code,logo,name,description,meta_description,language FROM svt_virtualtours WHERE id=$id;";
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_vt = $row['id'];
                $s3_params = check_s3_tour_enabled($id_vt);
                $s3_enabled = false;
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    if($s3Client==null) {
                        $s3Client = init_s3_client_no_wrapper($s3_params);
                        if($s3Client==null) {
                            $s3_enabled = false;
                        } else {
                            if(!empty($s3_params['custom_domain'])) {
                                $s3_url = "https://".$s3_params['custom_domain']."/";
                            } else {
                                try {
                                    $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.');
                                } catch (Aws\Exception\S3Exception $e) {}
                            }
                            $s3_enabled = true;
                        }
                    } else {
                        $s3_enabled = true;
                    }
                }
                $code = $row['code'];
                $logo = $row['logo'];
                $name = $row['name'];
                $lang = $row['language'];
                if(empty($lang)) {
                    $lang = $settings['language'];
                }
                $lang = strtok($lang, '_');
                if(empty($row['meta_description'])) {
                    $description = $row['description'];
                } else {
                    $description = $row['meta_description'];
                }
                if (!file_exists($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'v_'.$code.DIRECTORY_SEPARATOR)) {
                    mkdir($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'v_'.$code.DIRECTORY_SEPARATOR, 0775);
                }
                if (!file_exists($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'vr_'.$code.DIRECTORY_SEPARATOR)) {
                    mkdir($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'vr_'.$code.DIRECTORY_SEPARATOR, 0775);
                }
                if(!empty($logo)) {
                    if($s3_enabled) {
                        $url_logo = $s3_url.'viewer/content/'.$logo;
                    } else {
                        $url_logo = $url.'/viewer/content/'.$logo;
                    }
                    generate_favicons_api($api_key,$name,'../../viewer/'.$code,$url_logo,$path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'v_'.$code.DIRECTORY_SEPARATOR);
                    generate_favicons_api($api_key,$name,'../../vr/'.$code,$url_logo,$path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'vr_'.$code.DIRECTORY_SEPARATOR);
                }
                fix_manifest($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'v_'.$code.DIRECTORY_SEPARATOR,'../../viewer/'.$code,$name,$lang,$description);
                fix_manifest($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'vr_'.$code.DIRECTORY_SEPARATOR,'../../vr/'.$code,$name,$lang,$description);
            }
        }
    }
}

if($what=='showcase' || $what=='all') {
    if($what=='all') {
        $query = "SELECT code,logo,name,header_html,meta_description FROM svt_showcases;";
    } else {
        $query = "SELECT code,logo,name,header_html,meta_description FROM svt_showcases WHERE id=$id;";
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $logo = $row['logo'];
                $name = $row['name'];
                if(empty($row['meta_description'])) {
                    $description = strip_tags($row['header_html']);
                } else {
                    $description = $row['meta_description'];
                }
                if (!file_exists($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'s_'.$code.DIRECTORY_SEPARATOR)) {
                    mkdir($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'s_'.$code.DIRECTORY_SEPARATOR, 0775);
                }
                if(!empty($logo)) {
                    $url_logo = $url.'/viewer/content/'.$logo;
                    generate_favicons_api($api_key,$name,'../../showcase/'.$code,$url_logo,$path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'s_'.$code.DIRECTORY_SEPARATOR);
                }
                $lang = $settings['language'];
                $lang = strtok($lang, '_');
                fix_manifest($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'s_'.$code.DIRECTORY_SEPARATOR,'../../showcase/'.$code,$name,$lang,$description);
            }
        }
    }
}

if($what=='globe' || $what=='all') {
    if($what=='all') {
        $query = "SELECT code,logo,name,meta_description FROM svt_globes;";
    } else {
        $query = "SELECT code,logo,name,meta_description FROM svt_globes WHERE id=$id;";
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $logo = $row['logo'];
                $name = $row['name'];
                $description = $row['meta_description'];
                if (!file_exists($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'g_'.$code.DIRECTORY_SEPARATOR)) {
                    mkdir($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'g_'.$code.DIRECTORY_SEPARATOR, 0775);
                }
                if(!empty($logo)) {
                    $url_logo = $url.'/viewer/content/'.$logo;
                    generate_favicons_api($api_key,$name,'../../globe/'.$code,$url_logo,$path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'g_'.$code.DIRECTORY_SEPARATOR);
                }
                $lang = $settings['language'];
                $lang = strtok($lang, '_');
                fix_manifest($path.DIRECTORY_SEPARATOR.'favicons'.DIRECTORY_SEPARATOR.'g_'.$code.DIRECTORY_SEPARATOR,'../../globe/'.$code,$name,$lang,$description);
            }
        }
    }
}

ob_end_clean();
echo "ok";

function generate_favicons_api($api_key,$name,$start_url,$url_logo,$destination) {
    global $what,$debug,$date,$ip;
    if($what=='all' && file_exists($destination."favicon.ico")) return;
    if($debug) {
        file_put_contents(realpath(dirname(__FILE__))."/log_favicons.txt",$date." - ".$ip." "."GENERATE: ".$destination.PHP_EOL,FILE_APPEND);
    }
    $json = '{
        "favicon_generation": {
            "api_key": "'.$api_key.'",
            "master_picture": {
                "type": "url",
                "url": "'.$url_logo.'"
            },
            "files_location": {
                "type": "path",
                "path": "."
            },
            "favicon_design": {
                "desktop_browser": {},
                "ios": {
                    "picture_aspect": "background_and_margin",
                    "margin": "4",
                    "background_color": "#ffffff",
                    "assets": {
                        "ios6_and_prior_icons": false,
                        "ios7_and_later_icons": true,
                        "precomposed_icons": false,
                        "declare_only_default_icon": true
                    }
                },
                "windows": {
                    "picture_aspect": "white_silhouette",
                    "background_color": "#ffffff",
                    "assets": {
                        "windows_80_ie_10_tile": true,
                        "windows_10_ie_11_edge_tiles": {
                            "small": false,
                            "medium": true,
                            "big": true,
                            "rectangle": false
                        }
                    }
                },
                "android_chrome": {
                    "picture_aspect": "shadow",
                    "assets": {
                        "legacy_icon": true,
                        "low_resolution_icons": false
                    },
                    "manifest": {
                        "name": "'.$name.'",
                        "display": "standalone",
                        "orientation": "portrait",
                        "start_url": "'.$start_url.'"
                    },
                    "theme_color": "#ffffff"
                },
                "safari_pinned_tab": {
                    "picture_aspect": "black_and_white",
                    "threshold": 60,
                    "theme_color": "#ffffff"
                }
            },
            "settings": {
                "compression": "3",
                "scaling_algorithm": "Mitchell",
                "error_on_image_too_small": false,
                "readme_file": false,
                "html_code_file": false,
                "use_path_as_is": false
            }
        }
    }';
    $url = 'https://realfavicongenerator.net/api/favicon';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    $array = json_decode($result,true);
    $files_url = $array['favicon_generation_result']['favicon']['files_urls'];
    $opts = array(
        'http'=>array(
            'method'=>"GET",
            'timeout'=>60,
            'ignore_errors'=> true,
            'header'=>"Accept-language: en\r\n" .
                "Cookie: foo=bar\r\n" .
                "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n"

        )
    );
    $context = stream_context_create($opts);
    foreach ($files_url as $file_url) {
        $file_name = basename($file_url);
        $file_data = file_get_contents($file_url,false,$context);
        if($file_data==false) {
            $file_data = file_get_contents($file_url,false,$context);
        }
        file_put_contents($destination.$file_name,$file_data);
    }
}

function fix_manifest($path_dir,$url,$name,$lang,$description) {
    if (file_exists($path_dir.'site.webmanifest')) {
        $content = file_get_contents($path_dir.'site.webmanifest');
        $array = json_decode($content,true);
        if(!array_key_exists('start_url',$array)) {
            $array['start_url'] = $url;
        }
        if(!array_key_exists('url',$array)) {
            $array['url'] = $url;
        }
        if(!array_key_exists('scope',$array)) {
            $array['scope'] = $url;
        }
        if(!array_key_exists('dir',$array)) {
            $array['dir'] = 'ltr';
        }
        if(!array_key_exists('categories',$array)) {
            $array['categories'] = ["entertainment", "photo"];
        }
    } else {
        $content = '{"name":"","short_name":"","icons":[{"src":"","sizes":"192x192","type":"image\/png"}],"theme_color":"#ffffff","background_color":"#ffffff","start_url":"","display":"standalone","orientation":"portrait","url":"","scope":""}';
        $array = json_decode($content,true);
        $array['start_url'] = $url;
        $array['url'] = $url;
        $array['scope'] = $url;
        $array['dir'] = 'ltr';
        $array['categories'] = ["entertainment", "photo"];
    }
    $array['display'] = 'standalone';
    $array['display_override'] = ["standalone","fullscreen", "minimal-ui"];
    $array['lang'] = $lang;
    $array['name'] = $name;
    $array['short_name'] = $name;
    $array['orientation']="any";
    $array['description'] = $description;
    $array['prefer_related_applications']=false;
    if(empty($array['icons'][0]['src'])) {
        if(file_exists($path_dir."android-chrome-192x192.png")) {
            $array['icons'][0]['src']="android-chrome-192x192.png";
        } elseif(file_exists($path_dir."..".DIRECTORY_SEPARATOR."custom".DIRECTORY_SEPARATOR."android-chrome-192x192.png")) {
            $array['icons'][0]['src']="../custom/android-chrome-192x192.png";
        } elseif(file_exists($path_dir."..".DIRECTORY_SEPARATOR."android-chrome-192x192.png")) {
            $array['icons'][0]['src']="../android-chrome-192x192.png";
        }
    } else {
        for($i=0;$i<count($array['icons']);$i++) {
            $array['icons'][$i]['purpose']='any';
        }
    }
    $json = json_encode($array);
    file_put_contents($path_dir.'site.webmanifest',$json);
}

function get_client_ip() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function fatal_handler() {
    global $debug,$date,$ip;
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;
    $error = error_get_last();
    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_favicons.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}