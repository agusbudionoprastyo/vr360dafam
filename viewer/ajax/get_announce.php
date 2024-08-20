<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$announce=null;
$query = "SELECT ad.*,u.id_plan FROM svt_advertisements AS ad
JOIN svt_assign_advertisements AS aa ON ad.id=aa.id_advertisement
JOIN svt_virtualtours AS v ON v.id=aa.id_virtualtour
JOIN svt_users AS u ON u.id=v.id_user 
WHERE aa.id_virtualtour=$id_virtualtour LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $id_plans=$row['id_plans'];
        $id_plan=$row['id_plan'];
        $array_id_plans = explode(",",$id_plans);
        if(in_array($id_plan,$array_id_plans)) {
            switch($row['type']) {
                case 'image':
                    if(!empty($row['image'])) {
                        $announce=$row;
                    }
                    break;
                case 'video':
                    if(!empty($row['video']) || !empty($row['youtube'])) {
                        $announce=$row;
                    }
                    break;
                case 'iframe':
                    if(!empty($row['iframe_link'])) {
                        $announce=$row;
                    }
                    break;
            }
        }
    }
}
ob_end_clean();
echo json_encode(array("status"=>"ok","announce"=>$announce));