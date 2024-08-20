<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();
require_once("functions.php");
if(!file_exists("../config/config.inc.php")) {
    header("Location: ../install/start.php");
} else {
    if(!isset($_SESSION['full_group_by'])) {
        require_once("../config/config.inc.php");
        if (defined('FULL_GROUP_BY')) {
            $_SESSION['full_group_by'] = FULL_GROUP_BY;
        } else {
            $result = $mysqli->query("SELECT @@SESSION.sql_mode;");
            if($result) {
                $row = mysqli_fetch_array($result, MYSQLI_NUM);
                if (strpos($row[0], "ONLY_FULL_GROUP_BY") !== false) {
                    $_SESSION['full_group_by'] = true;
                } else {
                    $_SESSION['full_group_by'] = false;
                }
            } else {
                $_SESSION['full_group_by'] = false;
            }
        }
    }
}
if(isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
} else {
    header("Location:login.php");
    exit;
}
if(check_maintenance_mode('backend')) {
    if(file_exists("../error_pages/custom/maintenance_backend.html")) {
        include("../error_pages/custom/maintenance_backend.html");
    } else {
        include("../error_pages/default/maintenance_backend.html");
    }
    exit;
}
$session_id = session_id();
$_SESSION['svt_si']=$session_id;
$version = "7.7.1";
$rev = 2;
$v = time();
$settings = get_settings();
$need_update = false;
if(!empty($settings['version'])) {
    $version_c = $settings['version'];
    if($version!=$version_c) {
        $need_update = true;
    }
} else {
    $need_update = true;
}

