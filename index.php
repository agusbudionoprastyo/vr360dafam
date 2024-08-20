<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if(!file_exists("config/config.inc.php")) {
    header("Location: install/start.php");
} else {
    if(!file_exists("index.html")) {
        require_once('db/connection.php');
        require_once('backend/functions.php');
        $s3Client = null;
        $s3_enabled = false;
        $query = "SELECT id,code,name,description,background_image,meta_title,meta_description,meta_image,meta_title_l,meta_description_l,meta_image_l,show_in_first_page,show_in_first_page_l FROM svt_virtualtours WHERE (show_in_first_page=1 OR show_in_first_page_l=1) AND active=1 LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $id_vt = $row['id'];
                $s3_params = check_s3_tour_enabled($id_vt);
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
                $code_vt = $row['code'];
                $name_virtualtour = $row['name'];
                $description = $row['description'];
                $background_image = $row['background_image'];
                if($row['show_in_first_page']==1) {
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
                } elseif($row['show_in_first_page_l']==1) {
                    if(empty($row['meta_title_l'])) {
                        $meta_title = $name_virtualtour;
                    } else {
                        $meta_title = $row['meta_title_l'];
                    }
                    if(empty($row['meta_description_l'])) {
                        $meta_description = $description;
                    } else {
                        $meta_description = $row['meta_description_l'];
                    }
                    if(empty($row['meta_image_l'])) {
                        $meta_image = $background_image;
                    } else {
                        $meta_image = $row['meta_image_l'];
                    }
                }
                $html_meta_image = "";
                $html_meta_description = "";
                if(!empty($meta_image)) {
                    if($s3_enabled) {
                        $html_meta_image = '<meta itemprop="image" content="'.$s3_url.'viewer/content/'.$meta_image.'">
                                        <meta property="og:image" content="'.$s3_url.'viewer/content/'.$meta_image.'" />
                                        <meta property="twitter:image" content="'.$s3_url.'viewer/content/'.$meta_image.'">';
                    } else {
                        $html_meta_image = '<meta itemprop="image" content="viewer/content/'.$meta_image.'">
                                        <meta property="og:image" content="viewer/content/'.$meta_image.'" />
                                        <meta property="twitter:image" content="viewer/content/'.$meta_image.'">';
                    }
                }
                if(!empty($meta_description)) {
                    $html_meta_description = '<meta itemprop="description" content="'.$meta_description.'">
                                            <meta name="description" content="'.$meta_description.'"/>
                                            <meta property="og:description" content="'.$meta_description.'" />
                                            <meta property="twitter:description" content="'.$meta_description.'">';
                }
                if($row['show_in_first_page']==1) {
                    $url_iframe = "viewer/index.php?code=$code_vt"."&ignore_embedded=1";
                    $scrolling = "no";
                } elseif($row['show_in_first_page_l']==1) {
                    $url_iframe = "landing/index.php?code=$code_vt";
                    $scrolling = "yes";
                }
                $favicons = print_favicons_vt($code_vt);
                $html = <<<HTML_CODE
<html>
    <head>
        <title>$meta_title</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
        $favicons
        <meta property="og:type" content="website">
        <meta property="twitter:card" content="summary_large_image">
        <meta property="og:url" content="$url_iframe">
        <meta property="twitter:url" content="$url_iframe">
        <meta itemprop="name" content="$meta_title">
        <meta property="og:title" content="$meta_title">
        <meta property="twitter:title" content="$meta_title">
        $html_meta_image
        $html_meta_description
        <style>
            html, body { margin: 0; padding: 0; overflow: hidden; }
            iframe { height: 100%; }
        </style>
    </head>
    <body>
        <iframe allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="100%" frameborder="0" scrolling="$scrolling" marginheight="0" marginwidth="0" src="$url_iframe"></iframe>
    </body>
</html>
HTML_CODE;
                echo $html;
                exit;
            }
        }

        $query = "SELECT code,name,meta_title,meta_description,meta_image FROM svt_globes WHERE show_in_first_page=1 LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $code_globe = $row['code'];
                $name_globe = $row['name'];
                if(empty($row['meta_title'])) {
                    $meta_title = $name_globe;
                } else {
                    $meta_title = $row['meta_title'];
                }
                $meta_description = $row['meta_description'];
                $meta_image = $row['meta_image'];
                $html_meta_image = "";
                $html_meta_description = "";
                if(!empty($meta_image)) {
                    $html_meta_image = '<meta itemprop="image" content="viewer/content/'.$meta_image.'">
                                        <meta property="og:image" content="viewer/content/'.$meta_image.'" />
                                        <meta property="twitter:image" content="viewer/content/'.$meta_image.'">';
                }
                if(!empty($meta_description)) {
                    $html_meta_description = '<meta itemprop="description" content="'.$meta_description.'">
                                            <meta name="description" content="'.$meta_description.'"/>
                                            <meta property="og:description" content="'.$meta_description.'" />
                                            <meta property="twitter:description" content="'.$meta_description.'">';
                }
                $url_iframe = "globe/index.php?code=$code_globe";
                $favicons = print_favicons_globe($code_globe);
                $html = <<<HTML_CODE
<html>
    <head>
        <title>$meta_title</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
        $favicons
        <meta property="og:type" content="website">
        <meta property="twitter:card" content="summary_large_image">
        <meta property="og:url" content="$url_iframe">
        <meta property="twitter:url" content="$url_iframe">
        <meta itemprop="name" content="$meta_title">
        <meta property="og:title" content="$meta_title">
        <meta property="twitter:title" content="$meta_title">
        $html_meta_image
        $html_meta_description
        <style>
            html, body { margin: 0; padding: 0; overflow: hidden; }
            iframe { height: 100%; }
        </style>
    </head>
    <body>
        <iframe allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="$url_iframe"></iframe>
    </body>
