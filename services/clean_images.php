<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once(__DIR__."/../db/connection.php");

$path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;

$array_content_files = array();
$array_map_files = array();
$array_rooms_files = array();
$array_rooms_files_multires = array();
$array_rooms_v_files = array();
$array_gallery_files = array();
$array_media_library_files = array();
$array_icon_files = array();
$array_assets_files = array();
$array_thumbs_files = array();
$array_object360_files = array();
$array_products_files = array();
$array_id_vt = array();
$array_video_assets = array();
$array_pointclouds = array();
$array_settings_pointclouds = array();

$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'aws_s3';");
if($result) {
    if ($result->num_rows==0) {
        exit;
    }
}

$query = "SELECT id,song,logo,nadir_logo,background_image,background_image_mobile,background_video,background_image_mobile,background_video_mobile,intro_desktop,intro_mobile,presentation_video,meta_image,meta_image_l,dollhouse_glb,poweredby_image FROM svt_virtualtours WHERE aws_s3=0;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $id_vt = $row['id'];
            array_push($array_id_vt,$id_vt);
            $song = $row['song'];
            $logo = $row['logo'];
            $nadir_logo = $row['nadir_logo'];
            $background_image = $row['background_image'];
            $background_video = $row['background_video'];
            $background_image_mobile = $row['background_image_mobile'];
            $background_video_mobile = $row['background_video_mobile'];
            $intro_desktop = $row['intro_desktop'];
            $intro_mobile = $row['intro_mobile'];
            $presentation_video = $row['presentation_video'];
            $meta_image = $row['meta_image'];
            $meta_image_l = $row['meta_image_l'];
            $dollhouse_glb = $row['dollhouse_glb'];
            $poweredby_image = $row['poweredby_image'];
            if($song!='') {
                if(!in_array($song,$array_content_files)) {
                    array_push($array_content_files,$song);
                }
            }
            if($logo!='') {
                if(!in_array($logo,$array_content_files)) {
                    array_push($array_content_files,$logo);
                }
            }
            if($nadir_logo!='') {
                if(!in_array($nadir_logo,$array_content_files)) {
                    array_push($array_content_files,$nadir_logo);
                }
            }
            if($background_image!='') {
                if(!in_array($background_image,$array_content_files)) {
                    array_push($array_content_files,$background_image);
                }
            }
            if($background_video!='') {
                if(!in_array($background_video,$array_content_files)) {
                    array_push($array_content_files,$background_video);
                }
            }
            if($background_image_mobile!='') {
                if(!in_array($background_image_mobile,$array_content_files)) {
                    array_push($array_content_files,$background_image_mobile);
                }
            }
            if($background_video_mobile!='') {
                if(!in_array($background_video_mobile,$array_content_files)) {
                    array_push($array_content_files,$background_video_mobile);
                }
            }
            if($intro_desktop!='') {
                if(!in_array($intro_desktop,$array_content_files)) {
                    array_push($array_content_files,$intro_desktop);
                }
            }
            if($intro_mobile!='') {
                if(!in_array($intro_mobile,$array_content_files)) {
                    array_push($array_content_files,$intro_mobile);
                }
            }
            if($presentation_video!='') {
                $presentation_video = basename($presentation_video);
                if(!in_array($presentation_video,$array_content_files)) {
                    array_push($array_content_files,$presentation_video);
                }
            }
            $slideshow = $id_vt."_slideshow.mp4";
            if(!in_array($slideshow,$array_gallery_files)) {
                array_push($array_gallery_files,$slideshow);
            }
            if($meta_image!='') {
                if(!in_array($meta_image,$array_content_files)) {
                    array_push($array_content_files,$meta_image);
                }
            }
            if($meta_image_l!='') {
                if(!in_array($meta_image_l,$array_content_files)) {
                    array_push($array_content_files,$meta_image_l);
                }
            }
            if($dollhouse_glb!='') {
                if(!in_array($dollhouse_glb,$array_content_files)) {
                    array_push($array_content_files,$dollhouse_glb);
                }
            }
            if($poweredby_image!='') {
                if(!in_array($poweredby_image,$array_content_files)) {
                    array_push($array_content_files,$poweredby_image);
                }
            }
        }
    }
}

