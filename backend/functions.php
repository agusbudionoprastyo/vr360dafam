<?php
require_once(__DIR__."/../db/connection.php");

function is_ssl() {
    if ( isset( $_SERVER['HTTPS'] ) ) {
        if ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) {
            return true;
        }
        if ( '1' == $_SERVER['HTTPS'] ) {
            return true;
        }
    } elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
        return true;
    } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
        return true;
    }
    return false;
}

function isEnabled($func) {
    return is_callable($func) && false === stripos(ini_get('disable_functions'), $func);
}

function get_user_info($id_user) {
    global $mysqli;
    $return = array();
    $query = "SELECT u.*,p.name as plan,p.max_storage_space FROM svt_users as u LEFT JOIN svt_plans as p ON p.id=u.id_plan WHERE u.id = $id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $settings = get_settings();
            if(($settings['stripe_enabled']) && !empty($row['id_subscription_stripe']) && ($row['status_subscription_stripe']==0)) {
                $row['plan_status']='invalid_payment';
            } else {
                if($row['expire_plan_date']==null) {
                    $row['plan_status']='active';
                } else {
                    if (new DateTime() > new DateTime($row['expire_plan_date'])) {
                        $row['plan_status']='expired';
                    } else{
                        $row['plan_status']='expiring';
                    }
                }
            }
            if(empty($row['avatar'])) {
                $row['avatar']='img/avatar1.png';
            } else {
                $row['avatar']='assets/'.$row['avatar'];
            }
            if($row['role']=='editor') $row['plan_status']="active";
            $return=$row;
        }
    }
    return $return;
}

function set_user_log($id_user,$type,$params,$datetime) {
    global $mysqli;
    $type = strip_tags($type);
    $params = strip_tags($params);
    $query = "INSERT IGNORE INTO svt_users_log(id_user, date_time, type, params) VALUES(?,?,?,?);";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('isss',  $id_user,$datetime,$type,$params);
        $smt->execute();
    }
}

function get_user_role($id_user) {
    global $mysqli;
    $return = 'user';
    $query = "SELECT role FROM svt_users WHERE id = $id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $return=$row['role'];
        }
    }
    return $return;
}

function check_can_delete($id_user,$id_virtualtour) {
    global $mysqli;
    $return = false;
    switch(get_user_role($id_user)) {
        case 'administrator':
            $return = true;
            break;
        case 'customer':
            $query = "SELECT id FROM svt_virtualtours WHERE id=$id_virtualtour AND id_user=$id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $return = true;
                }
            }
            break;
        case 'editor':
            $query = "SELECT id_virtualtour FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtualtour LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $return = true;
                }
            }
            break;
    }
    return $return;
}

function get_virtual_tour($id_virtual_tour,$id_user) {
    global $mysqli;
    if(isset($id_virtual_tour)) {
        $return = array();
        $query = "SELECT v.*,(SELECT image FROM svt_icons WHERE id=v.markers_id_icon_library) as markers_image_icon_library,(SELECT id_virtualtour FROM svt_icons WHERE id=v.markers_id_icon_library) as markers_id_vt_library,(SELECT image FROM svt_icons WHERE id=v.pois_id_icon_library) as pois_image_icon_library,(SELECT id_virtualtour FROM svt_icons WHERE id=v.pois_id_icon_library) as pois_id_vt_library FROM svt_virtualtours as v WHERE v.id = $id_virtual_tour LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $id_user_vt = $row['id_user'];
                switch(get_user_role($id_user)) {
                    case 'administrator';
                        break;
                    case 'customer':
                        if($id_user!=$id_user_vt) return false;
                        break;
                    case 'editor':
                        $query = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtual_tour;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if($result->num_rows==0) {
                                return false;
                            }
                        }
                        break;
                }
                if(empty($row['languages_enabled'])) {
                    $row['languages_enabled']=array();
                } else {
                    $row['languages_enabled']=json_decode($row['languages_enabled'],true);
                }
                $return=$row;
            }
        }
        return $return;
    } else {
        return false;
    }
}

function get_showcase($id_showcase,$id_user) {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_showcases WHERE id = $id_showcase LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_user_s = $row['id_user'];
            $row['header_html'] = htmlspecialchars_decode($row['header_html']);
            $row['footer_html'] = htmlspecialchars_decode($row['footer_html']);
            $row['header_html'] = str_replace(["\r\n","\r","\n"], "<br>", $row['header_html']);
            $row['footer_html'] = str_replace(["\r\n","\r","\n"], "<br>", $row['footer_html']);
            $row['header_html'] = str_replace('"', '\"', $row['header_html']);
            $row['footer_html'] = str_replace('"', '\"', $row['footer_html']);
            switch(get_user_role($id_user)) {
                case 'administrator';
                    break;
                case 'customer':
                    if($id_user!=$id_user_s) return false;
                    break;
                case 'editor':
                    return false;
                    break;
            }
            $return=$row;
        }
    }
    return $return;
}

function get_globe($id_globe,$id_user) {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_globes WHERE id = $id_globe LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_user_s = $row['id_user'];
            switch(get_user_role($id_user)) {
                case 'administrator';
                    break;
                case 'customer':
                    if($id_user!=$id_user_s) return false;
                    break;
                case 'editor':
                    return false;
                    break;
            }
            $return=$row;
        }
    }
    return $return;
}

function get_advertisement($id_advertisement,$id_user) {
    global $mysqli;
    $return = array();
    if(get_user_role($id_user)!='administrator') {
        return false;
    }
    $query = "SELECT * FROM svt_advertisements WHERE id = $id_advertisement LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $return=$row;
        }
    }
    return $return;
}

function print_virtualtour_selector($check='no') {
    $id_user = $_SESSION['id_user'];
    $virtual_tours = get_virtual_tours($id_user,$check);
    $count_virtual_tours = count($virtual_tours);
    $array_list_vt = array();
    if ($count_virtual_tours==1) {
        $id_virtualtour_sel = $virtual_tours[0]['id'];
        $name_virtualtour_sel = $virtual_tours[0]['name'];
        $author_virtualtour_sel = $virtual_tours[0]['author'];
        $count_check = $virtual_tours[0]['count_check'];
        $_SESSION['id_virtualtour_sel'] = $id_virtualtour_sel;
        $_SESSION['name_virtualtour_sel'] = $name_virtualtour_sel;
        $array_list_vt[] = array("id"=>$id_virtualtour_sel,"name"=>$name_virtualtour_sel,"author"=>$author_virtualtour_sel,"count_check"=>$count_check);
    } else {
        if(isset($_GET['id_vt'])) {
            $id_virtualtour_sel = $_GET['id_vt'];
            $name_virtualtour_sel = get_virtual_tour($_GET['id_vt'],$id_user)['name'];
            $_SESSION['id_virtualtour_sel'] = $id_virtualtour_sel;
            $_SESSION['name_virtualtour_sel'] = $name_virtualtour_sel;
        } else {
            if(isset($_SESSION['id_virtualtour_sel'])) {
                $id_virtualtour_sel = $_SESSION['id_virtualtour_sel'];
            } else {
                $id_virtualtour_sel = $virtual_tours[0]['id'];
                $name_virtualtour_sel = $virtual_tours[0]['name'];
                $_SESSION['id_virtualtour_sel'] = $id_virtualtour_sel;
                $_SESSION['name_virtualtour_sel'] = $name_virtualtour_sel;
            }
        }
        foreach ($virtual_tours as $virtual_tour) {
            $id_virtualtour = $virtual_tour['id'];
            $name_virtualtour = $virtual_tour['name'];
            $author_virtualtour = $virtual_tour['author'];
            $count_check = $virtual_tour['count_check'];
            $array_list_vt[] = array("id"=>$id_virtualtour,"name"=>$name_virtualtour,"author"=>$author_virtualtour,"count_check"=>$count_check);
        }
    }
    $return = '<div class="vt_select_header">';
    $return .= '<i id="loading_header" class="fas fa-spin fa-spinner"></i>';
    $return .= '<select onchange="change_virtualtour();" id="virtualtour_selector" class="selectpicker" data-container="body" data-width="fit" data-live-search="true" data-style=" vt_selector_btn" data-none-results-text="'._("No results matched").' {0}">';
    foreach ($array_list_vt as $vt) {
        $name_vt = strlen($vt['name']) > 30 ? substr($vt['name'],0,30)."..." : $vt['name'];
        if($check=='no') {
            $icon = "";
        } else {
            if($vt['count_check']>0) {
                $icon = "data-icon='fas fa-circle'";
            } else {
                $icon = "data-icon='far fa-circle'";
            }
        }
        $return .= "<option $icon data-subtext=\"".$vt['author']."\" ".(($id_virtualtour_sel==$vt['id']) ? 'selected' : '')." id='".$vt['id']."'>".$name_vt."</option>";
    }
    $return .= '</select>';
    $return .= '<a href="index.php?p=edit_virtual_tour&id='.$id_virtualtour_sel.'" class="quick_action"><i class="fas fa-edit"></i></a>';
    $return .= '</div>';
    return $return;
}

function get_customers_count() {
    global $mysqli;
    $num = 0;
    $query = "SELECT COUNT(*) as num FROM svt_users WHERE role='customer';";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $num = $row['num'];
        }
    }
    return $num;
}

function get_virtual_tours($id_user,$check='no') {
    global $mysqli;
    $return = array();
    switch(get_user_role($id_user)) {
        case 'administrator':
            $where = "";
            break;
        case 'customer':
            $where = " WHERE v.id_user=$id_user ";
            break;
        case 'editor':
            $where = " WHERE v.id IN () ";
            $query = "SELECT GROUP_CONCAT(id_virtualtour) as ids FROM svt_assign_virtualtours WHERE id_user=$id_user;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $ids = $row['ids'];
                    $where = " WHERE v.id IN ($ids) ";
                }
            }
            break;
    }
    switch($check) {
        case 'no':
        case 'video_360':
            $query = "SELECT v.id,v.name,v.author,0 as count_check FROM svt_virtualtours as v $where ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'rooms':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT r.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'markers':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT m.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id LEFT JOIN svt_markers as m ON m.id_room=r.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'pois':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT p.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id LEFT JOIN svt_pois as p ON p.id_room=r.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'measures':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT ms.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id LEFT JOIN svt_measures as ms ON ms.id_room=r.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'maps':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT m.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_maps as m ON m.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'products':
            $query = "SELECT v.id,v.name,v.author,CASE WHEN v.shop_type = 'woocommerce' AND v.woocommerce_store_url <> '' AND v.woocommerce_customer_key <> '' AND v.woocommerce_customer_secret <> '' THEN 1 ELSE COUNT(DISTINCT p.id) END AS count_check FROM svt_virtualtours as v LEFT JOIN svt_products as p ON p.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'dollhouse':
            $query = "SELECT v.id,v.name,v.author,v.dollhouse,v.dollhouse_glb,0 as count_check FROM svt_virtualtours as v $where ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'info_box':
            $query = "SELECT v.id,v.name,v.author,IF(v.info_box IS NULL OR v.info_box = '' OR v.info_box='<p><br></p>',0,1) as count_check FROM svt_virtualtours as v $where ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'video_projects':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT vp.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_video_projects as vp ON vp.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'forms':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT f.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_forms_data as f ON f.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'leads':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT l.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_leads as l ON l.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'landing':
            $query = "SELECT v.id,v.name,v.author,IF(v.html_landing IS NULL OR v.html_landing = '' OR v.html_landing='<p><br></p>',0,1) as count_check FROM svt_virtualtours as v $where ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'icons_library':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT i.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_icons as i ON i.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'media_library':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT m.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_media_library as m ON m.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'music_library':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT m.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_music_library as m ON m.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'sound_library':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT m.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_sound_library as m ON m.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
        case 'gallery':
            $query = "SELECT v.id,v.name,v.author,COUNT(DISTINCT g.id) as count_check FROM svt_virtualtours as v LEFT JOIN svt_gallery as g ON g.id_virtualtour=v.id $where GROUP BY v.id,v.date_created ORDER BY v.date_created DESC, v.id DESC;";
            break;
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            $s3Client = null;
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                switch($check) {
                    case 'dollhouse':
                        $dollhouse = $row['dollhouse'];
                        $dollhouse_glb = $row['dollhouse_glb'];
                        if(!empty($dollhouse_glb)) {
                            $row['count_check'] = 1;
                        } else {
                            if(!empty($dollhouse)) {
                                $dollhouse = json_decode($dollhouse,true);
                                if(count($dollhouse['rooms'])>0) {
                                    $row['count_check'] = 1;
                                } else {
                                    $row['count_check'] = 0;
                                }
                            } else {
                                $row['count_check'] = 0;
                            }
                        }
                        break;
                    case 'video_360':
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
                                    $s3_enabled = true;
                                }
                            } else {
                                $s3_enabled = true;
                            }
                        }
                        $count_video360 = 0;
                        if($s3_enabled) {
                            $objects = $s3Client->getIterator('ListObjects', [
                                'Bucket' => $s3_bucket_name,
                                'Prefix' => 'video360/'.$id_vt.'/',
                            ]);
                            foreach ($objects as $object) {
                                if (substr($object['Key'], -strlen('.mp4')) == '.mp4') {
                                    $count_video360++;
                                }
                            }
                        } else {
                            $path = dirname(__FILE__).'/../video360/'.$id_vt.'/';
                            $exist = file_exists($path);
                            if($exist) {
                                $dir = new DirectoryIterator($path);
                                foreach ($dir as $fileinfo) {
                                    if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
                                        $file_ext = $fileinfo->getExtension();
                                        if ($file_ext == 'mp4') $count_video360++;
                                    }
                                }
                            }
                        }
                        $row['count_check'] = $count_video360;
                        break;
                }
                $return[]=$row;
            }
        }
    }
    return $return;
}

function get_virtual_tours_options_css() {
    global $mysqli;
    $return = "";
    $query = "SELECT code,name FROM svt_virtualtours ORDER BY name ASC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $name = $row['name'];
                $return .= "<option id='css_custom_$code'>$name</option>";
            }
        }
    }
    return $return;
}

function get_virtual_tours_options_js() {
    global $mysqli;
    $return = "";
    $query = "SELECT code,name FROM svt_virtualtours ORDER BY name ASC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $name = $row['name'];
                $return .= "<option id='js_custom_$code'>$name</option>";
            }
        }
    }
    return $return;
}

function get_virtual_tours_options_head() {
    global $mysqli;
    $return = "";
    $query = "SELECT code,name FROM svt_virtualtours ORDER BY name ASC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $name = $row['name'];
                $return .= "<option id='head_custom_$code'>$name</option>";
            }
        }
    }
    return $return;
}

function get_virtual_tours_editors_css() {
    global $mysqli;
    $return = "";
    $query = "SELECT code FROM svt_virtualtours;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $return .= '<div style="display:none;position: relative;width: 100%;height: 400px;" class="editors_css" id="custom_'.$code.'">'.htmlspecialchars(get_editor_css_content('custom_'.$code)).'</div>';
            }
        }
    }
    return $return;
}

function get_virtual_tours_editors_js() {
    global $mysqli;
    $return = "";
    $query = "SELECT code FROM svt_virtualtours;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $return .= '<div style="display:none;position: relative;width: 100%;height: 400px;" class="editors_js" id="custom_js_'.$code.'">'.htmlspecialchars(get_editor_js_content('custom_'.$code)).'</div>';
            }
        }
    }
    return $return;
}

function get_virtual_tours_editors_head() {
    global $mysqli;
    $return = "";
    $query = "SELECT code FROM svt_virtualtours;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $code = $row['code'];
                $return .= '<div style="display:none;position: relative;width: 100%;height: 400px;" class="editors_head" id="custom_head_'.$code.'">'.htmlspecialchars(get_editor_head_content('custom_'.$code)).'</div>';
            }
        }
    }
    return $return;
}

function get_editor_css_content($css) {
    if($css=='custom_b') {
        $url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_b.css';
    } else {
        $url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$css.'.css';
    }
    if(file_exists($url_css)) {
        return @file_get_contents($url_css);
    } else {
        return '';
    }
}

function get_editor_css_content_s($css) {
    $url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'showcase'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$css.'.css';
    if(file_exists($url_css)) {
        return @file_get_contents($url_css);
    } else {
        return '';
    }
}

function get_editor_css_content_g($css) {
    $url_css = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'globe'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$css.'.css';
    if(file_exists($url_css)) {
        return @file_get_contents($url_css);
    } else {
        return '';
    }
}

function get_editor_js_content($js) {
    if($js=='custom_b') {
        $url_js = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'custom_b.js';
    } else {
        $url_js = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.$js.'.js';
    }
    if(file_exists($url_js)) {
        return @file_get_contents($url_js);
    } else {
        return '';
    }
}

function get_editor_head_content($header) {
    if($header=='custom_b') {
        $url_header = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'header'.DIRECTORY_SEPARATOR.'custom_b.php';
    } else {
        $url_header = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'header'.DIRECTORY_SEPARATOR.$header.'.php';
    }
    if(file_exists($url_header)) {
        return @file_get_contents($url_header);
    } else {
        return '';
    }
}

function get_fisrt_room($id_virtualtour) {
    global $mysqli;
    $return = array();
    $query = "SELECT id,name,panorama_image FROM svt_rooms WHERE id_virtualtour=$id_virtualtour ORDER BY priority LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $return = $row;
        }
    }
    return $return;
}

function get_fisrt_floorplan($id_virtualtour) {
    global $mysqli;
    $return = array();
    $query = "SELECT id,map,width_d FROM svt_maps WHERE id_virtualtour=$id_virtualtour AND map_type='floorplan' ORDER BY priority LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $return = $row;
        }
    }
    return $return;
}

