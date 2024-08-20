<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$language = $_POST['language'];
$array_presentation_lang = array();
$query = "SELECT * FROM svt_presentations_lang WHERE language='$language' AND id_presentation IN (SELECT id FROM svt_presentations WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_presentation = $row['id_presentation'];
            unset($row['id_presentation']);
            unset($row['language']);
            $array_presentation_lang[$id_presentation]=$row;
        }
    }
}
$query = "SELECT p.id,p.id_room,p.action,p.sleep,p.video_wait_end,p.params,p.pos FROM svt_presentations as p 
          JOIN svt_rooms as r on p.id_room = r.id
          WHERE p.id_virtualtour=$id_virtualtour AND r.visible=1 ORDER BY p.priority_1,p.priority_2;";
$result = $mysqli->query($query);
$presentation = array();
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_presentation = $row['id'];
            $row['id_room'] = (int) $row['id_room'];
            $row['sleep'] = (int) $row['sleep'];
            switch ($row['action']) {
                case 'type':
                    if(array_key_exists($id_presentation,$array_presentation_lang)) {
                        if (!empty($row['params']) && !empty($array_presentation_lang[$id_presentation]['params'])) {
                            $row['params'] = $array_presentation_lang[$id_presentation]['params'];
                        }
                    }
                    $row['params'] = preg_split("/\\r\\n|\\r|\\n/", $row['params']);
                    if(end($row['params'])!='') {
                        array_push($row['params'],'');
                    }
                    break;
                case 'goto':
                    $row['params'] = (int) $row['params'];
                    break;
                case 'lookAt':
                    $row['params'] = explode(",",$row['params']);
                    $row['params'] = array_map('intval', $row['params']);
                    break;
            }
            if(empty($row['pos'])) $row['pos']="";
            $presentation[] = $row;
        }
        ob_end_clean();
        echo json_encode(array("status"=>"ok","presentation"=>$presentation));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
}