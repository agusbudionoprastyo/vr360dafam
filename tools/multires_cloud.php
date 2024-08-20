<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
ob_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
set_time_limit(9999);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$debug = false;
$path = realpath(dirname(__FILE__));

if(!isEnabled('shell_exec')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"php <b>shell_exec</b> "._("function disabled")));
    exit;
}

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("php zip not enabled")));
    exit;
}

$command = 'dpkg-query -W -f=\'${Status}\' python3 2>&1';
$output = shell_exec($command);
if (strpos($output, 'command not found') !== false) {
    $command = 'rpm -q python3 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        $command = 'command -v python3';
        $output = shell_exec($command);
        if (strpos(strtolower($output), 'python3') === false) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3</b>.<br>"._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
            exit;
        }
    }
} else {
    if (strpos($output, 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3</b>.<br>"._("Execute the command")." \"apt-get install python3\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' python3-pip 2>&1';
$output = shell_exec($command);
if (strpos($output, 'command not found') !== false) {
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
    if (strpos($output, 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-pip</b>.<br>"._("Execute the command")." \"apt-get install python3-pip\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' python3-pil 2>&1';
$output = shell_exec($command);
if (strpos($output, 'command not found') !== false) {
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
    if (strpos($output, 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-pil</b>.<br>"._("Execute the command")." \"apt-get install python3-pil\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' python3-numpy 2>&1';
$output = shell_exec($command);
if (strpos($output, 'command not found') !== false) {
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
    if (strpos($output, 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>python3-numpy</b>.<br>"._("Execute the command")." \"apt-get install python3-numpy\" "._("on your server")."."));
        exit;
    }
}
$command = 'dpkg-query -W -f=\'${Status}\' hugin-tools 2>&1';
$output = shell_exec($command);
if (strpos($output, 'command not found') !== false) {
    $command = 'rpm -q hugin-tools 2>&1';
    $output = shell_exec($command);
    if ((strpos(strtolower($output), 'not installed') !== false) || (strpos(strtolower($output), 'not found') !== false)) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>hugin-tools</b>.<br>"._("Execute the command")." \"apt-get install hugin-tools\" "._("on your server")."."));
        exit;
    }
} else {
    if (strpos($output, 'installed') === false) {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Missing package")." <b>hugin-tools</b>.<br>"._("Execute the command")." \"apt-get install hugin-tools\" "._("on your server")."."));
        exit;
    }
}

$command = 'pip3 list | grep -F pyshtools 2>&1';
$output = shell_exec($command);
if (strpos($output, 'pyshtools') === false) {
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
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing command")." python3."));
    exit;
} else {
    $path_python = trim($output);
}

if(isset($_GET['check'])) {
    ob_end_clean();
    echo "ok";
    exit;
}

if (!file_exists(dirname(__FILE__).'/panorama_tmp/')) {
    mkdir(dirname(__FILE__).'/panorama_tmp/', 0775);
}
if (!file_exists(dirname(__FILE__).'/multires_tmp/')) {
    mkdir(dirname(__FILE__).'/multires_tmp/', 0775);
}

if(isset($_POST['complete_pano'])) {
    if(file_exists(dirname(__FILE__).'/panorama_tmp/'.$_POST['complete_pano'].".jpg")) {
        unlink(dirname(__FILE__).'/panorama_tmp/'.$_POST['complete_pano'].".jpg");
    }
    if(file_exists(dirname(__FILE__).'/multires_tmp/'.$_POST['complete_pano'].".zip")) {
        unlink(dirname(__FILE__).'/multires_tmp/'.$_POST['complete_pano'].".zip");
    }
    if(file_exists(dirname(__FILE__).'/multires_tmp/'.$_POST['complete_pano'])) {
        $command = "rm -R ".$path.DIRECTORY_SEPARATOR."multires_tmp".DIRECTORY_SEPARATOR.$_POST['complete_pano'];
        shell_exec($command);
    }
    exit;
}

if($debug) {
    $ip = get_client_ip();
    $date = date('Y-m-d H:i');
    register_shutdown_function( "fatal_handler" );
}

$pano = $_POST['pano'];
$haov = $_POST['haov'];
$vaov = $_POST['vaov'];
$quality_t = $_POST['quality'];
if(empty($pano) || empty($haov) || empty($vaov) || empty($quality_t)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing parameters")));
    exit;
}

if($debug) {
    file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$ip." ".'POST: '.serialize($_POST).PHP_EOL,FILE_APPEND);
    file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$ip." ".'FILE: '.serialize($_FILES).PHP_EOL,FILE_APPEND);
}

if(isset($_FILES) && !empty($_FILES['file']['name'])) {
    if(file_exists(dirname(__FILE__).'/panorama_tmp/'.$_FILES['file']['name'])) {
        unlink(dirname(__FILE__).'/panorama_tmp/'.$_FILES['file']['name']);
    }
    if(file_exists(dirname(__FILE__).'/multires_tmp/'.$pano)) {
        $command = "rm -R ".$path.DIRECTORY_SEPARATOR."multires_tmp".DIRECTORY_SEPARATOR.$pano;
        shell_exec($command);
    }
    $moved = move_uploaded_file($_FILES['file']['tmp_name'],dirname(__FILE__).'/panorama_tmp/'.$_FILES['file']['name']);
    if($moved) {
        $command = $path_python." ".$path.DIRECTORY_SEPARATOR."generate.py --output ".$path.DIRECTORY_SEPARATOR."multires_tmp".DIRECTORY_SEPARATOR."$pano --haov $haov.0 --vaov $vaov.0 --nona $path_nona --quality $quality_t --thumbnailsize 256 ".$path.DIRECTORY_SEPARATOR."panorama_tmp".DIRECTORY_SEPARATOR.$_FILES['file']['name']." 2>&1";
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$ip." "."COMMAND: ".$command.PHP_EOL,FILE_APPEND);
        }
        $output = shell_exec($command);
        if($debug) {
            file_put_contents(realpath(dirname(__FILE__))."/log_multires.txt",$date." - ".$ip." "."OUTPUT: ".$output.PHP_EOL,FILE_APPEND);
        }
        if(file_exists($path.DIRECTORY_SEPARATOR."multires_tmp".DIRECTORY_SEPARATOR.$pano.DIRECTORY_SEPARATOR.'config.json')) {
            zip_folder($pano);
            if(file_exists($path.DIRECTORY_SEPARATOR."multires_tmp".DIRECTORY_SEPARATOR.$pano.".zip")) {
                $command = "rm -R ".$path.DIRECTORY_SEPARATOR."multires_tmp".DIRECTORY_SEPARATOR.$pano;
                shell_exec($command);
                unlink(dirname(__FILE__).'/panorama_tmp/'.$_FILES['file']['name']);
                ob_end_clean();
                echo json_encode(array("status"=>"ok"));
                exit;
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>_("Zip error")));
                exit;
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$output));
            exit;
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>'ERROR: code:'.$_FILES["file"]["error"]));
        exit;
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("Missing file")));
    exit;
}


function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

function zip_folder($dir_name) {
    $rootPath = realpath(dirname(__FILE__)."/multires_tmp/$dir_name/");
    $zip = new ZipArchive();
    $zip->open(dirname(__FILE__)."/multires_tmp/$dir_name.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
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