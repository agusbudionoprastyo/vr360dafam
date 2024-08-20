<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/vendor/amazon-aws-sdk/aws-autoloader.php");
require_once(__DIR__."/../backend/functions.php");

$result = $mysqli->query("SHOW COLUMNS FROM svt_virtualtours LIKE 'aws_s3';");
if($result) {
    if ($result->num_rows==0) {
        exit;
    }
}

$s3_enabled = false;
$query = "SELECT aws_s3_type,aws_s3_enabled,aws_s3_bucket,aws_s3_key,aws_s3_region,aws_s3_secret,aws_s3_accountid FROM svt_settings;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        switch($row['aws_s3_type']) {
            case 'aws':
                if($row['aws_s3_enabled'] && !empty($row['aws_s3_region']) && !empty($row['aws_s3_key']) && !empty($row['aws_s3_secret']) && !empty($row['aws_s3_bucket'])) {
                    $s3Config = [
                        'region' => $row['aws_s3_region'],
                        'version' => 'latest',
                        'credentials' => [
                            'key'    => $row['aws_s3_key'],
                            'secret' => $row['aws_s3_secret']
                        ]
                    ];
                    $s3Client = new Aws\S3\S3Client($s3Config);
                    $s3_bucket_name = $row['aws_s3_bucket'];
                    if($s3Client->doesBucketExist($s3_bucket_name)) {
                        try {
                            $s3Client->registerStreamWrapper();
                            $s3_enabled = true;
                        } catch (Aws\Exception\S3Exception $e) {}
                    }
                }
                break;
            case 'r2':
                if($row['aws_s3_enabled'] && !empty($row['aws_s3_accountid']) && !empty($row['aws_s3_key']) && !empty($row['aws_s3_secret']) && !empty($row['aws_s3_bucket'])) {
                    $credentials = new Aws\Credentials\Credentials($row['aws_s3_key'], $row['aws_s3_secret']);
                    $s3Config = [
                        'region' => 'auto',
                        'version' => 'latest',
                        'endpoint' => "https://".$row['aws_s3_accountid'].".r2.cloudflarestorage.com",
                        'credentials' => $credentials
                    ];
                    $s3Client = new Aws\S3\S3Client($s3Config);
                    $s3_bucket_name = $row['aws_s3_bucket'];
                    if($s3Client->doesBucketExist($s3_bucket_name)) {
                        try {
                            $s3Client->registerStreamWrapper();
                            $s3_enabled = true;
                        } catch (Aws\Exception\S3Exception $e) {}
                    }
                }
                break;
            case 'digitalocean':
                if($row['aws_s3_enabled'] && !empty($row['aws_s3_region']) && !empty($row['aws_s3_key']) && !empty($row['aws_s3_secret']) && !empty($row['aws_s3_bucket'])) {
                    $s3Config = [
                        'region' => 'us-east-1',
                        'version' => 'latest',
                        'endpoint' => "https://".$row['aws_s3_region'].".digitaloceanspaces.com",
                        'use_path_style_endpoint' => false,
                        'credentials' => [
                            'key'    => $row['aws_s3_key'],
                            'secret' => $row['aws_s3_secret']
                        ]
                    ];
                    $s3Client = new Aws\S3\S3Client($s3Config);
                    $s3_bucket_name = $row['aws_s3_bucket'];
                    if($s3Client->doesBucketExist($s3_bucket_name)) {
                        try {
                            $s3Client->registerStreamWrapper();
                            $s3_enabled = true;
                        } catch (Aws\Exception\S3Exception $e) {}
                    }
                }
                break;
        }
    }
}
if($s3_enabled) {
    $array_content_files = array();
    $array_map_files = array();
    $array_rooms_files = array();
    $array_rooms_files_multires = array();
    $array_rooms_v_files = array();
    $array_gallery_files = array();
    $array_media_library_files = array();
    $array_icon_files = array();
    $array_thumbs_files = array();
    $array_object360_files = array();
    $array_products_files = array();
    $array_id_vt = array();
    $array_video_assets = array();
    $array_pointclouds = array();
    $array_settings_pointclouds = array();

    $query = "SELECT id,song,logo,nadir_logo,background_image,background_image_mobile,background_video,background_image_mobile,background_video_mobile,intro_desktop,intro_mobile,presentation_video,meta_image,meta_image_l,dollhouse_glb FROM svt_virtualtours WHERE aws_s3=1;";
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
            }
        }
    }

    $query = "SELECT map FROM svt_maps WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT content,type FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)) AND type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf') AND content LIKE '%content/%';";
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

    $query = "SELECT pl.content,p.type FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)) AND p.type IN ('image','download','video','video360','audio','embed','object3d','lottie','pdf') AND p.content LIKE '%content/%';";
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

    $query = "SELECT embed_type,embed_content FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)) AND embed_content LIKE '%content/%';";
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

    $query = "SELECT pl.embed_content,p.embed_type FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)) AND pl.embed_content LIKE '%content/%';";
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

    $query = "SELECT id,content FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)) AND type IN ('pointclouds') AND content LIKE '%pointclouds/%';";
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

    $query = "SELECT p.id,p.content FROM svt_pois_lang as pl JOIN svt_pois as p ON p.id=pl.id_poi WHERE p.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)) AND p.type IN ('pointclouds') AND pl.content LIKE '%pointclouds/%';";
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

    $query = "SELECT panorama_image,panorama_json,thumb_image,panorama_video,song,logo FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT panorama_image FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE enable_multires=0 AND aws_s3=1);";
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
WHERE ra.poi=1 AND ra.id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1));";
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

    $query = "SELECT panorama_image FROM svt_rooms_alt WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1));";
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

    $query = "SELECT image FROM svt_gallery WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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
    $query = "SELECT image FROM svt_poi_gallery WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)));";
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
    $query = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)));";
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

    $query = "SELECT image FROM svt_poi_objects360 WHERE id_poi IN (SELECT id FROM svt_pois WHERE id_room IN (SELECT id FROM svt_rooms WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1)));";
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

    $query = "SELECT file FROM svt_media_library WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT file FROM svt_music_library WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT file FROM svt_sound_library WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT image FROM svt_icons WHERE id_virtualtour IS NULL OR id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT pi.image FROM svt_product_images as pi JOIN svt_products as p ON p.id=pi.id_product WHERE p.id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    $query = "SELECT v.file,svp.id_virtualtour FROM svt_video_project_slides as v JOIN svt_video_projects as svp on v.id_video_project = svp.id WHERE v.file IS NOT NULL AND svp.id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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
    $query = "SELECT id_virtualtour,id FROM svt_video_projects WHERE id_virtualtour IN (SELECT id FROM svt_virtualtours WHERE aws_s3=1);";
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

    check_files_s3("s3://$s3_bucket_name/viewer/content/",$array_content_files);
    check_files_s3("s3://$s3_bucket_name/viewer/content/thumb/",$array_content_files);
    check_files_s3("s3://$s3_bucket_name/viewer/maps/",$array_map_files);
    check_files_s3("s3://$s3_bucket_name/viewer/maps/thumb/",$array_map_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/",$array_rooms_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/original/",$array_rooms_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/mobile/",$array_rooms_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/thumb/",$array_rooms_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/lowres/",$array_rooms_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/preview/",$array_rooms_files);
    check_files_s3("s3://$s3_bucket_name/viewer/panoramas/thumb_custom/",$array_thumbs_files);

    $helperObj = new S3StreamHelper();
    $files = $helperObj->glob("s3://$s3_bucket_name/viewer/panoramas/multires/*");
    foreach ($files as $file) {
        if (is_dir($file)) {
            $filename = basename($file).".jpg";
            if (!in_array($filename, $array_rooms_files)) {
                try {
                    deleteDir_s3($s3Client,$s3_bucket_name,"viewer/panoramas/multires/".basename($file));
                } catch (Exception $e) {}
            }
        }
    }

    foreach ($array_rooms_files_multires as $filename) {
        if(file_exists("s3://$s3_bucket_name/viewer/panoramas/multires/" . basename($filename, ".jpg"))) {
            try {
                deleteDir_s3($s3Client,$s3_bucket_name,"viewer/panoramas/multires/". basename($filename, ".jpg"));
            } catch (Exception $e) {}
        }
    }

    check_files_s3("s3://$s3_bucket_name/viewer/videos/",$array_rooms_v_files);
    check_files_s3("s3://$s3_bucket_name/viewer/gallery/",$array_gallery_files);
    check_files_s3("s3://$s3_bucket_name/viewer/gallery/thumb/",$array_gallery_files);
    check_files_s3("s3://$s3_bucket_name/viewer/media/",$array_media_library_files);
    check_files_s3("s3://$s3_bucket_name/viewer/media/thumb/",$array_media_library_files);
    check_files_s3("s3://$s3_bucket_name/viewer/objects360/",$array_object360_files);
    check_files_s3("s3://$s3_bucket_name/viewer/products/",$array_products_files);
    check_files_s3("s3://$s3_bucket_name/viewer/products/thumb/",$array_products_files);
    check_files_s3("s3://$s3_bucket_name/viewer/icons/",$array_icon_files);

    foreach ($array_id_vt as $id_vt) {
        if(array_key_exists($id_vt,$array_video_assets)) {
            check_files_s3("s3://$s3_bucket_name/video/assets/$id_vt/",$array_video_assets[$id_vt]);
        }
    }

    $path_file = "s3://$s3_bucket_name/video/";
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

    $path_file = "s3://$s3_bucket_name/video/assets/";
    if ($dh = opendir($path_file)) {
        while (($filename = readdir($dh)) !== false) {
            if ($filename != "." && $filename != "..") {
                if(is_dir($path_file . $filename)) {
                    if(!in_array($filename,$array_id_vt)) {
                        deleteDir_s3($s3Client,$s3_bucket_name,"video/assets/".$filename);
                    }
                }
            }
        }
        closedir($dh);
    }

    $path_file = "s3://$s3_bucket_name/video360/";
    if ($dh = opendir($path_file)) {
        while (($filename = readdir($dh)) !== false) {
            if ($filename != "." && $filename != "..") {
                if(is_dir($path_file . $filename)) {
                    if(!in_array($filename,$array_id_vt)) {
                        deleteDir_s3($s3Client,$s3_bucket_name,"video360/".$filename);
                    }
                }
            }
        }
        closedir($dh);
    }

    $helperObj = new S3StreamHelper();
    $files = $helperObj->glob("s3://$s3_bucket_name/viewer/pointclouds/*");
    foreach ($files as $file) {
        if (is_dir($file)) {
            $filename = basename($file);
            if (!in_array($filename, $array_pointclouds)) {
                try {
                    deleteDir_s3($s3Client,$s3_bucket_name,"viewer/pointclouds/$filename");
                } catch (Exception $e) {}
            } else {
                $files_s = $helperObj->glob("s3://$s3_bucket_name/viewer/pointclouds/$filename/*");
                foreach ($files_s as $file_s) {
                    $filename_s = basename($file_s);
                    if (strpos($filename_s, 'settings_') !== false) {
                        if (!in_array($filename_s, $array_settings_pointclouds)) {
                            unlink("s3://$s3_bucket_name/viewer/pointclouds/$filename/$filename_s");
                        }
                    }
                }
            }
        }
    }
}
if(isset($_SESSION['id_user'])) {
    update_user_space_storage($_SESSION['id_user'],false);
}
ob_end_clean();

