<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
$insert_id = null;
$id_user = $_SESSION['id_user'];
$name = strip_tags($_POST['name']);
$author = strip_tags($_POST['author']);
$vt_type = strip_tags($_POST['vt_type']);
$external=0;
$ar_simulator=0;
switch ($vt_type) {
    case 0:
        $external=0;
        $ar_simulator=0;
        break;
    case 1:
        $external=1;
        $ar_simulator=0;
        break;
    case 2:
        $external=0;
        $ar_simulator=1;
        break;
}
$settings = get_settings();
$id_vt_template = $settings['id_vt_template'];
if(!$external && !$ar_simulator && !empty($id_vt_template)) {
    $mysqli->query("CREATE TEMPORARY TABLE svt_virtualtour_tmp SELECT * FROM svt_virtualtours WHERE id = $id_vt_template;");
    $query="UPDATE svt_virtualtour_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_virtualtours),active=1,code=NULL,list_alt=NULL,start_date=NULL,end_date=NULL,snipcart_api_key=NULL,woocommerce_store_url=NULL,woocommerce_customer_key=NULL,woocommerce_customer_secret=NULL,password=NULL,note=NULL,html_landing=NULL,description=NULL,dollhouse=NULL,ga_tracking_id=NULL,fb_page_id=NULL,friendly_url=NULL,id_user=?,name=?,author=?,date_created=NOW();";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('iss', $id_user,$name,$author);
        $smt->execute();
    }
    $result = $mysqli->query("INSERT INTO svt_virtualtours SELECT * FROM svt_virtualtour_tmp;");
    $insert_id = $mysqli->insert_id;
    $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_virtualtours_tmp;");
    $array_icons = array();
    $result = $mysqli->query("SELECT id FROM svt_icons WHERE id_virtualtour=$id_vt_template;");
    if($result) {
        if($result->num_rows>0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_icon = $row['id'];
                $mysqli->query("CREATE TEMPORARY TABLE svt_icon_tmp SELECT * FROM svt_icons WHERE id=$id_icon;");
                $mysqli->query("UPDATE svt_icon_tmp SET id=(SELECT MAX(id)+1 as id FROM svt_icons),id_virtualtour=$insert_id;");
                $mysqli->query("INSERT INTO svt_icons SELECT * FROM svt_icon_tmp;");
                $id_icon_new = $mysqli->insert_id;
                $array_icons[$id_icon] = $id_icon_new;
                $mysqli->query("DROP TEMPORARY TABLE IF EXISTS svt_icon_tmp;");
            }
        }
    }
    $query = "SELECT ui_style FROM svt_virtualtours WHERE id=$insert_id LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $ui_style = $row['ui_style'];
            if (!empty($ui_style)) {
                $ui_style_array = json_decode($ui_style, true);
                foreach ($ui_style_array['controls'] as $key => $item) {
                    if(!empty($item['icon_library']) && $item['icon_library']!=0) {
                        if(array_key_exists($item['icon_library'],$array_icons)) {
                            $ui_style_array['controls'][$key]['icon_library'] = $array_icons[$item['icon_library']];
                        }
                    }
                }
                $ui_style = str_replace("'","\'",json_encode($ui_style_array));
                $mysqli->query("UPDATE svt_virtualtours SET ui_style='$ui_style' WHERE id=$insert_id;");
            }
        }
    }
} else {
    if($ar_simulator==1) {
        $query = "INSERT INTO svt_virtualtours(id_user,date_created,name,author,hfov,min_hfov,max_hfov,external,ar_simulator,show_device_orientation,show_webvr,auto_show_slider,arrows_nav,show_autorotation_toggle,show_presentation,keyboard_mode)
            VALUES(?,NOW(),?,?,70,70,70,?,?,0,0,2,0,0,0,0);";
    } else {
        $query = "INSERT INTO svt_virtualtours(id_user,date_created,name,author,hfov,min_hfov,max_hfov,external,ar_simulator)
            VALUES(?,NOW(),?,?,100,50,100,?,?);";
    }
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('issii',  $id_user,$name,$author,$external,$ar_simulator);
        $result = $smt->execute();
        if($result) {
            $insert_id = $mysqli->insert_id;
        }
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
        exit;
    }
}
if($insert_id!=null) {
    $_SESSION['id_virtualtour_sel'] = $insert_id;
    $_SESSION['name_virtualtour_sel'] = $name;
    session_write_close();
    $code = md5($insert_id);
    $mysqli->query("UPDATE svt_virtualtours SET code='$code' WHERE id=$insert_id;");
    if($settings['aws_s3_enabled']==1 && $settings['aws_s3_vt_auto']==1) {
        $mysqli->query("UPDATE svt_virtualtours SET aws_s3=1 WHERE id=$insert_id;");
    }
    $query = "SELECT id FROM svt_advertisements WHERE auto_assign=1 LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row=$result->fetch_array(MYSQLI_ASSOC);
            $id_ads=$row['id'];
            $mysqli->query("INSERT INTO svt_assign_advertisements(id_advertisement,id_virtualtour) VALUES($id_ads,$insert_id);");
        }
    }
    set_user_log($id_user,'add_virtual_tour',json_encode(array("id"=>$insert_id,"name"=>$name)),date('Y-m-d H:i:s', time()));
    ob_end_clean();
    echo json_encode(array("status"=>"ok","id"=>$insert_id));
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>$mysqli->error));
}