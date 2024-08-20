<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
set_time_limit(9999);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$mysqli->query("SET session wait_timeout=3600");
$debug = false;
if($debug) {
    register_shutdown_function( "fatal_handler" );
}
$path = realpath(dirname(__FILE__) . '/..');
if(isset($_GET['check_req'])) {
    $check_req = 1;
} else {
    $check_req = 0;
}
$settings = get_settings();
set_language_force('en_US','default');
session_write_close();
if(isset($argv[1])) {
    if($argv[1]==1) {
        $force_update = 1;
    } else {
        $force_update = 0;
    }
} else {
    $force_update = 0;
}
if(isset($argv[2])) {
    $id_virtualtour = $argv[2];
    $where = "AND id = $id_virtualtour";
} else {
    $id_virtualtour = 0;
    $where = "";
}

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt","$date - check_req: $check_req, force_update: $force_update, id_virtualtour: $id_virtualtour".PHP_EOL,FILE_APPEND);
}

if(!isEnabled('shell_exec')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"php <b>shell_exec</b> "._("function disabled")));
    exit;
}

$command = 'dpkg-query -W -f=\'${Status}\' python3 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false || strpos(strtolower($output), 'commande introuvable') !== false) {
    $command = 'rpm -q python3 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'command -v python3 2>&1';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'python3') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3</b>.<br>"._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3</b>.<br>"._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' python3-pip 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false || strpos(strtolower($output), 'commande introuvable') !== false) {
    $command = 'rpm -q python3-pip 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'python3 -m pip list | grep -i \'pip\'';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'pip') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-pip</b>.<br>"._("Execute the command")." \"apt-get install python3-pip\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-pip</b>.<br>"._("Execute the command")." \"apt-get install python3-pip\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' python3-pil 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false || strpos(strtolower($output), 'commande introuvable') !== false) {
    $command = 'rpm -q python3-pil 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'python3 -m pip list | grep -i \'pillow\'';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'pillow') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-pil</b>.<br>"._("Execute the command")." \"apt-get install python3-pil\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-pil</b>.<br>"._("Execute the command")." \"apt-get install python3-pil\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' python3-numpy 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false || strpos(strtolower($output), 'commande introuvable') !== false) {
    $command = 'rpm -q python3-numpy 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'python3 -m pip list | grep -i \'numpy\'';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'numpy') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-numpy</b>.<br>"._("Execute the command")." \"apt-get install python3-numpy\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-numpy</b>.<br>"._("Execute the command")." \"apt-get install python3-numpy\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' hugin-tools 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'command not found') !== false || strpos(strtolower($output), 'no packages found') !== false || strpos(strtolower($output), 'commande introuvable') !== false) {
    $command = 'rpm -q hugin-tools 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>hugin-tools</b>.<br>"._("Execute the command")." \"apt-get install hugin-tools\" "._("on your server")."."));
        exit;
    }
} else {
    if (strpos(strtolower($output), 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>hugin-tools</b>.<br>"._("Execute the command")." \"apt-get install hugin-tools\" "._("on your server")."."));
        exit;
    }
}

$command = 'pip3 list | grep -F pyshtools 2>&1';
$output = shell_exec($command);
if (strpos(strtolower($output), 'pyshtools') === false) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>pyshtools</b>.<br>"._("Execute the command")." \"sudo pip3 install pyshtools\" "._("on your server")."."));
    exit;
}

$command = 'command -v nona 2>&1';
$output = shell_exec($command);
if($output=="") {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing command")." nona."));
    exit;
} else {
    $path_nona = trim($output);
}

$command = 'command -v python3 2>&1';
$output = shell_exec($command);
if($output=="") {
    $command = 'command -v python 2>&1';
    $output = shell_exec($command);
    if($output=="") {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing command")." python3."));
        exit;
    } else {
        $path_python = trim($output);
    }
} else {
    $path_python = trim($output);
}

try {
    shell_exec("chmod +x ".$path.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'generate.py');
} catch (Exception $e) {}

if($check_req)  {
    ob_end_clean();
    echo json_encode(array("status"=>"ok","msg"=>_("All requirements are met.")));
    exit;
}

if(empty($where)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Argv parameters not set.")));
    if($debug) {
        $date = date('Y-m-d H:i');
        file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ARGV Empty: ".serialize($argv).PHP_EOL,FILE_APPEND);
    }
    exit;
}

$s3Client = null;
$s3_bucket_name = "";
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
            if($force_update==1) {
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
                        $mysqli->query("UPDATE svt_rooms SET multires_status=1,multires_config=NULL WHERE id=$id_room;");
                    }
                }
            }
            if($force_update==1) {
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
                        $mysqli->query("UPDATE svt_rooms_alt SET multires_status=1,multires_config=NULL WHERE id=$id_room_alt;");
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
    if($force_update==1) {
        if($s3_enabled) {
            $results = $s3Client->listObjectsV2([
                'Bucket' => $s3_bucket_name,
                'Prefix' => 'viewer/panoramas/multires/'.$pano
            ]);
            if (isset($results['Contents'])) {
                foreach ($results['Contents'] as $result) {
                    $s3Client->deleteObject([
                        'Bucket' => $s3_bucket_name,
                        'Key' => $result['Key']
                    ]);
                }
                $s3Client->deleteObject([
                    'Bucket' => $s3_bucket_name,
                    'Key' => 'viewer/panoramas/multires/'.$pano.'/'
                ]);
            }
        } else {
            $command = "rm -R ".$path.DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."multires".DIRECTORY_SEPARATOR.$pano;
            shell_exec($command);
        }
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
    $command = $path_python." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate.py --output ".$path_dest." --haov $haov.0 --vaov $vaov.0 --nona $path_nona --quality $quality_t --thumbnailsize 256 $pano_path 2>&1";
    if($debug) {
        $date = date('Y-m-d H:i');
        file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$command.PHP_EOL,FILE_APPEND);
    }
    $output = shell_exec($command);
    if($debug) {
        $date = date('Y-m-d H:i');
        file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$output.PHP_EOL,FILE_APPEND);
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
                $query = "UPDATE svt_rooms_alt SET multires_status=2,,multires_config='$multires_config' WHERE id=$id_room;";
            }
        } else {
            if($t=='room') {
                $query = "UPDATE svt_rooms SET multires_status=0,multires_config='' WHERE id=$id_room;";
            } else {
                $query = "UPDATE svt_rooms_alt SET multires_status=0,multires_config='' WHERE id=$id_room;";
            }
        }
    }
    if($debug) {
        $date = date('Y-m-d H:i');
        file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$query.PHP_EOL,FILE_APPEND);
    }
    $result = $mysqli->query($query);
    if(!$result) {
        if($debug) {
            $date = date('Y-m-d H:i');
            file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$mysqli->error.PHP_EOL,FILE_APPEND);
        }
    }
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