<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once("../db/connection.php");
require_once("../backend/functions.php");
require_once("api_functions.php");
require_once("vendor/autoload.php");

register_shutdown_function("fatal_handler");

$method = $_SERVER["REQUEST_METHOD"];
if($method!='GET') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(array("message"=>"invalid method $method"));
    exit;
}

if(!empty($_GET)) {
    $params = $_GET;
} else {
    $content = trim(file_get_contents("php://input"));
    $params = json_decode($content, true);
}

$mandatory_params = ['token'];
check_api_missing_params($params,$mandatory_params);
$payload = validate_token($params['token']);
$id_user = $payload['id_user'];

if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$base_url = $protocol ."://". $_SERVER['SERVER_NAME'] . str_replace("api/tour_stats.php","",$_SERVER['SCRIPT_NAME']);

get_tour_stats_api($params,$id_user);
exit;

function get_tour_stats_api($params,$id_user) {
    global $mysqli,$base_url;
    $stats = [];
    if(isset($params['id_tour'])) {
        $id_virtualtour = $params['id_tour'];
    } else {
        $id_virtualtour = 'all';
    }
    $where = '';
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
    if($id_virtualtour!='all') {
        $where .= " AND v.id=$id_virtualtour ";
    }
    $unique = false;
    if(isset($params['type'])) {
        if($params['type']=="unique") {
            $unique = true;
        }
    }
    $stats['visitor_vt']['labels'] = array();
    $stats['visitor_vt']['data'] = array();
    if($id_virtualtour=='all') {
        if($unique) {
            $query = "SELECT COUNT(DISTINCT ip) as num,DAY(date_time) as d,MONTH(date_time) as m,YEAR(date_time) as y FROM svt_access_log
                        JOIN svt_virtualtours as v on svt_access_log.id_virtualtour = v.id
                        $where AND ip IS NOT NULL AND ip!=''
                        GROUP BY DAY(date_time),MONTH(date_time),YEAR(date_time);";
        } else {
            $query = "SELECT COUNT(*) as num,DAY(date_time) as d,MONTH(date_time) as m,YEAR(date_time) as y FROM svt_access_log 
                    JOIN svt_virtualtours as v on svt_access_log.id_virtualtour = v.id
                    $where
                    GROUP BY DAY(date_time),MONTH(date_time),YEAR(date_time);";
        }
    } else {
        if($id_user!=0) {
            $query = "SELECT COUNT(*) as num,DAY(date_time) as d,MONTH(date_time) as m,YEAR(date_time) as y FROM svt_access_log 
                    WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE id_user=$id_user)
                    GROUP BY DAY(date_time),MONTH(date_time),YEAR(date_time);";
        } else {
            if($unique) {
                $query = "SELECT COUNT(DISTINCT ip) as num,DAY(date_time) as d,MONTH(date_time) as m,YEAR(date_time) as y FROM svt_access_log
                        WHERE id_virtualtour=$id_virtualtour AND ip IS NOT NULL AND ip!=''
                        GROUP BY DAY(date_time),MONTH(date_time),YEAR(date_time);";
            } else {
                $query = "SELECT COUNT(*) as num,DAY(date_time) as d,MONTH(date_time) as m,YEAR(date_time) as y FROM svt_access_log 
                    WHERE id_virtualtour=$id_virtualtour
                    GROUP BY DAY(date_time),MONTH(date_time),YEAR(date_time);";
            }
        }
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $date_time = strtotime($row['y']."-".$row['m']."-".$row['d'])*1000;
                $num = intval($row['num']);
                $tmp = array();
                $tmp[0]=$date_time;
                $tmp[1]=$num;
                $stats['visitor_vt']['data'][] = $tmp;
            }
        } else {
            ob_end_clean();
            http_response_code(404);
            echo json_encode(array("message"=>"no tour found"));
            exit;
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
    usort($stats['visitor_vt']['data'], 'sortByOrder');
    $stats['rooms_access']['labels'] = array();
    $stats['rooms_access']['data'] = array();
    if($id_virtualtour=='all') {
        if($unique) {
            $query = "SELECT CONCAT(v.name,' - ',sr.name) as name,COUNT(DISTINCT salr.ip) as num,sr.id FROM svt_access_log_room AS salr
                    JOIN svt_rooms sr ON salr.id_room = sr.id
                    JOIN svt_virtualtours as v on sr.id_virtualtour = v.id
                    $where
                    GROUP BY sr.id
                    ORDER BY num DESC LIMIT 10";
        } else {
            $query = "SELECT CONCAT(v.name,' - ',r.name) as name,r.access_count as num,r.id FROM svt_rooms as r
                    JOIN svt_virtualtours as v on r.id_virtualtour = v.id
                    $where
                    GROUP BY r.id
                    ORDER BY num DESC LIMIT 10;";
        }
    } else {
        if($unique) {
            $query = "SELECT sr.name,COUNT(DISTINCT salr.ip) as num,sr.id FROM svt_access_log_room AS salr
                    JOIN svt_rooms sr ON salr.id_room = sr.id
                    WHERE sr.id_virtualtour=$id_virtualtour
                    GROUP BY sr.id
                    ORDER BY sr.priority";
        } else {
            $query = "SELECT r.name,r.access_count as num,r.id FROM svt_rooms as r
                    WHERE r.id_virtualtour=$id_virtualtour 
                    GROUP BY r.id
                    ORDER BY r.priority;";
        }
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($stats['rooms_access']['labels'],strtoupper($row['name']));
                array_push($stats['rooms_access']['data'],$row['num']);
            }
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
    $stats['rooms_time']['labels'] = array();
    $stats['rooms_time']['data'] = array();
    if($id_virtualtour=='all') {
        if($unique) {
            $query = "SELECT CONCAT(v.name,' - ',r.name) as name,AVG(time) as num,l.id_room FROM svt_rooms_access_log as l
                    JOIN svt_rooms as r on r.id=l.id_room
                    JOIN svt_virtualtours as v on r.id_virtualtour = v.id
                    $where AND ip IS NOT NULL AND ip!=''
                    GROUP BY l.id_room
                    ORDER BY num DESC LIMIT 10;";
        } else {
            $query = "SELECT CONCAT(v.name,' - ',r.name) as name,AVG(time) as num, l.id_room FROM svt_rooms_access_log as l
                    JOIN svt_rooms as r on r.id=l.id_room
                    JOIN svt_virtualtours as v on r.id_virtualtour = v.id
                    $where
                    GROUP BY l.id_room
                    ORDER BY num DESC LIMIT 10;";
        }
    } else {
        if($unique) {
            $query = "SELECT r.name,AVG(time) as num,l.id_room FROM svt_rooms_access_log as l
                    JOIN svt_rooms as r on r.id=l.id_room
                    WHERE r.id_virtualtour=$id_virtualtour AND ip IS NOT NULL AND ip!=''
                    GROUP BY l.id_room
                    ORDER BY r.priority;";
        } else {
            $query = "SELECT r.name,AVG(time) as num, l.id_room FROM svt_rooms_access_log as l
                    JOIN svt_rooms as r on r.id=l.id_room
                    WHERE r.id_virtualtour=$id_virtualtour 
                    GROUP BY l.id_room
                    ORDER BY r.priority;";
        }
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                array_push($stats['rooms_time']['labels'],strtoupper($row['name']));
                array_push($stats['rooms_time']['data'],round($row['num']));
            }
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
    $stats['pois'] = array();
    if($id_virtualtour=='all') {
        if($unique) {
            $query = "SELECT sr.name as room,COUNT(DISTINCT salp.ip) as access_count,sp.type,sp.content,sp.params,sp.id FROM svt_access_log_poi AS salp
                    JOIN svt_pois sp ON salp.id_poi = sp.id
                    JOIN svt_rooms sr on sp.id_room = sr.id
                    JOIN svt_virtualtours as v on sr.id_virtualtour = v.id
                    $where
                    AND sp.type != 'switch_pano'
                    GROUP BY sp.id
                    ORDER BY access_count DESC LIMIT 10;";
        } else {
            $query = "SELECT r.name as room,p.type,p.content,p.params,p.access_count,p.id FROM svt_pois as p
                    JOIN svt_rooms as r ON r.id=p.id_room
                    JOIN svt_virtualtours as v on r.id_virtualtour = v.id
                    $where
                    AND p.type != 'switch_pano'
                    AND p.access_count>0 
                    GROUP BY p.id
                    ORDER BY p.access_count DESC LIMIT 10;";
        }
    } else {
        if($unique) {
            $query = "SELECT sr.name as room,COUNT(DISTINCT salp.ip) as access_count,sp.type,sp.content,sp.params,sp.id FROM svt_access_log_poi AS salp
                    JOIN svt_pois sp ON salp.id_poi = sp.id
                    JOIN svt_rooms sr on sp.id_room = sr.id
                    WHERE sr.id_virtualtour=$id_virtualtour
                    AND sp.type != 'switch_pano'
                    GROUP BY sp.id
                    ORDER BY COUNT(DISTINCT salp.ip) DESC;";
        } else {
            $query = "SELECT r.name as room,p.type,p.content,p.params,p.access_count,p.id FROM svt_pois as p
                    JOIN svt_rooms as r ON r.id=p.id_room
                    WHERE r.id_virtualtour=$id_virtualtour 
                    AND p.type != 'switch_pano'
                    AND p.access_count>0 
                    GROUP BY p.id
                    ORDER BY p.access_count DESC;";
        }
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if(empty($row['type'])) $row['type']='';
                switch ($row['type']) {
                    case 'product':
                        $query_p = "SELECT name FROM svt_products WHERE id=".$row['content']." LIMIT 1;";
                        $ressult_p = $mysqli->query($query_p);
                        if($ressult_p) {
                            if($ressult_p->num_rows==1) {
                                $row_p = $ressult_p->fetch_array(MYSQLI_ASSOC);
                                $row['content'] = $row_p['name'];
                            }
                        }
                        break;
                }
                array_push($stats['pois'],$row);
            }
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
    $stats['time_slots']['labels'] = array();
    $stats['time_slots']['data'] = array();
    if($id_virtualtour=='all') {
        if($unique) {
            $query = "SELECT
                            CASE
                                WHEN HOUR(date_time) BETWEEN 7 AND 11 THEN 0
                                WHEN HOUR(date_time) BETWEEN 12 AND 17 THEN 1
                                WHEN HOUR(date_time) BETWEEN 18 AND 23 THEN 2
                                ELSE 3
                                END as time_slot,
                            COUNT(DISTINCT ip) as num,
                            ROUND(COUNT(DISTINCT ip)/(SELECT COUNT(DISTINCT ip) FROM svt_access_log JOIN svt_virtualtours as v on svt_access_log.id_virtualtour = v.id $where AND ip IS NOT NULL AND ip!='')*100, 2) as perc
                        FROM svt_access_log
                        JOIN svt_virtualtours as v on svt_access_log.id_virtualtour = v.id
                        $where AND ip IS NOT NULL AND ip!=''
                        GROUP BY time_slot
                        ORDER BY time_slot;";
        } else {
            $query = "SELECT
                            CASE
                                WHEN HOUR(date_time) BETWEEN 7 AND 11 THEN 0
                                WHEN HOUR(date_time) BETWEEN 12 AND 17 THEN 1
                                WHEN HOUR(date_time) BETWEEN 18 AND 23 THEN 2
                                ELSE 3
                                END as time_slot,
                            COUNT(*) as num,
                            ROUND(COUNT(*)/(SELECT COUNT(*) FROM svt_access_log JOIN svt_virtualtours as v on svt_access_log.id_virtualtour = v.id $where)*100, 2) as perc
                        FROM svt_access_log
                        JOIN svt_virtualtours as v on svt_access_log.id_virtualtour = v.id
                        $where
                        GROUP BY time_slot
                        ORDER BY time_slot;";
        }
    } else {
        if($unique) {
            $query = "SELECT
                            CASE
                                WHEN HOUR(date_time) BETWEEN 7 AND 11 THEN 0
                                WHEN HOUR(date_time) BETWEEN 12 AND 17 THEN 1
                                WHEN HOUR(date_time) BETWEEN 18 AND 23 THEN 2
                                ELSE 3
                                END as time_slot,
                            COUNT(DISTINCT ip) as num,
                            ROUND(COUNT(DISTINCT ip)/(SELECT COUNT(DISTINCT ip) FROM svt_access_log WHERE id_virtualtour=$id_virtualtour AND ip IS NOT NULL AND ip!='')*100, 2) as perc
                        FROM svt_access_log
                        WHERE id_virtualtour=$id_virtualtour AND ip IS NOT NULL AND ip!=''
                        GROUP BY time_slot
                        ORDER BY time_slot;";
        } else {
            $query = "SELECT
                            CASE
                                WHEN HOUR(date_time) BETWEEN 7 AND 11 THEN 0
                                WHEN HOUR(date_time) BETWEEN 12 AND 17 THEN 1
                                WHEN HOUR(date_time) BETWEEN 18 AND 23 THEN 2
                                ELSE 3
                                END as time_slot,
                            COUNT(*) as num,
                            ROUND(COUNT(*)/(SELECT COUNT(*) FROM svt_access_log WHERE id_virtualtour=$id_virtualtour)*100, 2) as perc
                        FROM svt_access_log
                        WHERE id_virtualtour=$id_virtualtour
                        GROUP BY time_slot
                        ORDER BY time_slot;";
        }
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                switch($row['time_slot']) {
                    case 0:
                        $row['time_slot'] = _("Morning");
                        break;
                    case 1:
                        $row['time_slot'] = _("Afternoon");
                        break;
                    case 2:
                        $row['time_slot'] = _("Evening");
                        break;
                    case 3:
                        $row['time_slot'] = _("Night");
                        break;
                }
                array_push($stats['time_slots']['labels'],strtoupper($row['time_slot']));
                array_push($stats['time_slots']['data'],$row['perc']);
            }
        }
    } else {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(array("message"=>"error"));
        exit;
    }
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
    $query = "SELECT COUNT(v.id) as num FROM svt_virtualtours as v $where LIMIT 1";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $num = $row['num'];
            $stats['count_virtual_tours'] = (int) $num;
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
            $stats['count_rooms'] = (int) $num;
            $stats['count_vt_rooms'] = (int) $num_vt;
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
            $stats['count_markers'] = (int) $num;
            $stats['count_vt_markers'] = (int) $num_vt;
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
            $stats['count_pois'] = (int) $num;
            $stats['count_vt_pois'] = (int) $num_vt;
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
            $stats['count_measures'] = (int) $num;
            $stats['count_vt_measures'] = (int) $num_vt;
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
                    $stats['total_visitors'] = (int) $row['count'];
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
                    $stats['total_visitors'] = (int) $total_visitors;
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
                    $stats['total_visitors'] = (int) $row['count'];
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
                    $stats['total_visitors'] = (int) $total_visitors;
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
            $stats['total_online_visitors'] = (int) $total_online_visitors;
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
            $stats['count_video_projects'] = (int) $num;
            $stats['count_vt_video_projects'] = (int) $num_vt;
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
    $dir = '../viewer/gallery/';
    $dirIterator = new DirectoryIterator($dir);
    foreach ($dirIterator as $file) {
        if ($file->getExtension() === 'mp4' && strpos($file->getFilename(), 'slideshow') !== false && (preg_match('/^(' . implode('|', $array_vt) . ')\D/', $file->getFilename()))) {
            $stats['count_slideshows']++;
            $stats['count_vt_slideshows']++;
        }
    }
    $dir = '../video360/';
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
    ob_end_clean();
    http_response_code(200);
    echo json_encode(array("message"=>"ok","data"=>$stats));
    exit;
}

function sortByOrder($a, $b) {
    return $a[0] - $b[0];
}