<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
require_once("../../db/connection.php");
$id_virtualtour = (int)$_POST['id_virtualtour'];
$email = strtolower(trim(strip_tags($_POST['email'])));
$name = trim(strip_tags($_POST['name']));
$phone = trim(strip_tags($_POST['phone']));
$check = (int)$_POST['check'];
$query_check = "SELECT * FROM svt_leads WHERE id_virtualtour=? AND email=? LIMIT 1;";
if($smt = $mysqli->prepare($query_check)) {
    $smt->bind_param('is', $id_virtualtour, $email);
    $result_check = $smt->execute();
    if ($result_check) {
        $result_check = get_result($smt);
        if (count($result_check) == 1) {
            ob_end_clean();
            echo json_encode(array("status"=>"ok"));
            exit;
        } else {
            if($check==0) {
                $query = "INSERT INTO svt_leads(id_virtualtour,name,email,phone,datetime) VALUES(?,?,?,?,NOW());";
                if($smt = $mysqli->prepare($query)) {
                    $smt->bind_param('isss',  $id_virtualtour,$name,$email,$phone);
                    $result = $smt->execute();
                    if ($result) {
                        ob_end_clean();
                        echo json_encode(array("status"=>"ok"));
                        exit;
                    }
                }
            }
        }
    }
}
ob_end_clean();
echo json_encode(array("status"=>"error"));
exit;

function get_result(\mysqli_stmt $statement) {
    $result = array();
    $statement->store_result();
    for ($i = 0; $i < $statement->num_rows; $i++)
    {
        $metadata = $statement->result_metadata();
        $params = array();
        while ($field = $metadata->fetch_field())
        {
            $params[] = &$result[$i][$field->name];
        }
        call_user_func_array(array($statement, 'bind_result'), $params);
        $statement->fetch();
    }
    return $result;
}