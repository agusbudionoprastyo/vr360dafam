<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id_virtualtour = (int)$_POST['id_virtualtour'];
if(isset($_POST['type'])) {
    $type = $_POST['type'];
} else {
    $type = 'all';
}
$array = array();
$s3_enabled = false;
if(empty($id_virtualtour)) {
    $query = "SELECT id,file,id_virtualtour FROM svt_media_library WHERE id_virtualtour IS NULL ORDER BY id DESC;";
} else {
    $query = "SELECT id,file,id_virtualtour FROM svt_media_library WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
    $s3_params = check_s3_tour_enabled($id_virtualtour);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
}
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $row['from']='media_library';
            if(empty($row['id_virtualtour'])) $row['id_virtualtour']='';
            if($s3_enabled && !empty($row['id_virtualtour'])) {
                $path_file = "s3://$s3_bucket_name/viewer/media/".$row['file'];
            } else {
                $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.$row['file'];
            }
            if(file_exists($path_file)) {
                $row['count']=0;
                $ext = explode('.',$row['file']);
                $ext = strtolower(end($ext));
                switch ($type) {
                    case 'images':
                        if ($ext!='mp4' && $ext!='mov' && $ext!='webm') {
                            $array[]=$row;
                        }
                        break;
                    case 'videos':
                        if ($ext=='mp4' || $ext=='webm') {
                            $array[]=$row;
                        }
                        break;
                    case 'videos_transparent':
                        if ($ext=='mov' || $ext=='webm') {
                            $array[]=$row;
                        }
                        break;
                    case 'all':
                        $array[]=$row;
                        break;
                }
            }
        }
    }
}
$query = "SELECT 0 as id,content as file FROM svt_pois WHERE type IN ('image','video','video360') AND (content LIKE 'content/%' OR content LIKE 'media/%') AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour)
UNION
SELECT 0 as id,pl.content as file FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.type IN ('image','video','video360') AND (pl.content LIKE 'content/%' OR pl.content LIKE 'media/%') AND p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['from']='content';
            $tmp = explode("/",$row['file']);
            $row['file'] = str_replace(['content/','media/'],'',$row['file']);
            if($s3_enabled && !empty($row['id_virtualtour'])) {
                $path_file = "s3://$s3_bucket_name/viewer/".$tmp[0]."/".$row['file'];
            } else {
                $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.$tmp[0].DIRECTORY_SEPARATOR.$row['file'];
            }
            if(file_exists($path_file)) {
                $ext = explode('.',$row['file']);
                $ext = strtolower(end($ext));
                switch ($type) {
                    case 'images':
                        if ($ext!='mp4' && $ext!='mov' && $ext!='webm') {
                            $index = searchForFile($row['file'],$array);
                            if($index!==false) {
                                $array[$index]['count']=$array[$index]['count']+1;
                            } else {
                                $row['count']=1;
                                $array[]=$row;
                            }
                        }
                        break;
                    case 'videos':
                        if ($ext=='mp4' || $ext=='webm') {
                            $index = searchForFile($row['file'],$array);
                            if($index!==false) {
                                $array[$index]['count']=$array[$index]['count']+1;
                            } else {
                                $row['count']=1;
                                $array[]=$row;
                            }
                        }
                        break;
                    case 'videos_transparent':
                        if ($ext=='mov' || $ext=='webm') {
                            $index = searchForFile($row['file'],$array);
                            if($index!==false) {
                                $array[$index]['count']=$array[$index]['count']+1;
                            } else {
                                $row['count']=1;
                                $array[]=$row;
                            }
                        }
                        break;
                    case 'all':
                        $index = searchForFile($row['file'],$array);
                        if($index!==false) {
                            $array[$index]['count']=$array[$index]['count']+1;
                        } else {
                            $row['count']=1;
                            $array[]=$row;
                        }
                        break;
                }
            }
        }
    }
}
$query = "SELECT 0 as id,embed_content as file FROM svt_pois WHERE embed_type IN ('image','video','video_transparent','video_chroma') AND (embed_content LIKE 'content/%' OR embed_content LIKE 'media/%') AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour)
UNION
SELECT 0 as id,pl.embed_content as file FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.embed_type IN ('image','video','video_transparent','video_chroma') AND (pl.embed_content LIKE 'content/%' OR pl.embed_content LIKE 'media/%') AND p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $row['from']='content';
            if (strpos($row['file'], ',') !== false) {
                $files = explode(",",$row['file']);
            } else {
                $files = [$row['file']];
            }
            foreach ($files as $file) {
                $row['file']=$file;
                $tmp = explode("/",$row['file']);
                $row['file'] = str_replace(['content/','media/'],'',$row['file']);
                if($s3_enabled && !empty($row['id_virtualtour'])) {
                    $path_file = "s3://$s3_bucket_name/viewer/".$tmp[0]."/".$row['file'];
                } else {
                    $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.$tmp[0].DIRECTORY_SEPARATOR.$row['file'];
                }
                if(file_exists($path_file)) {
                    $ext = explode('.',$row['file']);
                    $ext = strtolower(end($ext));
                    switch ($type) {
                        case 'images':
                            if ($ext!='mp4' && $ext!='mov' && $ext!='webm') {
                                $index = searchForFile($row['file'],$array);
                                if($index!==false) {
                                    $array[$index]['count']=$array[$index]['count']+1;
                                } else {
                                    $row['count']=1;
                                    $array[]=$row;
                                }
                            }
                            break;
                        case 'videos':
                            if ($ext=='mp4' || $ext=='webm') {
                                $index = searchForFile($row['file'],$array);
                                if($index!==false) {
                                    $array[$index]['count']=$array[$index]['count']+1;
                                } else {
                                    $row['count']=1;
                                    $array[]=$row;
                                }
                            }
                            break;
                        case 'videos_transparent':
                            if ($ext=='mov' || $ext=='webm') {
                                $index = searchForFile($row['file'],$array);
                                if($index!==false) {
                                    $array[$index]['count']=$array[$index]['count']+1;
                                } else {
                                    $row['count']=1;
                                    $array[]=$row;
                                }
                            }
                            break;
                        case 'all':
                            $index = searchForFile($row['file'],$array);
                            if($index!==false) {
                                $array[$index]['count']=$array[$index]['count']+1;
                            } else {
                                $row['count']=1;
                                $array[]=$row;
                            }
                            break;
                    }
                }
            }
        }
    }
}
ob_end_clean();
echo json_encode($array);

function searchForFile($file, $array) {
    foreach ($array as $key => $val) {
        if ($val['file'] === $file) {
            return $key;
        }
    }
    return false;
}