$query = "SELECT map FROM svt_maps WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $map = $row['map'];
            if($map!='') {
                if(!in_array($map,$array_map_files)) {
                    array_push($array_map_files,$map);
                }
            }
        }
    }
}

$query = "SELECT content,type FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)) AND type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf') AND content LIKE '%content/%';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            switch($row['type']) {
                case 'object3d':
                    if (strpos($row['content'], ',') !== false) {
                        $array_contents = explode(",",$row['content']);
                        foreach ($array_contents as $content) {
                            $content = basename($content);
                            if($content!='') {
                                if(!in_array($content,$array_content_files)) {
                                    array_push($array_content_files,$content);
                                }
                            }
                        }
                    } else {
                        $content = basename($row['content']);
                        if($content!='') {
                            if(!in_array($content,$array_content_files)) {
                                array_push($array_content_files,$content);
                            }
                        }
                    }
                    break;
                default:
                    $content = basename($row['content']);
                    if($content!='') {
                        if(!in_array($content,$array_content_files)) {
                            array_push($array_content_files,$content);
                        }
                    }
                    break;
            }
        }
    }
}

$query = "SELECT pl.content,p.type FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)) AND p.type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf') AND p.content LIKE '%content/%';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            switch($row['type']) {
                case 'object3d':
                    if (strpos($row['content'], ',') !== false) {
                        $array_contents = explode(",",$row['content']);
                        foreach ($array_contents as $content) {
                            $content = basename($content);
                            if($content!='') {
                                if(!in_array($content,$array_content_files)) {
                                    array_push($array_content_files,$content);
                                }
                            }
                        }
                    } else {
                        $content = basename($row['content']);
                        if($content!='') {
                            if(!in_array($content,$array_content_files)) {
                                array_push($array_content_files,$content);
                            }
                        }
                    }
                    break;
                default:
                    $content = basename($row['content']);
                    if($content!='') {
                        if(!in_array($content,$array_content_files)) {
                            array_push($array_content_files,$content);
                        }
                    }
                    break;
            }
        }
    }
}

$query = "SELECT embed_type,embed_content FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)) AND embed_content LIKE '%content/%';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            switch ($row['embed_type']) {
                case 'image':
                case 'video':
                case 'video_chroma':
                case 'object3d':
                case 'pdf':
                    $content = basename($row['embed_content']);
                    if($content!='') {
                        if(!in_array($content,$array_content_files)) {
                            array_push($array_content_files,$content);
                        }
                    }
                    break;
                case 'video_transparent':
                    if (strpos($row['embed_content'], ',') !== false) {
                        $array_contents = explode(",",$row['embed_content']);
                        foreach ($array_contents as $content) {
                            $content = basename($content);
                            if($content!='') {
                                if(!in_array($content,$array_content_files)) {
                                    array_push($array_content_files,$content);
                                }
                            }
                        }
                    } else {
                        $content = basename($row['embed_content']);
                        if($content!='') {
                            if(!in_array($content,$array_content_files)) {
                                array_push($array_content_files,$content);
                            }
                        }
                    }
                    break;
            }
        }
    }
}

$query = "SELECT pl.embed_content,p.embed_type FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)) AND pl.embed_content LIKE '%content/%';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            switch ($row['embed_type']) {
                case 'image':
                case 'video':
                case 'video_chroma':
                case 'object3d':
                case 'pdf':
                    $content = basename($row['embed_content']);
                    if($content!='') {
                        if(!in_array($content,$array_content_files)) {
                            array_push($array_content_files,$content);
                        }
                    }
                    break;
                case 'video_transparent':
                    if (strpos($row['embed_content'], ',') !== false) {
                        $array_contents = explode(",",$row['embed_content']);
                        foreach ($array_contents as $content) {
                            $content = basename($content);
                            if($content!='') {
                                if(!in_array($content,$array_content_files)) {
                                    array_push($array_content_files,$content);
                                }
                            }
                        }
                    } else {
                        $content = basename($row['embed_content']);
                        if($content!='') {
                            if(!in_array($content,$array_content_files)) {
                                array_push($array_content_files,$content);
                            }
                        }
                    }
                    break;
            }
        }
    }
}