function get_first_room_panorama($id_virtualtour) {
    global $mysqli;
    $row = array();
    $row['panorama_image'] = '';
    $row['multires']=0;
    $row['multires_config']='';
    $row['multires_dir']='';
    $query = "SELECT r.panorama_image,v.enable_multires FROM svt_rooms as r 
              JOIN svt_virtualtours as v ON r.id_virtualtour=v.id
              WHERE r.id_virtualtour=$id_virtualtour ORDER BY r.priority ASC LIMIT 1";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if($row['enable_multires']) {
                $room_pano = str_replace('.jpg','',$row['panorama_image']);
                $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
                if(file_exists($multires_config_file)) {
                    $multires_tmp = file_get_contents($multires_config_file);
                    $multires_array = json_decode($multires_tmp,true);
                    $multires_config = $multires_array['multiRes'];
                    $multires_config['basePath'] = '../viewer/panoramas/multires/'.$room_pano;
                    $row['multires']=1;
                    $row['multires_config']=json_encode($multires_config);
                    $row['multires_dir']='../viewer/panoramas/multires/'.$room_pano;
                } else {
                    $row['multires']=0;
                    $row['multires_config']='';
                    $row['multires_dir']='';
                }
            } else {
                $row['multires']=0;
                $row['multires_config']='';
                $row['multires_dir']='';
            }
        }
    }
    return $row;
}

function get_room($id_room,$id_user) {
    global $mysqli;
    if(empty($id_room)) return false;
    $return = array();
    $query = "SELECT r.*,v.id_user,m.map,m.point_size,m.north_degree,v.id as id_virtualtour,v.enable_multires FROM svt_rooms as r
            JOIN svt_virtualtours as v ON r.id_virtualtour=v.id
            LEFT JOIN svt_maps as m ON m.id=r.id_map
            WHERE r.id = $id_room LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_user_vt = $row['id_user'];
            $id_virtual_tour = $row['id_virtualtour'];
            switch(get_user_role($id_user)) {
                case 'administrator';
                    break;
                case 'customer':
                    if($id_user!=$id_user_vt) return false;
                    break;
                case 'editor':
                    $query = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtual_tour;";
                    $result = $mysqli->query($query);
                    if($result) {
                        if($result->num_rows==0) {
                            return false;
                        }
                    }
                    break;
            }
            if($row['enable_multires']) {
                $s3_params = check_s3_tour_enabled($id_virtual_tour);
                $s3_enabled = false;
                $s3_url = "";
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    $s3_region = $s3_params['region'];
                    $s3_url = init_s3_client($s3_params);
                    if($s3_url!==false) {
                        $s3_enabled = true;
                    }
                }
                $room_pano = str_replace('.jpg','',$row['panorama_image']);
                if($s3_enabled) {
                    $multires_config_file = "s3://$s3_bucket_name/viewer/panoramas/multires/$room_pano/config.json";
                } else {
                    $multires_config_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$room_pano.DIRECTORY_SEPARATOR.'config.json';
                }
                if(file_exists($multires_config_file)) {
                    $multires_tmp = file_get_contents($multires_config_file);
                    $multires_array = json_decode($multires_tmp,true);
                    $multires_config = $multires_array['multiRes'];
                    if($s3_enabled) {
                        $multires_config['basePath'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                    } else {
                        $multires_config['basePath'] = '../viewer/panoramas/multires/'.$room_pano;
                    }
                    $row['multires']=1;
                    $row['multires_config']=json_encode($multires_config);
                    if($s3_enabled) {
                        $row['multires_dir'] = $s3_url.'viewer/panoramas/multires/'.$room_pano;
                    } else {
                        $row['multires_dir']='../viewer/panoramas/multires/'.$room_pano;
                    }
                } else {
                    $row['multires']=0;
                    $row['multires_config']='';
                    $row['multires_dir']='';
                }
            } else {
                $row['multires']=0;
                $row['multires_config']='';
                $row['multires_dir']='';
            }
            $return=$row;
        }
    }
    return $return;
}

function get_product($id_product,$id_user) {
    global $mysqli;
    $return = array();
    $query = "SELECT p.*,v.id_user FROM svt_products as p JOIN svt_virtualtours as v ON v.id=p.id_virtualtour WHERE p.id = $id_product LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_user_vt = $row['id_user'];
            $id_virtual_tour = $row['id_virtualtour'];
            switch(get_user_role($id_user)) {
                case 'administrator';
                    break;
                case 'customer':
                    if($id_user!=$id_user_vt) return false;
                    break;
                case 'editor':
                    $query = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtual_tour;";
                    $result = $mysqli->query($query);
                    if($result) {
                        if($result->num_rows==0) {
                            return false;
                        }
                    }
                    break;
            }
            $return=$row;
        }
    }
    return $return;
}

function get_video($id_video,$id_user,$id_virtualtour) {
    global $mysqli;
    if(isset($id_virtualtour)) {
        $return = array();
        $query = "SELECT p.*,v.id_user FROM svt_video_projects as p JOIN svt_virtualtours as v ON v.id=p.id_virtualtour WHERE p.id_virtualtour=$id_virtualtour AND p.id = $id_video LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row=$result->fetch_array(MYSQLI_ASSOC);
                $id_user_vt = $row['id_user'];
                $id_virtual_tour = $row['id_virtualtour'];
                switch(get_user_role($id_user)) {
                    case 'administrator';
                        break;
                    case 'customer':
                        if($id_user!=$id_user_vt) return false;
                        break;
                    case 'editor':
                        $query = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtual_tour;";
                        $result = $mysqli->query($query);
                        if($result) {
                            if($result->num_rows==0) {
                                return false;
                            }
                        }
                        break;
                }
                $return=$row;
            } else {
                return false;
            }
        }
        return $return;
    } else {
        return false;
    }
}

function check_map_type($id_virtualtour) {
    global $mysqli;
    $query = "SELECT id FROM svt_maps WHERE map_type='map' AND id_virtualtour=$id_virtualtour;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==0) {
            return false;
        } else {
            return true;
        }
    }
}

function get_map($id_map,$id_user) {
    global $mysqli;
    $return = array();
    $query = "SELECT m.*,v.id_user,v.id as id_virtualtour FROM svt_maps as m
            JOIN svt_virtualtours as v ON m.id_virtualtour=v.id
            WHERE m.id = $id_map LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_user_vt = $row['id_user'];
            $id_virtual_tour = $row['id_virtualtour'];
            switch(get_user_role($id_user)) {
                case 'administrator';
                    break;
                case 'customer':
                    if($id_user!=$id_user_vt) return false;
                    break;
                case 'editor':
                    $query = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtual_tour;";
                    $result = $mysqli->query($query);
                    if($result) {
                        if($result->num_rows==0) {
                            return false;
                        }
                    }
                    break;
            }
            $return=$row;
        }
    }
    return $return;
}

function get_virtual_tours_options($id_vt_sel) {
    global $mysqli;
    $return = "";
    $query = "SELECT id,name,author FROM svt_virtualtours ORDER BY name ASC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $name = $row['name'];
                $author = $row['author'];
                if($id==$id_vt_sel) {
                    $return .= "<option selected id='$id'>$name ($author)</option>";
                } else {
                    $return .= "<option id='$id'>$name ($author)</option>";
                }
            }
        }
    }
    return $return;
}

function get_rooms($id_virtualtour) {
    global $mysqli;
    $array_rooms = [];
    $query = "SELECT id,type,name,panorama_image FROM svt_rooms WHERE id_virtualtour=$id_virtualtour;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                array_push($array_rooms,$row);
            }
        }
    }
    return $array_rooms;
}

function get_rooms_3d_view($id_virtualtour,$s3_enabled,$s3_bucket_name) {
    global $mysqli;
    $array_rooms = [];
    $query = "SELECT id,name,panorama_image FROM svt_rooms WHERE id_virtualtour=$id_virtualtour;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                if($s3_enabled) {
                    $pano_lowres = "s3://$s3_bucket_name/viewer/panoramas/lowres/".$row['panorama_image'];
                } else {
                    $pano_lowres = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'panoramas'.DIRECTORY_SEPARATOR.'lowres'.DIRECTORY_SEPARATOR.$row['panorama_image'];
                }
                if(file_exists($pano_lowres)) {
                    $row['panorama_3d']='lowres/'.$row['panorama_image'];
                } else {
                    $row['panorama_3d']=$row['panorama_image'];
                }
                array_push($array_rooms,$row);
            }
        }
    }
    return $array_rooms;
}

function get_rooms_option($id_virtualtour,$id_room_sel,$skip_id_room) {
    global $mysqli;
    $options = "";
    $query = "SELECT id,name,panorama_image,type FROM svt_rooms WHERE id!=$skip_id_room AND id_virtualtour=$id_virtualtour;";
    $result = $mysqli->query($query);
    if($result) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $name = $row['name'];
            $panorama = $row['panorama_image'];
            $type = $row['type'];
            if($id_room_sel==$id) {
                $options .= "<option selected data-type='$type' data-panorama='$panorama' id='$id'>$name</option>";
            } else {
                $options .= "<option data-type='$type' data-panorama='$panorama' id='$id'>$name</option>";
            }
        }
    }
    return $options;
}

function get_rooms_count($id_virtualtour) {
    global $mysqli;
    $num_rooms = 0;
    $query = "SELECT COUNT(id) as num_rooms FROM svt_rooms WHERE id_virtualtour=$id_virtualtour;";
    $result = $mysqli->query($query);
    if($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $num_rooms = $row['num_rooms'];
    }
    return $num_rooms;
}

function get_categories() {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_categories;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $return[] = $row;
            }
        }
    }
    return $return;
}

function get_categories_option($id_vt) {
    global $mysqli;
    $options = "";
    $query = "SELECT c.id,c.name,scva.id_virtualtour FROM svt_categories as c
                LEFT JOIN svt_category_vt_assoc as scva on c.id = scva.id_category AND scva.id_virtualtour=$id_vt
                ORDER BY c.name;";
    $result = $mysqli->query($query);
    if($result) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $name = $row['name'];
            $assoc = $row['id_virtualtour'];
            if(!empty($assoc)) {
                $options .= "<option selected id='$id'>$name</option>";
            } else {
                $options .= "<option id='$id'>$name</option>";
            }
        }
    }
    return $options;
}

function get_plans_options($id_plan_sel) {
    global $mysqli;
    if($id_plan_sel==0) {
        $options = "<option selected id='0'>None</option>";
    } else {
        $options = "<option id='0'>None</option>";
    }
    $query = "SELECT * FROM svt_plans;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $name = $row['name'];
                if($id_plan_sel==$id) {
                    $options .= "<option selected id='$id'>$name</option>";
                } else {
                    $options .= "<option id='$id'>$name</option>";
                }
            }
        }
    }
    return $options;
}

function get_plans($id_user) {
    global $mysqli;
    $return = array();
    $query = "SELECT p.* FROM svt_plans as p WHERE p.visible=1 ORDER BY IF(p.frequency='recurring' || p.frequency='month_year',((p.price*12)/p.interval_count),p.price) ASC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $return[] = $row;
            }
        }
    }
    return $return;
}

function check_plan($object,$id_user,$id_virtualtour=null) {
    global $mysqli;
    $role = get_user_role($id_user);
    switch($object) {
        case 'virtual_tour':
            $count_virtual_tours = 0;
            $plan_virtual_tours = -1;
            $query = "SELECT COUNT(*) as num FROM svt_virtualtours WHERE id_user = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $count_virtual_tours = $row['num'];
                }
            }
            $query = "SELECT n_virtual_tours FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $plan_virtual_tours = $row['n_virtual_tours'];
                }
            }
            $can_create = 0;
            if($plan_virtual_tours<0) {
                $can_create = 1;
            } else {
                if($count_virtual_tours>=$plan_virtual_tours) {
                    $can_create = 0;
                } else {
                    $can_create = 1;
                }
            }
            return $can_create;
            break;
        case 'room':
            $count_rooms = 0;
            $count_rooms_tour = 0;
            $plan_rooms = -1;
            $plan_rooms_tour = -1;
            $query = "SELECT COUNT(*) as num FROM svt_rooms as r
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE v.id_user = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $count_rooms = $row['num'];
                }
            }
            $query = "SELECT COUNT(*) as num FROM svt_rooms as r
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE v.id_user = $id_user AND v.id=$id_virtualtour LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $count_rooms_tour = $row['num'];
                }
            }
            $query = "SELECT n_rooms,n_rooms_tour FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $plan_rooms = $row['n_rooms'];
                    $plan_rooms_tour = $row['n_rooms_tour'];
                }
            }
            //echo "count_rooms: $count_rooms, count_rooms_tour: $count_rooms_tour, plan_rooms: $plan_rooms, plan_rooms_tour: $plan_rooms_tour";
            $can_create = 0;
            if($plan_rooms<0) {
                if($plan_rooms_tour<0) {
                    $can_create = 1;
                } else {
                    if($count_rooms_tour>=$plan_rooms_tour) {
                        $can_create = 0;
                    } else {
                        $can_create = 1;
                    }
                }
            } else {
                if($count_rooms>=$plan_rooms) {
                    $can_create = 0;
                } else {
                    if($plan_rooms_tour<0) {
                        $can_create = 1;
                    } else {
                        if($count_rooms_tour>=$plan_rooms_tour) {
                            $can_create = 0;
                        } else {
                            $can_create = 1;
                        }
                    }
                }
            }
            return $can_create;
            break;
        case 'marker':
            $count_markers = 0;
            $plan_markers = -1;
            $query = "SELECT COUNT(*) as num FROM svt_markers as m
                        JOIN svt_rooms as r ON m.id_room = r.id
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE id_user = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $count_markers = $row['num'];
                }
            }
            $query = "SELECT n_markers FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $plan_markers = $row['n_markers'];
                }
            }
            $can_create = 0;
            if($plan_markers<0) {
                $can_create = 1;
            } else {
                if($count_markers>=$plan_markers) {
                    $can_create = 0;
                } else {
                    $can_create = 1;
                }
            }
            return $can_create;
            break;
        case 'poi':
            $count_pois = 0;
            $plan_pois = -1;
            $query = "SELECT COUNT(*) as num FROM svt_pois as m
                        JOIN svt_rooms as r ON m.id_room = r.id
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE id_user = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $count_pois = $row['num'];
                }
            }
            $query = "SELECT n_pois FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
            $result = $mysqli->query($query);
            if($result) {
                if($result->num_rows==1) {
                    $row=$result->fetch_array(MYSQLI_ASSOC);
                    $plan_pois = $row['n_pois'];
                }
            }
            $can_create = 0;
            if($plan_pois<0) {
                $can_create = 1;
            } else {
                if($count_pois>=$plan_pois) {
                    $can_create = 0;
                } else {
                    $can_create = 1;
                }
            }
            return $can_create;
            break;
        default:
            return 0;
            break;
    }
}

function get_plan_permission($id_user) {
    global $mysqli;
    $return = [];
    $query = "SELECT p.* FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $return=$row;
        }
    }
    $return['create_landing'] = (empty($return['create_landing'])) ? 0 : $return['create_landing'];
    $return['create_showcase'] = (empty($return['create_showcase'])) ? 0 : $return['create_showcase'];
    $return['create_globes'] = (empty($return['create_globes'])) ? 0 : $return['create_globes'];
    $return['enable_live_session'] = (empty($return['enable_live_session'])) ? 0 : $return['enable_live_session'];
    $return['enable_meeting'] = (empty($return['enable_meeting'])) ? 0 : $return['enable_meeting'];
    $return['create_gallery'] = (empty($return['create_gallery'])) ? 0 : $return['create_gallery'];
    $return['create_presentation'] = (empty($return['create_presentation'])) ? 0 : $return['create_presentation'];
    $return['enable_chat'] = (empty($return['enable_chat'])) ? 0 : $return['enable_chat'];
    $return['enable_voice_commands'] = (empty($return['enable_voice_commands'])) ? 0 : $return['enable_voice_commands'];
    $return['enable_share'] = (empty($return['enable_share'])) ? 0 : $return['enable_share'];
    $return['enable_device_orientation'] = (empty($return['enable_device_orientation'])) ? 0 : $return['enable_device_orientation'];
    $return['enable_webvr'] = (empty($return['enable_webvr'])) ? 0 : $return['enable_webvr'];
    $return['enable_logo'] = (empty($return['enable_logo'])) ? 0 : $return['enable_logo'];
    $return['enable_nadir_logo'] = (empty($return['enable_nadir_logo'])) ? 0 : $return['enable_nadir_logo'];
    $return['enable_song'] = (empty($return['enable_song'])) ? 0 : $return['enable_song'];
    $return['enable_forms'] = (empty($return['enable_forms'])) ? 0 : $return['enable_forms'];
    $return['enable_annotations'] = (empty($return['enable_annotations'])) ? 0 : $return['enable_annotations'];
    $return['enable_panorama_video'] = (empty($return['enable_panorama_video'])) ? 0 : $return['enable_panorama_video'];
    $return['enable_ai_room'] = (empty($return['enable_ai_room'])) ? 0 : $return['enable_ai_room'];
    $return['enable_rooms_multiple'] = (empty($return['enable_rooms_multiple'])) ? 0 : $return['enable_rooms_multiple'];
    $return['enable_rooms_protect'] = (empty($return['enable_rooms_protect'])) ? 0 : $return['enable_rooms_protect'];
    $return['enable_info_box'] = (empty($return['enable_info_box'])) ? 0 : $return['enable_info_box'];
    $return['enable_maps'] = (empty($return['enable_maps'])) ? 0 : $return['enable_maps'];
    $return['enable_icons_library'] = (empty($return['enable_icons_library'])) ? 0 : $return['enable_icons_library'];
    $return['enable_media_library'] = (empty($return['enable_media_library'])) ? 0 : $return['enable_media_library'];
    $return['enable_music_library'] = (empty($return['enable_music_library'])) ? 0 : $return['enable_music_library'];
    $return['enable_sound_library'] = (empty($return['enable_sound_library'])) ? 0 : $return['enable_sound_library'];
    $return['enable_password_tour'] = (empty($return['enable_password_tour'])) ? 0 : $return['enable_password_tour'];
    $return['enable_expiring_dates'] = (empty($return['enable_expiring_dates'])) ? 0 : $return['enable_expiring_dates'];
    $return['enable_statistics'] = (empty($return['enable_statistics'])) ? 0 : $return['enable_statistics'];
    $return['enable_auto_rotate'] = (empty($return['enable_auto_rotate'])) ? 0 : $return['enable_auto_rotate'];
    $return['enable_flyin'] = (empty($return['enable_flyin'])) ? 0 : $return['enable_flyin'];
    $return['enable_multires'] = (empty($return['enable_multires'])) ? 0 : $return['enable_multires'];
    $return['enable_export_vt'] = (empty($return['enable_export_vt'])) ? 0 : $return['enable_export_vt'];
    $return['enable_download_slideshow'] = (empty($return['enable_download_slideshow'])) ? 0 : $return['enable_download_slideshow'];
    $return['enable_shop'] = (empty($return['enable_shop'])) ? 0 : $return['enable_shop'];
    $return['enable_dollhouse'] = (empty($return['enable_dollhouse'])) ? 0 : $return['enable_dollhouse'];
    $return['enable_editor_ui'] = (empty($return['enable_editor_ui'])) ? 0 : $return['enable_editor_ui'];
    $return['enable_custom_html'] = (empty($return['enable_custom_html'])) ? 0 : $return['enable_custom_html'];
    $return['enable_metatag'] = (empty($return['enable_metatag'])) ? 0 : $return['enable_metatag'];
    $return['enable_loading_iv'] = (empty($return['enable_loading_iv'])) ? 0 : $return['enable_loading_iv'];
    $return['enable_context_info'] = (empty($return['enable_context_info'])) ? 0 : $return['enable_context_info'];
    $return['create_video360'] = (empty($return['create_video360'])) ? 0 : $return['create_video360'];
    $return['create_video_projects'] = (empty($return['create_video_projects'])) ? 0 : $return['create_video_projects'];
    $return['enable_comments'] = (empty($return['enable_comments'])) ? 0 : $return['enable_comments'];
    $return['enable_multilanguage'] = (empty($return['enable_multilanguage'])) ? 0 : $return['enable_multilanguage'];
    $return['enable_auto_translation'] = (empty($return['enable_auto_translation'])) ? 0 : $return['enable_auto_translation'];
    $return['enable_poweredby'] = (empty($return['enable_poweredby'])) ? 0 : $return['enable_poweredby'];
    return $return;
}

