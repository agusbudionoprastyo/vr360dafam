<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../functions.php");
require_once("../../db/connection.php");
$id_virtualtour = $_POST['id_virtualtour'];
$id_user = (int)$_POST['id_user'];
if($id_virtualtour=='all') {
    $id_user = $_SESSION['id_user'];
}
$elem = $_POST['elem'];
$unique = false;
if(isset($_SESSION['statistics_type'])) {
    if($_SESSION['statistics_type']=="unique") {
        $unique = true;
    }
}
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
if(!isset($_SESSION['lang'])) {
    if(!empty($user_info['language'])) {
        $language = $user_info['language'];
    } else {
        $language = $settings['language'];
    }
} else {
    $language = $_SESSION['lang'];
}
set_language($language,$settings['language_domain']);
session_write_close();
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
$stats = array();
switch ($elem) {
    case 'chart_visitor_vt':
        $stats['labels'] = array();
        $stats['data'] = array();
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
                    $stats['data'][] = $tmp;
                }
            }
        }
        usort($stats['data'], 'sortByOrder');
        break;
    case 'chart_rooms_access':
        $stats['labels'] = array();
        $stats['data'] = array();
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
                    array_push($stats['labels'],strtoupper($row['name']));
                    array_push($stats['data'],$row['num']);
                }
            }
        }
        break;
    case 'chart_rooms_time':
        $stats['labels'] = array();
        $stats['data'] = array();
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
                    array_push($stats['labels'],strtoupper($row['name']));
                    array_push($stats['data'],round($row['num']));
                }
            }
        }
        break;
    case 'chart_poi_views':
        $stats['pois'] = array();
        $stats['total_poi'] = 0;
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
                    $stats['total_poi'] = $stats['total_poi'] + $row['access_count'];
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
        }
        break;
    case 'chart_time_slot':
        $stats['labels'] = array();
        $stats['data'] = array();
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
                    array_push($stats['labels'],strtoupper($row['time_slot']));
                    array_push($stats['data'],$row['perc']);
                }
            }
        }
        break;
}
ob_end_clean();
echo json_encode($stats);

function sortByOrder($a, $b) {
    return $a[0] - $b[0];
}