$query = "SELECT id,content FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)) AND type IN ('pointclouds') AND content LIKE '%pointclouds/%';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi = $row['id'];
            $content = str_replace("pointclouds/","",$row['content']);
            if($content!='') {
                if(!in_array("settings_".$id_poi.".json",$array_settings_pointclouds)) {
                    array_push($array_settings_pointclouds,"settings_".$id_poi.".json");
                }
                $content = explode("/",$content)[0];
                if(!in_array($content,$array_pointclouds)) {
                    array_push($array_pointclouds,$content);
                }
            }
        }
    }
}

$query = "SELECT p.id,p.content FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)) AND p.type IN ('pointclouds') AND pl.content LIKE '%pointclouds/%';";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_poi = $row['id'];
            $content = str_replace("pointclouds/","",$row['content']);
            if($content!='') {
                if(!in_array("settings_".$id_poi.".json",$array_settings_pointclouds)) {
                    array_push($array_settings_pointclouds,"settings_".$id_poi.".json");
                }
                $content = explode("/",$content)[0];
                if(!in_array($content,$array_pointclouds)) {
                    array_push($array_pointclouds,$content);
                }
            }
        }
    }
}

$query = "SELECT panorama_image,panorama_json,thumb_image,panorama_video,song,logo FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $panorama_image = $row['panorama_image'];
            if($panorama_image!='') {
                if(!in_array($panorama_image,$array_rooms_files)) {
                    array_push($array_rooms_files,$panorama_image);
                }
            }
            $thumb_image = $row['thumb_image'];
            if($thumb_image!='') {
                if(!in_array($thumb_image,$array_thumbs_files)) {
                    array_push($array_thumbs_files,$thumb_image);
                }
            }
            $panorama_video = $row['panorama_video'];
            if($panorama_video!='') {
                if(!in_array($panorama_video,$array_rooms_v_files)) {
                    array_push($array_rooms_v_files,$panorama_video);
                }
            }
            $panorama_json = $row['panorama_json'];
            if($panorama_json!='') {
                if(!in_array($panorama_json,$array_rooms_files)) {
                    array_push($array_rooms_files,$panorama_json);
                }
            }
            $song = $row['song'];
            if($song!='') {
                if(!in_array($song,$array_content_files)) {
                    array_push($array_content_files,$song);
                }
            }
            $logo = $row['logo'];
            if($logo!='') {
                if(!in_array($logo,$array_content_files)) {
                    array_push($array_content_files,$logo);
                }
            }
        }
    }
}

$query = "SELECT panorama_image FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE enable_multires=0 AND aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $panorama_image = $row['panorama_image'];
            if($panorama_image!='') {
                if(!in_array($panorama_image,$array_rooms_files_multires)) {
                    array_push($array_rooms_files_multires,$panorama_image);
                }
            }
        }
    }
}

$query_check = "SELECT ra.id,p.id as c FROM svt_rooms_alt as ra
LEFT JOIN svt_pois as p ON p.type='switch_pano' AND p.content=ra.id
WHERE ra.poi=1 AND ra.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0));";
$result_check = $mysqli->query($query_check);
if($result_check) {
    if($result_check->num_rows>0) {
        while($row_check = $result_check->fetch_array(MYSQLI_ASSOC)) {
            if(empty($row_check['c'])) {
                $id_room_alt = $row_check['id'];
                $mysqli->query("DELETE FROM svt_rooms_alt WHERE id=$id_room_alt;");
            }
        }
        $mysqli->query("ALTER TABLE svt_rooms_alt AUTO_INCREMENT = 1;");
    }
}

