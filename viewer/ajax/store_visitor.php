<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$ip_visitor = $_POST['ip_visitor'];
$id_visitor = $_POST['id_visitor'];
$id_room = (int)$_POST['id_room'];
$yaw = $_POST['yaw'];
$pitch = $_POST['pitch'];
if(isset($_POST['enable_visitor_rt'])) {
    $enable_visitor_rt = $_POST['enable_visitor_rt'];
} else {
    $enable_visitor_rt = false;
}
if(isset($_POST['interval_visitor_rt'])) {
    $interval_visitor_rt = $_POST['interval_visitor_rt'];
} else {
    $interval_visitor_rt = 20000;
}
if($interval_visitor_rt<335) $interval_visitor_rt=335;
$interval_delete = (($interval_visitor_rt/1000)/60)*3;
if($interval_delete<1) {
    $interval_delete = ceil($interval_delete*60);
    $interval_sql = "SECOND";
} else {
    $interval_delete = ceil($interval_delete);
    $interval_sql = "MINUTE";
}
if(!$enable_visitor_rt) {
    $yaw = 'NULL';
    $pitch = 'NULL';
}
$here = 1;
$total = 1;
$random_color = random_color();
$array_visitors_rooms = array();
$mysqli->query("INSERT INTO svt_visitors(id_virtualtour,datetime,ip,id,id_room,yaw,pitch,color) VALUES($id_virtualtour,NOW(),'$ip_visitor','$id_visitor',$id_room,$yaw,$pitch,'$random_color') ON DUPLICATE KEY UPDATE datetime=NOW(),id_room=$id_room,yaw=$yaw,pitch=$pitch;");
$mysqli->query("DELETE FROM svt_visitors WHERE (ip='$ip_visitor' AND id='$id_visitor' AND id_room!=$id_room) OR (datetime<(NOW() - INTERVAL $interval_delete $interval_sql)) AND id_virtualtour=$id_virtualtour;");
$response = array();
if($enable_visitor_rt) {
    $interval_check = (($interval_visitor_rt/1000)/60)*2;
    if($interval_check<1) {
        $interval_check = ceil($interval_check*60);
        $interval_sql = "SECOND";
    } else {
        $interval_check = ceil($interval_check);
        $interval_sql = "MINUTE";
    }
    $query = "SELECT ip,id,yaw,pitch,color FROM svt_visitors WHERE yaw IS NOT NULL AND pitch IS NOT NULL AND id!='$id_visitor' AND datetime>=(NOW() - INTERVAL $interval_check $interval_sql) AND id_virtualtour=$id_virtualtour AND id_room=$id_room ORDER BY ip,id;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $row['id']=str_replace(".","",$row['ip']).$row['id'];
                $response[]=$row;
            }
        }
    }
    $here = count($response)+1;
    $query = "SELECT id_room,COUNT(*) as num FROM svt_visitors WHERE yaw IS NOT NULL AND pitch IS NOT NULL AND datetime>=(NOW() - INTERVAL $interval_check $interval_sql) AND id_virtualtour=$id_virtualtour GROUP BY(id_room);";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            $total=0;
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_room=$row['id_room'];
                $num=$row['num'];
                $total=$total+$num;
                $array_visitors_rooms[$id_room]=$num;
            }
        }
    }
}
$mysqli->close();
ob_end_clean();
echo json_encode(array("visitors"=>$response,"visitors_count"=>$array_visitors_rooms,"here"=>$here,"total"=>$total));
exit;

function random_color() {
    $dt = '#';
    for($o=1;$o<=3;$o++) {
        $dt .= str_pad( dechex( mt_rand( 0, 160 ) ), 2, '0', STR_PAD_LEFT);
    }
    return $dt;
}

