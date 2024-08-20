<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../functions.php");
require_once("../../db/connection.php");
if(get_user_role($_SESSION['id_user']) != 'administrator') {
    die();
}
session_write_close();
$filename = "users.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename);
$query = "SELECT u.username, u.email, u.role, p.name as plan, u.active, u.registration_date, u.expire_plan_date, u.expire_plan_date_manual, u.language, u.first_name, u.last_name, u.company, u.tax_id, u.street, u.city, u.postal_code, u.country, u.tel, u.province FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan;";
$flag = false;
$output = fopen('php://output', 'w');
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while ($row=$result->fetch_array(MYSQLI_ASSOC)) {
            unset($row['id']);
            $expire_plan_date_manual = $row['expire_plan_date_manual'];
            unset($row['expire_plan_date_manual']);
            if(!empty($expire_plan_date_manual)) $row['expire_plan_date']=$expire_plan_date_manual;
            if (!$flag) {
                fputcsv($output, array_keys($row),";",'"');
                $flag = true;
            }
            fputcsv($output, array_values($row),";",'"');
        }
    }
}
exit;