$query = "SELECT panorama_image FROM svt_rooms_alt WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0));";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $panorama_image = $row['panorama_image'];
            if($panorama_image!='') {
                if(!in_array($panorama_image,$array_rooms_files)) {
                    array_push($array_rooms_files,$panorama_image);
                }
            }
        }
    }
}

$query = "SELECT image FROM svt_gallery WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_gallery_files)) {
                    array_push($array_gallery_files,$image);
                }
            }
            $image_orig = "o_".$row['image'];
            if($image_orig!='') {
                if(!in_array($image_orig,$array_gallery_files)) {
                    array_push($array_gallery_files,$image_orig);
                }
            }
        }
    }
}
$query = "SELECT image FROM svt_poi_gallery WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)));";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_gallery_files)) {
                    array_push($array_gallery_files,$image);
                }
            }
        }
    }
}
$query = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)));";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_gallery_files)) {
                    array_push($array_gallery_files,$image);
                }
            }
        }
    }
}

$query = "SELECT image FROM svt_poi_objects360 WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0)));";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_object360_files)) {
                    array_push($array_object360_files,$image);
                }
            }
        }
    }
}

$query = "SELECT file FROM svt_media_library WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            if($file!='') {
                if(!in_array($file,$array_media_library_files)) {
                    array_push($array_media_library_files,$file);
                }
            }
        }
    }
}

$query = "SELECT file FROM svt_music_library WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            if($file!='') {
                if(!in_array($file,$array_content_files)) {
                    array_push($array_content_files,$file);
                }
            }
        }
    }
}

$query = "SELECT file FROM svt_sound_library WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            if($file!='') {
                if(!in_array($file,$array_content_files)) {
                    array_push($array_content_files,$file);
                }
            }
        }
    }
}

$query = "SELECT image FROM svt_icons WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_icon_files)) {
                    array_push($array_icon_files,$image);
                }
            }
        }
    }
}

$query = "SELECT pi.image FROM svt_product_images as pi JOIN svt_products as p ON p.id=pi.id_product WHERE p.id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_products_files)) {
                    array_push($array_products_files,$image);
                }
            }
        }
    }
}

$query = "SELECT v.file,svp.id_virtualtour FROM svt_video_project_slides as v JOIN svt_video_projects as svp on v.id_video_project = svp.id WHERE v.file IS NOT NULL AND svp.id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            $id_virtualtour = $row['id_virtualtour'];
            if($file!='') {
                if(!array_key_exists($id_virtualtour,$array_video_assets)) {
                    $array_video_assets[$id_virtualtour] = array();
                }
                if(!in_array($file,$array_video_assets[$id_virtualtour])) {
                    array_push($array_video_assets[$id_virtualtour],$file);
                }
            }
        }
    }
}
$array_video_projects = array();
$query = "SELECT id_virtualtour,id FROM svt_video_projects WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=0);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id = $row['id'];
            $id_virtualtour = $row['id_virtualtour'];
            array_push($array_video_projects, $id_virtualtour . "_" . $id . ".mp4");
        }
    }
}

$query = "SELECT logo,small_logo,background,background_reg FROM svt_settings;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $logo = $row['logo'];
            if($logo!='') {
                if(!in_array($logo,$array_assets_files)) {
                    array_push($array_assets_files,$logo);
                }
            }
            $small_logo = $row['small_logo'];
            if($small_logo!='') {
                if(!in_array($small_logo,$array_assets_files)) {
                    array_push($array_assets_files,$small_logo);
                }
            }
            $background = $row['background'];
            if($background!='') {
                if(!in_array($background,$array_assets_files)) {
                    array_push($array_assets_files,$background);
                }
            }
            $background_reg = $row['background_reg'];
            if($background_reg!='') {
                if(!in_array($background_reg,$array_assets_files)) {
                    array_push($array_assets_files,$background_reg);
                }
            }
        }
    }
}

$query = "SELECT avatar FROM svt_users WHERE avatar != '';";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $avatar = $row['avatar'];
            if($avatar!='') {
                if(!in_array($avatar,$array_assets_files)) {
                    array_push($array_assets_files,$avatar);
                }
            }
        }
    }
}

