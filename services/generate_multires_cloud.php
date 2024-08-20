<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
ignore_user_abort(true);
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
set_time_limit(9999);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$mysqli->query("SET session wait_timeout=3600");
if(isset($_GET['check_req'])) {
    $check_req = 1;
    if(isset($_GET['multires_cloud_url'])) {
        $multires_cloud_url = $_GET['multires_cloud_url'];
        $mysqli->query("UPDATE svt_settings SET multires_cloud_url='$multires_cloud_url';");
    }
} else {
    $check_req = 0;
}
$debug = false;

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
$path = realpath(dirname(__FILE__) . '/..');
if(isset($_POST['curl'])) {
    if(isset($_POST['force_update'])) {
        if($_POST['force_update']==1) {
            $force_update = true;
        } else {
            $force_update = false;
        }
    } else {
        $force_update = false;
    }
    if(isset($_POST['id_vt'])) {
        $where = "AND id = ".$_POST['id_vt'];
    } else {
        $where = "";
    }
} else {
    if(isset($argv[1])) {
        if($argv[1]==1) {
            $force_update = true;
        } else {
            $force_update = false;
        }
    }
    if(isset($argv[2])) {
        $id_virtualtour = $argv[2];
        $where = "AND id = $id_virtualtour";
    }
}
$multires_cloud_url = $settings['multires_cloud_url'];
if (!file_exists(dirname(__FILE__).'/../viewer/panoramas/multires/')) {
    mkdir(dirname(__FILE__).'/../viewer/panoramas/multires/', 0775);
}

if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
    $check_url = file_get_contents($multires_cloud_url."?check=1", false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
    if(empty($check_url)) {
        $check_url = curl_get_file_contents($multires_cloud_url."?check=1");
    }
} else {
    $check_url = curl_get_file_contents($multires_cloud_url."?check=1");
}
if($check_url!='ok') {
    try {
        $json = json_decode($check_url,true);
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$json['msg']));
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$check_url));
        exit;
    }
}

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("php zip not enabled")));
    exit;
}

if($check_req)  {
    ob_end_clean();
    echo json_encode(array("status"=>"ok","msg"=>_("All requirements are met.")));
    exit;
}

if($debug) {
    $date = date('Y-m-d H:i');
    if(isset($_POST['curl'])) {
        file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt","$date - check_req: $check_req, post: ".serialize($_POST).PHP_EOL,FILE_APPEND);
    } else {
        file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt","$date - check_req: $check_req, post: ".serialize($argv).PHP_EOL,FILE_APPEND);
    }
}

