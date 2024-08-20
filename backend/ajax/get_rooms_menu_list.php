<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
$language = $_POST['language'];
if(get_user_role($id_user)=='administrator') {
    $where_user = "";
} else {
    $where_user = " AND v.id_user = $id_user ";
}
$array = array();
if(empty($language)) {
    $query = "SELECT r.id,r.name FROM svt_rooms as r 
                JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                WHERE v.id = $id_virtualtour $where_user
                GROUP BY r.id, r.priority
                ORDER BY r.priority ASC, r.id ASC";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $array[$row['id']]=$row['name'];
            }
        }
    }
} else {
    $query = "SELECT r.id,COALESCE(rl.name,r.name) as name FROM svt_rooms as r 
                JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                LEFT JOIN svt_rooms_lang as rl ON rl.id_room=r.id AND rl.language='$language'
                WHERE v.id = $id_virtualtour $where_user
                GROUP BY r.id, r.priority
                ORDER BY r.priority ASC, r.id ASC";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $array[$row['id']]=$row['name'];
            }
        }
    }
}

$array2 = array();
$array_id_rooms = array();
$query = "SELECT list_alt FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $list_alt = $row['list_alt'];
        $list_alt_lang = '';
        if(!empty($language)) {
            $query_l = "SELECT list_alt FROM svt_virtualtours_lang WHERE id_virtualtour=$id_virtualtour AND language='$language' LIMIT 1;";
            $result_l = $mysqli->query($query_l);
            if($result_l) {
                if ($result_l->num_rows == 1) {
                    $row_l = $result_l->fetch_array(MYSQLI_ASSOC);
                    $list_alt_lang = $row_l['list_alt'];
                }
            }
        }
        if(!empty($list_alt_lang)) $list_alt=$list_alt_lang;
        if ($list_alt == '') {
            foreach ($array as $id=>$name) {
                array_push($array2,["id"=>$id,"type"=>"room","hide"=>"0","name"=>$name]);
            }
        } else {
            $list_alt_array = json_decode($list_alt, true);
            foreach ($list_alt_array as $item) {
                switch ($item['type']) {
                    case 'room':
                        if(array_key_exists($item['id'],$array)) {
                            array_push($array2, ["id" => $item['id'], "type" => "room", "hide"=>$item['hide'], "name" => $array[$item['id']]]);
                        }
                        array_push($array_id_rooms,$item['id']);
                        break;
                    case 'category':
                        $childrens = array();
                        foreach ($item['children'] as $children) {
                            if ($children['type'] == "room") {
                                if(array_key_exists($children['id'],$array)) {
                                    array_push($childrens, ["id" => $children['id'], "type" => "room", "hide" => $children['hide'], "name" => $array[$children['id']]]);
                                }
                                array_push($array_id_rooms, $children['id']);
                            }
                        }
                        array_push($array2, ["id" => $item['id'], "type" => "category", "name" => $item['cat'], "childrens" => $childrens]);
                        break;
                }
            }
            foreach ($array as $id=>$name) {
                if(!in_array($id,$array_id_rooms)) {
                    array_push($array2,["id"=>$id,"type"=>"room","hide"=>"0","name"=>$name]);
                }
            }
        }
    }
}
ob_end_clean();
echo json_encode($array2);