$query = "SELECT banner,logo,meta_image FROM svt_showcases;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $banner = $row['banner'];
            $logo = $row['logo'];
            $meta_image = $row['meta_image'];
            if($banner!='') {
                if(!in_array($banner,$array_content_files)) {
                    array_push($array_content_files,$banner);
                }
            }
            if($logo!='') {
                if(!in_array($logo,$array_content_files)) {
                    array_push($array_content_files,$logo);
                }
            }
            if($meta_image!='') {
                if(!in_array($meta_image,$array_content_files)) {
                    array_push($array_content_files,$meta_image);
                }
            }
        }
    }
}

$query = "SELECT logo,meta_image FROM svt_globes;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $logo = $row['logo'];
            $meta_image = $row['meta_image'];
            if($logo!='') {
                if(!in_array($logo,$array_content_files)) {
                    array_push($array_content_files,$logo);
                }
            }
            if($meta_image!='') {
                if(!in_array($meta_image,$array_content_files)) {
                    array_push($array_content_files,$meta_image);
                }
            }
        }
    }
}

$query = "SELECT image FROM svt_advertisements WHERE image IS NOT NULL;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            if($image!='') {
                if(!in_array($image,$array_content_files)) {
                    array_push($array_content_files,$image);
                }
            }
        }
    }
}
$query = "SELECT video FROM svt_advertisements WHERE video IS NOT NULL;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows>0) {
        while($row=$result->fetch_array(MYSQLI_ASSOC)) {
            $video = $row['video'];
            if($video!='') {
                if(!in_array($video,$array_content_files)) {
                    array_push($array_content_files,$video);
                }
            }
        }
    }
}

check_files($path."viewer".DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR,$array_content_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$array_content_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."maps".DIRECTORY_SEPARATOR,$array_map_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."maps".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$array_map_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR,$array_rooms_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."original".DIRECTORY_SEPARATOR,$array_rooms_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."mobile".DIRECTORY_SEPARATOR,$array_rooms_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$array_rooms_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."lowres".DIRECTORY_SEPARATOR,$array_rooms_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."preview".DIRECTORY_SEPARATOR,$array_rooms_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."thumb_custom".DIRECTORY_SEPARATOR,$array_thumbs_files);

$files = glob($path . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "multires" . DIRECTORY_SEPARATOR . "*");
foreach ($files as $file) {
    if (is_dir($file)) {
        $filename = basename($file).".jpg";
        if (!in_array($filename, $array_rooms_files)) {
            try {
                deleteDir($path . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "multires" . DIRECTORY_SEPARATOR . basename($file));
                rmdir($path . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "multires" . DIRECTORY_SEPARATOR . basename($file));
            } catch (Exception $e) {}
        }
    }
}

foreach ($array_rooms_files_multires as $filename) {
    if(file_exists($path . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "multires" . DIRECTORY_SEPARATOR . basename($filename, ".jpg"))) {
        try {
            deleteDir($path . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "multires" . DIRECTORY_SEPARATOR . basename($filename, ".jpg"));
            rmdir($path . "viewer" . DIRECTORY_SEPARATOR . "panoramas" . DIRECTORY_SEPARATOR . "multires" . DIRECTORY_SEPARATOR . basename($filename, ".jpg"));
        } catch (Exception $e) {}
    }
}

check_files($path."viewer".DIRECTORY_SEPARATOR."videos".DIRECTORY_SEPARATOR,$array_rooms_v_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."gallery".DIRECTORY_SEPARATOR,$array_gallery_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."gallery".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$array_gallery_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR,$array_media_library_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$array_media_library_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."objects360".DIRECTORY_SEPARATOR,$array_object360_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."products".DIRECTORY_SEPARATOR,$array_products_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."products".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$array_products_files);
check_files($path."viewer".DIRECTORY_SEPARATOR."icons".DIRECTORY_SEPARATOR,$array_icon_files);
check_files($path."backend".DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR,$array_assets_files);

