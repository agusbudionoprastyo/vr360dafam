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
$s3Client = null;
$s3_url = '';
$globe_type = 'default';
if((isset($_GET['furl'])) || (isset($_GET['code']))) {
    if (isset($_GET['furl'])) {
        $furl = str_replace("'","\'",$_GET['furl']);
        $query = "SELECT id,type,code,name,logo,pointer_size,pointer_color,pointer_border,center_altitude,min_altitude,center_lat,center_lon,zoom_duration,default_view,meta_title,meta_description,meta_image FROM svt_globes WHERE (friendly_url='$furl' OR code='$furl');";
    }
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $query = "SELECT id,type,code,name,logo,pointer_size,pointer_color,pointer_border,center_altitude,min_altitude,center_lat,center_lon,zoom_duration,default_view,meta_title,meta_description,meta_image FROM svt_globes WHERE code='$code';";
    }
    $result = $mysqli->query($query);
    if ($result) {
        if ($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $id_s = $row['id'];
            $globe_type = $row['type'];
            $code = $row['code'];
            $name_s = $row['name'];
            $logo_s = $row['logo'];
            $pointer_size = $row['pointer_size'];
            $pointer_color = $row['pointer_color'];
            $pointer_border = $row['pointer_border'];
            $center_altitude =  $row['center_altitude'];
            if(empty($center_altitude)) $center_altitude=0;
            $min_altitude =  $row['min_altitude'];
            if(empty($min_altitude)) $min_altitude=0;
            $center_lat =  $row['center_lat'];
            $center_lon =  $row['center_lon'];
            $zoom_duration = $row['zoom_duration'];
            if(empty($zoom_duration)) $zoom_duration=1;
            if($zoom_duration < 1) $zoom_duration=1;
            $zoom_duration = $zoom_duration*1000;
            $default_view = $row['default_view'];
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
                $meta_image = '';
            } else {
                $meta_image = $row['meta_image'];
            }
            $query_list = "SELECT v.id,s.lat,s.lon,v.code,v.author,v.name as title,v.description,v.background_image as image,r.panorama_image,COUNT(al.id) as total_access
                            FROM svt_globe_list as s
                            JOIN svt_virtualtours as v ON s.id_virtualtour=v.id
                            LEFT JOIN svt_rooms as r ON r.id_virtualtour=v.id AND r.id=(SELECT id FROM svt_rooms WHERE id_virtualtour=v.id ORDER BY priority LIMIT 1)
                            LEFT JOIN svt_access_log as al ON al.id_virtualtour=v.id
                            WHERE s.id_globe=$id_s AND v.active=1
                            GROUP BY v.id,s.lat,s.lon,v.code,v.author,v.name,v.description,v.background_image,r.panorama_image;";
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
                        $row_list['s3'] = ($s3_enabled) ? 1 : 0;
                        $array_vt[] = $row_list;
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

$font_provider = "google";
$globe_ion_token = "";
$globe_arcgis_token = "";
$globe_googlemaps_key = "";
$query = "SELECT font_provider,globe_ion_token,globe_arcgis_token,globe_googlemaps_key FROM svt_settings LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows == 1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $font_provider = $row['font_provider'];
        $globe_ion_token = $row['globe_ion_token'];
        $globe_arcgis_token = $row['globe_arcgis_token'];
        if($globe_type=='google') {
            $globe_googlemaps_key = $row['globe_googlemaps_key'];
            if(empty($globe_googlemaps_key)) {
                $globe_type = 'default';
            }
        }
    }
}

