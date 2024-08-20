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
$array_vt = array();
$array_cat = array();
$header_html = '';
$footer_html = '';
$s3Client = null;
$s3_url = '';
if((isset($_GET['furl'])) || (isset($_GET['code']))) {
    if (isset($_GET['furl'])) {
        $furl = str_replace("'","\'",$_GET['furl']);
        $query = "SELECT id,code,name,banner,logo,bg_color,header_html,footer_html,meta_title,meta_description,meta_image,sort_settings FROM svt_showcases WHERE (friendly_url='$furl' OR code='$furl');";
    }
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $query = "SELECT id,code,name,banner,logo,bg_color,header_html,footer_html,meta_title,meta_description,meta_image,sort_settings FROM svt_showcases WHERE code='$code';";
    }
    $result = $mysqli->query($query);
    if ($result) {
        if ($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $id_s = $row['id'];
            $code = $row['code'];
            $name_s = $row['name'];
            $banner_s = $row['banner'];
            $logo_s = $row['logo'];
            $bg_color_s = $row['bg_color'];
            $header_html = $row['header_html'];
            $footer_html = $row['footer_html'];
            $sort_settings = $row['sort_settings'];
            if(empty($row['meta_title'])) {
                $meta_title = $name_s;
            } else {
                $meta_title = $row['meta_title'];
            }
            if(empty($row['meta_description'])) {
                $meta_description = '';
            } else {
                $meta_description = $row['meta_description'];
            }
            if(empty($row['meta_image'])) {
                $meta_image = $row['banner'];
            } else {
                $meta_image = $row['meta_image'];
            }
            $query_list = "SELECT v.id,v.date_created,s.type_viewer,s.priority,v.code,v.author,v.name as title,v.description,v.background_image as image,r.panorama_image,GROUP_CONCAT(DISTINCT c.id) as id_category,COUNT(al.id) as total_access
                            FROM svt_showcase_list as s
                            JOIN svt_virtualtours as v ON s.id_virtualtour=v.id
                            LEFT JOIN svt_category_vt_assoc as ca ON ca.id_virtualtour=v.id
                            LEFT JOIN svt_categories as c ON c.id=ca.id_category
                            LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id AND r.id=(SELECT id FROM svt_rooms WHERE id_virtualtour=v.id ORDER BY priority LIMIT 1)
                            LEFT JOIN svt_access_log as al ON al.id_virtualtour=v.id
                            WHERE s.id_showcase=$id_s AND v.active=1
                            GROUP BY v.id,v.date_created,s.type_viewer,s.priority,v.code,v.author,v.name,v.description,v.background_image,r.panorama_image;";
            $result_list = $mysqli->query($query_list);
            if($result_list) {
                if($result_list->num_rows>0) {
                    while($row_list = $result_list->fetch_array(MYSQLI_ASSOC)) {
                        $id_vt = $row_list['id'];
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
                        if(empty($row_list['image'])) {
                            if(!empty($row_list['panorama_image'])) {
                                if($s3_enabled) {
                                    $row_list['image']=$s3_url.'viewer/panoramas/preview/'.$row_list['panorama_image'];
                                } else {
                                    $row_list['image']='../viewer/panoramas/preview/'.$row_list['panorama_image'];
                                }
                            }
                        } else {
                            if($s3_enabled) {
                                $row_list['image']=$s3_url.'viewer/content/'.$row_list['image'];
                            } else {
                                $row_list['image']='../viewer/content/'.$row_list['image'];
                            }
                        }
                        $row_list['date']=strtotime($row_list['date_created']);
                        $row_list['s3'] = ($s3_enabled) ? 1 : 0;
                        $array_vt[] = $row_list;
                    }
                    $query_cat = "SELECT DISTINCT sc.id,sc.name FROM svt_showcase_list as s
                                    JOIN svt_virtualtours sv on s.id_virtualtour = sv.id
                                    JOIN svt_category_vt_assoc scva on s.id_virtualtour = scva.id_virtualtour
                                    JOIN svt_categories sc on scva.id_category = sc.id
                                    WHERE s.id_showcase=$id_s AND sv.active=1;";
                    $result_cat = $mysqli->query($query_cat);
                    if($result_cat) {
                        if ($result_cat->num_rows > 0) {
                            while ($row_cat = $result_cat->fetch_array(MYSQLI_ASSOC)) {
                                $category = $row_cat['id']."|".$row_cat['name'];
                                if(!in_array($category,$array_cat)) {
                                    array_push($array_cat,$category);
                                }
                            }
                        }
                    }
                }
            } else {
                die("Invalid Link");
            }
        } else {
            die("Invalid Link");
        }
    } else {
        die("Invalid Link");
    }
} else {
    die("Invalid Link");
}
$query = "SELECT language,language_domain FROM svt_settings LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row=$result->fetch_array(MYSQLI_ASSOC);
        $language = $row['language'];
        if (function_exists('gettext')) {
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
                    $result = putenv('LC_ALL='.$language);
                    if(!$result) {
                        putenv('LC_ALL='.$language.'.UTF-8');
                    }
                }
            }
            $domain = $row['language_domain'];
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
}
$count_order=0;

