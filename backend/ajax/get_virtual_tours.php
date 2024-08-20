<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_user = $_SESSION['id_user'];
$id_category = (int)$_POST['id_category'];
$id_user_f = (int)$_POST['id_user_f'];
$settings = get_settings();
$user_info = get_user_info($id_user);
if(!isset($_SESSION['lang'])) {
    if(!empty($user_info['language'])) {
        $language = $user_info['language'];
    } else {
        $language = $settings['language'];
    }
} else {
    $language = $_SESSION['lang'];
}
session_write_close();
$where = $where_f = "";
switch(get_user_role($id_user)) {
    case 'customer':
        $where = $where_f = " AND v.id_user=$id_user ";
        break;
    case 'editor':
        $where = $where_f = " AND v.id IN () ";
        $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $ids = $row['ids'];
                $where = $where_f = " AND v.id IN ($ids) ";
            }
        }
        break;
}
if($id_category!=0) {
    $where .= " AND ca.id_category = $id_category ";
}
if($id_user_f!=0) {
    $where .= " AND v.id_user = $id_user_f ";
}
$array_users = array();
$array_cat = array();
$query = "SELECT v.id,c.name as category_name,c.id as category_id,v.id_user,u.username FROM svt_virtualtours as v
            LEFT JOIN svt_users as u ON u.id=v.id_user
            LEFT JOIN svt_category_vt_assoc as scva on scva.id_virtualtour=v.id
            LEFT JOIN svt_categories as c ON c.id=scva.id_category
            WHERE 1=1 $where_f
            GROUP BY v.id,scva.id_category,c.name,c.id,v.id_user,u.username;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            if(!empty($row['category_id'])) {
                if(!in_array($row['category_id']."|".$row['category_name'],$array_cat)) {
                    $array_cat[] = $row['category_id']."|".$row['category_name'];
                }
            }
            if(!empty($row['id_user'])) {
                if(!in_array($row['id_user']."|".$row['username'],$array_users)) {
                    $array_users[] = $row['id_user']."|".$row['username'];
                }
            }
        }
    }
}
$array_vt = array();
if($_SESSION['full_group_by']===true) {
    $group_by = "v.id,v.aws_s3,v.date_created,p.expire_tours,v.external,v.name,v.author,v.id_user,u.username,v.start_date,v.end_date,v.active,u.expire_plan_date,v.info_box,v.background_image,v.note,v.dollhouse,v.dollhouse_glb,v.languages_enabled,v.language";
} else {
    $group_by = "v.id,v.date_created";
}
$s3Client = null;
$s3_url = "";
$query = "SELECT IFNULL(p.expire_tours,1) as expire_tours,v.id,v.aws_s3,GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as category_name,GROUP_CONCAT(DISTINCT c.id) as category_id,v.external,v.name,v.date_created,v.author,v.id_user,u.username,v.start_date,v.end_date,v.active,u.expire_plan_date,COUNT(DISTINCT r.id) as count_rooms,COUNT(DISTINCT m.id) as count_maps,COUNT(DISTINCT g.id) as count_gallery,COUNT(DISTINCT svp.id) as count_video_projects,IF(v.info_box IS NULL OR v.info_box = '' OR v.info_box='<p><br></p>',0,1) as info_box_check,GROUP_CONCAT(DISTINCT r.panorama_image ORDER BY r.priority ASC) as panoramas_list,v.background_image,v.note,v.dollhouse,v.dollhouse_glb,v.languages_enabled,v.language
            FROM svt_virtualtours as v 
            LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id
            LEFT JOIN svt_users as u ON u.id=v.id_user
            LEFT JOIN svt_maps as m ON m.id_virtualtour=v.id
            LEFT JOIN svt_gallery as g ON g.id_virtualtour=v.id
            LEFT JOIN svt_category_vt_assoc as ca ON ca.id_virtualtour=v.id
            LEFT JOIN svt_categories as c ON c.id=ca.id_category
            LEFT JOIN svt_plans AS p ON p.id=u.id_plan
            LEFT JOIN svt_video_projects svp on v.id = svp.id_virtualtour
            WHERE 1=1 $where
            GROUP BY $group_by
            ORDER BY v.date_created DESC,v.id DESC;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_vt = $row['id'];
            $s3_params = check_s3_tour_enabled($id_vt);
            $s3_enabled = false;
            if(!empty($s3_params)) {
                $s3_bucket_name = $s3_params['bucket'];
                if($s3Client==null) {
                    $s3Client = init_s3_client_no_wrapper($s3_params);
                    if($s3Client==null) {
                        $s3_enabled = false;
                    } else {
                        if(!empty($s3_params['custom_domain'])) {
                            $s3_url = "https://".$s3_params['custom_domain']."/";
                        } else {
                            try {
                                $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.');
                            } catch (Aws\Exception\S3Exception $e) {}
                        }
                        $s3_enabled = true;
                    }
                } else {
                    $s3_enabled = true;
                }
            }
            if($user_info['role']=='editor') {
                $editor_permissions = get_editor_permissions($id_user,$row['id']);
                if($editor_permissions['edit_virtualtour']==1) {
                    $row['edit_permission']=true;
                } else {
                    $row['edit_permission']=false;
                }
                if($editor_permissions['edit_virtualtour_ui']==1) {
                    $row['edit_ui_permission']=true;
                } else {
                    $row['edit_ui_permission']=false;
                }
                if($editor_permissions['edit_3d_view']==1) {
                    $row['edit_3d_view_permission']=true;
                } else {
                    $row['edit_3d_view_permission']=false;
                }
            } else {
                $row['edit_permission']=true;
                $row['edit_ui_permission']=true;
                $row['edit_3d_view_permission']=true;
            }
            if($row['active']==0) {
                $row['status']=0;
            } else {
                $row['status']=1;
            }
            $row['date_created'] = formatTime("%d %b %Y",$language,strtotime($row['date_created']));
            if(!empty($row['expire_plan_date'])) {
                if($row['expire_tours']==1) {
                    if (new DateTime() > new DateTime($row['expire_plan_date'])) {
                        $row['status'] = 0;
                    }
                }
            }
            if((!empty($row['start_date'])) && ($row['start_date']!='0000-00-00')) {
                if (new DateTime() < new DateTime($row['start_date']." 00:00:00")) {
                    $row['status']=0;
                }
                $row['start_date'] = formatTime("%d %b %Y",$language,strtotime($row['start_date']));
            } else {
                $row['start_date'] = "";
            }
            if((!empty($row['end_date'])) && ($row['end_date']!='0000-00-00')) {
                if (new DateTime() > new DateTime($row['end_date']." 23:59:59")) {
                    $row['status']=0;
                }
                $row['end_date'] = formatTime("%d %b %Y",$language,strtotime($row['end_date']));
            } else {
                $row['end_date'] = "";
            }
            if(($row['author']!=$row['username']) && (!empty($row['author']))) {
                $row['author'] = $row['username']." (".$row['author'].")";
            } else {
                $row['author'] = $row['username'];
            }
            $row['name'] = htmlentities($row['name']);
            $row['author'] = htmlentities($row['author']);
            if(empty($row['category_name'])) $row['category_name']='';
            if(empty($row['panoramas_list'])) $row['panoramas_list']='';
            if(empty($row['background_image'])) $row['background_image']='';
            if(empty($row['note'])) $row['note']='';
            $dollhouse = $row['dollhouse'];
            $dollhouse_glb = $row['dollhouse_glb'];
            if(!empty($dollhouse_glb)) {
                $row['dollhouse'] = 1;
            } else {
                if(!empty($dollhouse)) {
                    $dollhouse = json_decode($dollhouse,true);
                    if(count($dollhouse['rooms'])>0) {
                        $row['dollhouse'] = 1;
                    } else {
                        $row['dollhouse'] = 0;
                    }
                } else {
                    $row['dollhouse'] = 0;
                }
            }
            $count_video360 = 0;
            if($s3_enabled) {
                if($s3Client->doesObjectExist($s3_bucket_name,'video360/'.$row['id'].'/')) {
                    $objects = $s3Client->getIterator('ListObjects', array(
                        "Bucket" => $s3_bucket_name,
                        "Prefix" => 'video360/'.$row['id'].'/'
                    ));
                    foreach ($objects as $object) {
                        $file_ext = strtolower(substr($object['Key'], strrpos($object['Key'], '.')+1));
                        if ($file_ext == 'mp4') $count_video360++;
                    }
                }
            } else {
                $path = dirname(__FILE__).'/../../video360/'.$row['id'].'/';
                if(file_exists($path)) {
                    $dir = new DirectoryIterator($path);
                    foreach ($dir as $fileinfo) {
                        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
                            $file_ext = $fileinfo->getExtension();
                            if ($file_ext == 'mp4') $count_video360++;
                        }
                    }
                }
            }
            if($s3_enabled) {
                 if($s3Client->doesObjectExist($s3_bucket_name,'viewer/gallery/'.$row['id'].'_slideshow.mp4')) {
                     $row['slideshow_check']=1;
                 } else {
                     $row['slideshow_check']=0;
                 }
            } else {
                if(file_exists('../../viewer/gallery/'.$row['id'].'_slideshow.mp4')) {
                    $row['slideshow_check']=1;
                } else {
                    $row['slideshow_check']=0;
                }
            }
            $row['count_video360']=$count_video360;
            if($s3_enabled) {
                $row['aws_s3_url'] = $s3_url;
                $row['aws_s3']=1;
            } else {
                $row['aws_s3']=0;
            }
            if(!empty($row['languages_enabled'])) {
                $row['languages_enabled']=json_decode($row['languages_enabled'],true);
            } else {
                $row['languages_enabled']=array();
            }
            $default_language = $row['language'];
            if(empty($default_language)) {
                $default_language = $settings['language'];
            }
            $row['languages_enabled'][$default_language]=0;
            $array_languages = array();
            foreach ($row['languages_enabled'] as $lang=>$enabled) {
                if($enabled==1) {
                    array_push($array_languages,$lang);
                }
            }
            $row['default_language']=$default_language;
            $row['languages']=$array_languages;
            $array_vt[]=$row;
        }
    }
}
ob_end_clean();
echo json_encode(array("vt_list"=>$array_vt,"aws_s3_enabled"=>$settings['aws_s3_enabled'],"categories"=>$array_cat,"users"=>$array_users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);