function check_plan_rooms_count($id_user,$id_virtualtour) {
    global $mysqli;
    $rooms_count_create = 0;
    $count_rooms = 0;
    $plan_rooms = -1;
    $plan_rooms_tour = -1;
    $count_rooms_tour = 0;
    $query = "SELECT COUNT(*) as num FROM svt_rooms as r
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE id_user = $id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $count_rooms = $row['num'];
        }
    }
    $query = "SELECT COUNT(*) as num FROM svt_rooms as r
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE v.id_user = $id_user AND v.id=$id_virtualtour LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $count_rooms_tour = $row['num'];
        }
    }
    $query = "SELECT n_rooms,n_rooms_tour FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $plan_rooms = $row['n_rooms'];
            $plan_rooms_tour = $row['n_rooms_tour'];
        }
    }
    if($plan_rooms<0) {
        if($plan_rooms_tour<0) {
            $rooms_count_create = -1;
        } else {
            $rooms_count_create = $plan_rooms_tour-$count_rooms_tour;
        }
    } else {
        if($plan_rooms_tour<0) {
            $rooms_count_create = $plan_rooms-$count_rooms;
        } else {
            $rooms_count_create = $plan_rooms_tour-$count_rooms_tour;
        }
    }
    return $rooms_count_create;
}

function check_plan_gallery_images_count($id_user,$id_virtualtour) {
    global $mysqli;
    $gallery_images_count_create = 0;
    $plan_gallery_images = -1;
    $count_gallery_images = 0;
    $query = "SELECT COUNT(*) as num FROM svt_gallery as r
                        JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                        WHERE v.id_user = $id_user AND v.id=$id_virtualtour LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $count_gallery_images = $row['num'];
        }
    }
    $query = "SELECT n_gallery_images FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $plan_gallery_images = $row['n_gallery_images'];
        }
    }
    if($plan_gallery_images<0) {
        $gallery_images_count_create = -1;
    } else {
        if($count_gallery_images>$plan_gallery_images) {
            $gallery_images_count_create = 0;
        } else {
            $gallery_images_count_create = $plan_gallery_images-$count_gallery_images;
        }
    }
    return $gallery_images_count_create;
}

function get_voice_commands() {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_voice_commands LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $return=$row;
        }
    }
    return $return;
}

function get_settings() {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_settings LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            if(empty($row['languages_enabled'])) {
                $row['languages_enabled']=array();
                $row['languages_enabled']['en_US']=1;
            } else {
                $row['languages_enabled']=json_decode($row['languages_enabled'],true);
            }
            if(empty($row['languages_viewer_enabled'])) {
                $row['languages_viewer_enabled']=array();
            } else {
                $row['languages_viewer_enabled']=json_decode($row['languages_viewer_enabled'],true);
            }
            $row['languages_count']=0;
            foreach ($row['languages_enabled'] as $lang) {
                if($lang==1) {
                    $row['languages_count']++;
                }
            }
            if($row['languages_count']==0) {
                $row['languages_enabled']=array();
                $row['languages_enabled']['en_US']=1;
                $row['languages_count']=1;
            }
            if(empty($row['contact_email'])) {
                $query_ce = "SELECT email FROM svt_users WHERE role='administrator' LIMIT 1;";
                $result_ce = $mysqli->query($query_ce);
                if($result_ce) {
                    if ($result_ce->num_rows == 1) {
                        $row_ce = $result_ce->fetch_array(MYSQLI_ASSOC);
                        $row['contact_email'] = $row_ce['email'];
                    }
                }
            }
            if(empty($row['ai_key'])) {
                $row['enable_ai_room']=false;
            }
            $return=$row;
        }
    }
    return $return;
}

function check_language_enabled($lang,$languages_enabled) {
    if(empty($languages_enabled) && $lang=='en_US') {
        return true;
    } else if(empty($languages_enabled[$lang])) {
        return false;
    } else if($languages_enabled[$lang]==1) {
        return true;
    } else {
        return false;
    }
}

function check_language_enabled_viewer($lang,$languages_enabled) {
    if(empty($languages_enabled)) {
        return true;
    } else if(empty($languages_enabled[$lang])) {
        return false;
    } else if($languages_enabled[$lang]==1) {
        return true;
    } else {
        return false;
    }
}

function check_language_enabled_vt($lang,$languages_enabled,$languages_enabled_vt) {
    if(empty($languages_enabled)) {
        if($languages_enabled_vt[$lang]==1) {
            return true;
        } else {
            return false;
        }
    } else if(empty($languages_enabled_vt[$lang])) {
        return false;
    } else if($languages_enabled_vt[$lang]==1) {
        return true;
    } else {
        return false;
    }
}

function get_library_icons($id_virtualtour,$p) {
    global $mysqli,$s3_enabled,$s3_bucket_name,$s3_url;
    $return = "";
    $query = "SELECT * FROM svt_icons WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $id_vt = $row['id_virtualtour'];
                $image = $row['image'];
                $tmp = explode('.',$image);
                $ext = strtolower(end($tmp));
                if($ext=='json') {
                    if($p!='icons') {
                        $return .= "<div onclick='select_icon_library(\"$p\",$id,\"$image\",\"\",\"$id_vt\");' class=\"lottie_icon_list\" data-id=\"$id\" data-id_vt='$id_vt' data-image=\"$image\" id=\"lottie_icon_$id\" style=\"display:inline-block;height:50px;width:50px;vertical-align:middle;cursor:pointer;\"></div>";
                    }
                } else {
                    if(!empty($image)) {
                        if($s3_enabled && !empty($id_vt)) {
                            $base64 = convert_image_to_base64("s3://$s3_bucket_name/viewer/icons/$image");
                            $url_icon = $s3_url."viewer/icons/$image";
                        } else {
                            $base64 = convert_image_to_base64(dirname(__FILE__).'/../viewer/icons/'.$image);
                            $url_icon = "../viewer/icons/$image";
                        }
                    } else {
                        $base64 = '';
                    }
                    $return .= "<img onclick='select_icon_library(\"$p\",$id,\"$image\",\"$base64\",\"$id_vt\");' style='display: inline-block;width:50px;padding:3px;cursor:pointer;' src='$url_icon' />";
                }
            }
        } else {
            $return = _("No icons in this library.");
        }
    }
    return $return;
}

function get_library_icons_v($id_virtualtour,$p) {
    global $mysqli,$s3_enabled,$s3_url;
    $return = "";
    $query = "SELECT * FROM svt_icons WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $id_vt = $row['id_virtualtour'];
                $image = $row['image'];
                $tmp = explode('.',$image);
                $ext = strtolower(end($tmp));
                if($ext=='json') {
                    $return .= "<div onclick='select_icon_library_v(\"$p\",$id,\"$image\",\"$id_vt\");' class=\"lottie_icon_".$p."_list\" data-id=\"$id\" data-id_vt='$id_vt' data-image=\"$image\" id=\"lottie_icon_".$p."_$id\" style=\"display:inline-block;height:50px;width:50px;vertical-align:middle;cursor:pointer;\"></div>";
                } else {
                    if($s3_enabled && !empty($id_vt)) {
                        $url_icon = $s3_url."viewer/icons/$image";
                    } else {
                        $url_icon = "../viewer/icons/$image";
                    }
                    $return .= "<img onclick='select_icon_library_v(\"$p\",$id,\"$image\",\"$id_vt\");' style='display: inline-block;width:50px;padding:3px;cursor:pointer;' src='$url_icon' />";
                }
            }
        } else {
            $return = _("No icons in this library.");
        }
    }
    return $return;
}

function get_option_exist_logo($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT logo FROM svt_virtualtours WHERE id_user=$id_user AND logo!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $logo = $row['logo'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$logo' id='$logo'>$logo</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$logo' id='$logo'>$logo</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_poweredby($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT poweredby_image FROM svt_virtualtours WHERE id_user=$id_user AND poweredby_image!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $logo = $row['poweredby_image'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$logo' id='$logo'>$logo</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$logo' id='$logo'>$logo</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_nadir_logo($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT nadir_logo FROM svt_virtualtours WHERE id_user=$id_user AND nadir_logo!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $nadir_logo = $row['nadir_logo'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$nadir_logo' id='$nadir_logo'>$nadir_logo</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$nadir_logo' id='$nadir_logo'>$nadir_logo</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_background_logo($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT background_image FROM svt_virtualtours WHERE id_user=$id_user AND background_image!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $background_image = $row['background_image'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$background_image' id='$background_image'>$background_image</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$background_image' id='$background_image'>$background_image</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_background_m_logo($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT background_image_mobile FROM svt_virtualtours WHERE id_user=$id_user AND background_image_mobile!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $background_image = $row['background_image_mobile'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$background_image' id='$background_image'>$background_image</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$background_image' id='$background_image'>$background_image</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_background_video($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT background_video FROM svt_virtualtours WHERE id_user=$id_user AND background_video!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $background_video = $row['background_video'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$background_video' id='$background_video'>$background_video</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$background_video' id='$background_video'>$background_video</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_background_m_video($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT background_video_mobile FROM svt_virtualtours WHERE id_user=$id_user AND background_video_mobile!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $background_video = $row['background_video_mobile'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$background_video' id='$background_video'>$background_video</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$background_video' id='$background_video'>$background_video</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_introd($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT intro_desktop FROM svt_virtualtours WHERE id_user=$id_user AND intro_desktop!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $intro_desktop = $row['intro_desktop'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$intro_desktop' id='$intro_desktop'>$intro_desktop</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$intro_desktop' id='$intro_desktop'>$intro_desktop</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_introm($id_user,$s3_enabled,$s3_url) {
    global $mysqli;
    $return = "";
    $query = "SELECT DISTINCT intro_mobile FROM svt_virtualtours WHERE id_user=$id_user AND intro_mobile!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $intro_mobile = $row['intro_mobile'];
                if($s3_enabled) {
                    $return .= "<option data-left='".$s3_url."viewer/content/$intro_mobile' id='$intro_mobile'>$intro_mobile</option>";
                } else {
                    $return .= "<option data-left='../viewer/content/$intro_mobile' id='$intro_mobile'>$intro_mobile</option>";
                }
            }
        }
    }
    return $return;
}

function get_option_exist_song($id_user,$id_virtualtour,$audio_sel=null) {
    global $mysqli;
    $s3_enabled = false;
    $s3_params = check_s3_tour_enabled($id_virtualtour);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
    $return = "";
    $array_audio = array();
    $query = "SELECT file as song FROM svt_music_library WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $song = $row['song'];
                $song_c = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $song);
                $song_c = html_entity_decode($song_c, ENT_COMPAT, 'UTF-8');
                $song_c = str_replace('&#x', '\u', $song_c);
                if($s3_enabled) {
                    $path_file = "s3://$s3_bucket_name/viewer/content/$song_c";
                } else {
                    $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$song_c;
                }
                if(file_exists($path_file)) {
                    if(!in_array($song,$array_audio)) {
                        array_push($array_audio,$song);
                    }
                }
            }
        }
    }
    if($id_user==null) {
        $query = "SELECT DISTINCT song FROM svt_virtualtours WHERE id=$id_virtualtour AND song!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    } else {
        $query = "SELECT DISTINCT song FROM svt_virtualtours WHERE id_user=$id_user AND song!='' AND aws_s3=".(($s3_enabled) ? 1 : 0).";";
    }
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $song = $row['song'];
                $song_c = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $song);
                $song_c = html_entity_decode($song_c, ENT_COMPAT, 'UTF-8');
                $song_c = str_replace('&#x', '\u', $song_c);
                if($s3_enabled) {
                    $path_file = "s3://$s3_bucket_name/viewer/content/$song_c";
                } else {
                    $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$song_c;
                }
                if(file_exists($path_file)) {
                    if(!in_array($song,$array_audio)) {
                        array_push($array_audio,$song);
                    }
                }
            }
        }
    }
    $query = "SELECT song FROM svt_rooms WHERE id_virtualtour=$id_virtualtour AND song!='';";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $song = $row['song'];
                $song_c = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $song);
                $song_c = html_entity_decode($song_c, ENT_COMPAT, 'UTF-8');
                $song_c = str_replace('&#x', '\u', $song_c);
                if($s3_enabled) {
                    $path_file = "s3://$s3_bucket_name/viewer/content/$song_c";
                } else {
                    $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$song_c;
                }
                if(file_exists($path_file)) {
                    if(!in_array($song,$array_audio)) {
                        array_push($array_audio,$song);
                    }
                }
            }
        }
    }
    $query = "SELECT content as song FROM svt_pois WHERE type IN ('audio') AND content LIKE 'content/%' AND id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $song = str_replace('content/','',$row['song']);
                $song_c = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $song);
                $song_c = html_entity_decode($song_c, ENT_COMPAT, 'UTF-8');
                $song_c = str_replace('&#x', '\u', $song_c);
                if($s3_enabled) {
                    $path_file = "s3://$s3_bucket_name/viewer/content/$song_c";
                } else {
                    $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$song_c;
                }
                if(file_exists($path_file)) {
                    if(!in_array($song,$array_audio)) {
                        array_push($array_audio,$song);
                    }
                }
            }
        }
    }
    foreach($array_audio as $song) {
        if($audio_sel!=null && $audio_sel==$song) {
            $return .= "<option selected id='$song'>".substr($song, 0, strrpos($song, '.')) ?: $song."</option>";
        } else {
            $return .= "<option id='$song'>".substr($song, 0, strrpos($song, '.')) ?: $song."</option>";
        }
    }
    return $return;
}

function get_option_exist_sound($id_user,$id_virtualtour,$sound_sel) {
    global $mysqli;
    $s3_enabled = false;
    $s3_params = check_s3_tour_enabled($id_virtualtour);
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $s3_url = init_s3_client($s3_params);
        if($s3_url!==false) {
            $s3_enabled = true;
        }
    }
    $return = "";
    $array_audio = array();
    $query = "SELECT file as song FROM svt_sound_library WHERE id_virtualtour=$id_virtualtour OR id_virtualtour IS NULL ORDER BY id DESC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $song = $row['song'];
                $song_c = preg_replace('/u([0-9a-fA-F]{4})/', '&#x$1;', $song);
                $song_c = html_entity_decode($song_c, ENT_COMPAT, 'UTF-8');
                $song_c = str_replace('&#x', '\u', $song_c);
                if($s3_enabled) {
                    $path_file = "s3://$s3_bucket_name/viewer/content/$song_c";
                } else {
                    $path_file = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'viewer'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.$song_c;
                }
                if(file_exists($path_file)) {
                    if(!in_array($song,$array_audio)) {
                        array_push($array_audio,$song);
                    }
                }
            }
        }
    }
    foreach($array_audio as $song) {
        if($sound_sel==$song) {
            $return .= "<option selected id='$song'>".substr($song, 0, strrpos($song, '.')) ?: $song."</option>";
        } else {
            $return .= "<option id='$song'>".substr($song, 0, strrpos($song, '.')) ?: $song."</option>";
        }
    }
    return $return;
}

function get_option_products($id_virtualtour) {
    global $mysqli;
    $return = "";
    $s3_params = check_s3_tour_enabled($id_virtualtour);
    $s3_enabled = false;
    $s3_url = "";
    $s3Client = null;
    if(!empty($s3_params)) {
        $s3_bucket_name = $s3_params['bucket'];
        $s3_region = $s3_params['region'];
        $s3Client = init_s3_client_no_wrapper($s3_params);
        if($s3Client!==null) {
            if(!empty($s3_params['custom_domain'])) {
                $s3_url = "https://".$s3_params['custom_domain']."/";
            } else {
                try {
                    $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.');
                } catch (Aws\Exception\S3Exception $e) {}
            }
            $s3_enabled = true;
        }
    }
    $query = "SELECT p.id,p.purchase_type,p.custom_currency,v.snipcart_currency as currency,p.name,p.price,MIN(spi.image) as image FROM svt_products as p
                LEFT JOIN svt_product_images spi on p.id = spi.id_product
                JOIN svt_virtualtours as v ON v.id=p.id_virtualtour
                WHERE p.id_virtualtour=$id_virtualtour
                GROUP BY p.id;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_product = $row['id'];
                $name = $row['name'];
                $price = $row['price'];
                if($row['purchase_type']!='cart' && !empty($row['custom_currency'])) {
                    $price = $row['custom_currency']." ".$price;
                } else {
                    $price = format_currency($row['currency'],$price);
                }
                $image = $row['image'];
                if(!empty($image)) {
                    if($s3_enabled) {
                        $image = $s3_url.'viewer/products/thumb/'.$image;
                    } else {
                            $image = '../viewer/products/thumb/'.$image;
                        }
                    $thumb = "<img style=\"width:20px;height:20px;border-radius:20px;vertical-align:sub;\" src=\"".$image."\">";
                } else {
                    $thumb = "";
                }
                $return .= "<option data-content='$thumb $name ($price)' id='$id_product' value='$id_product'>$name</option>";
            }
        }
    }
    return $return;
}