$s3Client = null;
$array_rooms = array();
$query_vt = "SELECT id,compress_jpg FROM svt_virtualtours WHERE enable_multires=1 $where;";
$result_vt = $mysqli->query($query_vt);
if($result_vt) {
    if ($result_vt->num_rows>0) {
        while($row_vt = $result_vt->fetch_array(MYSQLI_ASSOC)) {
            $id_vt = $row_vt['id'];
            $s3_params = check_s3_tour_enabled($id_vt);
            $s3_enabled = false;
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
            $quality = $row_vt['compress_jpg'];
            if(empty($quality)) $quality = 100;
            if($force_update) {
                $query = "SELECT id,panorama_image,blur,haov,vaov FROM svt_rooms WHERE id_virtualtour=$id_vt AND type='image';";
            } else {
                $query = "SELECT id,panorama_image,blur,haov,vaov FROM svt_rooms WHERE id_virtualtour=$id_vt AND multires_status=0 AND type='image';";
            }
            $result = $mysqli->query($query);
            if($result) {
                if ($result->num_rows>0) {
                    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                        $row['t']='room';
                        $row['s3_enabled']=$s3_enabled;
                        $row['s3_bucket_name']=$s3_bucket_name;
                        $array_rooms[] = $row;
                        $id_room = $row['id'];
                        $mysqli->query("UPDATE svt_rooms SET multires_status=1 WHERE id=$id_room;");
                    }
                }
            }
            if($force_update) {
                $query_ra = "SELECT ra.id,ra.panorama_image,0 as blur,r.haov,r.vaov FROM svt_rooms_alt as ra JOIN svt_rooms as r ON r.id=ra.id_room WHERE ra.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_vt);";
            } else {
                $query_ra = "SELECT ra.id,ra.panorama_image,0 as blur,r.haov,r.vaov FROM svt_rooms_alt as ra JOIN svt_rooms as r ON r.id=ra.id_room WHERE ra.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_vt) AND ra.multires_status=0;";
            }
            $result_ra = $mysqli->query($query_ra);
            if($result_ra) {
                if ($result_ra->num_rows>0) {
                    while ($row_ra = $result_ra->fetch_array(MYSQLI_ASSOC)) {
                        $row_ra['t']='room_alt';
                        $row_ra['s3_enabled']=$s3_enabled;
                        $row_ra['s3_bucket_name']=$s3_bucket_name;
                        $array_rooms[] = $row_ra;
                        $id_room_alt = $row_ra['id'];
                        $mysqli->query("UPDATE svt_rooms_alt SET multires_status=1 WHERE id=$id_room_alt;");
                    }
                }
            }
        }
    }
}
foreach ($array_rooms as $room) {
    $id_room = $room['id'];
    $t = $room['t'];
    $s3_enabled = $room['s3_enabled'];
    $s3_bucket_name = $room['s3_bucket_name'];
    $blur = $room['blur'];
    $vaov = $room['vaov'];
    $haov = $room['haov'];
    if($blur==1) {
        $quality_t = 100;
    } else {
        $quality_t = $quality;
    }
    $pano = str_replace(".jpg","",$room['panorama_image']);
    if($force_update) {
        $command = "rm -R ".$path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano;
        shell_exec($command);
    }
    if($s3_enabled) {
        $exist_original = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/original/'.$pano.'.jpg');
        $pano_path = $path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR.$pano.".jpg";
        if($blur==0 && $exist_original) {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/panoramas/original/'.$pano.'.jpg',
                'SaveAs' => $pano_path
            ));
        } else {
            $s3Client->getObject(array(
                'Bucket' => $s3_bucket_name,
                'Key'    => 'viewer/panoramas/'.$pano.'.jpg',
                'SaveAs' => $pano_path
            ));
        }
        $path_dest = $path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano;
    } else {
        if($blur==0 && file_exists($path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."original".DIRECTORY_SEPARATOR.$pano.".jpg")) {
            $pano_path = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."original".DIRECTORY_SEPARATOR.$pano.".jpg";
        } else {
            $pano_path = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR.$pano.".jpg";
        }
        $path_dest = $path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano;
    }
    $cfile = new CURLFile($pano_path,'image/jpg',$room['panorama_image']);
    $post = array('file'=>$cfile,'id_room'=>$id_room,"pano"=>$pano,"quality"=>$quality_t,"haov"=>$haov,"vaov"=>$vaov);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$multires_cloud_url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $curl_result = curl_exec($ch);
    curl_close ($ch);
    $response = json_decode($curl_result,true);
    if($response['status']=='ok') {
        $file_url = stripFile($multires_cloud_url)."/multires_tmp/".$pano.".zip";
        if(function_exists('ini_get') && ini_get('allow_url_fopen')) {
            $file_zip = file_get_contents($file_url, false, stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false))));
            if(empty($file_zip)) {
                $file_zip = curl_get_file_contents($file_url);
            }
        } else {
            $file_zip = curl_get_file_contents($file_url);
        }
        file_put_contents(dirname(__FILE__).'/../viewer/panoramas/multires/'.$pano.".zip",$file_zip);
        if(file_exists(dirname(__FILE__).'/../viewer/panoramas/multires/'.$pano.".zip")) {
            $zip = new ZipArchive;
            $res = $zip->open(dirname(__FILE__).'/../viewer/panoramas/multires/'.$pano.".zip");
            if ($res === TRUE) {
                $zip->extractTo($path_dest);
                $zip->close();
                unlink(dirname(__FILE__).'/../viewer/panoramas/multires/'.$pano.".zip");
                $post = array('complete_pano'=>$pano);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$multires_cloud_url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                $curl_result = curl_exec($ch);
            }
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$response['msg']));
        exit;
    }
    if($s3_enabled) {
        if(file_exists($path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano.DIRECTORY_SEPARATOR."config.json")) {
            $multires_config = file_get_contents($path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano.DIRECTORY_SEPARATOR."config.json");
            $multires_config = str_replace("'","\'",$multires_config);
            $s3Client->uploadDirectory($path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano, $s3_bucket_name,'viewer/panoramas/multires/'.$pano);
            $exist_multires = $s3Client->doesObjectExist($s3_bucket_name,'viewer/panoramas/multires/'.$pano.'/config.json');
            unlink($path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR.$pano.".jpg");
            $command = "rm -R ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."import_tmp".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano;
            shell_exec($command);
            if($exist_multires) {
                if($settings['aws_s3_type']=='digitalocean') {
                    $objects = $s3Client->listObjects([
                        'Bucket' => $s3_bucket_name,
                        'Prefix' => "viewer/panoramas/multires/$pano/"
                    ])->get('Contents');
                    foreach ($objects as $object) {
                        $s3Client->putObjectAcl([
                            'Bucket' => $s3_bucket_name,
                            'Key' => $object['Key'],
                            'ACL' => 'public-read',
                        ]);
                    }
                }
                if($t=='room') {
                    $query = "UPDATE svt_rooms SET multires_status=2,multires_config='$multires_config' WHERE id=$id_room;";
                } else {
                    $query = "UPDATE svt_rooms_alt SET multires_status=2,multires_config='$multires_config' WHERE id=$id_room;";
                }
            } else {
                if($t=='room') {
                    $query = "UPDATE svt_rooms SET multires_status=0,multires_config='' WHERE id=$id_room;";
                } else {
                    $query = "UPDATE svt_rooms_alt SET multires_status=0,multires_config='' WHERE id=$id_room;";
                }
            }
        }
    } else {
        if(file_exists($path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano.DIRECTORY_SEPARATOR."config.json")) {
            $multires_config = file_get_contents($path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano.DIRECTORY_SEPARATOR."config.json");
            $multires_config = str_replace("'","\'",$multires_config);
            if($t=='room') {
                $query = "UPDATE svt_rooms SET multires_status=2,multires_config='$multires_config' WHERE id=$id_room;";
            } else {
                $query = "UPDATE svt_rooms_alt SET multires_status=2,multires_config='$multires_config' WHERE id=$id_room;";
            }
        } else {
            if($t=='room') {
                $query = "UPDATE svt_rooms SET multires_status=0,multires_config='' WHERE id=$id_room;";
            } else {
                $query = "UPDATE svt_rooms_alt SET multires_status=0,multires_config='' WHERE id=$id_room;";
            }
        }
    }
    $mysqli->query($query);
}

function stripFile($in){
    $pieces = explode("/", $in);
    if(strpos(end($pieces), ".") !== false){
        array_pop($pieces);
    }elseif(end($pieces) !== ""){
        $pieces[] = "";
    }
    return implode("/", $pieces);
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
            file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$ip." "."FATAL: ".format_error( $errno, $errstr, $errfile, $errline).PHP_EOL,FILE_APPEND);
        }
    }
}

function format_error( $errno, $errstr, $errfile, $errline ) {
    $trace = print_r( debug_backtrace( false ), true );
    $content = "Error: $errstr, Line:$errline";
    return $content;
}