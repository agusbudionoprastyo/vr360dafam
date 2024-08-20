<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_room = (int)$_POST['id_room'];

$date_now = date('Ymd');
$hour_now = date('Hi');
$day_now = date('N');

$array_pois = array();

$query = "SELECT id,schedule FROM svt_pois WHERE id_room=$id_room AND schedule<>'';";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi = $row['id'];
            $array_pois[$id_poi]=1;
            $schedule = json_decode($row['schedule'],true);
            if(!empty($schedule['from_date']) && !empty($schedule['to_date'])) {
                $date_from = date('Ymd',strtotime($schedule['from_date']));
                $date_to = date('Ymd',strtotime($schedule['to_date']));
                if(($date_now<$date_from) || ($date_now>$date_to)) {
                    $array_pois[$id_poi]=0;
                }
            }
            if(!empty($schedule['days'])) {
                $days = explode(",",$schedule['days']);
                $alldays_zero = 1;
                for($i=0;$i<count($days);$i++) {
                    if($days[$i]==1) {
                        $alldays_zero = 0;
                    }
                }
                if($days[$day_now-1]==0 && $alldays_zero==0) {
                    $array_pois[$id_poi]=0;
                }
            }
            if(!empty($schedule['from_hour']) && !empty($schedule['to_hour'])) {
                $hour_from = date('Hi',strtotime($schedule['from_hour']));
                $hour_to = date('Hi',strtotime($schedule['to_hour']));
                if(($hour_now<$hour_from) || ($hour_now>=$hour_to)) {
                    $array_pois[$id_poi]=0;
                }
            }
        }
    }
}

ob_end_clean();
echo json_encode($array_pois);