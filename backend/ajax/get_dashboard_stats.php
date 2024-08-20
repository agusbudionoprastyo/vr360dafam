<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = (int)$_POST['id_user'];
$id_virtualtour = $_POST['id_virtualtour'];
$unique = false;
if(isset($_SESSION['statistics_type'])) {
    if($_SESSION['statistics_type']=="unique") {
        $unique = true;
    }
}
session_write_close();
if(empty($id_virtualtour) || $id_virtualtour=='all') {
    switch(get_user_role($id_user)) {
        case 'administrator':
            $where = " WHERE 1=1 ";
            break;
        case 'customer':
            $where = " WHERE 1=1 AND v.id_user=$id_user ";
            break;
        case 'editor':
            $where = " WHERE 1=1 AND v.id IN () ";
            $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $ids = $row['ids'];
                    $where = " WHERE 1=1 AND v.id IN ($ids) ";
                }
            }
            break;
    }
} else {
    $where = " WHERE 1=1 AND v.id = $id_virtualtour ";
}
$stats = array();
$stats['count_virtual_tours'] = 0;
$stats['count_rooms'] = 0;
$stats['count_markers'] = 0;
$stats['count_pois'] = 0;
$stats['count_measures'] = 0;
$stats['count_video_projects']=0;
$stats['count_slideshows'] = 0;
$stats['count_video360'] = 0;
$stats['count_vt_rooms'] = 0;
$stats['count_vt_markers'] = 0;
$stats['count_vt_pois'] = 0;
$stats['count_vt_measures'] = 0;
$stats['count_vt_video_projects']=0;
$stats['count_vt_slideshows'] = 0;
$stats['count_vt_video360']=0;
$stats['total_visitors'] = 0;
$stats['total_online_visitors'] = 0;
$stats['visitors'] = array();
$stats['online_visitors'] = array();
$query = "SELECT COUNT(v.id) as num FROM svt_virtualtours as v $where LIMIT 1";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $num = $row['num'];
        $stats['count_virtual_tours'] = $num;
    }
}
$query = "SELECT COUNT(r.id) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_rooms as r
JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
$where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $num = $row['num'];
        $num_vt = $row['num_vt'];
        $stats['count_rooms'] = $num;
        $stats['count_vt_rooms'] = $num_vt;
    }
}
$query = "SELECT COUNT(m.id) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_markers as m
JOIN svt_rooms as r ON m.id_room = r.id
JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
$where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $num = $row['num'];
        $num_vt = $row['num_vt'];
        $stats['count_markers'] = $num;
        $stats['count_vt_markers'] = $num_vt;
    }
}
$query = "SELECT COUNT(m.id) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_pois as m
JOIN svt_rooms as r ON m.id_room = r.id
JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
$where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $num = $row['num'];
        $num_vt = $row['num_vt'];
        $stats['count_pois'] = $num;
        $stats['count_vt_pois'] = $num_vt;
    }
}
$query = "SELECT COUNT(m.id) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_measures as m
JOIN svt_rooms as r ON m.id_room = r.id
JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
$where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $num = $row['num'];
        $num_vt = $row['num_vt'];
        $stats['count_measures'] = $num;
        $stats['count_vt_measures'] = $num_vt;
    }
}
$total_visitors = 0;
if($id_virtualtour=='all') {
    if($unique==true) {
        $total_unique = 0;
        $query = "SELECT COUNT(DISTINCT l.ip) as count FROM svt_access_log as l 
                    LEFT JOIN svt_virtualtours as v ON v.id=l.id_virtualtour $where;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $stats['total_visitors'] = $row['count'];
            }
        }
    } else {
        $query = "SELECT v.id,v.name,COUNT(a.id) as count FROM svt_virtualtours as v
            LEFT JOIN svt_access_log as a ON v.id = a.id_virtualtour
            $where
            GROUP BY v.id
            ORDER BY count DESC;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows>0) {
                while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                    $count = $row['count'];
                    $total_visitors = $total_visitors + $count;
                    $stats['visitors'][] = $row;
                }
                $stats['total_visitors'] = $total_visitors;
            }
        }
    }
} else {
    if($unique==true && !empty($id_virtualtour)) {
        $total_unique = 0;
        $query = "SELECT COUNT(DISTINCT ip) as count FROM svt_access_log WHERE id_virtualtour=$id_virtualtour;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $stats['total_visitors'] = $row['count'];
            }
        }
    } else {
        $query = "SELECT v.id,v.name,COUNT(a.id) as count FROM svt_virtualtours as v
            LEFT JOIN svt_access_log as a ON v.id = a.id_virtualtour
            $where
            GROUP BY v.id
            ORDER BY count DESC;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows>0) {
                while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                    $count = $row['count'];
                    $total_visitors = $total_visitors + $count;
                    $stats['visitors'][] = $row;
                }
                $stats['total_visitors'] = $total_visitors;
            }
        }
    }
}
$total_online_visitors = 0;
$query = "SELECT v.id,COUNT(DISTINCT s.ip) as count FROM svt_virtualtours AS v
LEFT JOIN svt_visitors AS s ON s.id_virtualtour=v.id
$where
AND datetime>=(NOW() - INTERVAL 30 SECOND)
GROUP BY v.id;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $count = $row['count'];
            $total_online_visitors = $total_online_visitors + $count;
            $stats['online_visitors'][] = $row;
        }
        $stats['total_online_visitors'] = $total_online_visitors;
    }
}
$query = "SELECT COUNT(m.id) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_video_projects as m
JOIN svt_virtualtours as v ON v.id = m.id_virtualtour
$where LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $num = $row['num'];
        $num_vt = $row['num_vt'];
        $stats['count_video_projects'] = $num;
        $stats['count_vt_video_projects'] = $num_vt;
    }
}
$array_vt = array();
$query = "SELECT v.id FROM svt_virtualtours as v $where";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_vt = $row['id'];
            array_push($array_vt,$id_vt);
        }
    }
}
$dir = '../../viewer/gallery/';
$dirIterator = new DirectoryIterator($dir);
foreach ($dirIterator as $file) {
    if ($file->getExtension() === 'mp4' && strpos($file->getFilename(), 'slideshow') !== false && (preg_match('/^(' . implode('|', $array_vt) . ')\D/', $file->getFilename()))) {
        $stats['count_slideshows']++;
        $stats['count_vt_slideshows']++;
    }
}
$dir = '../../video360/';
$dirIterator = new DirectoryIterator($dir);
foreach ($dirIterator as $file) {
    if ($file->isDir() && !$file->isDot()) {
        if(in_array($file->getFilename(),$array_vt)) {
            $dirIterator2 = new DirectoryIterator($file->getPathname());
            $oo = false;
            foreach ($dirIterator2 as $file2) {
                if ($file2->getExtension() === 'mp4' && strpos($file2->getFilename(), 'video360') !== false) {
                    $stats['count_video360']++;
                    if(!$oo) {
                        $stats['count_vt_video360']++;
                        $oo = true;
                    }
                }
            }
        }
    }
}
$mysqli->query("DELETE FROM svt_visitors WHERE datetime<(NOW() - INTERVAL 1 MINUTE);");
ob_end_clean();
echo json_encode($stats);