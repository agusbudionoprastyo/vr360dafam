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
$v = time();
$s3Client = null;
$s3_enabled = false;
if((isset($_GET['furl'])) || (isset($_GET['code']))) {
    if(isset($_GET['furl'])) {
        $furl = $_GET['furl'];
        $where = "v.friendly_l_url = '$furl'";
    }
    if(isset($_GET['code'])) {
        $code = $_GET['code'];
        $where = "v.code = '$code'";
    }
    $query = "SELECT v.id,IFNULL(p.expire_tours,1) as expire_tours,v.html_landing,v.code,v.logo,v.name as name_virtualtour,v.background_image,v.description,u.expire_plan_date,v.start_date,v.end_date,v.start_url,v.end_url,u.id_subscription_stripe,u.status_subscription_stripe,v.meta_title_l,v.meta_description_l,v.meta_image_l FROM svt_virtualtours AS v
                JOIN svt_users AS u ON u.id=v.id_user
                LEFT JOIN svt_plans AS p ON p.id=u.id_plan
                WHERE $where AND v.active=1 LIMIT 1;";
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
            $id_virtualtour = $row['id'];
            $s3_params = check_s3_tour_enabled($id_virtualtour);
            if(!empty($s3_params)) {
                $s3_bucket_name = $s3_params['bucket'];
                if($s3Client==null) {
                    $s3Client = init_s3_client_no_wrapper($s3_params);
                    if($s3Client==null) {
                        $s3_enabled = false;
                    } else {
                        if(!empty($s3_params['custom_domain'])) {
                            $s3_url = "https://".$s3_params['custom_domain']."/viewer/";
                        } else {
                            try {
                                $s3_url = $s3Client->getObjectUrl($s3_bucket_name, '.')."viewer/";
                            } catch (Aws\Exception\S3Exception $e) {}
                        }
                        $s3_enabled = true;
                    }
                } else {
                    $s3_enabled = true;
                }
            }
            $code = $row['code'];
            $name_virtualtour = strtoupper($row['name_virtualtour']);
            $background_image = $row['background_image'];
            $logo = $row['logo'];
            $description = $row['description'];
            $html_landing = $row['html_landing'];
            if(empty($row['meta_title_l'])) {
                $meta_title = $name_virtualtour;
            } else {
                $meta_title = $row['meta_title_l'];
            }
            if(empty($row['meta_description_l'])) {
                $meta_description = $row['description'];
            } else {
                $meta_description = $row['meta_description_l'];
            }
            if(empty($row['meta_image_l'])) {
                $meta_image = $row['background_image'];
            } else {
                $meta_image = $row['meta_image_l'];
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
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$url = str_replace("/landing/","/",$url);

$iframe_html = "<iframe allowfullscreen allow=\"gyroscope; accelerometer; xr; microphone *\" width=\"100%\" height=\"100%\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"".$url."viewer/index.php?code=$code\"></iframe>";
$html_landing = str_replace("<img style=\"width: 100%;\" src=\"snippets/preview/vt_preview.jpg\">",$iframe_html,$html_landing);
?>
<!DOCTYPE HTML>
<html>
<head>
    <title><?php echo $meta_title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <meta property="og:type" content="website">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="og:url" content="<?php echo $url."landing/index.php?code=".$code; ?>">
    <meta property="twitter:url" content="<?php echo $url."landing/index.php?code=".$code; ?>">
    <meta itemprop="name" content="<?php echo $meta_title; ?>">
    <meta property="og:title" content="<?php echo $meta_title; ?>">
    <meta property="twitter:title" content="<?php echo $meta_title; ?>">
    <?php if($meta_image!='') : ?>
        <meta itemprop="image" content="<?php echo (($s3_enabled) ? $s3_url : $url.'viewer/')."content/".$meta_image; ?>">
        <meta property="og:image" content="<?php echo (($s3_enabled) ? $s3_url : $url.'viewer/')."content/".$meta_image; ?>" />
        <meta property="twitter:image" content="<?php echo (($s3_enabled) ? $s3_url : $url.'viewer/')."content/".$meta_image; ?>">
    <?php endif; ?>
    <?php if($meta_description!='') : ?>
        <meta itemprop="description" content="<?php echo $meta_description; ?>">
        <meta name="description" content="<?php echo $meta_description; ?>"/>
        <meta property="og:description" content="<?php echo $meta_description; ?>" />
        <meta property="twitter:description" content="<?php echo $meta_description; ?>">
    <?php endif; ?>
    <?php echo print_favicons_vt($code,$logo); ?>
    <link rel="stylesheet" type="text/css" href="../backend/vendor/keditor/plugins/bootstrap-3.4.1/css/bootstrap.min.css" data-type="keditor-style" />
</head>
<body>
    <style>
        body {
            overflow-x: hidden;
        }
        .row {
            padding: 15px;
        }
    </style>
    <?php echo $html_landing; ?>
</body>
</html>

<?php
function print_favicons_vt($code,$logo) {
    $path = '';
    $path_m = 'v_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/../favicons/v_'.$code.'/favicon.ico')) {
        $path = $path_m;
    } else if (file_exists(dirname(__FILE__).'/../favicons/custom/favicon.ico')) {
        $path = 'custom/';
    }
    $version = preg_replace('/[^0-9]/', '', $logo);
    return '<link rel="apple-touch-icon" sizes="180x180" href="../favicons/'.$path.'apple-touch-icon.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicons/'.$path.'favicon-32x32.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicons/'.$path.'favicon-16x16.png?v='.$version.'">
    <link rel="manifest" href="../favicons/'.$path_m.'site.webmanifest?v='.$version.'">
    <link rel="mask-icon" href="../favicons/'.$path.'safari-pinned-tab.svg?v='.$version.'" color="#ffffff">
    <link rel="shortcut icon" href="../favicons/'.$path.'favicon.ico?v='.$version.'">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="../favicons/'.$path.'browserconfig.xml?v='.$version.'">
    <meta name="theme-color" content="#ffffff">';
}
?>