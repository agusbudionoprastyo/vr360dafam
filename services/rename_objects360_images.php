<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
require_once(__DIR__."/../db/connection.php");

$time = time();

$array_objects360 = array();
$result = $mysqli->query("SELECT * FROM svt_poi_objects360 ORDER BY id_poi,priority;");
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi = $row['id_poi'];
            $tmp = array();
            $tmp['id'] = $row['id'];
            $tmp['image'] = $row['image'];
            $ext = substr(strrchr($row['image'], '.'), 1);
            $tmp['image_tmp'] = "tmp_".$row['id']."_".$time.".".$ext;
            $tmp['image_new'] = "object360_".$id_poi."_".($row['priority']+1).".".$ext;
            $array_objects360[] = $tmp;
        }
    }
}

if($s3_enabled) {
    $path = "s3://$s3_bucket_name/viewer/objects360/";
} else {
    $path = realpath(dirname(__FILE__) . '/..').DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'objects360'.DIRECTORY_SEPARATOR;
}

foreach ($array_objects360 as $object360) {
    $image = $object360['image'];
    $image_tmp = $object360['image_tmp'];
    $image_new = $object360['image_new'];
    if($image!=$image_new) {
        rename($path.$image,$path.$image_tmp);
    }
}

foreach ($array_objects360 as $object360) {
    $id = $object360['id'];
    $image = $object360['image'];
    $image_tmp = $object360['image_tmp'];
    $image_new = $object360['image_new'];
    if($image!=$image_new) {
        rename($path.$image_tmp,$path.$image_new);
        $mysqli->query("UPDATE svt_poi_objects360 SET image='$image_new' WHERE id=$id;");
    }
}