if($_SESSION['latest_version']=="") {
    $_SESSION['latest_version'] = $version;
}
$latest_version = $_SESSION['latest_version'];
$user_info = get_user_info($id_user);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    if(empty($_SESSION['lang'])) {
        $_SESSION['lang']=$settings['language'];
    }
    set_language($settings['language'],$settings['language_domain']);
}
$user_stats = get_user_stats($id_user);
$plan_info = get_plan($user_info['id_plan']);
if(isset($_GET['p'])) {
    $page = $_GET['p'];
} else {
    $page = "dashboard";
}
if(file_exists("../config/demo.inc.php")) {
    require_once("../config/demo.inc.php");
    $_SESSION['demo_developer_ip']=DEMO_DEVELOPER_IP;
    $_SESSION['demo_server_ip']=DEMO_SERVER_IP;
    $_SESSION['demo_user_id']=DEMO_USER_ID;
    if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) {
        $demo = true;
    } else {
        $demo = false;
    }
    if(($_SERVER['REMOTE_ADDR']==$_SESSION['demo_developer_ip'])) {
        $k = time();
    } else {
        $k = $version."_".$rev;
    }
} else {
    $demo = false;
    $k = $version."_".$rev;
    $_SESSION['demo_developer_ip']='';
    $_SESSION['demo_server_ip']='';
    $_SESSION['demo_user_id']='';
}
$_SESSION['demo'] = $demo;
$_SESSION['theme_color']=$settings['theme_color'];
$_SESSION['sidebar_color_1']=$settings['sidebar_color_1'];
$_SESSION['sidebar_color_2']=$settings['sidebar_color_2'];
$_SESSION['theme_color_dark']=$settings['theme_color_dark'];
$_SESSION['sidebar_color_1_dark']=$settings['sidebar_color_1_dark'];
$_SESSION['sidebar_color_2_dark']=$settings['sidebar_color_2_dark'];
$_SESSION['input_license']=0;
if(isset($_GET['wstep'])) {
    $wizard_step = $_GET['wstep'];
} else {
    $wizard_step = -1;
}
if(!isset($_SESSION['logged_in'])) {
    if(!$need_update) update_user_space_storage($id_user,false);
    $_SESSION['logged_in']=true;
}
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$base_url = str_replace("backend/","",$url);
if(isset($_SESSION['social_identifier'])) {
    unset($_SESSION['social_identifier']);
}
if(isset($_SESSION['social_provider'])) {
    unset($_SESSION['social_provider']);
}
if(isset($_SESSION['tab_edit_room'])) {
    if($page!='rooms' && $page!='edit_room') {
        unset($_SESSION['tab_edit_room']);
    }
}
if(isset($_SESSION['tab_edit_room_preview'])) {
    if($page!='rooms' && $page!='edit_room') {
        unset($_SESSION['tab_edit_room_preview']);
    }
}
$deepl_api_key = $settings['deepl_api_key'];
$enable_deepl = $settings['enable_deepl'];
if($enable_deepl && !empty($deepl_api_key)) {
    $deepl = 1;
} else {
    $deepl = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="author" content="">
    <title><?php echo $settings['name']; ?></title>
    <?php echo print_favicons_backend($settings['logo']); ?>
    <?php if(in_array($page,['markers','pois','measurements'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/slick/slick.css">
    <link rel="stylesheet" type="text/css" href="vendor/slick/slick-theme.css">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/fontawesome-free/css/all.min.css?v=5.15.4">
    <?php switch ($settings['font_provider']) {
        case 'google': ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link rel='stylesheet' type="text/css" crossorigin="anonymous" id="font_backend_link" href="https://fonts.googleapis.com/css2?family=<?php echo $settings['font_backend']; ?>">
            <?php break;
        case 'collabs': ?>
            <link rel="preconnect" href="https://api.fonts.coollabs.io" crossorigin>
            <link rel="stylesheet" type="text/css" id="font_backend_link" href="https://api.fonts.coollabs.io/css2?family=<?php echo $settings['font_backend']; ?>&display=swap">
            <?php break;
        default: ?>
            <link rel="stylesheet" type="text/css" crossorigin="anonymous" id="font_backend_link" href="">
            <?php break;
    } ?>
    <link rel="stylesheet" type="text/css" href="css/sb-admin-2.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/jquery-ui/jquery-ui.min.css">
    <?php if(in_array($page,['edit_virtual_tour','edit_room','rooms','edit_virtual_tour_ui','markers','pois','measurements','presentation','edit_video'])) : ?>
    <link rel="stylesheet" type="text/css" href="../viewer/css/pannellum.css">
    <?php endif; ?>
    <?php if(in_array($page,['leads','advertisements','forms_data','users','edit_user','plans','showcases','globes','products','edit_virtual_tour','video'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/datatables/dataTables.bootstrap4.min.css">
    <?php endif; ?>
    <?php if(in_array($page,['edit_virtual_tour','edit_virtual_tour_ui','markers','pois','edit_product','plans'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/iconpicker/iconpicker-1.5.0.css">
    <?php endif; ?>
    <?php if(in_array($page,['settings','pois','markers','edit_product','edit_virtual_tour','features'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/quill/quill.core.css">
    <link rel="stylesheet" type="text/css" href="vendor/quill/quill.snow.css">
    <link rel="stylesheet" type="text/css" href="vendor/quill/quill.bubble.css">
    <link rel="stylesheet" href="vendor/highlightjs/hljs.min.css">
    <?php endif; ?>
    <?php if(in_array($page,['edit_virtual_tour_ui','poi_object360','gallery','icons_library','maps_bulk','media_library','music_library','sound_library','poi_embed_gallery','poi_gallery','rooms_bulk','pois','markers','edit_product'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/dropzone/dropzone.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/dropzone/basic.min.css">
    <?php endif; ?>
    <?php if(in_array($page,['edit_map','edit_room','edit_showcase','edit_globe','edit_virtual_tour','edit_virtual_tour_ui','markers','pois','settings','measurements','edit_video','edit_product'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/spectrum/spectrum.min.css?v=2.0.9">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/tooltipster/css/tooltipster.bundle.min.css">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-borderless.min.css">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-white.min.css">
    <?php if(in_array($page,['edit_virtual_tour','edit_room','rooms','edit_virtual_tour_ui','markers','pois','presentation','measurements','video360'])) : ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/videojs/video-js.min.css?v=8.3.0">
    <?php endif; ?>
    <?php if(in_array($page,['rooms','edit_virtual_tour_ui'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/Nestable2/jquery.nestable.min.css">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap-select/css/bootstrap-select.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/selectator/fm.selectator.jquery.css">
    <?php if(in_array($page,['edit_profile'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/croppie/croppie.min.css">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap-select-country/css/bootstrap-select-country.min.css">
    <?php if(in_array($page,['edit_map','edit_room','edit_globe'])) : ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/leaflet/leaflet.css">
    <?php endif; ?>
    <?php if(in_array($page,['edit_virtual_tour','edit_virtual_tour_ui','settings'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/jquery.fontpicker/jquery.fontpicker.min.css?v=1.6">
    <?php endif; ?>
    <?php if(in_array($page,['pois'])) : ?>
    <link rel="stylesheet" type='text/css' href="../viewer/vendor/fancybox/jquery.fancybox.min.css">
    <?php endif; ?>
    <?php if(in_array($page,['pois','markers','edit_room'])) : ?>
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/glide/glide.core.min.css">
    <link rel="stylesheet" type="text/css" href="../viewer/vendor/glide/glide.theme.min.css">
    <?php endif; ?>
    <?php if(in_array($page,['edit_room'])) : ?>
    <link rel="stylesheet" type="text/css" href="../viewer/css/effects.css">
    <?php endif; ?>
    <?php if(in_array($page,['markers','pois','edit_virtual_tour_ui'])) : ?>
    <link rel="stylesheet" type="text/css" href="../viewer/css/animate.min.css">
    <?php endif; ?>
    <?php if($settings['enable_wizard']) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/shepherd/shepherd.css?v=10.0.1">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap4-toggle/bootstrap4-toggle.min.css">
    <?php if(in_array($page,['publish','landing','edit_showcase','edit_globe'])) : ?>
    <link rel="stylesheet" type="text/css" href="vendor/jssocials/jssocials.css">
    <link rel="stylesheet" type="text/css" href="vendor/jssocials/jssocials-theme-flat.css">
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" id="css_theme" href="css/theme.php?v=<?php echo $v; ?>">
    <link rel="stylesheet" type="text/css" id="css_theme_dark" href="css/theme_dark.php?v=<?php echo $v; ?>">
    <link rel="stylesheet" type="text/css" href="css/custom.css?v=<?php echo $k; ?>">
    <link rel="stylesheet" type="text/css" href="css/dark_mode.css?v=<?php echo $k; ?>">
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_b.css')) : ?>
    <link rel="stylesheet" type="text/css" href="css/custom_b.css?v=<?php echo $v; ?>">
    <?php endif; ?>
    <script type="text/javascript" src="vendor/jquery/jquery.min.js?v=3.7.1"></script>
    <script type="text/javascript" src="vendor/jquery-ui/jquery-ui.min.js?v=1.13.2"></script>
    <script type="text/javascript" src="vendor/jquery-ui/jquery.ui.touch-punch.min.js"></script>
    <script type="text/javascript" src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="vendor/bootstrap/js/bs-custom-file-input.min.js"></script>
    <script type="text/javascript" src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <?php if(in_array($page,['markers','pois','measurements'])) : ?>
    <script type="text/javascript" src="vendor/slick/slick.min.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="../viewer/js/mobile-detect.min.js"></script>
    <?php if(in_array($page,['edit_virtual_tour','edit_room','rooms','edit_virtual_tour_ui','markers','pois','measurements','presentation','edit_video'])) : ?>
    <script>
        window.quality_viewer = 1;
        window.zoom_to_pointer = 0;
    </script>
    <script type="text/javascript" src="../viewer/js/libpannellum.js?v=<?php echo $k; ?>"></script>
    <script type="text/javascript" src="../viewer/js/pannellum.js?v=<?php echo $k; ?>"></script>
    <script type="text/javascript" src="../viewer/vendor/videojs/video.min.js?v=8.5.2"></script>
    <script type="text/javascript" src="../viewer/js/videojs-pannellum-plugin.js"></script>
    <script type="text/javascript" src="../viewer/vendor/videojs/youtube.min.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_room','edit_showcase','edit_globe','publish','landing','dollhouse'])) : ?>
    <script type="text/javascript" src="vendor/clipboard.js/clipboard.min.js?v=2.0.11"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_virtual_tour','edit_virtual_tour_ui','markers','pois','edit_product','plans'])) : ?>
    <script type="text/javascript" src="vendor/iconpicker/iconpicker-1.5.0.js?v=1.5"></script>
    <?php endif; ?>
    <?php if(in_array($page,['settings','pois','markers','edit_product','edit_virtual_tour','features'])) : ?>
    <script type="text/javascript" src="vendor/highlightjs/hljs.min.js"></script>
    <script type="text/javascript" src="vendor/highlightjs/xml.min.js"></script>
    <script type="text/javascript" src="vendor/quill/quill.min.js"></script>
    <script type="text/javascript" src="vendor/quill/quill.html.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_virtual_tour_ui','poi_object360','gallery','icons_library','maps_bulk','media_library','music_library','sound_library','poi_embed_gallery','poi_gallery','rooms_bulk','pois','markers','edit_product'])) : ?>
    <script type="text/javascript" src="vendor/dropzone/dropzone.min.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['poi_object360','gallery','maps','rooms','poi_embed_gallery','poi_gallery','edit_virtual_tour','pois','edit_product','video360','edit_room','edit_video','edit_showcase'])) : ?>
    <script type="text/javascript" src="vendor/Sortable.min.js?v=1.14"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_map','edit_room','edit_showcase','edit_globe','edit_virtual_tour','edit_virtual_tour_ui','markers','pois','settings','measurements','edit_video','edit_product'])) : ?>
    <script type="text/javascript" src="vendor/spectrum/spectrum.min.js?v=2.0.9"></script>
    <?php endif; ?>
    <script type="text/javascript" src="../viewer/vendor/tooltipster/js/tooltipster.bundle.min.js"></script>
    <?php if(in_array($page,['statistics','statistics_all','edit_user'])) : ?>
    <script type="text/javascript" src="vendor/chart.js/Chart.min.js"></script>
    <script type="text/javascript" src="vendor/hchart/hs.min.js?v=1"></script>
    <script type="text/javascript" src="vendor/hchart/exporting.js"></script>
    <script type="text/javascript" src="vendor/hchart/accessibility.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['rooms','edit_virtual_tour_ui'])) : ?>
    <script type="text/javascript" src="vendor/Nestable2/jquery.nestable.min.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['virtual_tours','rooms','maps','publish','landing','edit_showcase','edit_globe','edit_user'])) : ?>
    <script type="text/javascript" src="vendor/jquery.searchable-1.1.0.min.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="vendor/bootstrap4-toggle/bootstrap4-toggle.min.js"></script>
    <?php if(in_array($page,['pois','edit_showcase','edit_globe','publish','settings','edit_virtual_tour_ui','edit_virtual_tour','landing','info_box'])) : ?>
    <script type="text/javascript" src="vendor/ace-editor/ace.js?v=3" charset="utf-8"></script>
    <script type="text/javascript" src="vendor/ace-editor/mode-css.js?v=3" charset="utf-8"></script>
    <script type="text/javascript" src="vendor/ace-editor/mode-javascript.js?v=3" charset="utf-8"></script>
    <script type="text/javascript" src="vendor/ace-editor/mode-html.js?v=3" charset="utf-8"></script>
    <script type="text/javascript" src="vendor/ace-editor/ext-language_tools.js?v=3" charset="utf-8"></script>
    <script type="text/javascript" src="vendor/ace-editor/theme-one_dark.min.js?v=7" charset="utf-8"></script>
    <?php endif; ?>
    <?php if(in_array($page,['leads','advertisements','forms_data','users','edit_user','plans','showcases','globes','products','edit_virtual_tour','video'])) : ?>
    <script type="text/javascript" src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="vendor/bootstrap-select/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" src="vendor/selectator/fm.selectator.jquery.js"></script>
    <?php if(in_array($page,['edit_profile'])) : ?>
    <script type="text/javascript" src="vendor/croppie/croppie.min.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="vendor/bootstrap-select-country/js/bootstrap-select-country.min.js?v=2"></script>
    <?php if(in_array($page,['edit_map','edit_room','edit_globe'])) : ?>
    <script type="text/javascript" src="../viewer/vendor/leaflet/leaflet.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="../viewer/js/numeric.min.js"></script>
    <?php if(in_array($page,['edit_virtual_tour_ui','settings'])) : ?>
    <?php
        switch ($settings['font_provider']) {
            case 'collabs':
                echo '<script type="text/javascript" src="vendor/jquery.fontpicker/jquery.fontpicker.collabs.min.js?v=1.6"></script>';
                break;
            default:
                echo '<script type="text/javascript" src="vendor/jquery.fontpicker/jquery.fontpicker.min.js?v=1.6"></script>';
                break;
        }
    ?>
    <?php endif; ?>
    <?php if(in_array($page,['pois','markers','edit_room'])) : ?>
    <script type="text/javascript" src="../viewer/vendor/fancybox/jquery.fancybox.min.js"></script>
    <script type="module" src="../viewer/js/model-viewer.min.js?v=3.2.1"></script>
    <?php endif; ?>
    <?php if(in_array($page,['pois','markers','edit_room'])) : ?>
    <script type="text/javascript" src="../viewer/vendor/glide/glide.min.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_room'])) : ?>
    <script type="text/javascript" src="../viewer/js/effects.js?v=2"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_virtual_tour_ui','edit_room','rooms','markers','pois','icons_library','media_library'])) : ?>
    <script type="text/javascript" src="../viewer/js/lottie.min.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_room','markers','pois','measurements'])) : ?>
    <script type="text/javascript" src="../viewer/js/pixi.min.js?v=6.5.9"></script>
    <?php endif; ?>
    <?php if(in_array($page,['edit_room'])) : ?>
    <script type="text/javascript" src="../viewer/js/hls.min.js"></script>
    <?php endif; ?>
    <?php if($settings['enable_wizard']) : ?>
    <script type="text/javascript" src="vendor/shepherd/shepherd.min.js?v=10.0.1"></script>
    <?php endif; ?>
    <script>window.wizard_step=<?php echo $wizard_step; ?>;</script>
    <?php if(in_array($page,['dollhouse'])) : ?>
    <script type="text/javascript" src="../viewer/vendor/threejs/three.min.js?v=139"></script>
    <script type="text/javascript" src="../viewer/vendor/threejs/GLTFLoader.min.js"></script>
    <script type="text/javascript" src="../viewer/vendor/threejs/GLTFExporter.js?v=139"></script>
    <script type="text/javascript" src="../viewer/vendor/threejs/OrbitControls.js"></script>
    <script type="text/javascript" src="../viewer/vendor/threejs/TransformControls.js"></script>
    <script type="text/javascript" src="../viewer/vendor/threejs/lil-gui.js"></script>
    <script type="text/javascript" src="../viewer/vendor/threejs/threex.domevents.js?v=2"></script>
    <?php endif; ?>
    <?php if(in_array($page,['publish','landing','edit_showcase','edit_globe'])) : ?>
    <script type="text/javascript" src="vendor/jssocials/jssocials.js?v=6"></script>
    <?php endif; ?>
    <?php if(in_array($page,['dashboard'])) : ?>
    <script type="text/javascript" src="js/jquery.twbsPagination.min.js"></script>
    <?php endif; ?>
    <?php if(in_array($page,['video360'])) : ?>
    <script type="text/javascript" src="../viewer/vendor/videojs/video.min.js?v=8.5.2"></script>
    <script type="text/javascript" src="../viewer/vendor/videojs/videojs-vr.min.js?v=2.0"></script>
    <?php endif; ?>
    <?php if(in_array($page,['measurements'])) : ?>
    <script type="text/javascript" src="../viewer/vendor/leaderLine/leader-line.min.js?v=2"></script>
    <?php endif; ?>
    <?php if(in_array($page,['rooms'])) : ?>
    <script type="text/javascript" src="js/pusher.min.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="js/purify.min.js"></script>
    <script type="text/javascript" src="js/pace.min.js"></script>
    <script type="text/javascript" src="js/function.js?v=<?php echo $k; ?>"></script>
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.'custom_b.js')) : ?>
    <script type="text/javascript" src="js/custom_b.js?v=<?php echo $v; ?>"></script>
    <?php endif; ?>
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'header'.DIRECTORY_SEPARATOR.'custom_b.php')) {
        include(__DIR__.DIRECTORY_SEPARATOR.'header'.DIRECTORY_SEPARATOR.'custom_b.php');
    } ?>
</head>
<body id="page-top" class="<?php echo ($settings['dark_mode']==2) ? 'dark_mode' : ''; ?>">
    <script>
        var dark_mode_setting = <?php echo $settings['dark_mode']; ?>;
        var dark_mode = '0';
        if(dark_mode_setting==1) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                dark_mode = '1';
            }
            if (localStorage.getItem("dark_mode") === null) {
                localStorage.setItem("dark_mode",dark_mode);
            } else {
                dark_mode = localStorage.getItem('dark_mode');
            }
            if(dark_mode=='1') document.body.classList.add("dark_mode");
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                dark_mode = e.matches ? '1' : '0';
                if(dark_mode=='1') {
                    $('.btn_mode_switcher').attr('id','btn_dark_mode');
                    $('.btn_mode_switcher').removeClass('fa-sun').addClass('fa-moon');
                    document.body.classList.add("dark_mode");
                    localStorage.setItem("dark_mode",'1');
                } else {
                    $('.btn_mode_switcher').attr('id','btn_light_mode');
                    $('.btn_mode_switcher').removeClass('fa-moon').addClass('fa-sun');
                    document.body.classList.remove("dark_mode");
                    localStorage.setItem("dark_mode",'0');
                }
            });
        }
        window.deepl = <?php echo $deepl; ?>;
    </script>
    <style id="style_css">
        *:not(i):not(.fas):not(.far):not(.fab):not(.leader-line):not(.leader-line *):not(.vjs-icon-placeholder):not(.vjs-play-progress):not(.poi_embed_text *) { font-family: '<?php echo $settings['font_backend']; ?>', sans-serif; }
    </style>
    <script>
        window.base_url = '<?php echo $base_url; ?>';
        window.backend_labels = {
            "add_action":`<?php echo _("ADD ACTION"); ?>`,
            "add_room":`<?php echo _("ADD ROOM"); ?>`,
            "add":`<?php echo _("Add"); ?>`,
            "save":`<?php echo _("Save"); ?>`,
            "search_vt":`<?php echo _("Search Virtual Tour ..."); ?>`,
            "edit":`<?php echo _("EDIT"); ?>`,
            "editor_ui":`<?php echo _("EDITOR UI"); ?>`,
            "dollhouse":`<?php echo _("3D VIEW"); ?>`,
            "rooms":`<?php echo _("ROOMS"); ?>`,
            "maps":`<?php echo _("MAPS"); ?>`,
            "gallery":`<?php echo _("GALLERY"); ?>`,
            "info_box":`<?php echo _("INFO BOX"); ?>`,
            "preview":`<?php echo _("PREVIEW"); ?>`,
            "publish":`<?php echo _("PUBLISH"); ?>`,
            "delete":`<?php echo _("DELETE"); ?>`,
            "search_map":`<?php echo _("Search Map ..."); ?>`,
            "rooms_assigned":`<?php echo _("Rooms assigned"); ?>`,
            "no_rooms_msg":`<?php echo sprintf(_('No rooms created for this Virtual Tour. Go to %s and create a new one!'),'<a href=\'index.php?p=rooms\'>'._("Rooms").'</a>'); ?>`,
            "markers":`<?php echo _("markers"); ?>`,
            "pois":`<?php echo _("pois"); ?>`,
            "measures":`<?php echo _("measures"); ?>`,
            "measure_label":`<?php echo _("measure"); ?>`,
            "content_image":`<?php echo _("Content - Link or upload Image"); ?>`,
            "content_panorama_image":`<?php echo _("Content - Panorama Image"); ?>`,
            "content_lottie":`<?php echo _("Content - Link or upload Json"); ?>`,
            "content_image_embed":`<?php echo _("Embedded - Link or upload Image"); ?>`,
            "content_video":`<?php echo _("Content - Youtube/Vimeo Link or upload Video MP4 / WEBM"); ?>`,
            "content_video_embed":`<?php echo _("Embedded - Youtube Link or upload Video MP4 / WEBM"); ?>`,
            "content_video_embed_transparent":`<?php echo _("Embedded - upload Video WEBM + MOV"); ?>`,
            "content_video_embed_chroma":`<?php echo _("Embedded - upload Video MP4 with Chroma background"); ?>`,
            "content_audio":`<?php echo _("Content - Audio MP3 Link or upload Audio MP3"); ?>`,
            "content_video360":`<?php echo _("Content - Video 360 MP4"); ?>`,
            "content_link_emb":`<?php echo _("Embedded - Link"); ?>`,
            "content_text_emb":`<?php echo _("Embedded - Text"); ?>`,
            "content_html_emb":`<?php echo _("Embedded - HTML"); ?>`,
            "content_link":`<?php echo _("Content - Link"); ?>`,
            "content_pdf":`<?php echo _("Content - PDF Link or upload PDF"); ?>`,
            "content_pointclouds":`<?php echo _("Content - upload a ZIP containing Js/Json and Point Cloud data"); ?>`,
            "content_link_ext":`<?php echo _("Content - Link (external)"); ?>`,
            "content_file":`<?php echo _("Content - Link or upload a File"); ?>`,
            "content_object3d":`<?php echo _("Content - Object 3D")." (GLB/GLTF + USDZ)"; ?>`,
            "content_object3d_emb":`<?php echo _("Embedded - Object 3D")." (GLB/GLTF)"; ?>`,
            "select_icon_msg":`<?php echo _("Please select an icon from library"); ?>`,
            "search_room":`<?php echo _("Search Room ..."); ?>`,
            "drag_change_pos":`<?php echo _("DRAG TO CHANGE POSITION"); ?>`,
            "panorama_image":`<?php echo _("Panorama image"); ?>`,
            "panorama_image_msg":`<?php echo _("Accepted only images in JPG/PNG format."); ?>`,
            "panorama_video":`<?php echo _("Panorama video"); ?>`,
            "panorama_video_msg":`<?php echo _("Accepted only 360 degree videos in MP4/WEBM format."); ?>`,
            "panorama_hls":`<?php echo _("Initial Image"); ?>`,
            "panorama_hls_msg":`<?php echo _("The initial image must be the same size as the video stream."); ?>`,
            "panorama_lottie":`<?php echo _("Initial Image"); ?>`,
            "panorama_lottie_msg":`<?php echo _("The initial image must be the same size as the lottie file."); ?>`,
            "valid":`<?php echo _("Valid"); ?>`,
            "invalid":`<?php echo _("Invalid"); ?>`,
            "no_image_msg":`<?php echo _("No images in this gallery."); ?>`,
            "no_files_msg":`<?php echo _("No files in this media library."); ?>`,
            "no_icon_msg":`<?php echo _("No files in this icon library."); ?>`,
            "duplicate":`<?php echo _("DUPLICATE"); ?>`,
            "image":`<?php echo _("Image (single)"); ?>`,
            "video":`<?php echo _("Video"); ?>`,
            "link":`<?php echo _("Link (emded)"); ?>`,
            "link_ext":`<?php echo _("Link (external)"); ?>`,
            "pdf":`PDF`,
            "pointclouds":`<?php echo _("Point Clouds"); ?>`,
            "html":`<?php echo _("Text"); ?>`,
            "html_sc":`<?php echo _("Html"); ?>`,
            "download":`<?php echo _("Download"); ?>`,
            "form":`<?php echo _("Form"); ?>`,
            "video360":`<?php echo _("Video 360"); ?>`,
            "video_projects":`<?php echo _("Video Projects"); ?>`,
            "slideshow":`<?php echo _("Slideshow"); ?>`,
            "image_gallery":`<?php echo _("Images (gallery)"); ?>`,
            "audio":`<?php echo _("Audio"); ?>`,
            "google_maps":`<?php echo _("Map"); ?>`,
            "object360":`<?php echo _("Object 360 (images)"); ?>`,
            "object3d":`<?php echo _("Object 3D")." (GLB/GLTF)"; ?>`,
            "product":`<?php echo _("Product"); ?>`,
            "switch_pano":`<?php echo _("Switch Panorama"); ?>`,
            "callout":`<?php echo _("Callout Text"); ?>`,
            "embed_image":`<?php echo _("Embed (image)"); ?>`,
            "embed_gallery":`<?php echo _("Embed (slideshow)"); ?>`,
            "embed_video":`<?php echo _("Embed (video)"); ?>`,
            "embed_video_transparent":`<?php echo _("Embed (video with transparency)"); ?>`,
            "embed_video_chroma":`<?php echo _("Embed (video with background removal)"); ?>`,
            "embed_object3d":`<?php echo _("Embed (object3d)"); ?>`,
            "embed_link":`<?php echo _("Embed (link)"); ?>`,
            "embed_text":`<?php echo _("Embed (text)"); ?>`,
            "embed_html":`<?php echo _("Embed (html)"); ?>`,
            "embed_selection":`<?php echo _("Selection Area"); ?>`,
            "icon":`<?php echo _("Icon"); ?>`,
            "none":`<?php echo _("None"); ?>`,
            "loading":`<?php echo _("Loading"); ?>`,
            "delete_sure_msg":`<?php echo _("Are you sure you want to delete?"); ?>`,
            "duplicate_sure_msg":`<?php echo _("Are you sure you want to duplicate?"); ?>`,
            "assign_sure_msg":`<?php echo _("Are you sure you want to assign all?"); ?>`,
            "unassign_sure_msg":`<?php echo _("Are you sure you want to unassign all?"); ?>`,
            "file_size_too_big":`<?php echo _("File size is too big."); ?>`,
            "all":`<?php echo _("All"); ?>`,
            "all_categories":`<?php echo _("All Categories"); ?>`,
            "all_users":`<?php echo _("All Users"); ?>`,
            "export_vt":`<?php echo _("Download"); ?>`,
            "change_poi_style_msg":`<?php echo _("Are you sure? the contents of this element will be lost!"); ?>`,
            "color":`<?php echo _("Color"); ?>`,
            "border_color":`<?php echo _("Border Color"); ?>`,
            "rooms_list":`<?php echo _("Rooms List"); ?>`,
            "wizard_title_1":`<?php echo _("Creating a New Tour"); ?>`,
            "wizard_title_2":`<?php echo _("Creating the first Room"); ?>`,
            "wizard_title_3":`<?php echo _("Creating the second Room"); ?>`,
            "wizard_title_4":`<?php echo _("Creating a Marker"); ?>`,
            "wizard_title_6":`<?php echo _("Creating a POI"); ?>`,
            "wizard_title_5":`<?php echo _("Preview the tour"); ?>`,
            "wizard_text_1":`<?php echo _("Click on the menu item <b>Virtual Tours</b>"); ?>`,
            "wizard_text_2":`<?php echo _("Click on the menu item <b>List Tours</b>"); ?>`,
            "wizard_text_3":`<?php echo _("This is the section to create a tour"); ?>`,
            "wizard_text_4":`<?php echo _("Enter the <b>name</b> of the tour"); ?>`,
            "wizard_text_5":`<?php echo _("Click on button <b>Create</b>"); ?>`,
            "wizard_text_6":`<?php echo _("Click on the menu item <b>Rooms</b>"); ?>`,
            "wizard_text_7":`<?php echo _("Click on the <b>plus</b> icon"); ?>`,
            "wizard_text_8":`<?php echo _("Enter the <b>name</b> of the room"); ?>`,
            "wizard_text_9":`<?php echo _("Upload a 360 panorama image for this Room by selecting from the <b>Browse</b> button and then click <b>Upload</b>"); ?>`,
            "wizard_text_10":`<?php echo _("Click on button <b>Create</b>"); ?>`,
            "wizard_text_11":`<?php echo _("Congratulations, your first room has been created, now create the second one"); ?>`,
            "wizard_text_17":`<?php echo _("Congratulations, your second room has been created, now let's look at how to create a marker to navigate from one to the other"); ?>`,
            "wizard_text_18":`<?php echo _("Click on the menu item <b>Markers</b>"); ?>`,
            "wizard_text_19":`<?php echo _("Select the first room"); ?>`,
            "wizard_text_20":`<?php echo _("Drag the view and center the white cursor at the position where you want to add the marker"); ?>`,
            "wizard_text_22":`<?php echo _("Here you can select the destination room linked to this marker"); ?>`,
            "wizard_text_23":`<?php echo _("Click the button <b>Add</b> to create the marker"); ?>`,
            "wizard_text_24":`<?php echo _("Congratulations, your marker has been created, now let's look at how to create a POI (Point Of Interest) to show content on the tour"); ?>`,
            "wizard_text_26":`<?php echo _("Click on the menu item <b>POIs</b>"); ?>`,
            "wizard_text_27":`<?php echo _("Select the first room"); ?>`,
            "wizard_text_28":`<?php echo _("Drag the view and center the white cursor at the position where you want to add the POI"); ?>`,
            "wizard_text_29":`<?php echo _("Here you can select the style and content of your POI"); ?>`,
            "wizard_text_30":`<?php echo _("Let's create a simple POI icon with an image as content"); ?>`,
            "wizard_text_31":`<?php echo _("Upload an image for this Poi by selecting from the <b>Browse</b> button and then click <b>Upload</b>"); ?>`,
            "wizard_text_32":`<?php echo _("Click on button <b>Save</b>"); ?>`,
            "wizard_text_33":`<?php echo _("Congratulations, your POI has been created"); ?>`,
            "wizard_text_25":`<?php echo _("And finally let's preview the tour you just created. Click on the menu item <b>Preview</b>"); ?>`,
            "wizard_continue":`<?php echo _("Continue"); ?>`,
            "wizard_close":`<?php echo _("Are you sure you want to exit the tour creation wizard?"); ?>`,
            "confirm_save_preset":`<?php echo _("Are you sure you want to update the preset?"); ?>`,
            "confirm_delete_preset":`<?php echo _("Are you sure you want to delete the preset?"); ?>`,
            "confirm_apply_preset":`<?php echo _("Are you sure you want to apply the preset?"); ?>`,
            "markers_quick_add":`<?php echo _("QUICK ADD THIS MARKER"); ?>`,
            "markers_add":`<?php echo _("ADD THIS MARKER"); ?>`,
            "list":`<?php echo _("List"); ?>`,
            "grid":`<?php echo _("Grid"); ?>`,
            "generic_error_msg": `<?php echo _("An error has occured."); ?>`,
            "copied":`<?php echo _("copied"); ?>`,
            "preview_presentation":`<?php echo _("preview from here"); ?>`,
            "no_panorams":`<?php echo _("preview from here"); ?>`,
            "main_view":`<?php echo _("Main View"); ?>`,
            "view":`<?php echo _("View"); ?>`,
            "start":`<?php echo _("START"); ?>`,
            "end":`<?php echo _("END"); ?>`,
            "set":`<?php echo _("SET"); ?>`,
        };
        refresh_session();
    </script>
    <?php if($need_update) : ?>
        <div id="updating_msg" class="text-center mt-4">
            <h1 class="text-primary"><?php echo _("UPDATE IN PROGRESS"); ?>...</h1>
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only"><?php echo _("Loading"); ?>...</span>
            </div>
            <p class="text-primary lead text-gray-800 mt-4"><?php echo _("Not close this window"); ?></p>
        </div>
        <script>
            $.ajax({
                url: "ajax/update.php",
                type: "POST",
                data: {
                    version: '<?php echo $version; ?>'
                },
                async: true,
                success: function () {
                    location.reload();
                },
                timeout: 300000
            });
        </script>
    <?php
    exit;
    endif;
    ?>

  <div id="wrapper">
      <?php include_once("sidebar.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
          <?php include_once("topbar.php"); ?>
        <div class="container-fluid">
            <?php
            if(($settings['stripe_enabled']) && !empty($user_info['id_subscription_stripe']) && ($user_info['status_subscription_stripe']==0)) {
                include_once("update_payment.php");
            } else {
                if(($settings['change_plan']) && ($user_info['id_plan']==0)) {
                    include_once("change_plan.php");
                } else {
                    if(check_profile_to_complete($id_user)) {
                        include_once("edit_profile.php");
                    } else {
						include_once("check_quota.php");
						include_once("$page.php");
                    }
                }
            }
            ?>
        </div>
      </div>
        <?php include_once("footer.php"); ?>
    </div>
  </div>
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>
  <script src="js/sb-admin-2.js?v=9"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js?v=<?php echo $version; ?>', {
                scope: '.'
            });
        }
    </script>
</body>
</html>