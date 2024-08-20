<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$array = array();
$s3_enabled = false;
if(empty($id_virtualtour)) {
    $query = "SELECT id,file,id_virtualtour FROM svt_sound_library WHERE id_virtualtour IS NULL ORDER BY id DESC;";
} else {
    $query = "SELECT id,file,id_virtualtour FROM svt_sound_library WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
    $s3_params = check_s3_tour_enabled($id_virtualtour);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
}
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if(empty($row['id_virtualtour'])) $row['id_virtualtour']='';
            if($s3_enabled && !empty($row['id_virtualtour'])) {
                $path_file = "s3://$s3_bucket_name/viewer/content/".$row['file'];
            } else {
                $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$row['file'];
            }
            if(file_exists($path_file)) {
                $row['count']=0;
                $array[]=$row;
            }
        }
    }
}
$query = "SELECT 0 as id,sound as file FROM svt_pois WHERE sound LIKE 'content/%' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['file'] = str_replace('content/','',$row['file']);
            if($s3_enabled) {
                $path_file = "s3://$s3_bucket_name/viewer/content/".$row['file'];
            } else {
                $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$row['file'];
            }
            if(file_exists($path_file)) {
                $index = searchForFile($row['file'],$array);
                if($index!=false) {
                    $array[$index]['count']=$array[$index]['count']+1;
                } else {
                    $row['count']=1;
                    $array[]=$row;
                }
            }
        }
    }
}
$query = "SELECT 0 as id,sound as file FROM svt_markers WHERE sound LIKE 'content/%' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['file'] = str_replace('content/','',$row['file']);
            if($s3_enabled) {
                $path_file = "s3://$s3_bucket_name/viewer/content/".$row['file'];
            } else {
                $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$row['file'];
            }
            if(file_exists($path_file)) {
                $index = searchForFile($row['file'],$array);
                if($index!=false) {
                    $array[$index]['count']=$array[$index]['count']+1;
                } else {
                    $row['count']=1;
                    $array[]=$row;
                }
            }
        }
    }
}
ob_end_clean();
echo json_encode($array);

function searchForFile($file, $array) {
    foreach ($array as $key => $val) {
        if ($val['file'] === $file) {
            return $key;
        }
    }
    return false;
}