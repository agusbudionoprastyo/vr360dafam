<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_room = (int)$_POST['id_room'];
$id_map = (int)$_POST['id_map'];
$map_type = $_POST['map_type'];
$lat = strip_tags($_POST['lat']);
$lon = strip_tags($_POST['lon']);
$top = (int)$_POST['top'];
$left = (int)$_POST['left'];
$all_room_exif = (int)$_POST['all_room_exif'];
$id_virtualtour = (int)$_POST['id_virtualtour'];
switch ($map_type) {
    case 'floorplan':
        $query = "UPDATE svt_rooms SET id_map=?,map_top=?,map_left=? WHERE id=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('iiii',  $id_map,$top,$left,$id_room);
            $result = $smt->execute();
            if ($result) {
                ob_end_clean();
                echo json_encode(array("status"=>"ok","coordinates"=>"0 - 0","id_room_point_sel"=>$id_room));
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error"));
        }
        break;
    case 'map':
        $path = realpath(dirname(__FILE__) . '/../..') . DIRECTORY_SEPARATOR . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "original" . DIRECTORY_SEPARATOR;
        if($all_room_exif==1) {
            if(function_exists('exif_read_data')) {
                $query = "SELECT id,panorama_image FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND lat IS NULL AND lon IS NULL;";
                $result = $mysqli->query($query);
                if ($result) {
                    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                        $id_room = $row['id'];
                        $panorama_image = $row['panorama_image'];
                        $lat = 0;
                        $lon = 0;
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
                            if($lat!=0 && $lon!=0) {
                                $query = "UPDATE svt_rooms SET lat=?,lon=? WHERE id=?;";
                                if($smt = $mysqli->prepare($query)) {
                                    $smt->bind_param('ssi', $lat, $lon, $id_room);
                                    $smt->execute();
                                }
                            }
                        }
                    }
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok","coordinates"=>"0 - 0","id_room_point_sel"=>0));
                }
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
            }
        } else {
            if(function_exists('exif_read_data')) {
                $query = "SELECT panorama_image FROM svt_rooms WHERE id=$id_room LIMIT 1;";
                $result = $mysqli->query($query);
                if ($result) {
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    $panorama_image = $row['panorama_image'];
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
            $query = "UPDATE svt_rooms SET lat=?,lon=? WHERE id=?;";
            if($smt = $mysqli->prepare($query)) {
                $smt->bind_param('ssi',  $lat,$lon,$id_room);
                $result = $smt->execute();
                if ($result) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok","coordinates"=>"$lat - $lon","id_room_point_sel"=>$id_room));
                } else {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error"));
                }
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error"));
            }
        }
        break;
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