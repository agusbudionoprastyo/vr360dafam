<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
if(!class_exists('Gumlet\ImageResize')) {
    require_once(dirname(__FILE__).'/ImageResizeException.php');
    require_once(dirname(__FILE__).'/ImageResize.php');
}
require_once(dirname(__FILE__)."/../backend/functions.php");
use \Gumlet\ImageResize;

if(isset($s3_enabled)) {
    $s3_enabled_g = $s3_enabled;
} else {
    $s3_enabled_g = false;
}

if(!isset($settings)) {
    $settings = get_settings();
}

if(isset($panorama_image_gt)) {
    if($s3_enabled_g) {
        $file_path = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image_gt";
        $mobile_path = "s3://$s3_bucket_name/viewer/panoramas/mobile/$panorama_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR.$panorama_image_gt;
        $mobile_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."mobile".DIRECTORY_SEPARATOR.$panorama_image_gt;
    }
    if(!file_exists($mobile_path)) {
        try {
            $image = new ImageResize($file_path);
            $image->quality_jpg = 90;
            $image->interlace = 1;
            $image->gamma(false);
            $image->resizeToWidth(4096,false);
            $image->save($mobile_path,IMAGETYPE_JPEG);
        } catch (Exception $e) {}
        if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/mobile/$panorama_image_gt",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    }
} else {
    $path = dirname(__FILE__).'/../viewer/panoramas/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            $mobile_path = str_replace(DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."mobile".DIRECTORY_SEPARATOR,$file_path);
            if(!file_exists($mobile_path)) {
                try {
                    $image = new ImageResize($file_path);
                    $image->quality_jpg = 90;
                    $image->interlace = 1;
                    $image->gamma(false);
                    $image->resizeToWidth(4096,false);
                    $image->save($mobile_path,IMAGETYPE_JPEG);
                } catch (Exception $e) {}
            }
        }
    }
}
ob_end_clean();