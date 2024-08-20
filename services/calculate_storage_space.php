<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
session_write_close();
$query = "SELECT id FROM svt_users WHERE role!='editor'";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            update_user_space_storage($id,true);
        }
    }
}
ob_end_clean();