$font_provider = "google";
$query = "SELECT font_provider FROM svt_settings LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $font_provider = $row['font_provider'];
    }
}

$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$url = str_replace("/showcase/","/",$url);
?>
<!DOCTYPE HTML>
<html>
<head>
    <title><?php echo $meta_title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <meta property="og:type" content="website">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="og:url" content="<?php echo $url."showcase/index.php?code=".$code; ?>">
    <meta property="twitter:url" content="<?php echo $url."showcase/index.php?code=".$code; ?>">
    <meta itemprop="name" content="<?php echo $meta_title; ?>">
    <meta property="og:title" content="<?php echo $meta_title; ?>">
    <meta property="twitter:title" content="<?php echo $meta_title; ?>">
    <?php if($meta_image!='') : ?>
        <meta itemprop="image" content="<?php echo $url."viewer/content/".$meta_image; ?>">
        <meta property="og:image" content="<?php echo $url."viewer/content/".$meta_image; ?>" />
        <meta property="twitter:image" content="<?php echo $url."viewer/content/".$meta_image; ?>">
    <?php endif; ?>
    <?php if($meta_description!='') : ?>
        <meta itemprop="description" content="<?php echo $meta_description; ?>">
        <meta name="description" content="<?php echo $meta_description; ?>"/>
        <meta property="og:description" content="<?php echo $meta_description; ?>" />
        <meta property="twitter:description" content="<?php echo $meta_description; ?>">
    <?php endif; ?>
    <?php echo print_favicons_showcase($code,$logo_s); ?>
    <?php switch ($font_provider) {
        case 'google': ?>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;300;400;500;600;800&display=swap" rel="stylesheet">
            <?php break;
        case 'collabs': ?>
            <link rel="preconnect" href="https://api.fonts.coollabs.io" crossorigin>
            <link href="https://api.fonts.coollabs.io/css2?family=Montserrat:wght@100;300;400;500;600;800&display=swap" rel="stylesheet">
            <?php break;
    } ?>
    <link rel="stylesheet" type='text/css' href="../viewer/vendor/fontawesome-free/css/all.min.css?v=5.15.4">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type='text/css' href="../viewer/css/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="css/index.css?v=<?php echo $v; ?>">
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_'.$code.'.css')) : ?>
        <link rel="stylesheet" type="text/css" href="css/custom_<?php echo $code; ?>.css?v=<?php echo $v; ?>">
    <?php endif; ?>
    <script type="text/javascript" src="js/jquery.min.js?v=3.7.1"></script>
    <script type="text/javascript" src="js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="../viewer/js/libpannellum.js?v=<?php echo $v; ?>"></script>
    <script type="text/javascript" src="../viewer/js/pannellum.js?v=<?php echo $v; ?>"></script>
