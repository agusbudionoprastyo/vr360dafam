<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
session_write_close();
$id = (int)$_POST['id'];
$id_virtualtour = (int)$_POST['id_virtualtour'];
$direction = $_POST['direction'];
$priority = (int)$_POST['priority'];
$query = "SELECT priority_1,priority_2,id_room FROM svt_presentations WHERE id=$id;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $priority_1 = (int)$row['priority_1'];
        $priority_2 = (int)$row['priority_2'];
        $id_room = $row['id_room'];
    }
}
switch($priority) {
    case 1:
        switch($direction) {
            case 'down':
                $query = "SELECT id FROM svt_presentations WHERE priority_1=".($priority_1+1).";";
                $result = $mysqli->query($query);
                if($result) {
                    if ($result->num_rows > 0) {
                        $mysqli->query("UPDATE svt_presentations SET priority_1=priority_1+1 WHERE priority_1=$priority_1;");
                        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                            $idm = $row['id'];
                            $mysqli->query("UPDATE svt_presentations SET priority_1=priority_1-1 WHERE id=$idm;");
                        }
                    }
                }
                break;
            case 'up':
                $query = "SELECT id FROM svt_presentations WHERE priority_1=".($priority_1-1).";";
                $result = $mysqli->query($query);
                if($result) {
                    if ($result->num_rows > 0) {
                        $mysqli->query("UPDATE svt_presentations SET priority_1=priority_1-1 WHERE priority_1=$priority_1;");
                        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                            $idm = $row['id'];
                            $mysqli->query("UPDATE svt_presentations SET priority_1=priority_1+1 WHERE id=$idm;");
                        }
                    }
                }
                break;
        }
        break;
    case 2:
        switch($direction) {
            case 'down':
                $query = "SELECT id FROM svt_presentations WHERE priority_2=".($priority_2+1)." AND id_room=$id_room;";
                $result = $mysqli->query($query);
                if($result) {
                    if ($result->num_rows > 0) {
                        $mysqli->query("UPDATE svt_presentations SET priority_2=priority_2+1 WHERE priority_2=$priority_2 AND id_room=$id_room;");
                        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                            $idm = $row['id'];
                            $mysqli->query("UPDATE svt_presentations SET priority_2=priority_2-1 WHERE id=$idm;");
                        }
                    }
                }
                break;
            case 'up':
                $query = "SELECT id FROM svt_presentations WHERE priority_2=".($priority_2-1)." AND id_room=$id_room;";
                $result = $mysqli->query($query);
                if($result) {
                    if ($result->num_rows > 0) {
                        $mysqli->query("UPDATE svt_presentations SET priority_2=priority_2-1 WHERE priority_2=$priority_2 AND id_room=$id_room;");
                        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                            $idm = $row['id'];
                            $mysqli->query("UPDATE svt_presentations SET priority_2=priority_2+1 WHERE id=$idm;");
                        }
                    }
                }
                break;
        }
        break;
}
ob_end_clean();