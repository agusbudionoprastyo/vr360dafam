<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$id_virtualtour = $_SESSION['id_virtualtour_sel'];
session_write_close();
$p = strip_tags($_POST['p']);
$element = strip_tags($_POST['element']);
$icon = strip_tags($_POST['icon']);
$icon_type = strip_tags($_POST['icon_type']);
$id_icon_library = (int)$_POST['id_icon_library'];
$color = strip_tags($_POST['color']);
$background = strip_tags($_POST['background']);
$style = (int)$_POST['style'];
$tooltip_type = strip_tags($_POST['tooltip_type']);
$tooltip_visibility = strip_tags($_POST['tooltip_visibility']);
$tooltip_background = strip_tags($_POST['tooltip_background']);
$tooltip_color = strip_tags($_POST['tooltip_color']);
if(isset($_POST['default_scale'])) {
    $default_scale = $_POST['default_scale'];
} else {
    $default_scale = 1;
}
$sound = strip_tags($_POST['sound']);
$animation = strip_tags($_POST['animation']);
$apply_style = (int)$_POST['apply_style'];
$apply_icon = (int)$_POST['apply_icon'];
$apply_color = (int)$_POST['apply_color'];
$apply_background = (int)$_POST['apply_background'];
$apply_icon_type = (int)$_POST['apply_icon_type'];
$apply_tooltip_type = (int)$_POST['apply_tooltip_type'];
$apply_tooltip_visibility = (int)$_POST['apply_tooltip_visibility'];
$apply_tooltip_background = (int)$_POST['apply_tooltip_background'];
$apply_tooltip_color = (int)$_POST['apply_tooltip_color'];
if(isset($_POST['apply_default_scale'])) {
    $apply_default_scale = (int)$_POST['apply_default_scale'];
} else {
    $apply_default_scale = 0;
}
$apply_sound = (int)$_POST['apply_sound'];
$apply_animation = (int)$_POST['apply_animation'];
$set_as_default = (int)$_POST['set_as_default'];
$query_b = "";
switch ($element) {
    case 'markers':
        if($style!=4) $id_icon_library=0;
        $query_add = "";
        if($apply_style==1) {
            $query_add .= "show_room=$style,";
        }
        if($apply_icon==1) {
            $query_add .= "icon='$icon',id_icon_library=$id_icon_library,";
        } else {
            if($style!=4) {
                $query_add .= "id_icon_library=0,";
            }
        }
        if($apply_color==1) {
            $query_add .= "color='$color',";
        }
        if($apply_background==1) {
            $query_add .= "background='$background',";
        }
        if($apply_icon_type==1) {
            $query_add .= "icon_type='$icon_type',";
        }
        if($apply_tooltip_type==1) {
            $query_add .= "tooltip_type='$tooltip_type',";
        }
        if($apply_tooltip_visibility==1) {
            $query_add .= "tooltip_visibility='$tooltip_visibility',";
        }
        if($apply_tooltip_background==1) {
            $query_add .= "tooltip_background='$tooltip_background',";
        }
        if($apply_tooltip_color==1) {
            $query_add .= "tooltip_color='$tooltip_color',";
        }
        if($apply_default_scale==1) {
            $query_add .= "scale=$default_scale,";
        }
        if($apply_sound==1) {
            if(empty($sound)) {
                $query_add .= "sound=NULL,";
            } else {
                $query_add .= "sound='content/$sound',";
            }
        }
        if($apply_animation==1) {
            $query_add .= "animation='$animation',";
        }
        $query_add = rtrim($query_add,",");
        $query = "UPDATE svt_virtualtours SET markers_icon='$icon',markers_id_icon_library=$id_icon_library,markers_color='$color',markers_background='$background',markers_show_room=$style,markers_tooltip_type='$tooltip_type',markers_icon_type='$icon_type',markers_tooltip_visibility='$tooltip_visibility',markers_tooltip_background='$tooltip_background',markers_tooltip_color='$tooltip_color',markers_default_sound='$sound',markers_animation='$animation' WHERE id=$id_virtualtour;";
        $query_a = "UPDATE svt_markers SET $query_add WHERE exclude_from_apply_all=0 AND (embed_type!='selection' OR embed_type IS NULL) AND id_room IN (SELECT DISTINCT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
        if($set_as_default==1) {
            $query_b_add = "";
            if($apply_style==1) {
                $query_b_add .= "markers_show_room=$style,";
            }
            if($apply_icon==1) {
                $query_b_add .= "markers_icon='$icon',markers_id_icon_library=$id_icon_library,";
            } else {
                if($style!=4) {
                    $query_b_add .= "markers_id_icon_library=0,";
                }
            }
            if($apply_color==1) {
                $query_b_add .= "markers_color='$color',";
            }
            if($apply_background==1) {
                $query_b_add .= "markers_background='$background',";
            }
            if($apply_icon_type==1) {
                $query_b_add .= "markers_icon_type='$icon_type',";
            }
            if($apply_tooltip_type==1) {
                $query_b_add .= "markers_tooltip_type='$tooltip_type',";
            }
            if($apply_tooltip_visibility==1) {
                $query_b_add .= "markers_tooltip_visibility='$tooltip_visibility',";
            }
            if($apply_tooltip_background==1) {
                $query_b_add .= "markers_tooltip_background='$tooltip_background',";
            }
            if($apply_tooltip_color==1) {
                $query_b_add .= "markers_tooltip_color='$tooltip_color',";
            }
            if($apply_sound==1) {
                if(empty($sound)) {
                    $query_b_add .= "markers_default_sound=NULL,";
                } else {
                    $query_b_add .= "markers_default_sound='content/$sound',";
                }
            }
            if($apply_animation==1) {
                $query_b_add .= "markers_animation='$animation',";
            }
            if(!empty($query_b_add)) {
                $query_b_add = rtrim($query_b_add,",");
                $query_b = "UPDATE svt_virtualtours SET $query_b_add WHERE id=$id_virtualtour;";
            }
        }
        break;
    case 'pois':
        if($style!=1) $id_icon_library=0;
        $query_add = "";
        if($apply_style==1) {
            $query_add .= "style=$style,";
        }
        if($apply_icon==1) {
            $query_add .= "icon='$icon',id_icon_library=$id_icon_library,";
        } else {
            if($style!=1) {
                $query_add .= "id_icon_library=0,";
            }
        }
        if($apply_color==1) {
            $query_add .= "color='$color',";
        }
        if($apply_background==1) {
            $query_add .= "background='$background',";
        }
        if($apply_icon_type==1) {
            $query_add .= "icon_type='$icon_type',";
        }
        if($apply_tooltip_type==1) {
            $query_add .= "tooltip_type='$tooltip_type',";
        }
        if($apply_tooltip_visibility==1) {
            $query_add .= "tooltip_visibility='$tooltip_visibility',";
        }
        if($apply_tooltip_background==1) {
            $query_add .= "tooltip_background='$tooltip_background',";
        }
        if($apply_tooltip_color==1) {
            $query_add .= "tooltip_color='$tooltip_color',";
        }
        if($apply_default_scale==1) {
            $query_add .= "scale=$default_scale,";
        }
        if($apply_sound==1) {
            if(empty($sound)) {
                $query_add .= "sound=NULL,";
            } else {
                $query_add .= "sound='content/$sound',";
            }
        }
        if($apply_animation==1) {
            $query_add .= "animation='$animation',";
        }
        $query_add = rtrim($query_add,",");
        $query = "UPDATE svt_virtualtours SET pois_icon='$icon',pois_id_icon_library=$id_icon_library,pois_color='$color',pois_background='$background',pois_style=$style,pois_tooltip_type='$tooltip_type',pois_icon_type='$icon_type',pois_tooltip_visibility='$tooltip_visibility',pois_tooltip_background='$tooltip_background',pois_tooltip_color='$tooltip_color',pois_default_sound='$sound',pois_animation='$animation' WHERE id=$id_virtualtour;";
        $query_a = "UPDATE svt_pois SET $query_add WHERE exclude_from_apply_all=0 AND (embed_type!='selection' OR embed_type IS NULL) AND id_room IN (SELECT DISTINCT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour);";
        if($set_as_default==1) {
            $query_b_add = "";
            if($apply_style==1) {
                $query_b_add .= "pois_style=$style,";
            }
            if($apply_icon==1) {
                $query_b_add .= "pois_icon='$icon',pois_id_icon_library=$id_icon_library,";
            } else {
                if($style!=1) {
                    $query_b_add .= "pois_id_icon_library=0,";
                }
            }
            if($apply_color==1) {
                $query_b_add .= "pois_color='$color',";
            }
            if($apply_background==1) {
                $query_b_add .= "pois_background='$background',";
            }
            if($apply_icon_type==1) {
                $query_b_add .= "pois_icon_type='$icon_type',";
            }
            if($apply_tooltip_type==1) {
                $query_b_add .= "pois_tooltip_type='$tooltip_type',";
            }
            if($apply_tooltip_visibility==1) {
                $query_b_add .= "pois_tooltip_visibility='$tooltip_visibility',";
            }
            if($apply_tooltip_background==1) {
                $query_b_add .= "pois_tooltip_background='$tooltip_background',";
            }
            if($apply_tooltip_color==1) {
                $query_b_add .= "pois_tooltip_color='$tooltip_color',";
            }
            if($apply_sound==1) {
                if(empty($sound)) {
                    $query_b_add .= "pois_default_sound=NULL,";
                } else {
                    $query_b_add .= "pois_default_sound='content/$sound',";
                }
            }
            if($apply_animation==1) {
                $query_b_add .= "pois_animation='$animation',";
            }
            if(!empty($query_b_add)) {
                $query_b_add = rtrim($query_b_add,",");
                $query_b = "UPDATE svt_virtualtours SET $query_b_add WHERE id=$id_virtualtour;";
            }
        }
        break;
}
switch($p) {
    case 'markers':
    case 'pois':
        $result = $mysqli->query($query);
        if($result) {
            $result_a = $mysqli->query($query_a);
            if($result_a) {
                ob_end_clean();
                echo json_encode(array("status"=>"ok"));
                exit;
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
                exit;
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
            exit;
        }
        break;
    case 'markers_e':
    case 'pois_e':
        $result_a = $mysqli->query($query_a);
        if($result_a) {
            if(!empty($query_b)) {
                $result_b = $mysqli->query($query_b);
                if($result_b) {
                    ob_end_clean();
                    echo json_encode(array("status"=>"ok"));
                    exit;
                } else {
                    ob_end_clean();
                    echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
                    exit;
                }
            } else {
                ob_end_clean();
                echo json_encode(array("status"=>"ok"));
                exit;
            }
        } else {
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
            exit;
        }
        break;
}