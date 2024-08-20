<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$array = array();
$total_rooms=0;
$total_rooms_p=0;
$query = "SELECT COUNT(id) as num FROM svt_rooms WHERE id_virtualtour=$id_virtualtour;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $total_rooms = $row['num'];
    }
}
$query = "SELECT COUNT(DISTINCT id_room) as num FROM svt_presentations WHERE id_virtualtour=$id_virtualtour;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $total_rooms_p = $row['num'];
    }
}
if($total_rooms_p==$total_rooms) {
    $add=0;
} else {
    $add=1;
}
$array_lang = array();
$query = "SELECT * FROM svt_presentations_lang WHERE id_presentation IN(SELECT id FROM svt_presentations WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_presentation=$row['id_presentation'];
            if(!array_key_exists($id_presentation,$array_lang)) $array_lang[$id_presentation]=array();
            array_push($array_lang[$id_presentation],$row);
        }
    }
}
$query = "SELECT p.*,r.panorama_image,r.type as panorama_type,r.name as room_name FROM svt_presentations as p 
LEFT JOIN svt_rooms as r ON r.id=p.id_room
WHERE p.id_virtualtour=$id_virtualtour ORDER BY p.priority_1,p.priority_2;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_presentation=$row['id'];
            $row['array_lang'] = array();
            switch ($row['action']) {
                case 'type':
                    $row['text'] = $row['params'];
                    $row['params'] = preg_split("/\\r\\n|\\r|\\n/", $row['params']);
                    $row['params'] = implode(" | ",$row['params']);
                    if(array_key_exists($id_presentation,$array_lang)) {
                        foreach ($array_lang[$id_presentation] as $array_l) {
                            $row['array_lang'][]=$array_l;
                        }
                    }
                    break;
                case 'lookAt':
                    $row['params'] = explode(",",$row['params']);
                    $row['params'] = array_map('intval', $row['params']);
                    $row['yaw'] = $row['params'][1];
                    $row['pitch'] = $row['params'][0];
                    $row['hfov'] = $row['params'][2];
                    $row['animation'] = $row['params'][3];
                    $row['params'] = $row['params'][1].",".$row['params'][0]." (".$row['params'][2].") <i class='far fa-clock'></i> ".$row['params'][3]."ms";
                    break;
            }
            if(empty($row['pos'])) $row['pos']="";
            $array[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode(array("presentation"=>$array,"add"=>$add,"total_rooms"=>$total_rooms_p));