function get_option_products_wc($virtual_tour) {
    $return = "";
    $woocommerce_client = init_woocommerce_api($virtual_tour['woocommerce_store_url'],$virtual_tour['woocommerce_customer_key'],$virtual_tour['woocommerce_customer_secret']);
    $page = 1;
    $products = [];
    $all_products = [];
    do{
        try {
            $products = $woocommerce_client->get('products',array('per_page' => 100, 'page' => $page, 'orderby' => 'title', 'order' => 'asc'));
        } catch(Automattic\WooCommerce\HttpClient\HttpClientException $e) {}
        $all_products = array_merge($all_products,$products);
        $page++;
    } while (count($products) > 0);
    foreach ($all_products as $product) {
        $id_product = $product->id;
        $name = $product->name;
        $sku = $product->sku;
        $price = trim(strip_tags(preg_replace('/<del.*?>(.*?)<\/del>/', '',  $product->price_html)));
        if(isset($product->images[0])) {
            $thumb = "<img style=\"width:20px;height:20px;border-radius:20px;vertical-align:sub;\" src=\"".$product->images[0]->src."\">";
        } else {
            $thumb = "";
        }
        $return .= "<option data-content='$thumb $name ($price) <small class=\"text-muted\">$sku</small>' id='$id_product' value='$id_product'>$name</option>";
    }
    return $return;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function update_plans_expires_date($id_user) {
    global $mysqli;
    if(!empty($id_user)) {
        $where = " WHERE u.id=$id_user";
    } else {
        $where = "";
    }
    $query = "SELECT u.id,u.registration_date,u.expire_plan_date_manual,p.days,u.role FROM svt_users as u
                LEFT JOIN svt_plans as p ON p.id=u.id_plan $where";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id_user = $row['id'];
                $reg_date = $row['registration_date'];
                $expire_plan_date_manual = $row['expire_plan_date_manual'];
                $days = $row['days'];
                if(empty($days)) $days=0;
                $role = $row['role'];
                switch($role) {
                    case 'administrator':
                    case 'editor':
                        $mysqli->query("UPDATE svt_users SET expire_plan_date=NULL WHERE id=$id_user;");
                        break;
                    case 'customer':
                        if(!empty($expire_plan_date_manual)) {
                            $mysqli->query("UPDATE svt_users SET expire_plan_date=expire_plan_date_manual WHERE id=$id_user;");
                        } else {
                            if(empty($row['id_subscription_stripe'])) {
                                if($days<0) {
                                    $mysqli->query("UPDATE svt_users SET expire_plan_date=NULL WHERE id=$id_user;");
                                } else {
                                    $exp_date = date('Y-m-d H:i:s', strtotime($reg_date. " + $days days"));
                                    $mysqli->query("UPDATE svt_users SET expire_plan_date='$exp_date' WHERE id=$id_user;");
                                }
                            }
                        }
                        break;
                }
            }
        }
    }
}

function get_user_stats($id_user) {
    global $mysqli;
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
    $stats['total_visitors']=0;
    $query = "SELECT COUNT(*) as num FROM svt_virtualtours WHERE id_user = $id_user LIMIT 1";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $num = $row['num'];
            $stats['count_virtual_tours'] = $num;
        }
    }
    $query = "SELECT COUNT(*) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_rooms as r
                JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                WHERE id_user = $id_user LIMIT 1;";
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
    $query = "SELECT COUNT(*) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_markers as m
                JOIN svt_rooms as r ON m.id_room = r.id
                JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                WHERE id_user = $id_user LIMIT 1;";
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
    $query = "SELECT COUNT(*) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_pois as m
                JOIN svt_rooms as r ON m.id_room = r.id
                JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                WHERE id_user = $id_user LIMIT 1;";
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
    $query = "SELECT COUNT(*) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_measures as m
                JOIN svt_rooms as r ON m.id_room = r.id
                JOIN svt_virtualtours as v ON v.id = r.id_virtualtour
                WHERE id_user = $id_user LIMIT 1;";
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
    $query = "SELECT COUNT(m.id) as num,COUNT(DISTINCT v.id) as num_vt FROM svt_video_projects as m
            JOIN svt_virtualtours as v ON v.id = m.id_virtualtour
            WHERE id_user = $id_user LIMIT 1;";
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
    $query = "SELECT v.id FROM svt_virtualtours as v WHERE id_user = $id_user;";
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
    $total_visitors = 0;
    $query = "SELECT v.id,v.name,COUNT(a.id) as count FROM svt_virtualtours as v
            LEFT JOIN svt_access_log as a ON v.id = a.id_virtualtour
            WHERE v.id_user = $id_user
            GROUP BY v.id
            ORDER BY count DESC;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $count = $row['count'];
                $total_visitors = $total_visitors + $count;
            }
            $stats['total_visitors'] = $total_visitors;
        }
    }
    return $stats;
}

function get_plan($id) {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_plans WHERE id=$id LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $return=$row;
        }
    }
    return $return;
}

function get_id_plan_stripe($id_product_stripe) {
    global $mysqli;
    $id_plan = "";
    $query = "SELECT id FROM svt_plans WHERE id_product_stripe='$id_product_stripe' LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_plan=$row['id'];
        }
    }
    return $id_plan;
}

function get_name_plan_stripe($id_product_stripe) {
    global $mysqli;
    $name_plan = "";
    $query = "SELECT name FROM svt_plans WHERE id_product_stripe='$id_product_stripe' LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $name_plan=$row['name'];
        }
    }
    return $name_plan;
}

function get_next_prev_room_id($id_room,$id_virtualtour) {
    global $mysqli;
    if(isset($id_virtualtour)) {
        $array_rooms = array();
        $query = "SELECT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour ORDER BY priority;";
        $result = $mysqli->query($query);
        if($result) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                array_push($array_rooms,$id);
            }
        }
        $index = array_search($id_room,$array_rooms);
        if($index!==false) {
            $len = count($array_rooms);
            $prev_id = $array_rooms[($index+$len-1)%$len];
            $next_id = $array_rooms[($index+1)%$len];
            return [$next_id,$prev_id];
        } else {
            return [0,0];
        }
    } else {
        return [0,0];
    }
}

function get_assign_virtualtours($id_user) {
    global $mysqli;
    $return = "";
    $query = "SELECT id,name,author FROM svt_virtualtours ORDER BY name;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $name = $row['name'];
                $author = $row['author'];
                $query_c = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id LIMIT 1";
                $result_c = $mysqli->query($query_c);
                if($result_c) {
                    if ($result_c->num_rows == 1) {
                        $return .= "<div class='col-md-4 mb-1'><label><input id='$id' checked type='checkbox'> $name ($author)</label></div>";
                    } else {
                        $return .= "<div class='col-md-4 mb-1'><label><input id='$id' type='checkbox'> $name ($author)</label></div>";
                    }
                }
            }
        }
    }
    return $return;
}

function get_editor_permissions($id_user,$id_virtualtour) {
    global $mysqli;
    $query = "SELECT * FROM svt_assign_virtualtours WHERE id_user=$id_user AND id_virtualtour=$id_virtualtour LIMIT 1;";
    $result = $mysqli->query($query);
    $return = array();
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $return=$row;
        }
    }
    return $return;
}

function get_showcase_virtualtours($id_user,$id_showcase) {
    global $mysqli;
    $return = "";
    $where = "";
    switch(get_user_role($id_user)) {
        case 'administrator';
            $where = "";
            break;
        case 'customer':
            $where = "WHERE v.id_user=$id_user";
            break;
        case 'editor':
            return '';
            break;
    }
    $query = "SELECT v.id,v.name,v.author,s.id_virtualtour as id_s,s.type_viewer,s.priority FROM svt_virtualtours AS v
                LEFT JOIN svt_showcase_list AS s ON s.id_virtualtour=v.id AND s.id_showcase=$id_showcase
                $where
                ORDER BY IFNULL(s.priority,9999),v.date_created;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $name = $row['name'];
                $author = $row['author'];
                $id_s = $row['id_s'];
                $type_viewer = $row['type_viewer'];
                $priority = $row['priority'];
                $select_type = "<select style='margin-left:5px;font-size:12px;vertical-align:text-top' id='t_$id'>";
                switch($type_viewer) {
                    case 'viewer':
                        $select_type .= "<option selected id='viewer'>V</option><option id='landing'>L</option>";
                        break;
                    case 'landing':
                        $select_type .= "<option id='viewer'>V</option><option selected id='landing'>L</option>";
                        break;
                    default:
                        $select_type .= "<option selected id='viewer'>V</option><option id='landing'>L</option>";
                        break;
                }
                $select_type .= "</select>";
                $priority_input = "<i style='display:none;cursor:pointer;' class='fas move_vt fa-arrows-alt'></i> <input style='display:none;width:30px;font-size:12px;vertical-align:text-bottom' id='p_$id' class='vt_priority' data-id-vt='$id' readonly type='text' value='$priority' />";
                if (!empty($id_s)) {
                    $return .= "<div class='col-md-4 mb-1 vt_block'><label><input onchange='fix_vt_priority();' id='$id' checked type='checkbox'>$select_type $priority_input $name ($author)</label></div>";
                } else {
                    $return .= "<div class='col-md-4 mb-1 vt_block'><label><input onchange='fix_vt_priority();' id='$id' type='checkbox'>$select_type $priority_input $name ($author)</label></div>";
                }
            }
        }
    }
    return $return;
}

function get_advertisement_virtualtours($id_advertisement) {
    global $mysqli;
    $return = "";
    $query = "SELECT v.id,v.name,s.id_virtualtour as id_s FROM svt_virtualtours AS v
                LEFT JOIN svt_assign_advertisements AS s ON s.id_virtualtour=v.id AND s.id_advertisement=$id_advertisement
                WHERE v.external=0
                ORDER BY v.date_created;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $name = $row['name'];
                $id_s = $row['id_s'];
                if (!empty($id_s)) {
                    $return .= "<div class='col-md-4 mb-1'><label><input id='$id' checked type='checkbox'> $name</label></div>";
                } else {
                    $return .= "<div class='col-md-4 mb-1'><label><input id='$id' type='checkbox'> $name</label></div>";
                }
            }
        }
    }
    return $return;
}

function get_advertisement_plans($id_advertisement) {
    global $mysqli;
    $return = "";
    $id_plans = array();
    $query = "SELECT id_plans FROM svt_advertisements WHERE id=$id_advertisement LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $id_plans = explode(",",$row['id_plans']);
        }
    }
    $query = "SELECT id,name FROM svt_plans;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows>0) {
            while($row=$result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['id'];
                $name = $row['name'];
                if (in_array($id,$id_plans)) {
                    $return .= "<div class='col-md-4 mb-1'><label><input id='$id' checked type='checkbox'> $name</label></div>";
                } else {
                    $return .= "<div class='col-md-4 mb-1'><label><input id='$id' type='checkbox'> $name</label></div>";
                }
            }
        }
    }
    return $return;
}

function get_users($id_user_sel) {
    global $mysqli;
    $options = "";
    $count = 0;
    $query = "SELECT id,username,role FROM svt_users WHERE role IN('customer','administrator') ORDER BY username;";
    $result = $mysqli->query($query);
    if($result) {
        $count = $result->num_rows;
        if ($count > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_user = $row['id'];
                $username = $row['username'];
                $role = $row['role'];
                if($role=='administrator') $username=$username." ("._("administrator").")";
                if($id_user==$id_user_sel) {
                    $options .= "<option selected id='$id_user'>$username</option>";
                } else {
                    $options .= "<option id='$id_user'>$username</option>";
                }
            }
        }
    }
    return array("options"=>$options,"count"=>$count);
}

function get_users_delete($id_user) {
    global $mysqli;
    $options = "";
    $count = 0;
    $query = "SELECT id,username,role FROM svt_users WHERE role IN('customer','administrator') AND id!=$id_user ORDER BY username;";
    $result = $mysqli->query($query);
    if($result) {
        $count = $result->num_rows;
        if ($count > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_user = $row['id'];
                $username = $row['username'];
                $role = $row['role'];
                if($role=='administrator') $username=$username." ("._("administrator").")";
                $options .= "<option id='$id_user'>$username</option>";
            }
        }
    }
    return array("options"=>$options,"count"=>$count);
}

function check_profile_to_complete($id_user) {
    global $mysqli;
    $settings = get_settings();
    $query = "SELECT first_name,last_name,company,tax_id,street,city,province,postal_code,country,tel FROM svt_users WHERE id=$id_user;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if($settings['first_name_enable'] && $settings['first_name_mandatory'] && empty($row['first_name'])) {
                return true;
            }
            if($settings['last_name_enable'] && $settings['last_name_mandatory'] && empty($row['last_name'])) {
                return true;
            }
            if($settings['company_enable'] && $settings['company_mandatory'] && empty($row['company'])) {
                return true;
            }
            if($settings['tax_id_enable'] && $settings['tax_id_mandatory'] && empty($row['tax_id'])) {
                return true;
            }
            if($settings['street_enable'] && $settings['street_mandatory'] && empty($row['street'])) {
                return true;
            }
            if($settings['city_enable'] && $settings['city_mandatory'] && empty($row['city'])) {
                return true;
            }
            if($settings['province_enable'] && $settings['province_mandatory'] && empty($row['province'])) {
                return true;
            }
            if($settings['postal_code_enable'] && $settings['postal_code_mandatory'] && empty($row['postal_code'])) {
                return true;
            }
            if($settings['country_enable'] && $settings['country_mandatory'] && empty($row['country'])) {
                return true;
            }
            if($settings['tel_enable'] && $settings['tel_mandatory'] && empty($row['tel'])) {
                return true;
            }
        }
    }
    return false;
}

function get_presets($id_virtualtour,$type) {
    global $mysqli;
    $return = array();
    $query = "SELECT * FROM svt_presets WHERE id_virtualtour=$id_virtualtour AND type='$type';";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $return[]=$row;
            }
        }
    }
    return $return;
}

function get_presets_editor_ui($id_user) {
    global $mysqli;
    $return = array();
    if(get_user_role($id_user)=='administrator') {
        $query = "SELECT id,id_user,name,public FROM svt_editor_ui_presets ORDER BY name;";
    } else {
        $query = "SELECT id,id_user,name,public FROM svt_editor_ui_presets WHERE id_user=$id_user OR public=1 ORDER BY name;";
    }
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $return[]=$row;
            }
        }
    }
    return $return;
}