</html>
HTML_CODE;
                echo $html;
                exit;
            }
        }

        $query = "SELECT code,name,meta_title,meta_description,meta_image FROM svt_showcases WHERE show_in_first_page=1 LIMIT 1;";
        $result = $mysqli->query($query);
        if($result) {
            if($result->num_rows==1) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $code_showcase = $row['code'];
                $name_showcase = $row['name'];
                if(empty($row['meta_title'])) {
                    $meta_title = $name_showcase;
                } else {
                    $meta_title = $row['meta_title'];
                }
                $meta_description = $row['meta_description'];
                $meta_image = $row['meta_image'];
                $html_meta_image = "";
                $html_meta_description = "";
                if(!empty($meta_image)) {
                    $html_meta_image = '<meta itemprop="image" content="viewer/content/'.$meta_image.'">
                                        <meta property="og:image" content="viewer/content/'.$meta_image.'" />
                                        <meta property="twitter:image" content="viewer/content/'.$meta_image.'">';
                }
                if(!empty($meta_description)) {
                    $html_meta_description = '<meta itemprop="description" content="'.$meta_description.'">
                                            <meta name="description" content="'.$meta_description.'"/>
                                            <meta property="og:description" content="'.$meta_description.'" />
                                            <meta property="twitter:description" content="'.$meta_description.'">';
                }
                $url_iframe = "showcase/index.php?code=$code_showcase";
                $favicons = print_favicons_showcase($code_showcase);
                $html = <<<HTML_CODE
<html>
    <head>
        <title>$meta_title</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
        $favicons
        <meta property="og:type" content="website">
        <meta property="twitter:card" content="summary_large_image">
        <meta property="og:url" content="$url_iframe">
        <meta property="twitter:url" content="$url_iframe">
        <meta itemprop="name" content="$meta_title">
        <meta property="og:title" content="$meta_title">
        <meta property="twitter:title" content="$meta_title">
        $html_meta_image
        $html_meta_description
        <style>
            html, body { margin: 0; padding: 0; overflow: hidden; }
            iframe { height: 100%; }
        </style>
    </head>
    <body>
        <iframe allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" width="100%" height="100%" frameborder="0" scrolling="yes" marginheight="0" marginwidth="0" src="$url_iframe"></iframe>
    </body>
</html>
HTML_CODE;
                echo $html;
                exit;
            }
        }
        header("Location: backend/login.php");
    } else {
        header("Location: index.html");
    }
}

function print_favicons_vt($code) {
    $path = '';
    $version = time();
    $path_m = 'v_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/favicons/v_'.$code.'/favicon.ico')) {
        $path = $path_m;
    } else if (file_exists(dirname(__FILE__).'/favicons/custom/favicon.ico')) {
        $path = 'custom/';
    }
    $path = "favicons/".$path;
    if (file_exists(dirname(__FILE__).'/favicons/v_'.$code.'/site.webmanifest')) {
        $manifest = '<link rel="manifest" href="favicons/'.$path_m.'site.webmanifest?v='.$version.'">';
    } else {
        $manifest = "";
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
function print_favicons_globe($code) {
    $path = '';
    $version = time();
    $path_m = 'g_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/favicons/g_'.$code.'/favicon.ico')) {
        $path = 'g_'.$code.'/';
    } else if (file_exists(dirname(__FILE__).'/favicons/custom/favicon.ico')) {
        $path = 'custom/';
    }
    return '<link rel="apple-touch-icon" sizes="180x180" href="favicons/'.$path.'apple-touch-icon.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="32x32" href="favicons/'.$path.'favicon-32x32.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="16x16" href="favicons/'.$path.'favicon-16x16.png?v='.$version.'">
    <link rel="manifest" href="favicons/'.$path_m.'site.webmanifest?v='.$version.'">
    <link rel="mask-icon" href="favicons/'.$path.'safari-pinned-tab.svg?v='.$version.'" color="#ffffff">
    <link rel="shortcut icon" href="favicons/'.$path.'favicon.ico?v='.$version.'">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="favicons/'.$path.'browserconfig.xml?v='.$version.'">
    <meta name="theme-color" content="#ffffff">';
}
function print_favicons_showcase($code) {
    $path = '';
    $version = time();
    $path_m = 's_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/favicons/s_'.$code.'/favicon.ico')) {
        $path = 's_'.$code.'/';
    } else if (file_exists(dirname(__FILE__).'/favicons/custom/favicon.ico')) {
        $path = 'custom/';
    }
    return '<link rel="apple-touch-icon" sizes="180x180" href="favicons/'.$path.'apple-touch-icon.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="32x32" href="favicons/'.$path.'favicon-32x32.png?v='.$version.'">
    <link rel="icon" type="image/png" sizes="16x16" href="favicons/'.$path.'favicon-16x16.png?v='.$version.'">
    <link rel="manifest" href="favicons/'.$path_m.'site.webmanifest?v='.$version.'">
    <link rel="mask-icon" href="favicons/'.$path.'safari-pinned-tab.svg?v='.$version.'" color="#ffffff">
    <link rel="shortcut icon" href="favicons/'.$path.'favicon.ico?v='.$version.'">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-config" content="favicons/'.$path.'browserconfig.xml?v='.$version.'">
    <meta name="theme-color" content="#ffffff">';
}