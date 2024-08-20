<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_vt = (int)$_POST['id_vt'];
$id_globe = (int)$_POST['id_globe'];
$lat = strip_tags($_POST['lat']);
$lon = strip_tags($_POST['lon']);
if(function_exists('exif_read_data')) {
    $query = "SELECT panorama_image FROM svt_rooms WHERE id_virtualtour=$id_vt LIMIT 1;";
    $result = $mysqli->query($query);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $panorama_image = $row['panorama_image'];
        $path = realpath(dirname(__FILE__) . '/../..') . DIRECTORY_SEPARATOR . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "original" . DIRECTORY_SEPARATOR;
        if (file_exists($path . $panorama_image)) {
            $exif = exif_read_data($path . $panorama_image);
            if ((isset($exif["GPSLatitude"])) && (isset($exif["GPSLongitude"]))) {
                $latitude = gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
                $longitude = gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
                if ($latitude != 0 && !empty($latitude) && !is_nan($latitude)) {
                    $lat = $latitude;
                }
                if ($longitude != 0 && !empty($longitude) && !is_nan($longitude)) {
                    $lon = $longitude;
                }
            }
        }
    }
}
$query = "INSERT INTO svt_globe_list(id_globe,id_virtualtour,lat,lon) VALUES(?,?,?,?);";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('iiss',  $id_globe,$id_vt,$lat,$lon);
    $result = $smt->execute();
    if ($result) {
        ob_end_clean();
        echo json_encode(array("status"=>"ok","coordinates"=>"$lat - $lon","id_vt_point_sel"=>$id_vt));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}

function gps($coordinate, $hemisphere) {
    if (is_string($coordinate)) {
        $coordinate = array_map("trim", explode(",", $coordinate));
    }
    for ($i = 0; $i < 3; $i++) {
        $part = explode('/', $coordinate[$i]);
        if (count($part) == 1) {
            $coordinate[$i] = $part[0];
        } else if (count($part) == 2) {
            if($part[1]!=0) {
                $coordinate[$i] = floatval($part[0])/floatval($part[1]);
            } else {
                $coordinate[$i] = 0;
            }
        } else {
            $coordinate[$i] = 0;
        }
    }
    list($degrees, $minutes, $seconds) = $coordinate;
    $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
    return $sign * ($degrees + $minutes/60 + $seconds/3600);
}