$total_local_size = 0;
$total_s3_size = 0;
function get_disk_size_stat($id_user,$id_virtualtour) {
    global $mysqli,$total_local_size,$total_s3_size;
    $total_size = 0;
    if($id_virtualtour==null) {
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
    $s3Client = null;
    $query = "SELECT v.id,v.logo,v.nadir_logo,v.song,v.background_image,v.background_video,v.background_image_mobile,v.background_video_mobile,v.intro_desktop,v.intro_mobile,v.presentation_video,v.dollhouse_glb FROM svt_virtualtours as v $where;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_vt = $row['id'];
                $s3_params = check_s3_tour_enabled($id_vt);
                $s3_enabled = false;
                $s3_bucket_name = "";
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    if($s3Client==null) {
                        $s3Client = init_s3_client_no_wrapper($s3_params);
                        if($s3Client==null) {
                            $s3_enabled = false;
                        } else {
                            $s3_enabled = true;
                        }
                    } else {
                        $s3_enabled = true;
                    }
                }
                if($s3_enabled) {
                    $path = "viewer/";
                } else {
                    $path = realpath(dirname(__FILE__) . '/..').DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR;
                }
                $logo = $row['logo'];
                $nadir_logo = $row['nadir_logo'];
                $song = $row['song'];
                $background_image = $row['background_image'];
                $background_video = $row['background_video'];
                $background_image_mobile = $row['background_image_mobile'];
                $background_video_mobile = $row['background_video_mobile'];
                $intro_desktop = $row['intro_desktop'];
                $intro_mobile = $row['intro_mobile'];
                $presentation_video = $row['presentation_video'];
                $dollhouse_glb = $row['dollhouse_glb'];
                if(!empty($logo)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$logo);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$logo);
                }
                if(!empty($nadir_logo)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$nadir_logo);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$nadir_logo);
                }
                if(!empty($song)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$song);
                }
                if(!empty($background_image)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_image);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$background_image);
                }
                if(!empty($background_image_mobile)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_image_mobile);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$background_image_mobile);
                }
                if(!empty($background_video)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_video);
                }
                if(!empty($background_video_mobile)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_video_mobile);
                }
                if(!empty($intro_desktop)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$intro_desktop);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$intro_desktop);
                }
                if(!empty($intro_mobile)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$intro_mobile);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$intro_mobile);
                }
                if(!empty($presentation_video)) {
                    $presentation_video = str_replace("content/","",$presentation_video);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$presentation_video);
                }
                if(!empty($dollhouse_glb)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$dollhouse_glb);
                }
                $query_a = "SELECT a.image FROM svt_advertisements as a JOIN svt_assign_advertisements as aa ON aa.id_advertisement=a.id WHERE aa.id_virtualtour=$id_vt;";
                $result_a = $mysqli->query($query_a);
                if($result_a) {
                    if ($result_a->num_rows > 0) {
                        while ($row_a = $result_a->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_a['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$image);
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_i = "SELECT image FROM svt_icons WHERE id_virtualtour=$id_vt;";
                $result_i = $mysqli->query($query_i);
                if($result_i) {
                    if ($result_i->num_rows > 0) {
                        while ($row_i = $result_i->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_i['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'icons'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_g = "SELECT image FROM svt_gallery WHERE id_virtualtour=$id_vt;";
                $result_g = $mysqli->query($query_g);
                if($result_g) {
                    if ($result_g->num_rows > 0) {
                        while ($row_g = $result_g->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_g['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$image);
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$id_vt.'_slideshow.mp4');
                if($s3_enabled) {
                    $list_tmp = $s3Client->listObjects([
                        'Bucket' => $s3_bucket_name,
                        'Prefix' => 'video360/'.$id_vt.'/',
                    ]);
                    foreach ($list_tmp['Contents'] as $object_tmp) {
                        $total_size += $object_tmp['Size'];
                        $total_s3_size += $object_tmp['Size'];
                    }
                } else {
                    if(file_exists($path.'..'.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_vt)) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.'..'.DIRECTORY_SEPARATOR.'video360'.DIRECTORY_SEPARATOR.$id_vt), RecursiveIteratorIterator::LEAVES_ONLY);
                        foreach ($files as $file) {
                            if (!$file->isDir()) {
                                $total_size += $file->getSize();
                                $total_local_size += $file->getSize();
                            }
                        }
                    }
                }
                $query_ml = "SELECT file FROM svt_media_library WHERE id_virtualtour=$id_vt;";
                $result_ml = $mysqli->query($query_ml);
                if($result_ml) {
                    if ($result_ml->num_rows > 0) {
                        while ($row_ml = $result_ml->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_ml['file'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'media'.DIRECTORY_SEPARATOR.$file);
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'media'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$file);
                        }
                    }
                }
                $query_mu = "SELECT file FROM svt_music_library WHERE id_virtualtour=$id_vt;";
                $result_mu = $mysqli->query($query_mu);
                if($result_mu) {
                    if ($result_mu->num_rows > 0) {
                        while ($row_mu = $result_mu->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_mu['file'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$file);
                        }
                    }
                }
                $query_so = "SELECT file FROM svt_sound_library WHERE id_virtualtour=$id_vt;";
                $result_so = $mysqli->query($query_so);
                if($result_so) {
                    if ($result_so->num_rows > 0) {
                        while ($row_so = $result_so->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_so['file'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$file);
                        }
                    }
                }
                $query_m = "SELECT map FROM svt_maps WHERE map!='' AND map IS NOT NULL AND id_virtualtour=$id_vt;";
                $result_m = $mysqli->query($query_m);
                if($result_m) {
                    if ($result_m->num_rows > 0) {
                        while ($row_m = $result_m->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_m['map'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'maps'.DIRECTORY_SEPARATOR.$image);
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'maps'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_p = "SELECT pi.image FROM svt_product_images as pi LEFT JOIN svt_products as p ON p.id=pi.id_product WHERE p.id_virtualtour=$id_vt;";
                $result_p = $mysqli->query($query_p);
                if($result_p) {
                    if ($result_p->num_rows > 0) {
                        while ($row_p = $result_p->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_p['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'products'.DIRECTORY_SEPARATOR.$image);
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'products'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_vp = "SELECT id,id_virtualtour FROM svt_video_projects as vp WHERE vp.id_virtualtour IN (SELECT v.id FROM svt_virtualtours as v $where)";
                $result_vp = $mysqli->query($query_vp);
                if($result_vp) {
                    if ($result_vp->num_rows > 0) {
                        while ($row_vp = $result_vp->fetch_array(MYSQLI_ASSOC)) {
                            $id_video_project = $row_vp['id'];
                            $id_vt_vp = $row_vp['id_virtualtour'];
                            if($s3_enabled) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,'video/'.$id_vt_vp."_".$id_video_project.".mp4");
                            } else {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'..'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.$id_vt_vp."_".$id_video_project.".mp4");
                            }
                        }
                    }
                }
                $query_vp = "SELECT vps.file,vp.id_virtualtour FROM svt_video_project_slides as vps JOIN svt_video_projects as vp on vps.id_video_project = vp.id WHERE vp.id_virtualtour IN (SELECT v.id FROM svt_virtualtours as v $where)";
                $result_vp = $mysqli->query($query_vp);
                if($result_vp) {
                    if ($result_vp->num_rows > 0) {
                        while ($row_vp = $result_vp->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_vp['file'];
                            $id_vt_vp = $row_vp['id_virtualtour'];
                            if($s3_enabled) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,'video/assets/'.$id_vt_vp.'/'.$file);
                            } else {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'..'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_vt_vp.DIRECTORY_SEPARATOR.$file);
                            }
                        }
                    }
                }
                $query_r = "SELECT id,panorama_image,panorama_video,panorama_json,thumb_image FROM svt_rooms WHERE id_virtualtour=$id_vt;";
                $result_r = $mysqli->query($query_r);
                if($result_r) {
                    if ($result_r->num_rows > 0) {
                        while ($row_r = $result_r->fetch_array(MYSQLI_ASSOC)) {
                            $id_room = $row_r['id'];
                            $panorama_image = $row_r['panorama_image'];
                            $panorama_video = $row_r['panorama_video'];
                            $panorama_json = $row_r['panorama_json'];
                            $thumb_image = $row_r['thumb_image'];
                            if(!empty($thumb_image)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'thumb_custom'.DIRECTORY_SEPARATOR.$thumb_image);
                            }
                            if(!empty($panorama_image)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.$panorama_image);
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'lowres'.DIRECTORY_SEPARATOR.$panorama_image);
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'mobile'.DIRECTORY_SEPARATOR.$panorama_image);
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'preview'.DIRECTORY_SEPARATOR.$panorama_image);
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$panorama_image);
                                $panorama_name = str_replace('.jpg','',$panorama_image);
                                if($s3_enabled) {
                                    $list_tmp = $s3Client->listObjects([
                                        'Bucket' => $s3_bucket_name,
                                        'Prefix' => 'viewer/panoramas/multires/'.$panorama_name.'/',
                                    ]);
                                    foreach ($list_tmp['Contents'] as $object_tmp) {
                                        $total_size += $object_tmp['Size'];
                                        $total_s3_size += $object_tmp['Size'];
                                    }
                                } else {
                                    if(file_exists($path.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$panorama_name)) {
                                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$panorama_name), RecursiveIteratorIterator::LEAVES_ONLY);
                                        foreach ($files as $file) {
                                            if (!$file->isDir()) {
                                                $total_size += $file->getSize();
                                                $total_local_size += $file->getSize();
                                            }
                                        }
                                    }
                                }
                            }
                            if(!empty($panorama_video)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'videos'.DIRECTORY_SEPARATOR.$panorama_video);
                            }
                            if(!empty($panorama_json)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.$panorama_json);
                            }
                            $query_ra = "SELECT panorama_image FROM svt_rooms_alt WHERE id_room=$id_room;";
                            $result_ra = $mysqli->query($query_ra);
                            if($result_ra) {
                                if ($result_ra->num_rows > 0) {
                                    while ($row_ra = $result_ra->fetch_array(MYSQLI_ASSOC)) {
                                        $panorama_image = $row_ra['panorama_image'];
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.$panorama_image);
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'lowres'.DIRECTORY_SEPARATOR.$panorama_image);
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'mobile'.DIRECTORY_SEPARATOR.$panorama_image);
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'preview'.DIRECTORY_SEPARATOR.$panorama_image);
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$panorama_image);
                                        $panorama_name = str_replace('.jpg','',$panorama_image);
                                        if($s3_enabled) {
                                            $list_tmp = $s3Client->listObjects([
                                                'Bucket' => $s3_bucket_name,
                                                'Prefix' => 'viewer/panoramas/multires/'.$panorama_name.'/',
                                            ]);
                                            foreach ($list_tmp['Contents'] as $object_tmp) {
                                                $total_size += $object_tmp['Size'];
                                                $total_s3_size += $object_tmp['Size'];
                                            }
                                        } else {
                                            if(file_exists($path.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$panorama_name)) {
                                                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$panorama_name), RecursiveIteratorIterator::LEAVES_ONLY);
                                                foreach ($files as $file) {
                                                    if (!$file->isDir()) {
                                                        $total_size += $file->getSize();
                                                        $total_local_size += $file->getSize();
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT content,type FROM svt_pois WHERE id_room=$id_room AND type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf','pointclouds') AND (content LIKE '%content/%' OR content LIKE '%pointclouds/%');";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['type']) {
                                            case 'object3d':
                                                if (strpos($row_poi['content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                            case 'pointclouds':
                                                $path_pc = dirname($row_poi['content']);
                                                if($s3_enabled) {
                                                    $list_tmp = $s3Client->listObjects([
                                                        'Bucket' => $s3_bucket_name,
                                                        'Prefix' => 'viewer/'.$path_pc.'/',
                                                    ]);
                                                    foreach ($list_tmp['Contents'] as $object_tmp) {
                                                        $total_size += $object_tmp['Size'];
                                                        $total_s3_size += $object_tmp['Size'];
                                                    }
                                                } else {
                                                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.$path_pc), RecursiveIteratorIterator::LEAVES_ONLY);
                                                    foreach ($files as $file) {
                                                        if (!$file->isDir()) {
                                                            $total_size += $file->getSize();
                                                            $total_local_size += $file->getSize();
                                                        }
                                                    }
                                                }
                                                break;
                                            default:
                                                $content = basename($row_poi['content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT pl.content,p.type FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room=$id_room AND p.type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf','pointclouds') AND (pl.content LIKE '%content/%' OR pl.content LIKE '%pointclouds/%');";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['type']) {
                                            case 'object3d':
                                                if (strpos($row_poi['content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                            case 'pointclouds':
                                                $path_pc = dirname($row_poi['content']);
                                                if($s3_enabled) {
                                                    $list_tmp = $s3Client->listObjects([
                                                        'Bucket' => $s3_bucket_name,
                                                        'Prefix' => 'viewer/'.$path_pc.'/',
                                                    ]);
                                                    foreach ($list_tmp['Contents'] as $object_tmp) {
                                                        $total_size += $object_tmp['Size'];
                                                        $total_s3_size += $object_tmp['Size'];
                                                    }
                                                } else {
                                                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.$path_pc), RecursiveIteratorIterator::LEAVES_ONLY);
                                                    foreach ($files as $file) {
                                                        if (!$file->isDir()) {
                                                            $total_size += $file->getSize();
                                                            $total_local_size += $file->getSize();
                                                        }
                                                    }
                                                }
                                                break;
                                            default:
                                                $content = basename($row_poi['content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT embed_type,embed_content FROM svt_pois WHERE id_room=$id_room AND embed_content LIKE '%content/%';";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['embed_type']) {
                                            case 'image':
                                            case 'video':
                                            case 'video_chroma':
                                            case 'object3d':
                                                $content = basename($row_poi['embed_content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                            case 'video_transparent':
                                                if (strpos($row_poi['embed_content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['embed_content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['embed_content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT p.embed_type,pl.embed_content FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room=$id_room AND pl.embed_content LIKE '%content/%';";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['embed_type']) {
                                            case 'image':
                                            case 'video':
                                            case 'video_chroma':
                                            case 'object3d':
                                                $content = basename($row_poi['embed_content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                            case 'video_transparent':
                                                if (strpos($row_poi['embed_content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['embed_content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['embed_content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_pg = "SELECT id FROM svt_pois WHERE (type='gallery' OR type='object360' OR embed_type='gallery') AND id_room=$id_room;";
                            $result_pg = $mysqli->query($query_pg);
                            if($result_pg) {
                                if ($result_pg->num_rows > 0) {
                                    while ($row_pg = $result_pg->fetch_array(MYSQLI_ASSOC)) {
                                        $id_poi = $row_pg['id'];
                                        $query_eg = "SELECT image FROM svt_poi_gallery WHERE id_poi=$id_poi;";
                                        $result_eg = $mysqli->query($query_eg);
                                        if($result_eg) {
                                            if ($result_eg->num_rows > 0) {
                                                while ($row_eg = $result_eg->fetch_array(MYSQLI_ASSOC)) {
                                                    $image = $row_eg['image'];
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$image);
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                                                }
                                            }
                                        }
                                        $query_eg = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi;";
                                        $result_eg = $mysqli->query($query_eg);
                                        if($result_eg) {
                                            if ($result_eg->num_rows > 0) {
                                                while ($row_eg = $result_eg->fetch_array(MYSQLI_ASSOC)) {
                                                    $image = $row_eg['image'];
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$image);
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                                                }
                                            }
                                        }
                                        $query_eg = "SELECT image FROM svt_poi_objects360 WHERE id_poi=$id_poi;";
                                        $result_eg = $mysqli->query($query_eg);
                                        if($result_eg) {
                                            if ($result_eg->num_rows > 0) {
                                                while ($row_eg = $result_eg->fetch_array(MYSQLI_ASSOC)) {
                                                    $image = $row_eg['image'];
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'objects360'.DIRECTORY_SEPARATOR.$image);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return array(formatBytes($total_size),formatBytes($total_local_size),formatBytes($total_s3_size));
}

function get_disk_size_original($id_user,$id_virtualtour) {
    global $mysqli,$total_local_size,$total_s3_size;
    $total_size = 0;
    if($id_virtualtour==null) {
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
    $s3Client = null;
    $query = "SELECT v.id FROM svt_virtualtours as v $where;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_vt = $row['id'];
                $s3_params = check_s3_tour_enabled($id_vt);
                $s3_enabled = false;
                $s3_bucket_name = "";
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    if($s3Client==null) {
                        $s3Client = init_s3_client_no_wrapper($s3_params);
                        if($s3Client==null) {
                            $s3_enabled = false;
                        } else {
                            $s3_enabled = true;
                        }
                    } else {
                        $s3_enabled = true;
                    }
                }
                if($s3_enabled) {
                    $path = "viewer/";
                } else {
                    $path = realpath(dirname(__FILE__) . '/..').DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR;
                }
                $query_r = "SELECT panorama_image FROM svt_rooms WHERE id_virtualtour=$id_vt;";
                $result_r = $mysqli->query($query_r);
                if($result_r) {
                    if ($result_r->num_rows > 0) {
                        while ($row_r = $result_r->fetch_array(MYSQLI_ASSOC)) {
                            $panorama_image = $row_r['panorama_image'];
                            if(!empty($panorama_image)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
                            }
                        }
                    }
                }
            }
        }
    }
    return array(formatBytes($total_size),formatBytes($total_local_size),formatBytes($total_s3_size));
}

function get_disk_size_room($id_user,$id_virtualtour,$id_room) {
    global $mysqli,$total_local_size,$total_s3_size;
    $total_size = 0;
    if($id_virtualtour==null) {
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
    $s3Client = null;
    $query = "SELECT v.id FROM svt_virtualtours as v $where;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_vt = $row['id'];
                $s3_params = check_s3_tour_enabled($id_vt);
                $s3_enabled = false;
                $s3_bucket_name = "";
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    if($s3Client==null) {
                        $s3Client = init_s3_client_no_wrapper($s3_params);
                        if($s3Client==null) {
                            $s3_enabled = false;
                        } else {
                            $s3_enabled = true;
                        }
                    } else {
                        $s3_enabled = true;
                    }
                }
                if($s3_enabled) {
                    $path = "viewer/";
                } else {
                    $path = realpath(dirname(__FILE__) . '/..').DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR;
                }
                $query_r = "SELECT panorama_image,panorama_video,panorama_json,type FROM svt_rooms WHERE id=$id_room;";
                $result_r = $mysqli->query($query_r);
                if($result_r) {
                    if ($result_r->num_rows > 0) {
                        while ($row_r = $result_r->fetch_array(MYSQLI_ASSOC)) {
                            $panorama_image = $row_r['panorama_image'];
                            $panorama_video = $row_r['panorama_video'];
                            $panorama_json = $row_r['panorama_json'];
                            switch($row_r['type']) {
                                case 'image':
                                    if(!empty($panorama_image)) {
                                        $total_size = get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
                                    }
                                    $total_size_o = $total_size;
                                    $total_local_size_o = $total_local_size;
                                    $total_s3_size_o = $total_s3_size;
                                    $total_local_size = 0;
                                    $total_s3_size = 0;
                                    $total_size = 0;
                                    if(!empty($panorama_image)) {
                                        $total_size = get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.$panorama_image);
                                    }
                                    $total_size_c = $total_size;
                                    $total_local_size_c = $total_local_size;
                                    $total_s3_size_c = $total_s3_size;
                                    $total_local_size = 0;
                                    $total_s3_size = 0;
                                    $total_size = 0;
                                    $panorama_name = str_replace('.jpg','',$panorama_image);
                                    if($s3_enabled) {
                                        $list_tmp = $s3Client->listObjects([
                                            'Bucket' => $s3_bucket_name,
                                            'Prefix' => 'viewer/panoramas/multires/'.$panorama_name.'/',
                                        ]);
                                        foreach ($list_tmp['Contents'] as $object_tmp) {
                                            $total_size += $object_tmp['Size'];
                                            $total_s3_size += $object_tmp['Size'];
                                        }
                                    } else {
                                        if(file_exists($path.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$panorama_name)) {
                                            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.'panoramas'.DIRECTORY_SEPARATOR.'multires'.DIRECTORY_SEPARATOR.$panorama_name), RecursiveIteratorIterator::LEAVES_ONLY);
                                            foreach ($files as $file) {
                                                if (!$file->isDir()) {
                                                    $total_size += $file->getSize();
                                                    $total_local_size += $file->getSize();
                                                }
                                            }
                                        }
                                    }
                                    $total_size_m = $total_size;
                                    $total_local_size_m = $total_local_size;
                                    $total_s3_size_m = $total_s3_size;
                                    $total_local_size = 0;
                                    $total_s3_size = 0;
                                    $total_size = 0;
                                    break;
                                case 'video':
                                    if(!empty($panorama_video)) {
                                        $total_size = get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'videos'.DIRECTORY_SEPARATOR.$panorama_video);
                                    }
                                    $total_size_o = $total_size;
                                    $total_local_size_o = $total_local_size;
                                    $total_s3_size_o = $total_s3_size;
                                    $total_local_size = 0;
                                    $total_s3_size = 0;
                                    $total_size = 0;
                                    $total_size_c = 0;
                                    $total_local_size_c = 0;
                                    $total_s3_size_c = 0;
                                    $total_size_m = 0;
                                    $total_local_size_m = 0;
                                    $total_s3_size_m = 0;
                                    break;
                                case 'lottie':
                                    if(!empty($panorama_json)) {
                                        $total_size = get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.$panorama_json);
                                    }
                                    $total_size_o = $total_size;
                                    $total_local_size_o = $total_local_size;
                                    $total_s3_size_o = $total_s3_size;
                                    $total_local_size = 0;
                                    $total_s3_size = 0;
                                    $total_size = 0;
                                    $total_size_c = 0;
                                    $total_local_size_c = 0;
                                    $total_s3_size_c = 0;
                                    $total_size_m = 0;
                                    $total_local_size_m = 0;
                                    $total_s3_size_m = 0;
                                    break;
                                case 'hls':
                                    $total_size_o = 0;
                                    $total_local_size_o = 0;
                                    $total_s3_size_o = 0;
                                    $total_local_size = 0;
                                    $total_s3_size = 0;
                                    $total_size = 0;
                                    $total_size_c = 0;
                                    $total_local_size_c = 0;
                                    $total_s3_size_c = 0;
                                    $total_size_m = 0;
                                    $total_local_size_m = 0;
                                    $total_s3_size_m = 0;
                                    break;
                            }

                            $total_size_t = $total_size_o + $total_size_c + $total_size_m;
                            $total_local_size_t = $total_local_size_o + $total_local_size_c + $total_local_size_m;
                            $total_s3_size_t = $total_s3_size_o + $total_s3_size_c + $total_s3_size_m;
                        }
                    }
                }
            }
        }
    }
    return array(formatBytes($total_size_o),formatBytes($total_local_size_o),formatBytes($total_s3_size_o),formatBytes($total_size_c),formatBytes($total_local_size_c),formatBytes($total_s3_size_c),formatBytes($total_size_m),formatBytes($total_local_size_m),formatBytes($total_s3_size_m),formatBytes($total_size_t),formatBytes($total_local_size_t),formatBytes($total_s3_size_t));
}

function get_disk_size_stat_uploaded($id_user) {
    global $mysqli,$total_local_size,$total_s3_size;
    $total_size = 0;
    $s3Client = null;
    $query = "SELECT v.id,v.logo,v.nadir_logo,v.song,v.background_image,v.background_video,v.background_image_mobile,v.background_video_mobile,v.intro_desktop,v.intro_mobile,v.presentation_video,v.dollhouse_glb FROM svt_virtualtours as v WHERE v.id_user=$id_user;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_vt = $row['id'];
                $s3_params = check_s3_tour_enabled($id_vt);
                $s3_enabled = false;
                $s3_bucket_name = "";
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    if($s3Client==null) {
                        $s3Client = init_s3_client_no_wrapper($s3_params);
                        if($s3Client==null) {
                            $s3_enabled = false;
                        } else {
                            $s3_enabled = true;
                        }
                    } else {
                        $s3_enabled = true;
                    }
                }
                if($s3_enabled) {
                    $path = "viewer/";
                } else {
                    $path = realpath(dirname(__FILE__) . '/..').DIRECTORY_SEPARATOR."viewer".DIRECTORY_SEPARATOR;
                }
                $logo = $row['logo'];
                $nadir_logo = $row['nadir_logo'];
                $song = $row['song'];
                $background_image = $row['background_image'];
                $background_video = $row['background_video'];
                $background_image_mobile = $row['background_image_mobile'];
                $background_video_mobile = $row['background_video_mobile'];
                $intro_desktop = $row['intro_desktop'];
                $intro_mobile = $row['intro_mobile'];
                $presentation_video = $row['presentation_video'];
                $dollhouse_glb = $row['dollhouse_glb'];
                if(!empty($logo)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$logo);
                }
                if(!empty($nadir_logo)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$nadir_logo);
                }
                if(!empty($song)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$song);
                }
                if(!empty($background_image)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_image);
                }
                if(!empty($background_video)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_video);
                }
                if(!empty($background_image_mobile)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_image_mobile);
                }
                if(!empty($background_video_mobile)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$background_video_mobile);
                }
                if(!empty($intro_desktop)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$intro_desktop);
                }
                if(!empty($intro_mobile)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$intro_mobile);
                }
                if(!empty($presentation_video)) {
                    $presentation_video = str_replace("content/","",$presentation_video);
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$presentation_video);
                }
                if(!empty($dollhouse_glb)) {
                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$dollhouse_glb);
                }
                $query_a = "SELECT a.image FROM svt_advertisements as a JOIN svt_assign_advertisements as aa ON aa.id_advertisement=a.id WHERE aa.id_virtualtour=$id_vt;";
                $result_a = $mysqli->query($query_a);
                if($result_a) {
                    if ($result_a->num_rows > 0) {
                        while ($row_a = $result_a->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_a['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_i = "SELECT image FROM svt_icons WHERE id_virtualtour=$id_vt;";
                $result_i = $mysqli->query($query_i);
                if($result_i) {
                    if ($result_i->num_rows > 0) {
                        while ($row_i = $result_i->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_i['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'icons'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_g = "SELECT image FROM svt_gallery WHERE id_virtualtour=$id_vt;";
                $result_g = $mysqli->query($query_g);
                if($result_g) {
                    if ($result_g->num_rows > 0) {
                        while ($row_g = $result_g->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_g['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_ml = "SELECT file FROM svt_media_library WHERE id_virtualtour=$id_vt;";
                $result_ml = $mysqli->query($query_ml);
                if($result_ml) {
                    if ($result_ml->num_rows > 0) {
                        while ($row_ml = $result_ml->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_ml['file'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'media'.DIRECTORY_SEPARATOR.$file);
                        }
                    }
                }
                $query_mu = "SELECT file FROM svt_music_library WHERE id_virtualtour=$id_vt;";
                $result_mu = $mysqli->query($query_mu);
                if($result_mu) {
                    if ($result_mu->num_rows > 0) {
                        while ($row_mu = $result_mu->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_mu['file'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$file);
                        }
                    }
                }
                $query_so = "SELECT file FROM svt_sound_library WHERE id_virtualtour=$id_vt;";
                $result_so = $mysqli->query($query_so);
                if($result_so) {
                    if ($result_so->num_rows > 0) {
                        while ($row_so = $result_so->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_so['file'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$file);
                        }
                    }
                }
                $query_m = "SELECT map FROM svt_maps WHERE map!='' AND map IS NOT NULL AND id_virtualtour=$id_vt;";
                $result_m = $mysqli->query($query_m);
                if($result_m) {
                    if ($result_m->num_rows > 0) {
                        while ($row_m = $result_m->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_m['map'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'maps'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_p = "SELECT pi.image FROM svt_product_images as pi LEFT JOIN svt_products as p ON p.id=pi.id_product WHERE p.id_virtualtour=$id_vt;";
                $result_p = $mysqli->query($query_p);
                if($result_p) {
                    if ($result_p->num_rows > 0) {
                        while ($row_p = $result_p->fetch_array(MYSQLI_ASSOC)) {
                            $image = $row_p['image'];
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'products'.DIRECTORY_SEPARATOR.$image);
                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'products'.DIRECTORY_SEPARATOR.'thumb'.DIRECTORY_SEPARATOR.$image);
                        }
                    }
                }
                $query_vp = "SELECT vps.file,vp.id_virtualtour FROM svt_video_project_slides as vps JOIN svt_video_projects as vp on vps.id_video_project = vp.id WHERE vp.id_virtualtour=$id_vt;";
                $result_vp = $mysqli->query($query_vp);
                if($result_vp) {
                    if ($result_vp->num_rows > 0) {
                        while ($row_vp = $result_vp->fetch_array(MYSQLI_ASSOC)) {
                            $file = $row_vp['file'];
                            $id_vt_vp = $row_vp['id_virtualtour'];
                            if($s3_enabled) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,'video/assets/'.$id_vt_vp.'/'.$file);
                            } else {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'..'.DIRECTORY_SEPARATOR.'video'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$id_vt_vp.DIRECTORY_SEPARATOR.$file);
                            }
                        }
                    }
                }
                $query_r = "SELECT id,panorama_image,panorama_video,panorama_json,thumb_image FROM svt_rooms WHERE id_virtualtour=$id_vt;";
                $result_r = $mysqli->query($query_r);
                if($result_r) {
                    if ($result_r->num_rows > 0) {
                        while ($row_r = $result_r->fetch_array(MYSQLI_ASSOC)) {
                            $id_room = $row_r['id'];
                            $panorama_image = $row_r['panorama_image'];
                            $panorama_video = $row_r['panorama_video'];
                            $panorama_json = $row_r['panorama_json'];
                            $thumb_image = $row_r['thumb_image'];
                            if(!empty($thumb_image)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'thumb_custom'.DIRECTORY_SEPARATOR.$thumb_image);
                            }
                            if(!empty($panorama_image)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
                            }
                            if(!empty($panorama_video)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'videos'.DIRECTORY_SEPARATOR.$panorama_video);
                            }
                            if(!empty($panorama_json)) {
                                $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.$panorama_json);
                            }
                            $query_ra = "SELECT panorama_image FROM svt_rooms_alt WHERE id_room=$id_room;";
                            $result_ra = $mysqli->query($query_ra);
                            if($result_ra) {
                                if ($result_ra->num_rows > 0) {
                                    while ($row_ra = $result_ra->fetch_array(MYSQLI_ASSOC)) {
                                        $panorama_image = $row_ra['panorama_image'];
                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'panoramas'.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$panorama_image);
                                    }
                                }
                            }
                            $query_poi = "SELECT content,type FROM svt_pois WHERE id_room=$id_room AND type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf','pointclouds') AND (content LIKE '%content/%' OR content LIKE '%pointclouds/%');";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['type']) {
                                            case 'object3d':
                                                if (strpos($row_poi['content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                            case 'pointclouds':
                                                $path_pc = dirname($row_poi['content']);
                                                if($s3_enabled) {
                                                    $list_tmp = $s3Client->listObjects([
                                                        'Bucket' => $s3_bucket_name,
                                                        'Prefix' => 'viewer/'.$path_pc.'/',
                                                    ]);
                                                    foreach ($list_tmp['Contents'] as $object_tmp) {
                                                        $total_size += $object_tmp['Size'];
                                                        $total_s3_size += $object_tmp['Size'];
                                                    }
                                                } else {
                                                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.$path_pc), RecursiveIteratorIterator::LEAVES_ONLY);
                                                    foreach ($files as $file) {
                                                        if (!$file->isDir()) {
                                                            $total_size += $file->getSize();
                                                            $total_local_size += $file->getSize();
                                                        }
                                                    }
                                                }
                                            default:
                                                $content = basename($row_poi['content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT pl.content,p.type FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room=$id_room AND p.type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf','pointclouds') AND (pl.content LIKE '%content/%' OR pl.content LIKE '%pointclouds/%');";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['type']) {
                                            case 'object3d':
                                                if (strpos($row_poi['content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                            case 'pointclouds':
                                                $path_pc = dirname($row_poi['content']);
                                                if($s3_enabled) {
                                                    $list_tmp = $s3Client->listObjects([
                                                        'Bucket' => $s3_bucket_name,
                                                        'Prefix' => 'viewer/'.$path_pc.'/',
                                                    ]);
                                                    foreach ($list_tmp['Contents'] as $object_tmp) {
                                                        $total_size += $object_tmp['Size'];
                                                        $total_s3_size += $object_tmp['Size'];
                                                    }
                                                } else {
                                                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.$path_pc), RecursiveIteratorIterator::LEAVES_ONLY);
                                                    foreach ($files as $file) {
                                                        if (!$file->isDir()) {
                                                            $total_size += $file->getSize();
                                                            $total_local_size += $file->getSize();
                                                        }
                                                    }
                                                }
                                            default:
                                                $content = basename($row_poi['content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT embed_type,embed_content FROM svt_pois WHERE id_room=$id_room AND embed_content LIKE '%content/%';";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['embed_type']) {
                                            case 'image':
                                            case 'video':
                                            case 'video_chroma':
                                            case 'object3d':
                                                $content = basename($row_poi['embed_content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                            case 'video_transparent':
                                                if (strpos($row_poi['embed_content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['embed_content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['embed_content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_poi = "SELECT p.embed_type,pl.embed_content FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room=$id_room AND pl.embed_content LIKE '%content/%';";
                            $result_poi = $mysqli->query($query_poi);
                            if($result_poi) {
                                if ($result_poi->num_rows > 0) {
                                    while ($row_poi = $result_poi->fetch_array(MYSQLI_ASSOC)) {
                                        switch ($row_poi['embed_type']) {
                                            case 'image':
                                            case 'video':
                                            case 'video_chroma':
                                            case 'object3d':
                                                $content = basename($row_poi['embed_content']);
                                                if($content!='') {
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                }
                                                break;
                                            case 'video_transparent':
                                                if (strpos($row_poi['embed_content'], ',') !== false) {
                                                    $array_contents = explode(",",$row['embed_content']);
                                                    foreach ($array_contents as $content) {
                                                        $content = basename($content);
                                                        if($content!='') {
                                                            $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                        }
                                                    }
                                                } else {
                                                    $content = basename($row_poi['embed_content']);
                                                    if($content!='') {
                                                        $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'content'.DIRECTORY_SEPARATOR.$content);
                                                    }
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                            $query_pg = "SELECT id FROM svt_pois WHERE (type='gallery' OR type='object360' OR embed_type='gallery') AND id_room=$id_room;";
                            $result_pg = $mysqli->query($query_pg);
                            if($result_pg) {
                                if ($result_pg->num_rows > 0) {
                                    while ($row_pg = $result_pg->fetch_array(MYSQLI_ASSOC)) {
                                        $id_poi = $row_pg['id'];
                                        $query_eg = "SELECT image FROM svt_poi_gallery WHERE id_poi=$id_poi;";
                                        $result_eg = $mysqli->query($query_eg);
                                        if($result_eg) {
                                            if ($result_eg->num_rows > 0) {
                                                while ($row_eg = $result_eg->fetch_array(MYSQLI_ASSOC)) {
                                                    $image = $row_eg['image'];
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$image);
                                                }
                                            }
                                        }
                                        $query_eg = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi=$id_poi;";
                                        $result_eg = $mysqli->query($query_eg);
                                        if($result_eg) {
                                            if ($result_eg->num_rows > 0) {
                                                while ($row_eg = $result_eg->fetch_array(MYSQLI_ASSOC)) {
                                                    $image = $row_eg['image'];
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'gallery'.DIRECTORY_SEPARATOR.$image);
                                                }
                                            }
                                        }
                                        $query_eg = "SELECT image FROM svt_poi_objects360 WHERE id_poi=$id_poi;";
                                        $result_eg = $mysqli->query($query_eg);
                                        if($result_eg) {
                                            if ($result_eg->num_rows > 0) {
                                                while ($row_eg = $result_eg->fetch_array(MYSQLI_ASSOC)) {
                                                    $image = $row_eg['image'];
                                                    $total_size += get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path.'objects360'.DIRECTORY_SEPARATOR.$image);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return array(formatBytes($total_size),formatBytes($total_local_size),formatBytes($total_s3_size),isa_convert_bytes_to_specified($total_size,'M',2));
}

$array_files_size = array();
$array_files_size_s3 = array();
function get_file_size($s3_enabled,$s3_bucket_name,$s3Client,$path) {
    global $array_files_size,$array_files_size_s3,$total_s3_size,$total_local_size;
    if (strpos($path, 'content/') !== false) {

    } else {
        //return 0;
    }
    if(substr($path, -1) === '/') {
        return 0;
    }
    if($s3_enabled) {
        try {
            $file_exist = $s3Client->doesObjectExist($s3_bucket_name,$path);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $file_exist = false;
        }
        if($file_exist) {
            if(!in_array($path,$array_files_size_s3)) {
                array_push($array_files_size_s3, $path);
                try {
                    $file = $s3Client->headObject([
                        'Bucket' => $s3_bucket_name,
                        'Key'    => $path,
                    ]);
                    $size = $file['ContentLength'];
                    $total_s3_size += $size;
                } catch (\Aws\S3\Exception\S3Exception $e) {
                    $size = 0;
                }
                return $size;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    } else {
        if(file_exists($path)) {
            if(!in_array($path,$array_files_size)) {
                array_push($array_files_size,$path);
                //file_put_contents(realpath(dirname(__FILE__))."/log_storage.txt",$path.PHP_EOL,FILE_APPEND);
                try {
                    $size = filesize($path);
                    $total_local_size += $size;
                } catch (Exception $e) {
                    $size = 0;
                }
                return $size;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}

function update_user_space_storage($id_user,$force=false) {
    global $mysqli;
    if(get_user_info($id_user)['max_storage_space']!=-1 || $force) {
        $size = get_disk_size_stat_uploaded($id_user)[3];
        if(is_numeric($size)) {
            $mysqli->query("UPDATE svt_users SET storage_space=$size WHERE id=$id_user;");
        }
    }
}

function generate_multires($force_update,$id_virtualtour) {
    $settings = get_settings();
    if(isEnabled('shell_exec')) {
        try {
            $command = 'command -v php 2>&1';
            $output = shell_exec($command);
            if(empty($output)) $output = PHP_BINARY;
            $path_php = trim($output);
            $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
            $path = realpath(dirname(__FILE__) . '/..');
            if($force_update) {
                switch($settings['multires']) {
                    case 'local':
                        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_multires.php 1 $id_virtualtour > /dev/null &";
                        break;
                    case 'cloud':
                        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_multires_cloud.php 1 $id_virtualtour > /dev/null &";
                        break;
                }
            } else {
                switch($settings['multires']) {
                    case 'local':
                        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_multires.php 0 $id_virtualtour > /dev/null &";
                        break;
                    case 'cloud':
                        $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_multires_cloud.php 0 $id_virtualtour > /dev/null &";
                        break;
                }
            }
            shell_exec($command);
        } catch (Exception $e) {}
    } else {
        if($settings['multires']=='cloud') {
            if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
            $url = $protocol ."://". $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
            $url = str_replace('backend/ajax','services/generate_multires_cloud.php',$url);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, array(
                "curl" => 1,
                "id_vt" => $id_virtualtour,
                "force_update" => ($force_update) ? 1 : 0,
                "id_user" => $_SESSION['id_user']
            ));
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 100);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_exec($curl);
            curl_close($curl);
        }
    }
}

function generate_favicons($what,$id) {
    $currentPath = $_SERVER['PHP_SELF'];
    $pathInfo = pathinfo($currentPath);
    $hostName = $_SERVER['HTTP_HOST'];
    if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
    $url = $protocol."://".$hostName.$pathInfo['dirname'];
    if(isEnabled('shell_exec')) {
        try {
            $command = 'command -v php 2>&1';
            $output = shell_exec($command);
            if(empty($output)) $output = PHP_BINARY;
            $path_php = trim($output);
            $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
            $path = realpath(dirname(__FILE__) . '/..');
            $command = $path_php." ".$path.DIRECTORY_SEPARATOR."services".DIRECTORY_SEPARATOR."generate_favicons.php $url $what $id > /dev/null &";
            shell_exec($command);
        } catch (Exception $e) {
            return '../services/generate_favicons.php?id='.$id.'&what='.$what.'&url='.$url;
        }
        return 1;
    } else {
        return '../services/generate_favicons.php?id='.$id.'&what='.$what.'&url='.$url;
    }
}

function convert_image_to_base64($path) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = "";
    if(!empty($data)) {
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    } else {
        $data = file_get_contents_curl($path);
        if(!empty($data)) {
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    }
    return $base64;
}

function set_language($language,$domain) {
    session_start();
    if (function_exists('gettext')) {
        if(!isset($_SESSION['lang']) || $_SESSION['lang']=='') {
            $_SESSION['lang']=$language;
        } else {
            $language=$_SESSION['lang'];
        }
        if(defined('LC_MESSAGES')) {
            $result = setlocale(LC_MESSAGES, $language);
            if(!$result) {
                setlocale(LC_MESSAGES, $language.'.UTF-8');
            }
            if (function_exists('putenv')) {
                $result = putenv('LC_MESSAGES='.$language);
                if(!$result) {
                    putenv('LC_MESSAGES='.$language.'.UTF-8');
                }
            }
        } else {
            if (function_exists('putenv')) {
                $result = putenv('LC_ALL=' . $language);
                if (!$result) {
                    putenv('LC_ALL=' . $language . '.UTF-8');
                }
            }
        }
        $result = bindtextdomain($domain, "../locale");
        if(!$result) {
            $domain = "default";
            bindtextdomain($domain, "../locale");
        }
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
    } else {
        function _($a) {
            return $a;
        }
    }
}

function set_language_force($language,$domain) {
    if(defined('LC_MESSAGES')) {
        $result = setlocale(LC_MESSAGES, $language);
        if(!$result) {
            setlocale(LC_MESSAGES, $language.'.UTF-8');
        }
        if (function_exists('putenv')) {
            $result = putenv('LC_MESSAGES=' . $language);
            if (!$result) {
                putenv('LC_MESSAGES=' . $language . '.UTF-8');
            }
        }
    } else {
        if (function_exists('putenv')) {
            $result = putenv('LC_ALL=' . $language);
            if (!$result) {
                putenv('LC_ALL=' . $language . '.UTF-8');
            }
        }
    }
    $result = bindtextdomain($domain, "../locale");
    if(!$result) {
        $domain = "default";
        bindtextdomain($domain, "../locale");
    }
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);
}

function print_favicons_backend($logo) {
    $path = '';
    $version = time();
    if (file_exists(dirname(__FILE__).'/../favicons/custom/favicon.ico')) {
        $path = 'custom/';
        $version = preg_replace('/[^0-9]/', '', $logo);
    }
    return '<link rel="apple-touch-icon" sizes="180x180" href="../favicons/'.$path.'apple-touch-icon.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicons/'.$path.'favicon-32x32.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicons/'.$path.'favicon-16x16.png?v='.$version.'">
    <link rel="manifest" href="../favicons/'.$path.'site.webmanifest?v='.$version.'">
    <link rel="mask-icon" href="../favicons/'.$path.'safari-pinned-tab.svg?v='.$version.'" color="#ffffff">
    <link rel="shortcut icon" href="../favicons/'.$path.'favicon.ico?v='.$version.'">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="../favicons/'.$path.'browserconfig.xml?v='.$version.'">
    <meta name="theme-color" content="#ffffff">';
}

function dateDiffInDays($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $difference = $datetime1->diff($datetime2);
    $diff = $difference->format("%r%a");
    return $diff;
}

function _GetMaxAllowedUploadSize(){
    $Sizes = array();
    $Sizes[] = ini_get('upload_max_filesize');
    $Sizes[] = ini_get('post_max_size');
    $Sizes[] = ini_get('memory_limit');
    for($x=0;$x<count($Sizes);$x++){
        $Last = strtolower($Sizes[$x][strlen($Sizes[$x])-1]);
        if($Last == 'k'){
            $Sizes[$x] *= 1024;
        } elseif($Last == 'm'){
            $Sizes[$x] *= 1024;
            $Sizes[$x] *= 1024;
        } elseif($Last == 'g'){
            $Sizes[$x] *= 1024;
            $Sizes[$x] *= 1024;
            $Sizes[$x] *= 1024;
        } elseif($Last == 't'){
            $Sizes[$x] *= 1024;
            $Sizes[$x] *= 1024;
            $Sizes[$x] *= 1024;
            $Sizes[$x] *= 1024;
        }
    }
    return isa_convert_bytes_to_specified_d(min($Sizes),'M',0);
}

function format_currency($currency,$price) {
    switch ($currency) {
        case 'AED':
            $currency = "AED ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'ILS':
            $currency = "₪ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'RUB':
            $currency = "₽ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'AUD':
            $currency = "A$ ";
            $price = $currency.number_format($price,2,'.',' ');
            break;
        case 'BRL':
            $currency = "R$ ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'CAD':
            $currency = "C$ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'CHF':
            $currency = "₣ ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'CNY':
            $currency = "¥ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'CZK':
            $currency = "Kč ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'CLP':
            $currency = "$ ";
            $price = $currency.number_format($price,0,'.',',');
            break;
        case 'JPY':
            $currency = "¥ ";
            $price = $currency.number_format($price,0,'.',',');
            break;
        case 'EUR':
            $currency = "€ ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'GBP':
            $currency = "£ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'IDR':
            $currency = "Rp ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'INR':
            $currency = "Rs ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'PLN':
            $currency = "zł ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'SEK':
            $currency = "kr ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'TRY':
            $currency = "₺ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'TJS':
            $currency = "SM ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'USD':
        case 'ARS':
            $currency = "$ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'HKD':
            $currency = "HK$ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'SGD':
            $currency = "S$ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'NGN':
            $currency = "₦ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'MXN':
            $currency = "Mex$ ";
            $price = $currency.number_format($price,2,',','.');
            break;
        case 'MYR':
            $currency = "RM ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'PHP':
            $currency = "₱ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'THB':
            $currency = "฿ ";
            $price = $currency.number_format($price,2,'.',',');
            break;
        case 'RWF':
            $currency = "FRw ";
            $price = $currency.number_format($price,0,'',',');
            break;
        case 'VND':
            $currency = "₫ ";
            $price = $currency.number_format($price,0,'.',',');
            break;
        case 'PYG':
            $currency = "₲ ";
            $price = $currency.number_format($price,0,'.',',');
            break;
        case 'ZAR':
            $currency = "R ";
            $price = $currency.number_format($price,2,',',' ');
            break;
        default:
            $price = $currency." ".$price;
            break;
    }
    return $price;
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1000, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function isa_convert_bytes_to_specified($bytes, $to, $decimal_places = 1) {
    $formulas = array(
        'K' => number_format($bytes / 1000, $decimal_places,'.',''),
        'M' => number_format($bytes / 1000000, $decimal_places,'.',''),
        'G' => number_format($bytes / 1000000000, $decimal_places,'.','')
    );
    return isset($formulas[$to]) ? $formulas[$to] : 0;
}

function isa_convert_bytes_to_specified_d($bytes, $to, $decimal_places = 1) {
    $formulas = array(
        'K' => number_format($bytes / 1024, $decimal_places,'.',''),
        'M' => number_format($bytes / 1048576, $decimal_places,'.',''),
        'G' => number_format($bytes / 1099511627776, $decimal_places,'.','')
    );
    return isset($formulas[$to]) ? $formulas[$to] : 0;
}

function xor_obfuscator($string) {
    if (!strlen($string)) {
        return $string;
    }
    $key = ord($string[0]);
    $new = pack("C",
        ($key & 0xf0)
        |
        (
            ($key & 0x0f)
            ^
            (($key >> 4) & 0x0f)
        )
    );
    for ($c=1;$c<strlen($string);$c++) {
        $new .= pack("C",ord($string[$c]) ^ $key);
    }
    return base64_encode($new);
}

function xor_deobfuscator($string) {
    $string = base64_decode($string);
    if (!strlen($string)) {
        return $string;
    }
    $keys = unpack("C*",$string);
    $key = $keys[1];
    $key = ($key & 0xf0)
        |
        (
            ($key & 0x0f)
            ^
            (
                ($key >> 4)
                &
                0x0f
            )
        );
    $new = chr($key);
    for ($c=2;$c<=count($keys);$c++) {
        $new .= chr($keys[$c] ^ $key);
    }
    return $new;
}

function formatTime($format, $language = null, $timestamp = null) {
    switch ($language) {
        case 'ar_SA':
            setlocale(LC_TIME, 'ar_SA.utf8', 'ar_SA.UTF-8', 'ar_SA', 'ar');
            break;
        case 'zh_CN':
            setlocale(LC_TIME, 'zh_CN.utf8', 'zh_CN.UTF-8', 'zh_CN', 'zh');
            break;
        case 'zh_HK':
            setlocale(LC_TIME, 'zh_HK.utf8', 'zh_HK.UTF-8', 'zh_HK', 'zh');
            break;
        case 'zh_TW':
            setlocale(LC_TIME, 'zh_TW.utf8', 'zh_TW.UTF-8', 'zh_TW', 'zh');
            break;
        case 'th_TH':
            setlocale(LC_TIME, 'th_TH.utf8', 'th_TH.UTF-8', 'th_TH', 'th');
            break;
        case 'ja_JP':
            setlocale(LC_TIME, 'ja_JP.utf8', 'ja_JP.UTF-8', 'ja_JP', 'ja');
            break;
        case 'hu_HU':
            setlocale(LC_TIME, 'hu_HU.utf8', 'hu_HU.UTF-8', 'hu_HU', 'hr');
            break;
        case 'ru_RU':
            setlocale(LC_TIME, 'ru_RU.utf8', 'ru_RU.UTF-8', 'ru_RU', 'ru');
            break;
        case 'de_DE':
            setlocale(LC_TIME, 'de_DE.utf8', 'de_DE.UTF-8', 'de_DE', 'de');
            break;
        case 'fr_FR':
            setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR.UTF-8', 'fr_FR', 'fr');
            break;
        case 'pl_PL':
            setlocale(LC_TIME, 'pl_PL.utf8', 'pl_PL.UTF-8', 'pl_PL', 'pl');
            break;
        case 'tr_TR':
            setlocale(LC_TIME, 'tr_TR.utf8', 'tr_TR.UTF-8', 'tr_TR', 'tr');
            break;
        case 'cs_CZ':
            setlocale(LC_TIME, 'cs_CZ.utf8', 'cs_CZ.UTF-8', 'cs_CZ', 'cz');
            break;
        case 'rw_RW':
            setlocale(LC_TIME, 'rw_RW.utf8', 'rw_RW.UTF-8', 'rw_RW', 'rw');
            break;
        case 'fil_PH':
            setlocale(LC_TIME, 'fil_PH.utf8', 'fil_PH.UTF-8', 'fil_PH', 'ph');
            break;
        case 'fa_IR':
            setlocale(LC_TIME, 'fa_IR.utf8', 'fa_IR.UTF-8', 'fa_IR', 'ir');
            break;
        case 'tg_TJ':
            setlocale(LC_TIME, 'tg_TJ.utf8', 'tg_TJ.UTF-8', 'tg_TJ', 'tj');
            break;
        case 'ko_KR':
            setlocale(LC_TIME, 'ko_KR.utf8', 'ko_KR.UTF-8', 'ko_KR', 'kr');
            break;
        default:
            setlocale(LC_TIME, $language);
            break;
    }
    if (!is_numeric($timestamp)) {
        $datetime = strftime($format);
    } else {
        $datetime = strftime($format, $timestamp);
    }
    return $datetime;
}

function get_ip_server() {
    $server_ip = '';
    $server_name = $_SERVER['SERVER_NAME'];
    if(array_key_exists('SERVER_ADDR', $_SERVER)) {
        $server_ip = $_SERVER['SERVER_ADDR'];
        if(!filter_var($server_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $server_ip = gethostbyname($server_name);
        }
    } elseif(array_key_exists('LOCAL_ADDR', $_SERVER)) {
        $server_ip = $_SERVER['LOCAL_ADDR'];
    } elseif(array_key_exists('SERVER_NAME', $_SERVER)) {
        $server_ip = gethostbyname($_SERVER['SERVER_NAME']);
    } else {
        if(stristr(PHP_OS, 'WIN')) {
            $server_ip = gethostbyname(php_uname("n"));
        } else {
            $ifconfig = shell_exec('/sbin/ifconfig eth0');
            preg_match('/addr:([\d\.]+)/', $ifconfig, $match);
            $server_ip = $match[1];
        }
    }
    return $server_ip;
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function encrypt_decrypt($action, $string, $secret_key = "supersecret_key") {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_iv = '#svt#';
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}


function calculatePercentage($first, $second) {
    return ($first / $second) * 100;
}

function limit_filename_length($filename, $length) {
    if (strlen($filename) < $length) {
        return $filename;
    }
    $ext = '';
    if (strpos($filename, '.') !== FALSE) {
        $parts          = explode('.', $filename);
        $ext            = '.'.array_pop($parts);
        $filename       = implode('.', $parts);
    }
    return substr($filename, 0, ($length - strlen($ext))).$ext;
}

function curl_get_file_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, base64_decode('c3Z0X3VzZXJfYWdlbnQ='));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function file_get_contents_curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

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

function parse_user_agent( $u_agent = null ) {
    if( $u_agent === null && isset($_SERVER['HTTP_USER_AGENT']) ) {
        $u_agent = (string)$_SERVER['HTTP_USER_AGENT'];
    }

    if( $u_agent === null ) {
        throw new \InvalidArgumentException('parse_user_agent requires a user agent');
    }

    $platform = null;
    $browser  = null;
    $version  = null;

    $return = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );

    if( !$u_agent ) {
        return $return;
    }

    if( preg_match('/\((.*?)\)/m', $u_agent, $parent_matches) ) {
        preg_match_all(<<<'REGEX'
/(?P<platform>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|(?:Open|Net|Free)BSD|Macintosh|
Windows(?:\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(?:New\ )?Nintendo\ (?:WiiU?|3?DS|Switch)|Xbox(?:\ One)?)
(?:\ [^;]*)?
(?:;|$)/imx
REGEX
            , $parent_matches[1], $result);

        $priority = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish' );

        $result['platform'] = array_unique($result['platform']);
        if( count($result['platform']) > 1 ) {
            if( $keys = array_intersect($priority, $result['platform']) ) {
                $platform = reset($keys);
            } else {
                $platform = $result['platform'][0];
            }
        } elseif( isset($result['platform'][0]) ) {
            $platform = $result['platform'][0];
        }
    }

    if( $platform == 'linux-gnu' || $platform == 'X11' ) {
        $platform = 'Linux';
    } elseif( $platform == 'CrOS' ) {
        $platform = 'Chrome OS';
    } elseif( $platform == 'Adr' ) {
        $platform = 'Android';
    } elseif( $platform === null ) {
        if(preg_match_all('%(?P<platform>Android)[:/ ]%ix', $u_agent, $result)) {
            $platform = $result['platform'][0];
        }
    }

    preg_match_all(<<<'REGEX'
%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|
OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|
Baiduspider|Applebot|Facebot|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
Valve\ Steam\ Tenfoot|
NintendoBrowser|PLAYSTATION\ (?:\d|Vita)+)
\)?;?
(?:[:/ ](?P<version>[0-9A-Z.]+)|/[A-Z]*)%ix
REGEX
        , $u_agent, $result);

    // If nothing matched, return null (to avoid undefined index errors)
    if( !isset($result['browser'][0]) || !isset($result['version'][0]) ) {
        if( preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result) ) {
            return array( 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null );
        }

        return $return;
    }

    if( preg_match('/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result) ) {
        $rv_result = $rv_result['version'];
    }

    $browser = $result['browser'][0];
    $version = $result['version'][0];

    $lowerBrowser = array_map('strtolower', $result['browser']);

    $find = function ( $search, &$key = null, &$value = null ) use ( $lowerBrowser ) {
        $search = (array)$search;

        foreach( $search as $val ) {
            $xkey = array_search(strtolower($val), $lowerBrowser);
            if( $xkey !== false ) {
                $value = $val;
                $key   = $xkey;

                return true;
            }
        }

        return false;
    };

    $findT = function ( array $search, &$key = null, &$value = null ) use ( $find ) {
        $value2 = null;
        if( $find(array_keys($search), $key, $value2) ) {
            $value = $search[$value2];

            return true;
        }

        return false;
    };

    $key = 0;
    $val = '';
    if( $findT(array( 'OPR' => 'Opera', 'Facebot' => 'iMessageBot', 'UCBrowser' => 'UC Browser', 'YaBrowser' => 'Yandex', 'Iceweasel' => 'Firefox', 'Icecat' => 'Firefox', 'CriOS' => 'Chrome', 'Edg' => 'Edge', 'EdgA' => 'Edge', 'XiaoMi/MiuiBrowser' => 'MiuiBrowser' ), $key, $browser) ) {
        $version = is_numeric(substr($result['version'][$key], 0, 1)) ? $result['version'][$key] : null;
    }elseif( $find('Playstation Vita', $key, $platform) ) {
        $platform = 'PlayStation Vita';
        $browser  = 'Browser';
    } elseif( $find(array( 'Kindle Fire', 'Silk' ), $key, $val) ) {
        $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
        $platform = 'Kindle Fire';
        if( !($version = $result['version'][$key]) || !is_numeric($version[0]) ) {
            $version = $result['version'][array_search('Version', $result['browser'])];
        }
    } elseif( $find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS' ) {
        $browser = 'NintendoBrowser';
        $version = $result['version'][$key];
    } elseif( $find('Kindle', $key, $platform) ) {
        $browser = $result['browser'][$key];
        $version = $result['version'][$key];
    } elseif( $find('Opera', $key, $browser) ) {
        $find('Version', $key);
        $version = $result['version'][$key];
    } elseif( $find('Puffin', $key, $browser) ) {
        $version = $result['version'][$key];
        if( strlen($version) > 3 ) {
            $part = substr($version, -2);
            if( ctype_upper($part) ) {
                $version = substr($version, 0, -2);

                $flags = array( 'IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows' );
                if( isset($flags[$part]) ) {
                    $platform = $flags[$part];
                }
            }
        }
    } elseif( $find(array( 'Applebot', 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome', 'SailfishBrowser' ), $key, $browser) ) {
        $version = $result['version'][$key];
    } elseif( $rv_result && $find('Trident') ) {
        $browser = 'MSIE';
        $version = $rv_result;
    } elseif( $browser == 'AppleWebKit' ) {
        if( $platform == 'Android' ) {
            $browser = 'Android Browser';
        } elseif( strpos((string)$platform, 'BB') === 0 ) {
            $browser  = 'BlackBerry Browser';
            $platform = 'BlackBerry';
        } elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
            $browser = 'BlackBerry Browser';
        } else {
            $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
        }

        $find('Version', $key);
        $version = $result['version'][$key];
    } elseif( $pKey = preg_grep('/playstation \d/i', $result['browser']) ) {
        $pKey = reset($pKey);

        $platform = 'PlayStation ' . preg_replace('/\D/', '', $pKey);
        $browser  = 'NetFront';
    }

    return array( 'platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null );
}

function check_s3_tour_enabled($id_virtualtour) {
    global $mysqli;
    $return = array();
    $query = "SELECT aws_s3 FROM svt_virtualtours WHERE id=$id_virtualtour LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $aws_s3 = $row['aws_s3'];
            if($aws_s3) {
                $settings = get_settings();
                switch ($settings['aws_s3_type']) {
                    case 'aws':
                    case 'digitalocean':
                        if(!empty($settings['aws_s3_region']) && !empty($settings['aws_s3_key']) && !empty($settings['aws_s3_secret']) && !empty($settings['aws_s3_bucket'])) {
                            $return = array("type"=>$settings['aws_s3_type'],"account_id"=>"","region"=>$settings['aws_s3_region'],"key"=>$settings['aws_s3_key'],"secret"=>$settings['aws_s3_secret'],"bucket"=>$settings['aws_s3_bucket'],"custom_domain"=>$settings['aws_s3_custom_domain']);
                        }
                        break;
                    case 'r2':
                        if(!empty($settings['aws_s3_accountid']) && !empty($settings['aws_s3_key']) && !empty($settings['aws_s3_secret']) && !empty($settings['aws_s3_bucket'])) {
                            $return = array("type"=>$settings['aws_s3_type'],"account_id"=>$settings['aws_s3_accountid'],"region"=>"","key"=>$settings['aws_s3_key'],"secret"=>$settings['aws_s3_secret'],"bucket"=>$settings['aws_s3_bucket'],"custom_domain"=>$settings['aws_s3_custom_domain']);
                        }
                        break;
                }
            }
        }
    }
    return $return;
}

function init_s3_client($s3_params) {
    global $s3Client;
    require_once(dirname(__FILE__)."/vendor/amazon-aws-sdk/aws-autoloader.php");
    $url = false;
    switch($s3_params['type']) {
        case 'aws':
            $s3Config = [
                'region' => $s3_params['region'],
                'version' => 'latest',
                'credentials' => [
                    'key'    => $s3_params['key'],
                    'secret' => $s3_params['secret']
                ]
            ];
            break;
        case 'r2':
            $credentials = new Aws\Credentials\Credentials($s3_params['key'], $s3_params['secret']);
            $s3Config = [
                'region' => 'auto',
                'version' => 'latest',
                'endpoint' => "https://".$s3_params['account_id'].".r2.cloudflarestorage.com",
                'credentials' => $credentials
            ];
            break;
        case 'digitalocean':
            $s3Config = [
                'region' => 'us-east-1',
                'version' => 'latest',
                'endpoint' => "https://".$s3_params['region'].".digitaloceanspaces.com",
                'use_path_style_endpoint' => false,
                'credentials' => [
                    'key'    => $s3_params['key'],
                    'secret' => $s3_params['secret']
                ]
            ];
            break;
    }
    $s3Client = new Aws\S3\S3Client($s3Config);
    $s3_bucket_name = $s3_params['bucket'];
    if($s3Client->doesBucketExist($s3_bucket_name)) {
        try {
            $s3Client->registerStreamWrapper();
        } catch (Aws\Exception\S3Exception $e) {}
        if(!empty($s3_params['custom_domain'])) {
            $url = "https://".$s3_params['custom_domain']."/";
        } else {
            try {
                $url = $s3Client->getObjectUrl($s3_bucket_name, '.');
            } catch (Aws\Exception\S3Exception $e) {}
        }
    }
    return $url;
}

function init_s3_client_no_wrapper($s3_params) {
    require_once(dirname(__FILE__)."/vendor/amazon-aws-sdk/aws-autoloader.php");
    switch($s3_params['type']) {
        case 'aws':
            $s3Config = [
                'region' => $s3_params['region'],
                'version' => 'latest',
                'credentials' => [
                    'key'    => $s3_params['key'],
                    'secret' => $s3_params['secret']
                ]
            ];
            break;
        case 'r2':
            $credentials = new Aws\Credentials\Credentials($s3_params['key'], $s3_params['secret']);
            $s3Config = [
                'region' => 'auto',
                'version' => 'latest',
                'endpoint' => "https://".$s3_params['account_id'].".r2.cloudflarestorage.com",
                'credentials' => $credentials
            ];
            break;
        case 'digitalocean':
            $s3Config = [
                'region' => 'us-east-1',
                'version' => 'latest',
                'endpoint' => "https://".$s3_params['region'].".digitaloceanspaces.com",
                'use_path_style_endpoint' => false,
                'credentials' => [
                    'key'    => $s3_params['key'],
                    'secret' => $s3_params['secret']
                ]
            ];
            break;
    }
    $s3Client = new Aws\S3\S3Client($s3Config);
    $s3_bucket_name = $s3_params['bucket'];
    if(!$s3Client->doesBucketExist($s3_bucket_name)) {
        $s3Client = null;
    }
    return $s3Client;
}

function check_directory_s3($s3Client,$aws_s3_bucket,$path) {
    global $s3Client,$aws_s3_type;
    switch($aws_s3_type) {
        case 'digitalocean':
            try {
                $s3Client->putObject(array(
                    'Bucket' => $aws_s3_bucket,
                    'Key'    => $path,
                    'Body'   => "",
                    'ACL'    => 'public-read'
                ));
            } catch (S3Exception $e) {}
            break;
        default:
            try {
                $s3Client->putObject(array(
                    'Bucket' => $aws_s3_bucket,
                    'Key'    => $path,
                    'Body'   => "",
                ));
            } catch (S3Exception $e) {}
            break;
    }
}

function get_s3_tour_count() {
    global $mysqli;
    $count = 0;
    $query = "SELECT COUNT(id) as num FROM svt_virtualtours WHERE aws_s3=1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $count = $row['num'];
        }
    }
    return $count;
}

function get_user_ai_generated($id_user) {
    global $mysqli;
    $count = 0;
    $query = "SELECT COUNT(*) AS num FROM svt_ai_log WHERE id_user = $id_user AND DATE_FORMAT(date_time, '%Y-%m') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m');";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $count = $row['num'];
        }
    }
    return $count;
}

function init_woocommerce_api($store_url,$customer_key,$customer_secret) {
    require_once(dirname(__FILE__)."/vendor/woocommerce/autoload.php");
    $woocommerce_client = null;
    try {
        $woocommerce_client = new Automattic\WooCommerce\Client($store_url,$customer_key,$customer_secret,
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true,
                'verify_ssl' => false
            ]
        );
        return $woocommerce_client;
    } catch (Automattic\WooCommerce\HttpClient\HttpClientException $e) {
        return null;
    }
}

function get_woocommerce_products($woocommerce_client) {
    $page = 1;
    $products = [];
    $all_products = [];
    $return_ptoducts = [];
    do{
        try {
            $products = $woocommerce_client->get('products',array('per_page' => 100, 'page' => $page, 'orderby' => 'title', 'order' => 'asc'));
        } catch(Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            exit;
        }
        $all_products = array_merge($all_products,$products);
        $page++;
    } while (count($products) > 0);
    foreach ($all_products as $product) {
        $tmp_product = array();
        $tmp_product['id'] = $product->id;
        $tmp_product['type'] = $product->type;
        $tmp_product['sku'] = $product->sku;
        $tmp_product['name'] = $product->name;
        $tmp_product['price'] = $product->price_html;
        $tmp_product['link'] = $product->permalink;
        $tmp_product['stock_status'] = $product->stock_status;
        $images = $product->images;
        $tmp_product['images'] = array();
        foreach ($images as $image) {
            array_push($tmp_product['images'],$image->src);
        }
        array_push($return_ptoducts,$tmp_product);
    }
    return $return_ptoducts;
}

function get_woocommerce_attributes($woocommerce_client) {
    $products_attributes = [];
    try {
        $products_attributes = $woocommerce_client->get('products/attributes');
    } catch(Automattic\WooCommerce\HttpClient\HttpClientException $e) {
        return null;
    }
    return $products_attributes;
}

function get_woocommerce_currency($woocommerce_client) {
    try {
        $storeSettings = $woocommerce_client->get('system_status');
        $currency = $storeSettings->settings->currency_symbol;
        $decimalSeparator = $storeSettings->settings->decimal_separator;
        $thousandSeparator = $storeSettings->settings->thousand_separator;
        $number_of_decimals = $storeSettings->settings->number_of_decimals;
        $currency_position = $storeSettings->settings->currency_position;
    } catch(Automattic\WooCommerce\HttpClient\HttpClientException $e) {
        return null;
    }
    return[$currency,$decimalSeparator,$thousandSeparator,$number_of_decimals,$currency_position];
}

function get_woocommerce_products_vt($woocommerce_client, $ids, $currency_settings) {
    $products = [];
    try {
        $ids_str = implode(',', $ids);
        $raw_products = $woocommerce_client->get('products', ['include' => $ids_str]);
        foreach ($raw_products as $raw_product) {
            $tmp_product = [
                'id' => $raw_product->id,
                'type' => $raw_product->type,
                'sku' => $raw_product->sku,
                'name' => $raw_product->name,
                'description' => $raw_product->description,
                'price' => $raw_product->price_html,
                'link' => $raw_product->permalink,
                'stock_status' => $raw_product->stock_status,
                'external_url' => $raw_product->external_url,
                'grouped_products' => $raw_product->grouped_products,
                'button_text' => strtoupper($raw_product->button_text),
                'images' => array_map(function($image) {
                    return $image->src;
                }, $raw_product->images),
                'attributes' => [],
                'variations' => []
            ];
            if ($raw_product->type == 'variable') {
                $attributes = $raw_product->attributes;
                foreach ($attributes as $attribute) {
                    $tmp_product['attributes'][] = [
                        'name' => $attribute->name,
                        'options' => $attribute->options
                    ];
                }
                $variations = $woocommerce_client->get('products/' . $raw_product->id . '/variations');
                foreach ($variations as $variation) {
                    $tmp_variation = [
                        'id' => $variation->id,
                        'stock_quantity' => $variation->stock_quantity,
                        'stock_status' => $variation->stock_status,
                        'attributes' => array_map(function($attribute) {
                            return [
                                'name' => $attribute->name,
                                'option' => $attribute->option
                            ];
                        }, $variation->attributes)
                    ];
                    if ($currency_settings != null) {
                        $currency = $currency_settings[0];
                        $currency_minor_unit = $currency_settings[3];
                        $currency_decimal_separator = $currency_settings[1];
                        $currency_thousand_separator = $currency_settings[2];
                        $currency_position = $currency_settings[4];
                        if ($currency_position == 'left') {
                            $tmp_variation['price'] = $currency . " " . number_format($variation->price, $currency_minor_unit, $currency_decimal_separator, $currency_thousand_separator);
                        } else {
                            $tmp_variation['price'] = number_format($variation->price, $currency_minor_unit, $currency_decimal_separator, $currency_thousand_separator) . " " . $currency;
                        }
                    } else {
                        $tmp_variation['price'] = $variation->price;
                    }
                    $tmp_product['variations'][] = $tmp_variation;
                }
            }
            $products[$raw_product->id] = $tmp_product;
        }
    } catch (Automattic\WooCommerce\HttpClient\HttpClientException $e) {
        return [];
    }
    return $products;
}

function obfuscateEmail($email) {
    $stars = 4;
    $at = strpos($email,'@');
    if($at - 2 > $stars) $stars = $at - 2;
    return substr($email,0,1) . str_repeat('*',$stars) . substr($email,$at - 1);
}

function print_language_input_selector($languages,$default_language,$elem) {
    global $demo,$_SESSION;
    $html = "";
    $plan_permission = get_plan_permission($_SESSION['id_user']);
    if($plan_permission['enable_multilanguage']==0) return "";
    if(count($languages)>1) {
        $html = '&nbsp;&nbsp;<div data-elem="'.$elem.'" data-default-lang="'.$default_language.'" class="dropdown d-inline-block no-arrow lang_input_switcher">
            <a id="dropdown_lang_'.$elem.'" style="vertical-align:text-bottom" class="dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img style="height: 14px;" src="img/flags_lang/'.$default_language.'.png?v=2" />
            </a>';
        if($plan_permission['enable_auto_translation']==1) {
            $html .= '&nbsp;<span onclick="translate_deepl(\''.$elem.'\');" class="badge '.(($demo) ? 'disabled_d':'').' badge-primary translate_deepl_btn">'._("translate").' <i class="fas fa-globe"></i></span>';
        }
        $html .= '<div style="width:300px;" class="dropdown-menu text-center shadow">';
        foreach ($languages as $lang) {
            $html .= '<span style="cursor: pointer;width:80px;" onclick="switch_input_language(\''.$lang.'\',\''.$default_language.'\',\''.$elem.'\',false);" class="lang noselect mx-1 my-1 p-0 d-inline-block align-middle"><img class="mb-1" src="img/flags_lang/'.$lang.'.png"> <span>'.str_replace("_","-",$lang).'</span></span>';
        }
        $html.="</div></div>";
        $html .="<script>
                $( document ).ready(function() {
                    try {
                        var url = new URL(location.href);
                        var url_params = new URLSearchParams(url.search);
                        var page_section = url_params.get('p');
                        if(page_section!='info') {
                            var lang = sessionStorage.getItem('lang_".$_SESSION['id_virtualtour_sel']."_'+page_section);
                            if(lang!==null) {
                                switch_input_language(lang,'$default_language','$elem');
                            }
                        }
                    } catch (e) {}
                });
                </script>";
    }
    return $html;
}

function get_languages_vt() {
    global $virtual_tour,$settings;
    $count_languages_enabled = 0;
    foreach ($virtual_tour['languages_enabled'] as $lang_enabled) {
        if($lang_enabled==1) {
            $count_languages_enabled++;
        }
    }
    $default_language = $virtual_tour['language'];
    if(empty($default_language)) {
        $default_language = $settings['language'];
    }
    if($count_languages_enabled>0) {
        if(array_key_exists($default_language,$virtual_tour['languages_enabled'])) {
            $virtual_tour['languages_enabled'][$default_language]=1;
        }
    }
    $array_languages = array();
    foreach ($virtual_tour['languages_enabled'] as $lang=>$enabled) {
        if($enabled==1) {
            array_push($array_languages,$lang);
        }
    }
    return [$array_languages,$default_language];
}

function save_input_langs($array_lang,$table,$id_column,$id) {
    global $mysqli;
    $new_array_lang = array();
    foreach ($array_lang as $key => $values) {
        foreach ($values as $lang => $text) {
            $new_array_lang[$lang][$key] = $text;
        }
    }
    foreach ($new_array_lang as $lang => $values) {
        $columns_array = [];
        $values_array = [];
        $bind_types = "is";
        foreach ($values as $column => $value) {
            $columns_array[] = $column;
            if(empty($value)) $value=null;
            $values_array[] = $value;
            $bind_types .= 's';
        }
        $placeholders = implode(',', array_fill(0, count($columns_array), '?'));
        $sql = "INSERT INTO $table ($id_column, language, " . implode(",", $columns_array) . ") VALUES (?,?,$placeholders)
                        ON DUPLICATE KEY UPDATE ";
        foreach ($columns_array as $column) {
            $sql .= "$column = VALUES($column),";
        }
        $sql = rtrim($sql,",");
        $smt = $mysqli->prepare($sql);
        if($smt) {
            $bind_params = array_merge([$id, $lang], $values_array);
            $smt->bind_param($bind_types, ...$bind_params);
            $smt->execute();
        }
    }
}

function check_maintenance_mode($what) {
    global $mysqli;
    try {
        $query = "SELECT maintenance_ip,maintenance_$what FROM svt_settings LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $maintenance_ip = $row['maintenance_ip'];
                $maintenance_mode = $row["maintenance_$what"];
                if($maintenance_mode) {
                    $my_ip = $_SERVER['REMOTE_ADDR'];
                    $ip_list = explode(',', $maintenance_ip);
                    if(!in_array($my_ip, $ip_list)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}