</head>
<body style="background: <?php echo $bg_color_s; ?>">
<style>
    :root {
        --bg_color: <?php echo $bg_color_s; ?>;
    }
    .header:before {
        <?php if(!empty($banner_s)) { ?>
        background-image: url('../viewer/content/<?php echo $banner_s; ?>');
        <?php } else { ?>
        background-color: rgba(0,0,0,0.4);
        <?php } ?>
    }
    .frame_banner:before {
    <?php if(!empty($banner_s)) : ?>
        background-image: url('../viewer/content/<?php echo $banner_s; ?>');
    <?php endif; ?>
    }
    <?php if(empty($banner_s)) { ?>
    .header {
        height: auto;
        min-height: 100px;
    }
    .info {
        padding-top: 25px;
    }
    .info h1 {
        margin-bottom: 0;
    }
    .header:after {
        background: none;
    }
    .logo img {
        margin-top: 10px;
        margin-bottom: 25px;
    }
    .frame_banner:before {
        background: none;
    }
    <?php } ?>
</style>
<div class="showcase noselect">
    <div class="header">
        <div class="info">
            <h1><?php echo $name_s; ?></h1>
            <?php if(!empty($logo_s)) : ?>
                <div class="logo">
                    <img src="../viewer/content/<?php echo $logo_s; ?>" />
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="custom_header <?php echo (empty($header_html)) ? 'd-none' : ''; ?>">
        <?php echo html_entity_decode($header_html); ?>
    </div>
    <div class='categories'>
        <?php
        if(count($array_cat)>1) {
            foreach ($array_cat as $category) {
                $res = explode("|",$category);
                $id_cat = $res[0];
                $name_cat = $res[1];
                echo "<button id='btn_cat_$id_cat' onclick='filter_cat($id_cat);' class='btn bg-light text-dark border border-secondary mb-1'>$name_cat</button>";
            }
        }
        if(!empty($sort_settings)) {
            $sort_settings = json_decode($sort_settings,true);
        } else {
            $sort_settings = array();
            $sort_settings['date']=1;
            $sort_settings['relevance']=1;
            $sort_settings['name']=1;
            $sort_settings['category']=1;
            $sort_settings['author']=1;
            $sort_settings['views']=1;
            $sort_settings['default']='date|asc';
        }
        $count_sort = 0;
        if($sort_settings['date']==1) $count_sort++;
        if($sort_settings['relevance']==1) $count_sort++;
        if($sort_settings['name']==1) $count_sort++;
        if($sort_settings['category']==1) $count_sort++;
        if($sort_settings['author']==1) $count_sort++;
        if($sort_settings['views']==1) $count_sort++;
        $default_sort_type = explode("|",$sort_settings['default'])[0];
        $default_sort_by = explode("|",$sort_settings['default'])[1];
        if($sort_settings[$default_sort_type]==0) {
            foreach ($sort_settings as $index => $value) {
                if ($value === 1) {
                    $default_sort_type = $index;
                    $default_sort_by = 'asc';
                    break;
                }
            }
        }
        ?>
        <div id="btn_sort_by" class="btn-group mb-1 <?php echo ($count_sort<=1) ? 'd-none' : ''; ?>">
            <button id="btn_sort_by_type" type="button" class="btn bg-light text-dark border border-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?php echo _("Date"); ?>
            </button>
            <div class="dropdown-menu">
                <a id="sort_date" onclick="change_sorty_by_type('date');" class="dropdown-item <?php echo ($sort_settings['date']==1) ? '' : 'd-none'; ?>" href="#"><?php echo _("Date"); ?></a>
                <a id="sort_relevance" onclick="change_sorty_by_type('relevance');" class="dropdown-item <?php echo ($sort_settings['relevance']==1) ? '' : 'd-none'; ?>" href="#"><?php echo _("Relevance"); ?></a>
                <a id="sort_name" onclick="change_sorty_by_type('name');" class="dropdown-item <?php echo ($sort_settings['name']==1) ? '' : 'd-none'; ?>" href="#"><?php echo _("Name"); ?></a>
                <a id="sort_category" onclick="change_sorty_by_type('category');" class="dropdown-item <?php echo ($sort_settings['category']==1) ? '' : 'd-none'; ?>" href="#"><?php echo _("Category"); ?></a>
                <a id="sort_author" onclick="change_sorty_by_type('author');" class="dropdown-item <?php echo ($sort_settings['author']==1) ? '' : 'd-none'; ?>" href="#"><?php echo _("Author"); ?></a>
                <a id="sort_views" onclick="change_sorty_by_type('views');" class="dropdown-item <?php echo ($sort_settings['views']==1) ? '' : 'd-none'; ?>" href="#"><?php echo _("Views"); ?></a>
            </div>
            <button onclick="change_sorty_by_order();" id="btn_sort_by_order" class="btn bg-light text-dark border border-secondary"><i class="fas fa-sort-alpha-down"></i></button>
        </div>
    </div>
    <section>
        <div style="opacity:0" class="container">
            <div class="d-flex flex-row flex-wrap">
                <?php foreach ($array_vt as $vt) {
                    $count_order++;
                    ?>
                    <div id="vt_<?php echo $vt['id']; ?>" style="order: <?php echo $count_order; ?>;" class="col-xl-3 col-lg-4 col-sm-6 col-xs-12">
                        <div data-id="<?php echo $vt['id']; ?>" data-s3="<?php echo $vt['s3']; ?>" data-name="<?php echo $vt['title']; ?>" data-author="<?php echo $vt['author']; ?>" data-panorama="<?php echo $vt['panorama_image']; ?>" data-image="<?php echo $vt['image']; ?>" data-type="<?php echo $vt['type_viewer']; ?>" data-priority="<?php echo $vt['priority']; ?>" data-category="<?php echo $vt['id_category']; ?>" data-category-name="<?php echo $vt['name_category']; ?>" data-views="<?php echo $vt['total_access']; ?>" data-date="<?php echo $vt['date']; ?>" data-code="<?php echo $vt['code']; ?>" class="card vt-card">
                            <div class="card-img-block">
                                <div id="panorama_preview_<?php echo $vt['id']; ?>" class="panorama_preview"></div>
                                <div class="overlay"></div>
                                <i class="fas fa-play-circle"></i>
                                <?php if(empty($vt['image'])) { ?>
                                    <div style="height: 180px;background-color: darkgrey" class="card-img-top"></div>
                                <?php } else { ?>
                                    <img class="card-img-top" src="<?php echo $vt['image']; ?>" alt="card image">
                                <?php } ?>
                                <div class="card-access"><i class="far fa-eye"></i> <?php echo $vt['total_access']; ?></div>
                            </div>
                            <div class="card-body pt-0">
                                <h5 class="card-title"><?php echo $vt['title']; ?></h5>
                                <p class="card-author"><?php echo $vt['author']; ?></p>
                                <p class="card-text"><?php echo $vt['description']; ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <div class="custom_footer <?php echo (empty($footer_html)) ? 'd-none' : ''; ?>">
        <?php echo html_entity_decode($footer_html); ?>
    </div>
</div>
<div class="vt_viewer">
    <i class="fa fa-spin fa-circle-notch loading_icon"></i>
    <div class="frame_banner noselect">
        <?php if(!empty($logo_s)) : ?>
            <img src="../viewer/content/<?php echo $logo_s; ?>" />
        <?php endif; ?>
        <span><?php echo $name_s; ?></span>
        <i onclick="show_showcase()" class="fas fa-arrow-circle-left"></i>
    </div>
    <iframe referrerpolicy="origin" allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src=""></iframe>
</div>
<div class="ripple-wrap"><div class="ripple"><i class="fa fa-spin fa-circle-notch"></i></div></div>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js', {
            scope: '.'
        });
    }