$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$url = str_replace("/globe/","/",$url);
?>
<!DOCTYPE HTML>
<html>
<head>
    <title><?php echo $meta_title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1, minimum-scale=1">
    <meta property="og:type" content="website">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="og:url" content="<?php echo $url."globe/index.php?code=".$code; ?>">
    <meta property="twitter:url" content="<?php echo $url."globe/index.php?code=".$code; ?>">
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
    <?php echo print_favicons_globe($code,$logo_s); ?>
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
    <?php if(empty($globe_ion_token)) { ?>
        <link href="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
    <?php } else { ?>
        <link href="https://cesium.com/downloads/cesiumjs/releases/1.110/Build/Cesium/Widgets/widgets.css" rel="stylesheet">
    <?php } ?>
    <link rel="stylesheet" type='text/css' href="../viewer/css/pannellum.css"/>
    <link rel="stylesheet" type="text/css" href="css/index.css?v=<?php echo $v; ?>">
    <?php if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'custom_'.$code.'.css')) : ?>
        <link rel="stylesheet" type="text/css" href="css/custom_<?php echo $code; ?>.css?v=<?php echo $v; ?>">
    <?php endif; ?>
    <script type="text/javascript" src="js/jquery.min.js?v=3.7.1"></script>
    <script type="text/javascript" src="js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jquery.ui.touch-punch.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.bundle.min.js"></script>
    <?php if(empty($globe_ion_token)) { ?>
        <script src="https://cesium.com/downloads/cesiumjs/releases/1.104/Build/Cesium/Cesium.js"></script>
    <?php } else { ?>
        <script src="https://cesium.com/downloads/cesiumjs/releases/1.110/Build/Cesium/Cesium.js"></script>
    <?php } ?>
    <script type="text/javascript" src="../viewer/js/libpannellum.js?v=<?php echo $v; ?>"></script>
    <script type="text/javascript" src="../viewer/js/pannellum.js?v=<?php echo $v; ?>"></script>
</head>
<body>

<i id="loading">
    <svg width="150" height="150" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient x1="8.042%" y1="0%" x2="65.682%" y2="23.865%" id="a">
                <stop stop-color="#fff" stop-opacity="0" offset="0%"/>
                <stop stop-color="#fff" stop-opacity=".631" offset="63.146%"/>
                <stop stop-color="#fff" offset="100%"/>
            </linearGradient>
        </defs>
        <g fill="none" fill-rule="evenodd">
            <g transform="translate(1 1)">
                <path d="M36 18c0-9.94-8.06-18-18-18" id="Oval-2" stroke="url(#a)" stroke-width="2">
                    <animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="0.9s" repeatCount="indefinite" />
                </path>
                <circle fill="#fff" cx="36" cy="18" r="1">
                    <animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="0.9s" repeatCount="indefinite" />
                </circle>
            </g>
        </g>
    </svg>
</i>

<?php if(!empty($logo_s)) : ?>
    <div class="logo">
        <img draggable="false" src="../viewer/content/<?php echo $logo_s; ?>" />
    </div>
<?php endif; ?>

<div id="btn_return_globe"><img src="img/globe.png" /></div>
<div id="vt_viewer">
    <iframe referrerpolicy="origin" allow="accelerometer; camera; display-capture; fullscreen; geolocation; gyroscope; magnetometer; microphone; midi; xr-spatial-tracking;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src=""></iframe>
</div>
<div id="cesiumContainer"></div>

<?php foreach ($array_vt as $vt) { ?>
    <div id="vt_card_<?php echo $vt['id']; ?>" data-id="<?php echo $vt['id']; ?>" data-s3="<?php echo $vt['s3']; ?>" data-image="<?php echo $vt['image']; ?>" data-panorama="<?php echo $vt['panorama_image']; ?>" data-code="<?php echo $vt['code']; ?>" data-lat="<?php echo $vt['lat']; ?>" data-lon="<?php echo $vt['lon']; ?>" class="card vt-card">
        <div class="card-img-block noselect">
            <div id="panorama_preview_<?php echo $vt['id']; ?>" class="panorama_preview"></div>
            <?php if(empty($vt['image'])) { ?>
                <div style="height: 180px;background-color: darkgrey" class="card-img-top"></div>
            <?php } else { ?>
                <img draggable="false" class="card-img-top" src="<?php echo $vt['image']; ?>" alt="card image">
            <?php } ?>
            <div class="card-access noselect"><i class="far fa-eye"></i> <?php echo $vt['total_access']; ?></div>
        </div>
        <div class="card-body pt-0">
            <div class="row p-0">
                <div class="col-sm-6 col-6" style="padding: 0 10px;">
                    <button onclick="view_vt(<?php echo $vt['id']; ?>);" class="btn btn_view_vt btn-sm btn-block btn-outline-dark mb-3"><i class="fas fa-play"></i></button>
                </div>
                <div class="col-sm-6 col-6" style="padding: 0 10px;">
                    <button onclick="flyto_vt(<?php echo $vt['id']; ?>,false,false);" class="btn btn_fly_vt btn-sm btn-block btn-outline-dark mb-3"><i class="fas fa-crosshairs"></i></button>
                </div>
            </div>
            <h5 class="card-title noselect"><?php echo $vt['title']; ?></h5>
            <p class="card-author noselect"><?php echo $vt['author']; ?></p>
            <p class="card-text noselect"><?php echo $vt['description']; ?></p>
        </div>
    </div>
<?php } ?>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js', {
            scope: '.'
        });
    }