foreach ($array_id_vt as $id_vt) {
    if(array_key_exists($id_vt,$array_video_assets)) {
        check_files($path."video".DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR.$id_vt.DIRECTORY_SEPARATOR,$array_video_assets[$id_vt]);
    }
}

$path_file = $path . "video" . DIRECTORY_SEPARATOR;
if ($dh = opendir($path_file)) {
    while (($filename = readdir($dh)) !== false) {
        if ($filename != "." && $filename != "..") {
            if(is_file($path_file.$filename)) {
                $extension = substr($filename, strrpos($filename, '.') + 1);
                if(strtolower($extension)=='mp4') {
                    if(!in_array($filename,$array_video_projects)) {
                        unlink($path_file.$filename);
                    }
                }
            }
        }
    }
    closedir($dh);
}

$dir = $path . "video" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR;
$files = scandir($dir);
foreach ($files as $file) {
    if (is_dir($dir . DIRECTORY_SEPARATOR . $file) && $file != '.' && $file != '..') {
        if(!in_array($file,$array_id_vt)) {
            deleteDir($dir . DIRECTORY_SEPARATOR . $file);
        } else {
            $files = glob($dir . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . '*');
            if (!$files) {
                deleteDir($dir . DIRECTORY_SEPARATOR . $file);
            }
        }
    }
}

$dir = $path . "video360" . DIRECTORY_SEPARATOR;
$files = scandir($dir);
foreach ($files as $file) {
    if (is_dir($dir . DIRECTORY_SEPARATOR . $file) && $file != '.' && $file != '..') {
        if(!in_array($file,$array_id_vt)) {
            deleteDir($dir . DIRECTORY_SEPARATOR . $file);
        } else {
            $files = glob($dir . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . '*');
            if (!$files) {
                deleteDir($dir . DIRECTORY_SEPARATOR . $file);
            }
        }
    }
}

$files = glob($path . "viewer" . DIRECTORY_SEPARATOR . "pointclouds" . DIRECTORY_SEPARATOR . "*");
foreach ($files as $file) {
    if (is_dir($file)) {
        $filename = basename($file);
        if (!in_array($filename, $array_pointclouds)) {
            try {
                deleteDir($path . "viewer" . DIRECTORY_SEPARATOR . "pointclouds" . DIRECTORY_SEPARATOR . $filename);
                rmdir($path . "viewer" . DIRECTORY_SEPARATOR . "pointclouds" . DIRECTORY_SEPARATOR . $filename);
            } catch (Exception $e) {}
        } else {
            $files_s = glob($path . "viewer" . DIRECTORY_SEPARATOR . "pointclouds" . DIRECTORY_SEPARATOR . $filename . DIRECTORY_SEPARATOR . "settings_*");
            foreach ($files_s as $file_s) {
                $filename_s = basename($file_s);
                if (!in_array($filename_s, $array_settings_pointclouds)) {
                    unlink($file_s);
                }
            }
        }
    }
}

clean_images_s3();
ob_end_clean();

function check_files($path_file,$array) {
    if ($dh = opendir($path_file)) {
        while (($filename = readdir($dh)) !== false) {
            if ($filename != "." && $filename != "..") {
                if(is_file($path_file.$filename)) {
                    if(!in_array($filename,$array)) {
                        unlink($path_file.$filename);
                    }
                }
            }
        }
        closedir($dh);
    }
}

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

function clean_images_s3() {
    if(isEnabled('shell_exec')) {
        try {
            $command = 'command -v php 2>&1';
            $output = shell_exec($command);
            if (empty($output)) $output = PHP_BINARY;
            $path_php = trim($output);
            $path_php = str_replace("sbin/php-fpm","bin/php",$path_php);
            $path = realpath(dirname(__FILE__));
            $command = $path_php." ".$path.DIRECTORY_SEPARATOR."clean_images_s3.php > /dev/null &";
            shell_exec($command);
        } catch (Exception $e) {}
    }
}