</script>
<script>
    window.sort_type = '<?php echo $default_sort_type; ?>';
    window.sort_by = '<?php echo $default_sort_by; ?>';
    window.array_vt = [];
    window.s3_url = '<?php echo $s3_url; ?>';
    $(document).ready(function() {
        populate_array_vt();
        switch(window.sort_by) {
            case 'asc':
                $('#btn_sort_by_order i').removeClass('fa-sort-alpha-up').addClass('fa-sort-alpha-down');
                break;
            case 'desc':
                $('#btn_sort_by_order i').removeClass('fa-sort-alpha-down').addClass('fa-sort-alpha-up');
                break;
        }
        change_sorty_by_type(window.sort_type);
        $('.container').css('opacity',1);
        var height_footer = $('.custom_footer ').outerHeight();
        $('section').css('margin-bottom',height_footer+'px');
        var ripple_wrap = $('.ripple-wrap'), rippler = $('.ripple'), finish = false, vt_code='', type='viewer', image_sel='',
        monitor = function(el) {
            var computed = window.getComputedStyle(el, null),
                borderwidth = parseFloat(computed.getPropertyValue('border-left-width'));
            if (!finish && borderwidth >= 1500) {
                el.style.WebkitAnimationPlayState = "paused";
                el.style.animationPlayState = "paused";
            }
            if (finish) {
                el.style.WebkitAnimationPlayState = "running";
                el.style.animationPlayState = "running";
                return;
            } else {
                window.requestAnimationFrame(function() {monitor(el)});
            }
        };
        rippler.bind("webkitAnimationEnd oAnimationEnd msAnimationEnd mozAnimationEnd animationend", function(e){
            $('.ripple i').hide();
            ripple_wrap.removeClass('goripple');
        });
        $('body').on('click', '.vt-card', function(e) {
            vt_code = $(this).attr('data-code');
            type = $(this).attr('data-type');
            image_sel = $(this).attr('data-image');
            $('body').css('overflow-y','hidden');
            $('.vt_viewer').css('height','100vh');
            $('.ripple i').show();
            rippler.css('left', e.clientX + 'px');
            rippler.css('top', e.clientY + 'px');
            e.preventDefault();
            finish = false;
            ripple_wrap.addClass('goripple');
            setTimeout(function () {
                swapContent();
            },1000);
            window.requestAnimationFrame(function() {monitor(rippler[0])});
        });
        function swapContent() {
            $('.vt_viewer iframe').attr('src','../'+type+'/index.php?code='+vt_code+'&ignore_embedded=1');
            switch(type) {
                case 'viewer':
                    $('.vt_viewer iframe').attr('scrolling','no');
                    break;
                case 'landing':
                    $('.vt_viewer iframe').attr('scrolling','yes');
                    break;
            }
            $('.vt_viewer').show();
            $('.showcase').hide();
            if(!image_sel.includes('preview')) {
                $('.vt_viewer').css('background-image','url('+image_sel+')');
            } else {
                $('.vt_viewer').css('background-image','none');
            }
            $('.ripple i').fadeOut(500);
            setTimeout(function() {
                finish = true;
            },500);
        }
    });
    var show_showcase = function() {
        $('.vt_viewer').fadeOut(function () {
            $('.vt_viewer iframe').attr('src','');
            $('.showcase').fadeIn();
            $('body').css('overflow-y','auto');
        });
    };
    var filter_cat = function (id) {
        if($('#btn_cat_'+id).hasClass('bg-primary')) {
            $('#btn_cat_'+id).removeClass('bg-primary').addClass('bg-light').removeClass('text-white').addClass('text-dark');
        } else {
            $('#btn_cat_'+id).removeClass('bg-light').addClass('bg-primary').removeClass('text-dark').addClass('text-white');
        }
        filter_cats();
    }

    function filter_cats() {
        var all_disabled = true;
        $('.vt-card').parent().addClass('d-none');
        $('.categories .bg-primary').each(function(i, obj) {
            all_disabled = false;
            var id = $(this).attr('id').replace('btn_cat_','');
            $('.vt-card').each(function() {
                var id_categories = $(this).attr('data-category');
                var array_categories = id_categories.split(',');
                for(var i=0;i<array_categories.length;i++) {
                    if(parseInt(id)==parseInt(array_categories[i])) {
                        $(this).parent().removeClass('d-none');
                    }
                }
            });
        });
        if(all_disabled) {
            $('.vt-card').parent().removeClass('d-none');
        }
    }

    function change_sorty_by_order() {
        if(window.sort_by == 'asc') {
            window.sort_by='desc';
        } else if(window.sort_by == 'desc') {
            window.sort_by='asc';
        }
        switch(window.sort_by) {
            case 'asc':
                $('#btn_sort_by_order i').removeClass('fa-sort-alpha-up').addClass('fa-sort-alpha-down');
                break;
            case 'desc':
                $('#btn_sort_by_order i').removeClass('fa-sort-alpha-down').addClass('fa-sort-alpha-up');
                break;
        }
        change_sorty_by_type(window.sort_type);
    }

    function change_sorty_by_type(type) {
        $('#btn_sort_by_type').html($('#sort_'+type).html());
        var array_vt_tmp = array_vt;
        var reverse = false;
        if(sort_by=='desc') reverse = true;
        switch(type) {
            case 'name':
                array_vt_tmp.sort(sort_by_f('name', reverse, (a) =>  a.toUpperCase()));
                break;
            case 'author':
                array_vt_tmp.sort(sort_by_f('author', reverse, (a) =>  a.toUpperCase()));
                break;
            case 'category':
                array_vt_tmp.sort(sort_by_f('category', reverse, (a) =>  a.toUpperCase()));
                break;
            case 'date':
                array_vt_tmp.sort(sort_by_f('date', reverse, parseInt));
                break;
            case 'views':
                array_vt_tmp.sort(sort_by_f('views', reverse, parseInt));
                break;
            case 'relevance':
                array_vt_tmp.sort(sort_by_f('relevance', reverse, parseInt));
                break;
        }
        window.sort_type = type;
        jQuery.each(array_vt_tmp, function (index,vt) {
            var id = vt.id;
            $('#vt_'+id).css('order',index);
        });
    }

    function populate_array_vt() {
        $('.vt-card').each(function () {
            var id = $(this).attr('data-id');
            var name = $(this).attr('data-name');
            var author = $(this).attr('data-author');
            var category = $(this).attr('data-category-name');
            var date = $(this).attr('data-date');
            var views = $(this).attr('data-views');
            var priority = $(this).attr('data-priority');
            var tmp = {};
            tmp['id']=id;
            tmp['name']=name;
            tmp['author']=author;
            tmp['category']=category;
            tmp['date']=date;
            tmp['views']=views;
            tmp['relevance']=priority;
            array_vt.push(tmp);
        });
    }

    var panorama_preview = null, timeout_destroy;
    function initialize_panorama_preview(id,image,s3) {
        try {
            panorama_preview.destroy();
        } catch (e) {}
        panorama_preview = pannellum.viewer('panorama_preview_'+id, {
            "type": "equirectangular",
            "autoLoad": true,
            "autoRotate": -20,
            "showControls": false,
            "compass": false,
            "panorama": (s3==1) ? window.s3_url+"viewer/panoramas/lowres/"+image : "../viewer/panoramas/lowres/"+image
        });
        panorama_preview.on('load',function () {
            setTimeout(function () {
                $('#panorama_preview_'+id).css('opacity',1);
            },50);
        });
        $('.panorama_preview').css('opacity',0);
    }

    $('.vt-card').on('mouseenter', function () {
        var id = $(this).attr('data-id');
        var image = $(this).attr('data-panorama');
        var s3 = parseInt($(this).attr('data-s3'));
        if(image!='') {
            clearTimeout(timeout_destroy);
            initialize_panorama_preview(id,image,s3);
        }
    });

    $('.vt-card').on('mouseleave', function () {
        $('.panorama_preview').css('opacity',0);
        timeout_destroy = setTimeout(function() {
            try {
                panorama_preview.destroy();
            } catch (e) {}
        },300);
    });

    const sort_by_f = (field, reverse, primer) => {
        const key = primer ?
            function(x) {
                return primer(x[field])
            } :
            function(x) {
                return x[field]
            };
        reverse = !reverse ? 1 : -1;
        return function(a, b) {
            return a = key(a), b = key(b), reverse * ((a > b) - (b > a));
        }
    }
</script>
</body>
</html>

<?php
function print_favicons_showcase($code,$logo) {
    $path = '';
    $path_m = 's_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/../favicons/s_'.$code.'/favicon.ico')) {
        $path = 's_'.$code.'/';
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