</script>
<script>
    $(document).ready(function() {
        window.viewer = null;
        window.viewer_initialized = false;
        window.array_entity = [];
        window.scratch3dPosition = new Cesium.Cartesian3();
        window.scratch2dPosition = new Cesium.Cartesian2();
        window.pointer_size = <?php echo $pointer_size; ?>;
        window.pointer_color = '<?php echo $pointer_color; ?>';
        window.pointer_border = '<?php echo $pointer_border; ?>';
        var drag_p = false, start_drag, end_drag;
        window.open_vt_card = false;
        window.id_open_vt_card = 0;
        window.current_height = 0;
        window.center_altitude = <?php echo $center_altitude; ?>;
        window.min_altitude = <?php echo $min_altitude; ?>;
        window.center_lat = '<?php echo $center_lat; ?>';
        window.center_lon = '<?php echo $center_lon; ?>';
        window.zoom_duration = <?php echo $zoom_duration; ?>;
        window.default_view = '<?php echo $default_view; ?>';
        window.s3_url = '<?php echo $s3_url; ?>';
        var globe_type = '<?php echo $globe_type; ?>';
        <?php if(!empty($globe_ion_token)) : ?>
        Cesium.Ion.defaultAccessToken = '<?php echo $globe_ion_token; ?>';
        Cesium.ArcGisMapService.defaultAccessToken = '<?php echo $globe_arcgis_token; ?>';
        Cesium.GoogleMaps.defaultApiKey = '<?php echo $globe_googlemaps_key; ?>';
        <?php endif; ?>

        $("#btn_return_globe").draggable({
            containment: "#cesiumContainer",
            start: function( event, ui ) {
                $('#vt_viewer').css('pointer-events','none');
                $(this).addClass('dragging');
            },
            stop: function( event, ui ) {
                $('#vt_viewer').css('pointer-events','initial');
            },
        });

        $('#btn_return_globe').click(function (event) {
            if ($(this).parent().hasClass('dragging')) {
                $(this).parent().removeClass('dragging');
            } else {
                return_globe();
            }
        });

        var imageryProviders = Cesium.createDefaultImageryProviderViewModels();
        var imageryProviders_o = [];
        imageryProviders_o.push(imageryProviders[3]);
        imageryProviders_o.push(imageryProviders[6]);

        switch(window.default_view) {
            case 'satellite':
                var imagery_provider = imageryProviders_o[0];
                break;
            case 'street':
                var imagery_provider = imageryProviders_o[1];
                break;
        }

        switch(globe_type) {
            case 'google':
                var google_tilest = null;
                viewer = new Cesium.Viewer('cesiumContainer', {
                    baseLayerPicker: false,
                    geocoder: false,
                    animation: false,
                    timeline: false,
                    fullscreenButton: false,
                    selectionIndicator: false,
                    infoBox: false,
                    sceneModePicker: false,
                    terrain: Cesium.Terrain.fromWorldTerrain()
                });
                //viewer.scene.globe.show = false;
                async function init_google_tiles() {
                    try {
                        google_tilest = await Cesium.createGooglePhotorealistic3DTileset();
                        viewer.scene.primitives.add(google_tilest);
                    } catch (error) {
                        console.log(`Failed to load tileset: `+error);
                    }
                    if(!viewer_initialized) {
                        init_globe(google_tilest);
                    }
                }
                init_google_tiles();
                break;
            default:
                viewer = new Cesium.Viewer('cesiumContainer', {
                    imageryProviderViewModels: imageryProviders_o,
                    selectedImageryProviderViewModel: imagery_provider,
                    terrainProviderViewModels: [],
                    baseLayerPicker: true,
                    geocoder: false,
                    animation: false,
                    timeline: false,
                    fullscreenButton: false,
                    selectionIndicator: false,
                    infoBox: false,
                    sceneModePicker: false
                });

                Cesium.subscribeAndEvaluate(viewer.baseLayerPicker.viewModel, 'selectedImagery', function(newValue) {
                    if(newValue.name=='Open­Street­Map') {
                        viewer.scene.skyAtmosphere.show = false;
                        viewer.scene.fog.enabled = false;
                        viewer.scene.globe.showGroundAtmosphere = false;
                    } else {
                        viewer.scene.skyAtmosphere.show = true;
                        viewer.scene.fog.enabled = true;
                        viewer.scene.globe.showGroundAtmosphere = true;
                    }
                });
                break;
        }

        if(window.min_altitude!=0) {
            viewer.scene.screenSpaceCameraController.minimumZoomDistance = window.min_altitude*1000;
        }

        window.cartographic = new Cesium.Cartographic();
        window.cartesian = new Cesium.Cartesian3();
        window.camera = viewer.scene.camera;
        window.ellipsoid = viewer.scene.mapProjection.ellipsoid;

        switch (globe_type) {
            case 'google':
                break;
            default:
                viewer.scene.globe.tileLoadProgressEvent.addEventListener(function (queuedTileCount) {
                    if(viewer.scene.globe.tilesLoaded && !viewer_initialized) {
                        init_globe(null);
                    }
                });
                break;
        }
    });

    function init_globe(google_tileset=null) {
        viewer_initialized = true;

        var array_coords = [];
        $('.vt-card').each(function () {
            var id_vt = $(this).attr('data-id');
            var lat = $(this).attr('data-lat');
            var lon = $(this).attr('data-lon');
            var entity = viewer.entities.add({
                id: id_vt,
                type: 'vt',
                position: Cesium.Cartesian3.fromDegrees(lon, lat),
                point: {
                    show: true,
                    color: Cesium.Color.fromCssColorString(pointer_color),
                    pixelSize: pointer_size,
                    outlineColor: Cesium.Color.fromCssColorString(pointer_border),
                    outlineWidth: (pointer_size/10)
                },
            });
            if(google_tileset!=null) {
                setInterval(function () {
                    var altitude = Cesium.Ellipsoid.WGS84.cartesianToCartographic(viewer.camera.position).height/300;
                    if(altitude!=null) {
                        var cartesianPosition = viewer.scene.clampToHeight(Cesium.Cartesian3.fromDegrees(lon, lat), [entity]);
                        var cartographicPosition = Cesium.Cartographic.fromCartesian(cartesianPosition);
                        cartographicPosition.height += altitude;
                        var shiftedCartesianPosition = viewer.scene.globe.ellipsoid.cartographicToCartesian(cartographicPosition);
                        entity.position = shiftedCartesianPosition;
                    }
                },250);
            }
            var tmp = [];
            tmp[0]=lat;
            tmp[1]=lon;
            array_coords.push(tmp);
            array_entity[id_vt] = entity;
        });

        if(window.center_lat!='' && window.center_lon!='') {
            if(window.center_altitude!=0) {
                var altitude = window.center_altitude*1000;
            } else {
                var altitude = Cesium.Ellipsoid.WGS84.cartesianToCartographic(viewer.camera.position).height;
            }
            if(window.min_altitude!=0) {
                if(altitude<(window.min_altitude*1000)) {
                    altitude = window.min_altitude*1000;
                }
            }
            viewer.camera.flyTo({
                destination: Cesium.Cartesian3.fromDegrees(window.center_lon, window.center_lat, altitude),
                duration: 1
            });
            viewer.homeButton.viewModel.command.beforeExecute.addEventListener(
                function(e) {
                    e.cancel = true;
                    viewer.camera.flyTo({
                        destination: Cesium.Cartesian3.fromDegrees(window.center_lon, window.center_lat, altitude)
                    });
                }
            );
        } else {
            if(array_coords.length>0) {
                var center = getLatLngCenter(array_coords);
                var center_lat = center[0];
                var center_lon = center[1];
                if(window.center_altitude!=0) {
                    var altitude = window.center_altitude*1000;
                } else {
                    var altitude = Cesium.Ellipsoid.WGS84.cartesianToCartographic(viewer.camera.position).height;
                }
                if(altitude<(window.min_altitude*1000)) {
                    altitude = window.min_altitude*1000;
                }
                viewer.camera.flyTo({
                    destination: Cesium.Cartesian3.fromDegrees(center_lon, center_lat, altitude),
                    duration: 1
                });
                viewer.homeButton.viewModel.command.beforeExecute.addEventListener(
                    function(e) {
                        e.cancel = true;
                        viewer.camera.flyTo({
                            destination: Cesium.Cartesian3.fromDegrees(center_lon, center_lat, altitude)
                        });
                    }
                );
            }
        }

        const handler = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
        viewer.screenSpaceEventHandler.removeInputAction(Cesium.ScreenSpaceEventType.LEFT_DOUBLE_CLICK);
        handler.setInputAction(function (movement) {
            if(open_vt_card) {
                start_drag = new Date().getTime();
                drag_p = false;
            }
        }, Cesium.ScreenSpaceEventType.LEFT_DOWN);
        handler.setInputAction(function (movement) {
            if(open_vt_card) {
                end_drag = new Date().getTime();
                drag_p = true;
            }
            jQuery.each(array_entity, function(id_t, entity_t) {
                if(entity_t!==undefined) {
                    if (entity_t.hasOwnProperty('_point')) {
                        entity_t.point.pixelSize = pointer_size;
                    }
                }
            });
            document.getElementById('cesiumContainer').style.cursor = 'default';
            const pickedObject = viewer.scene.pick(movement.endPosition);
            if (Cesium.defined(pickedObject)) {
                if(pickedObject.id!==undefined) {
                    if (pickedObject.id.hasOwnProperty('type')) {
                        var id = pickedObject.id._id;
                        array_entity[id].point.pixelSize = pointer_size*1.3;
                        document.getElementById('cesiumContainer').style.cursor = 'pointer';
                    }
                }
            }
        }, Cesium.ScreenSpaceEventType.MOUSE_MOVE);
        handler.setInputAction(function (movement) {
            if(open_vt_card) {
                var diff_drag = end_drag - start_drag;
                if(drag_p == false || diff_drag < 100) {
                    $('.vt-card').hide();
                    $('.vt-card').css('opacity',0);
                    open_vt_card = false;
                    id_open_vt_card = 0;
                    viewer.selectedEntity = undefined;
                }
            }
        }, Cesium.ScreenSpaceEventType.LEFT_UP);

        viewer.selectedEntityChanged.addEventListener(function(entity) {
            if(entity!==undefined) {
                if(entity.hasOwnProperty('type')) {
                    if(!open_vt_card) {
                        var id = entity.id;
                        id_open_vt_card = id;
                        $('#vt_card_'+id).show();
                        var image_sel = $('#vt_card_'+id).attr('data-image');
                        if(!image_sel.includes('preview')) {
                            $('#vt_viewer').css('background-image','url('+image_sel+')');
                        } else {
                            $('#vt_viewer').css('background-image','none');
                        }
                        setTimeout(function () {
                            open_vt_card = true;
                        },50);
                        viewer.selectedEntity = undefined;
                    } else {
                        var id = entity.id;
                        if(id_open_vt_card!=id) {
                            setTimeout(function () {
                                id_open_vt_card = id;
                                $('#vt_card_'+id).show();
                                open_vt_card = true;
                                viewer.selectedEntity = undefined;
                            },50);
                        }
                    }
                    var lat = parseFloat($('#vt_card_'+id).attr('data-lat'));
                    var lon = parseFloat($('#vt_card_'+id).attr('data-lon'));
                    var dest_coord = Cesium.Cartesian3.fromDegrees(lon, lat, window.current_height);
                    viewer.camera.flyTo({
                        destination: dest_coord,
                        duration: 0.5,
                    });
                }
            }
        });

        viewer.clock.onTick.addEventListener(function(clock) {
            ellipsoid.cartesianToCartographic(camera.positionWC, cartographic);
            window.current_height = cartographic.height;
            if (cartographic.height>10000) {
                $('.btn_fly_vt').removeClass('disabled');
            } else {
                $('.btn_fly_vt').addClass('disabled');
            }
            if(open_vt_card && id_open_vt_card!=0) {
                var position3d;
                var position2d;
                var vt_card = $('#vt_card_'+id_open_vt_card);
                var entity = array_entity[id_open_vt_card];
                if (entity.position) {
                    position3d = entity.position.getValue(clock.currentTime, scratch3dPosition);
                }
                if (position3d) {
                    position2d = Cesium.SceneTransforms.wgs84ToWindowCoordinates(
                        viewer.scene, position3d, scratch2dPosition);
                }
                if (position2d) {
                    vt_card.css('right',(window.innerWidth - position2d.x) + 'px');
                    vt_card.css('bottom',(window.innerHeight - position2d.y) + (pointer_size+10) + 'px');
                    vt_card.css('opacity',1);
                }
            }
        });

        setTimeout(function () {
            $('#loading').fadeOut();
        },250);
    }

    window.flyto_vt = function(id_vt,duration,view_mode) {
        var lat = $('#vt_card_'+id_vt).attr('data-lat');
        var lon = $('#vt_card_'+id_vt).attr('data-lon');
        $('#vt_card_'+id_vt).css('display','none');
        if(duration==false) {
            if(window.current_height>10000) {
                duration = window.zoom_duration;
            } else {
                duration = 500;
            }
        }
        if((window.min_altitude*1000)<2000) {
            var altitude = 2000;
        } else {
            var altitude = window.min_altitude*1000;
        }
        viewer.camera.flyTo({
            destination: Cesium.Cartesian3.fromDegrees(lon, lat, altitude),
            duration: (duration/1000),
        });
        if(!view_mode) {
            setTimeout(function () {
                $('#vt_card_'+id_vt).css('display','block');
            },duration);
        }
    }

    window.view_vt = function (id_vt) {
        var vt_code = $('#vt_card_'+id_vt).attr('data-code');
        if(window.current_height>10000) {
            var duration = window.zoom_duration;
        } else {
            var duration = 500;
        }
        flyto_vt(id_vt,duration,true);
        $('#vt_viewer iframe').attr('src','../viewer/index.php?code='+vt_code+'&ignore_embedded=1');
        $('.vt-card').hide();
        $('.vt-card').css('opacity',0);
        open_vt_card = false;
        id_open_vt_card = 0;
        viewer.selectedEntity = undefined;
        setTimeout(function () {
            $('#vt_viewer').fadeIn(200,function () {
                $('.cesium-home-button').trigger('click');
            });
            $('#btn_return_globe').fadeIn(200);
            $('#vt_viewer').css('z-index',10);
            $('#btn_return_globe').css('z-index',11);
            $('#vt_viewer iframe').css('opacity',1);
        },duration);
    }

    window.return_globe = function () {
        $('#vt_viewer').fadeOut(200);
        $('#btn_return_globe').hide();
        setTimeout(function () {
            $('#vt_viewer iframe').attr('src','');
            $('#vt_viewer iframe').css('opacity',0);
            $('#vt_viewer').css('z-index',0);
            $('#btn_return_globe').css('z-index',0);
        },200);
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

    function rad2degr(rad) { return rad * 180 / Math.PI; }
    function degr2rad(degr) { return degr * Math.PI / 180; }

    function getLatLngCenter(latLngInDegr) {
        var LATIDX = 0;
        var LNGIDX = 1;
        var sumX = 0;
        var sumY = 0;
        var sumZ = 0;
        for (var i=0; i<latLngInDegr.length; i++) {
            var lat = degr2rad(latLngInDegr[i][LATIDX]);
            var lng = degr2rad(latLngInDegr[i][LNGIDX]);
            sumX += Math.cos(lat) * Math.cos(lng);
            sumY += Math.cos(lat) * Math.sin(lng);
            sumZ += Math.sin(lat);
        }
        var avgX = sumX / latLngInDegr.length;
        var avgY = sumY / latLngInDegr.length;
        var avgZ = sumZ / latLngInDegr.length;
        var lng = Math.atan2(avgY, avgX);
        var hyp = Math.sqrt(avgX * avgX + avgY * avgY);
        var lat = Math.atan2(avgZ, hyp);
        return ([rad2degr(lat), rad2degr(lng)]);
    }
</script>
</body>
</html>

<?php
function print_favicons_globe($code,$logo) {
    $path = '';
    $path_m = 'g_'.$code.'/';
    if (file_exists(dirname(__FILE__).'/../favicons/g_'.$code.'/favicon.ico')) {
        $path = 'g_'.$code.'/';
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
