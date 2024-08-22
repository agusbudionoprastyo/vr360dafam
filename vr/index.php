<?php
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
require_once("../db/connection.php");
require_once("../backend/functions.php");
if(check_maintenance_mode('viewer')) {
    if(file_exists("../error_pages/custom/maintenance_viewer.html")) {
        include("../error_pages/custom/maintenance_viewer.html");
    } else {
        include("../error_pages/default/maintenance_viewer.html");
    }
    exit;
}
$array_rooms = array();
$array_markers = array();
$array_pois = array();
$rooms_count = 0;
$s3Client = null;
$s3_enabled = false;
$s3_version = time();
if(isset($_GET['export'])) {
    $export=1;
} else {
    $export=0;
}
if(isset($_GET['export_s3'])) {
    $export_s3=1;
} else {
    $export_s3=0;
}
if((isset($_GET['furl'])) || (isset($_GET['code']))) {
    if(isset($_GET['furl'])) {
        $furl = $_GET['furl'];
        $where = "(v.friendly_url = '$furl' OR v.code = '$furl')";
    }
    if(isset($_GET['code'])) {
        $code = $_GET['code'];
        $where = "v.code = '$code'";
    }
    $query = "SELECT v.id,v.code,v.id_user,v.name as name_virtualtour,v.author,v.language,v.ga_tracking_id,v.font_viewer,v.logo,v.background_image,v.song,v.description,u.expire_plan_date,v.start_date,v.end_date,v.start_url,v.end_url,u.id_subscription_stripe,u.status_subscription_stripe,u.id_subscription_paypal,u.status_subscription_paypal,v.meta_title,v.meta_description,v.meta_image 
                FROM svt_virtualtours AS v
                JOIN svt_users AS u ON u.id=v.id_user
                LEFT JOIN svt_plans AS p ON p.id=u.id_plan
                WHERE v.active=1 AND $where LIMIT 1;";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows == 1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if(!empty($row['id_subscription_stripe'])) {
                if($row['status_subscription_stripe']==0 && $row['expire_tours']==1) {
                    die("Expired link");
                }
            }
            if(!empty($row['id_subscription_paypal'])) {
                if($row['status_subscription_paypal']==0 && $row['expire_tours']==1) {
                    die("Expired link");
                }
            }
            if(!empty($row['expire_plan_date'])) {
                if($row['expire_tours']==1) {
                    if (new DateTime() > new DateTime($row['expire_plan_date'])) {
                        die("Expired link");
                    }
                }
            }
            if((!empty($row['start_date'])) && ($row['start_date']!='0000-00-00')) {
                if (new DateTime() < new DateTime($row['start_date']." 00:00:00")) {
                    if(!empty($row['start_url'])) {
                        header("Location: ".$row['start_url']);
                        exit();
                    } else {
                        die("Expired link");
                    }
                }
            }
            if((!empty($row['end_date'])) && ($row['end_date']!='0000-00-00')) {
                if (new DateTime() > new DateTime($row['end_date']." 23:59:59")) {
                    if(!empty($row['end_url'])) {
                        header("Location: ".$row['end_url']);
                        exit();
                    } else {
                        die("Expired link");
                    }
                }
            }
            $code = $row['code'];
            $id_virtualtour = $row['id'];
            if($export==0) {
                $s3_params = check_s3_tour_enabled($id_virtualtour);
                $s3_enabled = false;
                $s3_url = "";
                if(!empty($s3_params)) {
                    $s3_bucket_name = $s3_params['bucket'];
                    $s3_url = init_s3_client($s3_params);
                    if($s3_url!==false) {
                        $s3_enabled = true;
                    }
                }
            }
            $name_virtualtour = $row['name_virtualtour'];
            $author_virtualtour = $row['author'];
            $id_user = $row['id_user'];
            $ga_tracking_id = $row['ga_tracking_id'];
            $logo = $row['logo'];
            $song = $row['song'];
            $font_viewer = $row['font_viewer'];
            $background_image = $row['background_image'];
            $description = $row['description'];
            $vt_language = $row['language'];
            if(empty($row['meta_title'])) {
                $meta_title = $name_virtualtour;
            } else {
                $meta_title = $row['meta_title'];
            }
            if(empty($row['meta_description'])) {
                $meta_description = $description;
            } else {
                $meta_description = $row['meta_description'];
            }
            if(empty($row['meta_image'])) {
                $meta_image = $background_image;
            } else {
                $meta_image = $row['meta_image'];
            }
            $query_rooms = "SELECT id,name,northOffset,panorama_image,panorama_video,yaw FROM svt_rooms WHERE id_virtualtour=$id_virtualtour ORDER BY priority, id;";
            $result_rooms = $mysqli->query($query_rooms);
            if($result_rooms) {
                $rooms_count = $result_rooms->num_rows;
                if ($rooms_count > 0) {
                    while ($row_room = $result_rooms->fetch_array(MYSQLI_ASSOC)) {
                       array_push($array_rooms,$row_room);
                    }
                }
            }
            $query_markers = "SELECT m.id,m.yaw,m.pitch,m.id_room,m.id_room_target,r.name as name_room_target FROM svt_markers AS m
                                JOIN svt_rooms AS r on m.id_room_target = r.id
                                WHERE m.id_room IN (SELECT DISTINCT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour ORDER BY priority, id);";
            $result_markers = $mysqli->query($query_markers);
            if($result_markers) {
                if ($result_markers->num_rows > 0) {
                    while ($row_marker = $result_markers->fetch_array(MYSQLI_ASSOC)) {
                        $id_room = $row_marker['id_room'];
                        if(!array_key_exists($id_room,$array_markers)) {
                            $array_markers[$id_room] = array();
                        }
                        array_push($array_markers[$id_room],$row_marker);
                    }
                }
            }
            $query_pois = "SELECT id,yaw,pitch,id_room,type,content FROM svt_pois WHERE type IN ('image','video','object3d','html','audio','video360') AND content!='' AND id_room IN (SELECT DISTINCT id FROM svt_rooms WHERE id_virtualtour=$id_virtualtour ORDER BY priority, id);";
            $result_pois = $mysqli->query($query_pois);
            if($result_pois) {
                if ($result_pois->num_rows > 0) {
                    while ($row_poi = $result_pois->fetch_array(MYSQLI_ASSOC)) {
                        $id_room = $row_poi['id_room'];
                        $content = $row_poi['content'];
                        $type = $row_poi['type'];
                        $skip = false;
                        switch ($type) {
                            case 'image':
                                if($s3_enabled) {
                                    list($width, $height, $type, $attr) = getimagesize("s3://$s3_bucket_name/viewer/".$content);
                                } else {
                                    if($export_s3==1) {
                                        list($width, $height, $type, $attr) = getimagesize(dirname(__FILE__).'/../services/export_tmp/'.$code."_vr".'/'.$content);
                                    } else {
                                        list($width, $height, $type, $attr) = getimagesize(dirname(__FILE__).'/../viewer/'.$content);
                                    }
                                }
                                $row_poi['aspect_ratio'] = $width / $height;
                                break;
                            case 'video':
                                if (strpos($content, 'http') === false && strpos($content, '.mp4') !== false) {
                                    include_once('vendor/getid3/getid3.php');
                                    $getID3 = new getID3();
                                    if($s3_enabled) {
                                        $video_content = file_get_contents("s3://$s3_bucket_name/viewer/$content");
                                        if(empty($video_content)) {
                                            $video_content = curl_get_file_contents($s3_url."viewer/$content");
                                        }
                                        $tmpfname = tempnam(sys_get_temp_dir(), "video_vr_");
                                        rename($tmpfname, $tmpfname .= '.mp4');
                                        file_put_contents($tmpfname,$video_content);
                                        $file = $getID3->analyze($tmpfname);
                                        unlink($tmpfname);
                                    } else {
                                        if($export_s3==1) {
                                            $file = $getID3->analyze(dirname(__FILE__).'/../services/export_tmp/'.$code."_vr".'/'.$content);
                                        } else {
                                            $file = $getID3->analyze(dirname(__FILE__).'/../viewer/'.$content);
                                        }
                                    }
                                    $width = $file['video']['resolution_x'];
                                    $height = $file['video']['resolution_y'];
                                    $row_poi['aspect_ratio'] = $width / $height;
                                } else {
                                    $skip = true;
                                }
                                break;
                        }
                        if(!$skip) {
                            if(!array_key_exists($id_room,$array_pois)) {
                                $array_pois[$id_room] = array();
                            }
                            array_push($array_pois[$id_room],$row_poi);
                        }
                    }
                }
            }
        } else {
            die("Invalid link");
        }
    } else {
        die("Invalid link");
    }
} else {
    die("Invalid link");
}
$query = "SELECT enable_webvr FROM svt_plans as p LEFT JOIN svt_users AS u ON u.id_plan=p.id WHERE u.id = $id_user LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        if($row['enable_webvr']==0) {
            die("Not allowed");
        }
    }
}
$lang_code = "en";
$font_provider = "google";
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$query = "SELECT name,language,language_domain,font_provider FROM svt_settings LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $name_app = $row['name'];
        $font_provider = $row['font_provider'];
        if ($vt_language != '') {
            $language = $vt_language;
        } else {
            $language = $row['language'];
        }
        switch ($language) {
            case 'pt_BR':
                $lang_code = 'pt-BR';
                break;
            case 'pt_PT':
                $lang_code = 'pt-PT';
                break;
            case 'zh_CN':
                $lang_code = 'zh-CN';
                break;
            case 'zh_TW':
                $lang_code = 'zh-TW';
                break;
            case 'zh_HK':
                $lang_code = 'zh-hk';
                break;
            default:
                $lang_code = substr($language, 0, 2);
                break;
        }
        if (function_exists('gettext')) {
            if (defined('LC_MESSAGES')) {
                $result = setlocale(LC_MESSAGES, $language);
                if (!$result) {
                    setlocale(LC_MESSAGES, $language . '.UTF-8');
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
            $domain = $row['language_domain'];
            $result = bindtextdomain($domain, "../locale");
            if (!$result) {
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
}
$ip_visitor = getIPAddress();
if($export==0) {
    if($s3_enabled) {
        $path = $s3_url.'viewer/';
    } else {
        $path = "../viewer/";
    }
    $mysqli->query("INSERT INTO svt_access_log(id_virtualtour,date_time,ip) VALUES($id_virtualtour,NOW(),'$ip_visitor');");
} else {
    $path = "";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<head>
    <title><?php echo $meta_title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta property="og:type" content="website">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="og:url" content="<?php echo $url."index.php?code=".$code; ?>">
    <meta property="twitter:url" content="<?php echo $url."index.php?code=".$code; ?>">
    <meta itemprop="name" content="<?php echo $meta_title; ?>">
    <meta property="og:title" content="<?php echo $meta_title; ?>">
    <meta property="twitter:title" content="<?php echo $meta_title; ?>">
    <?php if($meta_image!='') : ?>
        <meta itemprop="image" content="<?php echo $url."content/".$meta_image; ?>">
        <meta property="og:image" content="<?php echo $url."content/".$meta_image; ?>" />
        <meta property="twitter:image" content="<?php echo $url."content/".$meta_image; ?>">
    <?php endif; ?>
    <?php if($meta_description!='') : ?>
        <meta itemprop="description" content="<?php echo $meta_description; ?>">
        <meta name="description" content="<?php echo $meta_description; ?>"/>
        <meta property="og:description" content="<?php echo $meta_description; ?>" />
        <meta property="twitter:description" content="<?php echo $meta_description; ?>">
    <?php endif; ?>
    <?php echo print_favicons_vt($code,$logo,$export); ?>
    <?php switch ($font_provider) {
        case 'google': ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel='stylesheet' type="text/css" crossorigin="anonymous" href="https://fonts.googleapis.com/css2?family=<?php echo $font_viewer; ?>">
            <?php break;
        case 'collabs': ?>
            <link rel="preconnect" href="https://api.fonts.coollabs.io" crossorigin>
            <link href="https://api.fonts.coollabs.io/css2?family=<?php echo $font_viewer; ?>&display=swap" rel="stylesheet">
            <?php break;
    } ?>
    <?php if($export==1) { ?>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script type="text/javascript" src="js/script.js"></script>
    <?php } else { ?>
    <link rel="stylesheet" type="text/css" href="css/progress.css">
    <link rel="stylesheet" type="text/css" href="css/index.css?v=<?php echo time(); ?>">
    <script type="text/javascript" src="js/progress.min.js"></script>
    <script type="text/javascript" src="js/aframe-master.min.js?v=1.4.2"></script>
    <script type="text/javascript" src="js/aframe-look-at-billboard-component.js?v=2"></script>
    <?php } ?>
</head>
<body>
<style>
    *:not(i) { font-family: '<?php echo $font_viewer; ?>', sans-serif; }
</style>
<div class="loading">
    <div class="progress-circle noselect"></div>
    <div class="progress">
        <?php if($logo!='') : ?>
            <img src="<?php echo $path; ?>content/<?php echo $logo.(($s3_enabled) ? '?v=s3='.$s3_version : ''); ?>" />
        <?php endif; ?>
        <!-- <h3 class="noselect" id="name_virtualtour"><?php echo $name_virtualtour; ?></h3> -->
        <!-- <h2 class="noselect <?php echo (empty($author_virtualtour)) ? 'hidden' : ''; ?>" id="author_virtualtour"><?php echo _("presented by")." ".$author_virtualtour; ?></h2> -->
    </div>
    <?php if(!empty($background_image)) : ?>
    <div id="background_loading" class="background_opacity" style="background-image: url('<?php echo $path; ?>content/<?php echo $background_image.(($s3_enabled) ? '?v=s3='.$s3_version : ''); ?>');"></div>
    <?php endif; ?>
</div>
<?php
    $timeout = $rooms_count*1000;
    if($timeout<=5000) $timeout=5000;
    if($timeout>=60000) $timeout=60000;
    $timeout_interval = $timeout/100;
?>
<script>
    window.ip_visitor = '<?php echo $ip_visitor; ?>';
    window.assets_interval = <?php echo $timeout_interval; ?>;
    window.export = <?php echo $export; ?>;
</script>
<?php if(strpos($_SERVER['HTTP_REFERER'], "/viewer/") !== false) { ?>
    <script> var exit_button = true; </script>
<?php } else { ?>
    <script> var exit_button = false; </script>
<?php } ?>
<script type="text/javascript" src="js/index.js?v=<?php echo time(); ?>"></script>
<button onclick="redirect_to_normal();" id="exit_vr_button"><?php echo _("EXIT VR"); ?></button>
<a-scene id="vt_scene" scenelistener light="defaultLightsEnabled: false" style="opacity: 0;pointer-events: none;">
    <a-assets timeout="<?php echo $timeout; ?>">
        <?php if (file_exists(dirname(__FILE__).'/img/custom/marker.png')) { ?>
            <img crossorigin="anonymous" id="marker" src="img/custom/marker.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="marker" src="img/marker.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/close.png')) { ?>
            <img crossorigin="anonymous" id="close" src="img/custom/close.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="close" src="img/close.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/poi_image.png')) { ?>
            <img crossorigin="anonymous" id="poi_image" src="img/custom/poi_image.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="poi_image" src="img/poi_image.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/poi_video.png')) { ?>
            <img crossorigin="anonymous" id="poi_video" src="img/custom/poi_video.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="poi_video" src="img/poi_video.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/poi_video360.png')) { ?>
            <img crossorigin="anonymous" id="poi_video360" src="img/custom/poi_video360.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="poi_video360" src="img/poi_video360.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/poi_object3d.png')) { ?>
            <img crossorigin="anonymous" id="poi_object3d" src="img/custom/poi_object3d.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="poi_object3d" src="img/poi_object3d.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/poi_html.png')) { ?>
            <img crossorigin="anonymous" id="poi_html" src="img/custom/poi_html.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="poi_html" src="img/poi_html.png?v=3" />
        <?php } ?>
        <?php if (file_exists(dirname(__FILE__).'/img/custom/poi_audio.png')) { ?>
            <img crossorigin="anonymous" id="poi_audio" src="img/custom/poi_audio.png?v=<?php echo time(); ?>" />
        <?php } else { ?>
            <img crossorigin="anonymous" id="poi_audio" src="img/poi_audio.png?v=3" />
        <?php } ?>
        <?php
        $first_room = 0;
        $first_north = 0;
        $first_id_room = 0;
        $first_room_name = '';
        $cam_rotation_fix = 0;
        foreach ($array_rooms as $room) {
            $id_room = $room['id'];
            if(empty($room['northOffset'])) {
                $north = 0;
            } else {
                $north = -$room['northOffset'];
            }
            $panorama_image = $room['panorama_image'];
            $panorama_video = $room['panorama_video'];
            if($first_room==0) {
                $first_north = $north;
                $first_id_room = $id_room;
                $cam_rotation_fix = $north+90-$room['yaw'];
                $first_room = $id_room;
                $first_room_name = $room['name'];
            }
            if(!empty($panorama_video)) {
                echo "<video muted crossorigin='anonymous' loop='true' playsinline webkit-playsinline width='3' height='1.5' id='r_$id_room' class='panorama_video' data-north='$north' src='".$path."videos/$panorama_video".(($s3_enabled) ? '?v=s3='.$s3_version : '')."'></video>";
            } else {
                if($s3_enabled) {
                    $pano_mobile_exist = file_exists("s3://$s3_bucket_name/viewer/panoramas/mobile/$panorama_image");
                } else {
                    $pano_mobile_exist = file_exists(dirname(__FILE__).'/../viewer/panoramas/mobile/'.$panorama_image);
                }
                if($pano_mobile_exist) {
                    echo "<img crossorigin='anonymous' id='r_$id_room' class='panorama_image' data-north='$north' src='".$path."panoramas/mobile/$panorama_image".(($s3_enabled) ? '?v=s3='.$s3_version : '')."' />";
                } else {
                    echo "<img crossorigin='anonymous' id='r_$id_room' class='panorama_image' data-north='$north' src='".$path."panoramas/$panorama_image".(($s3_enabled) ? '?v=s3='.$s3_version : '')."' />";
                }
            }
        }
        ?>
        <?php if(!empty($song)) : ?>
            <audio crossorigin="anonymous" id="background_music" loop="true" src="<?php echo $path; ?>content/<?php echo $song; ?>"></audio>
        <?php endif; ?>
        <?php
        foreach ($array_pois as $id_room => $pois) {
            foreach ($pois as $poi) {
                $id_poi = $poi['id'];
                $type = $poi['type'];
                $content = $poi['content'];
                switch($type) {
                    case 'image':
                        echo "<img crossorigin='anonymous' id='p_$id_poi' class='poi' src='".$path."$content".(($s3_enabled) ? '?v=s3='.$s3_version : '')."' />";
                        break;
                    case 'video':
                        echo "<video crossorigin='anonymous' playsinline webkit-playsinline id='p_$id_poi' class='poi' src='".$path."$content".(($s3_enabled) ? '?v=s3='.$s3_version : '')."'></video>";
                        break;
                    case 'object3d':
                        if (strpos($content, ',') !== false) {
                            $tmp_array = explode(",",$content);
                            foreach ($tmp_array as $tmp) {
                                $tmp2 = strtolower($tmp);
                                if ((strpos($tmp2, '.glb') !== false) || (strpos($tmp2, '.gltf') !== false)) {
                                    $content = $tmp;
                                }
                            }
                        }
                        echo "<a-asset-item crossorigin='anonymous' id='p_$id_poi' class='poi' src='".$path."$content".(($s3_enabled) ? '?v=s3='.$s3_version : '')."'></a-asset-item>";
                        break;
                    case 'audio':
                        echo "<audio crossorigin='anonymous' id='p_$id_poi' class='poi' src='".$path."$content".(($s3_enabled) ? '?v=s3='.$s3_version : '')."'></audio>";
                        break;
                    case 'video360':
                        echo "<video crossorigin='anonymous' loop='true' playsinline webkit-playsinline width='3' height='1.5' id='p_$id_poi' class='poi' src='".$path."$content".(($s3_enabled) ? '?v=s3='.$s3_version : '')."'></video>";
                        break;
                }
            }
        }
        ?>
    </a-assets>
    <a-entity light="type: ambient; color: #FFF"></a-entity>
    <a-entity light="type: directional; color: #FFF; intensity: 0.9" position="5 5 1"></a-entity>
    <a-entity raycaster="objects:.landscape,.environmentGround,.environmentDressing; far:0.5;"></a-entity>
    <a-entity id="spots" rotation="0 <?php echo $first_north; ?> 0" hotspots>
        <?php
        foreach ($array_markers as $id_room => $markers) {
            if($id_room==$first_room) {
                $scale_marker = "1 1 1";
            } else {
                $scale_marker = "0 0 0";
            }
            $entity = "<a-entity id='markers_$id_room' rotation='0 90 0' scale='$scale_marker'>";
            foreach ($markers as $marker) {
                $yaw = -$marker['yaw'];
                $pitch = $marker['pitch']+10;
                $id_room_target = $marker['id_room_target'];
                $name_room_target = $marker['name_room_target'];
                $entity .= "<a-entity data-raycastable rotation='$pitch $yaw 0'>";
                $entity .= "<a-image class='marker_icon' data-raycastable material='alpha-test:0.5;transparent:true;' spot='object:marker;type:;linkto:#r_$id_room_target;spotgroup:markers_$id_room_target;room_name:$name_room_target;' position='0 0 -10'></a-image>";
                $entity .= "</a-entity>";
            }
            $entity .= "</a-entity>";
            echo $entity;
        }

        foreach ($array_pois as $id_room => $pois) {
            if($id_room==$first_room) {
                $scale_poi = "1 1 1";
            } else {
                $scale_poi = "0 0 0";
            }
            $entity = "<a-entity id='pois_$id_room' rotation='0 90 0' scale='$scale_poi'>";
            foreach ($pois as $poi) {
                $id_poi = $poi['id'];
                $yaw = -$poi['yaw'];
                $pitch = $poi['pitch']+7;
                $pitch_inv = -$pitch;
                $type = $poi['type'];
                $entity .= "<a-entity rotation='$pitch $yaw 0'>";
                $entity .= "<a-image class='poi_icon' data-raycastable material='alpha-test:0.5;transparent:true;' spot='object:poi;type:$type;linkto:#poi_content_$id_poi;spotgroup:pois_$id_room' position='0 0 -12' scale='1 1 1'></a-image>";
                switch($type) {
                    case 'image':
                        $aspect_ratio = $poi['aspect_ratio'];
                        $width = 13 * $aspect_ratio;
                        $height = 13;
                        $entity .= "<a-image data-width='$width' data-height='$height' data-raycastable id='poi_content_$id_poi' src='#p_$id_poi' rotation='-7 0 0' position='0 0 -11' scale='0 0 0'></a-image>";
                        break;
                    case 'video':
                        $aspect_ratio = $poi['aspect_ratio'];
                        $width = 13 * $aspect_ratio;
                        $height = 13;
                        $entity .= "<a-video data-width='$width' data-height='$height' data-raycastable id='poi_content_$id_poi' src='#p_$id_poi' rotation='-7 0 0' position='0 0 -11' scale='0 0 0'></a-video>";
                        break;
                    case 'audio':
                        $entity .= "<a-audio data-raycastable id='poi_content_$id_poi' src='#p_$id_poi' rotation='-7 0 0' position='0 0 -11' scale='0 0 0'></a-audio>";
                        break;
                    case 'object3d':
                        if($pitch<0) {
                            $fix_pos_close = -11;
                        } else {
                            $fix_pos_close = -9;
                        }
                        $entity .= "<a-entity data-raycastable id='poi_content_$id_poi' rotation='$pitch_inv 0 0' position='0 -4 -11' scale='1 1 1' visible='false'>
                                        <a-entity id='object3d_$id_poi' data-pitch='$pitch_inv' natural-size='height:10;' gltf-model='#p_$id_poi' position='0 0 0' animation='property: rotation; to: 0 360 0; easing: linear; loop: true; dur: 10000'></a-entity>
                                    </a-entity>";
                        $entity .= "<a-image id='close_object3d_$id_poi' billboard data-raycastable material='alpha-test:0.5;transparent:true;' src='#close' scale='0 0 0' rotation='$pitch 0 0' position='0 $fix_pos_close -11'></a-image>";
                        break;
                    case 'html':
                        $text = $poi['content'];
                        $text = str_replace('<p>','',$text);
                        $text = str_replace('</p>','\n',$text);
                        $text = preg_replace('/ style="[^"]*"/', '', $text);
                        $text = strip_tags($text,'<li>');
                        $text = str_replace("<li>","- ",$text);
                        $text = str_replace("</li>","\n",$text);
                        $text = str_replace('"',"''",$text);
                        $entity .= "<a-entity data-width='13' data-height='13' data-raycastable id='poi_content_$id_poi' material='alpha-test:0.5;opacity:0.75;transparent:true;color:black;' geometry='primitive:plane;width:auto;height:auto;' text=\"width:auto;color:#fff;align:center;zOffset:1;value:$text;\" planepadder='padding:0.2;addPadding:true;' rotation='-7 0 0' position='0 0 -11' scale='0 0 0' visible='false'></a-entity>";
                        break;
                }
                $entity .= "</a-entity>";
            }
            $entity .= "</a-entity>";
            echo $entity;
        }
        ?>
    </a-entity>
    <a-image id='close_video360' billboard data-raycastable material='alpha-test:0.5;transparent:true;' src='#close' scale='0 0 0' rotation='0 0 0' position='0 -11 0'></a-image>
    <?php if(!empty($song)) : ?>
    <a-sound src="#background_music"></a-sound>
    <?php endif; ?>
    <a-sky id="overlay" radius="400" opacity="0" color="#000000" position="0 0 0"></a-sky>
    <a-sky id="skybox" data-id-room="<?php echo $first_id_room;?>" src="#r_<?php echo $first_id_room; ?>" position="0 0 0" rotation="0 <?php echo $first_north; ?> 0"></a-sky>
    <a-entity id=“cam_wrapper” rotation="0 <?php echo $cam_rotation_fix; ?> 0">
        <a-entity id="cam" position="0 1.6 0" camera="fov:80" camera look-controls="touchEnabled: false"
                  animation__zoomin="property:camera.fov;dur:600;to:60;startEvents:zoomin;"
                  animation__zoomout="property:camera.fov;dur:400;to:80;startEvents:zoomout;">
            <a-text font="font/Roboto-Regular-msdf.json" font-image="font/Roboto-Regular.png" negate="false" id="room_name" value="<?php echo $first_room_name; ?>" color="white" align="center" position="0 0.25 -4" scale="0 0 0" opacity="0"
                    animation__fadein="property:opacity;to:1;dur:400;startEvents:roomNameFadeIn"
                    animation__fadeout="property:opacity;to:0;dur:400;startEvents:roomNameFadeOut"></a-text>
            <a-text font="font/Roboto-Regular-msdf.json" font-image="font/Roboto-Regular.png" negate="false" id="msg_close_video" value="<?php echo _("Look down to close the video"); ?>" color="white" align="center" position="0 0.25 -4" scale="0 0 0"></a-text>
            <a-entity id="cursor-visual" cursor="fuse:true;fuseTimeout:2000"
                      material="shader:flat;color:#ffffff;opacity:1;"
                      position="0 0 -0.9"
                      geometry="primitive: ring; radiusInner: 0.01; radiusOuter: 0.015;thetaLength:0"
                      raycaster="objects: [data-raycastable]"
                      animation="property: geometry.thetaLength; dir: alternate; dur: 250; easing: easeInSine; from:0;to: 360;startEvents:startFuseFix;pauseEvents:stopFuse;autoplay:false"
                      animation__mouseenter="property: geometry.thetaLength; dir: alternate; dur: 2000; easing: easeInSine; from:0;to: 360;startEvents:startFuse;pauseEvents:stopFuse;autoplay:false"
                      animation__mouseleave="property: geometry.thetaLength; dir: alternate; dur: 500; easing: easeInSine; to: 0;startEvents:stopFuse;autoplay:false">
                <a-entity id="cursor-visual-bg" geometry="primitive:ring;radiusOuter:0.015;radiusInner:0.01" material="shader:flat;color:#000000;opacity:1;"></a-entity>
            </a-entity>
            <a-plane id="camfadeplane" rotation="10 0.5 0" position="0 0 -0.5" material="color:#000000;transparent:true;opacity:0" width="3" height="3"
                     animation__fadein="property:material.opacity;to:1;dur:300;startEvents:camFadeIn"
                     animation__fadeout="property:material.opacity;to:0;dur:200;startEvents:camFadeOut"></a-plane>
        </a-entity>
    </a-entity>
</a-scene>
<?php if($ga_tracking_id!='' && $export==0) : ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_tracking_id; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo $ga_tracking_id; ?>');
    </script>
<?php endif; ?>
<?php if($export==0) : ?>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js', {
            scope: '.'
        });
    }
</script>
<?php endif; ?>
<script>
    window.id_frist_room = <?php echo $first_room; ?>;
</script>
</body>
</html>
<?php
function print_favicons_vt($code,$logo,$export) {
    $path = '';
    $version = time();
    $path_m = 'vr_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/../favicons/vr_'.$code.'/favicon.ico')) {
        $path = $path_m;
        $version = preg_replace('/[^0-9]/', '', $logo);
    } else if (file_exists(dirname(__FILE__).'/../favicons/custom/favicon.ico')) {
        $path = 'custom/';
    }
    if($export==1) {
        $path = "favicons/".$path;
        $manifest = "";
    } else {
        $path = "../favicons/".$path;
        if (file_exists(dirname(__FILE__).'/../favicons/vr_'.$code.'/site.webmanifest')) {
            $manifest = '<link rel="manifest" href="../favicons/'.$path_m.'site.webmanifest?v='.$version.'">';
        } else {
            $manifest = "";
        }
    }
    return '<link rel="apple-touch-icon" sizes="180x180" href="'.$path.'apple-touch-icon.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="32x32" href="'.$path.'favicon-32x32.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="16x16" href="'.$path.'favicon-16x16.png?v='.$version.'">
    '.$manifest.'
    <link rel="mask-icon" href="'.$path.'safari-pinned-tab.svg?v='.$version.'" color="#ffffff">
    <link rel="shortcut icon" href="'.$path.'favicon.ico?v='.$version.'">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="'.$path.'browserconfig.xml?v='.$version.'">
    <meta name="theme-color" content="#ffffff">';
}
function getIPAddress() {
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
?>