function check_files_s3($path_file,$array) {
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

function deleteDir_s3($s3Client,$bucket,$directory) {
    $s3Client->deleteMatchingObjects($bucket, $directory);
}

class S3StreamHelper {
    public function glob($pattern) {
        $return = [];
        $patternFound = preg_match('(\*|\?|\[.+\])', $pattern, $parentPattern, PREG_OFFSET_CAPTURE);
        if ($patternFound) {
            $parent = dirname(substr($pattern, 0, $parentPattern[0][1] + 1));
            $parentLength = strlen($parent);
            $leftover = substr($pattern, $parentPattern[0][1]);
            if (($index = strpos($leftover, '/')) !== FALSE) {
                $searchPattern = substr($pattern, $parentLength + 1, $parentPattern[0][1] - $parentLength + $index - 1);
            } else {
                $searchPattern = substr($pattern, $parentLength + 1);
            }

            $replacement = [
                '/\*/' => '.*',
                '/\?/' => '.'
            ];
            $searchPattern = preg_replace(array_keys($replacement), array_values($replacement), $searchPattern);
            if (is_dir($parent."/") && ($dh = opendir($parent."/"))) {
                while($dir = readdir($dh)) {
                    if (!in_array($dir, ['.', '..'])) {
                        if (preg_match("/^". $searchPattern ."$/", $dir)) {
                            if ($index === FALSE || strlen($leftover) == $index + 1) {
                                $return[] = $parent . "/" . $dir;
                            } else {
                                if (strlen($leftover) > $index + 1) {
                                    $return = array_merge($return, self::glob("{$parent}/{$dir}" . substr($leftover, $index)));
                                }
                            }
                        }
                    }
                }
            }
        } elseif(is_dir($pattern) || is_file($$pattern)) {
            $return[] = $pattern;
        }
        return $return;
    }
}