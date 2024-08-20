<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
include_once(dirname(__FILE__)."/thumb.php");
require_once(dirname(__FILE__)."/../db/connection.php");
require_once(dirname(__FILE__)."/../backend/functions.php");
use claviska\SimpleImage;
class ThumbNailer extends SimpleImage{
    public function generateThumbnail($source,$destination,$width=300,$height=300){
        try{
            $this->fromFile($source)
                ->thumbnail($width, $height, "center")
                ->toFile($destination);
            return true;
        }
        catch(Exception $e){
            echo $e;
            return false;
        }
    }
}
$tn = new ThumbNailer();

$force_all_thumb = false;

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
        $thumb_path =  "s3://$s3_bucket_name/viewer/panoramas/thumb/$panorama_image_gt";
        $lowres_path =  "s3://$s3_bucket_name/viewer/panoramas/lowres/$panorama_image_gt";
        $preview_path =  "s3://$s3_bucket_name/viewer/panoramas/preview/$panorama_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR.$panorama_image_gt;
        $thumb_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$panorama_image_gt;
        $lowres_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."lowres".DIRECTORY_SEPARATOR.$panorama_image_gt;
        $preview_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."preview".DIRECTORY_SEPARATOR.$panorama_image_gt;
    }
    list($width, $height, $type, $attr) = getimagesize($file_path);
    $aspct_ratio = $height/$width;
    if(!file_exists($thumb_path)) {
        $tn->generateThumbnail($file_path,$thumb_path,213,120);
        if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/thumb/$panorama_image_gt",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    }
    if(!file_exists($lowres_path)) {
        $tn->generateThumbnail($file_path,$lowres_path,1280,1280*$aspct_ratio);
        if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/lowres/$panorama_image_gt",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    }
    if(!file_exists($preview_path)) {
        $crop_width = $width * 0.5;
        $crop_height = $height * 0.5;
        $im = @imagecreatefromjpeg($file_path);
        if($im === false) {
            $im = imagecreatefrompng($file_path);
        }
        if($im != false) {
            imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$preview_path,100);
            try {
                imagedestroy($im);
            } catch (Exception $e) {}
            $tn->generateThumbnail($preview_path,$preview_path,213,120);
        }
        if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/panoramas/preview/$panorama_image_gt",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    }
} else if(isset($array_panoramas_gt)) {
    foreach ($array_panoramas_gt as $panorama_image_gt) {
        if($s3_enabled_g) {
            $file_path = "s3://$s3_bucket_name/viewer/panoramas/$panorama_image_gt";
            $thumb_path =  "s3://$s3_bucket_name/viewer/panoramas/thumb/$panorama_image_gt";
            $lowres_path =  "s3://$s3_bucket_name/viewer/panoramas/lowres/$panorama_image_gt";
            $preview_path =  "s3://$s3_bucket_name/viewer/panoramas/preview/$panorama_image_gt";
        } else {
            $path = dirname(__FILE__).'/../viewer/';
            $file_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR.$panorama_image_gt;
            $thumb_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$panorama_image_gt;
            $lowres_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."lowres".DIRECTORY_SEPARATOR.$panorama_image_gt;
            $preview_path = $path.DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."preview".DIRECTORY_SEPARATOR.$panorama_image_gt;
        }
        list($width, $height, $type, $attr) = getimagesize($file_path);
        $aspct_ratio = $height/$width;
        if(!file_exists($thumb_path)) {
            $tn->generateThumbnail($file_path,$thumb_path,213,120);
            if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/panoramas/thumb/$panorama_image_gt",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        }
        if(!file_exists($lowres_path)) {
            $tn->generateThumbnail($file_path,$lowres_path,1280,1280*$aspct_ratio);
            if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/panoramas/lowres/$panorama_image_gt",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        }
        if(!file_exists($preview_path)) {
            $crop_width = $width * 0.5;
            $crop_height = $height * 0.5;
            $im = @imagecreatefromjpeg($file_path);
            if($im === false) {
                $im = imagecreatefrompng($file_path);
            }
            if($im != false) {
                imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$preview_path,100);
                try {
                    imagedestroy($im);
                } catch (Exception $e) {}
                $tn->generateThumbnail($preview_path,$preview_path,213,120);
            }
            if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/panoramas/preview/$panorama_image_gt",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        }
    }
} else if(isset($map_image_gt)) {
    if($s3_enabled_g) {
        $file_path = "s3://$s3_bucket_name/viewer/maps/$map_image_gt";
        $thumb_path = "s3://$s3_bucket_name/viewer/maps/thumb/$map_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."maps".DIRECTORY_SEPARATOR.$map_image_gt;
        $thumb_path = $path.DIRECTORY_SEPARATOR."maps".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$map_image_gt;
    }
    if(!file_exists($thumb_path)) {
        list($width, $height, $type, $attr) = getimagesize($file_path);
        $crop_width = $width * 0.5;
        $crop_height = $height * 0.5;
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $im = false;
        switch($ext) {
            case 'png':
                $im = @imagecreatefrompng($file_path);
                break;
            case 'jpg':
            case 'jpeg':
                $im = @imagecreatefromjpeg($file_path);
                if($im === false) {
                    $im = @imagecreatefrompng($file_path);
                }
                break;
        }
        if($im != false) {
            imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$thumb_path,100);
            try {
                imagedestroy($im);
            } catch (Exception $e) {}
            $tn->generateThumbnail($thumb_path,$thumb_path,213,120);
        }
        if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/maps/thumb/$map_image_gt",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    }
} else if(isset($gallery_image_gt)) {
    if($s3_enabled_g) {
        $file_path = "s3://$s3_bucket_name/viewer/gallery/$gallery_image_gt";
        $thumb_path = "s3://$s3_bucket_name/viewer/gallery/thumb/$gallery_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."gallery".DIRECTORY_SEPARATOR.$gallery_image_gt;
        $thumb_path = $path.DIRECTORY_SEPARATOR."gallery".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$gallery_image_gt;
    }
    if(@is_array(getimagesize($file_path))){
        if(!file_exists($thumb_path)) {
            $tn->generateThumbnail($file_path,$thumb_path,200,200);
            if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/gallery/thumb/$gallery_image_gt",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        }
    }
} else if(isset($content_image_gt)) {
    if($s3_enabled_g) {
        $file_path = "s3://$s3_bucket_name/viewer/content/$content_image_gt";
        $thumb_path = "s3://$s3_bucket_name/viewer/content/thumb/$content_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR.$content_image_gt;
        $thumb_path = $path.DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$content_image_gt;
    }
    if(@is_array(getimagesize($file_path))){
        if(!file_exists($thumb_path)) {
            $tn->generateThumbnail($file_path,$thumb_path,200,200);
            if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/content/thumb/$content_image_gt",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        }
    }
} else if(isset($media_image_gt)) {
    if($s3_enabled_g) {
        $file_path = "s3://$s3_bucket_name/viewer/media/$media_image_gt";
        $thumb_path = "s3://$s3_bucket_name/viewer/media/thumb/$media_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR.$media_image_gt;
        $thumb_path = $path.DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$media_image_gt;
    }
    if(@is_array(getimagesize($file_path))){
        if(!file_exists($thumb_path)) {
            $tn->generateThumbnail($file_path,$thumb_path,200,200);
            if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
                try {
                    $s3Client->putObjectAcl([
                        'Bucket' => $s3_bucket_name,
                        'Key' => "viewer/media/thumb/$media_image_gt",
                        'ACL' => 'public-read',
                    ]);
                } catch (\Aws\S3\Exception\S3Exception $e) {}
            }
        }
    }
} else if(isset($product_image_gt)) {
    if($s3_enabled_g) {
        $file_path = "s3://$s3_bucket_name/viewer/products/$product_image_gt";
        $thumb_path = "s3://$s3_bucket_name/viewer/products/thumb/$product_image_gt";
    } else {
        $path = dirname(__FILE__).'/../viewer/';
        $file_path = $path.DIRECTORY_SEPARATOR."products".DIRECTORY_SEPARATOR.$product_image_gt;
        $thumb_path = $path.DIRECTORY_SEPARATOR."products".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR.$product_image_gt;
    }
    if(!file_exists($thumb_path)) {
        list($width, $height, $type, $attr) = getimagesize($file_path);
        $crop_width = $width * 0.5;
        $crop_height = $height * 0.5;
        $im = @imagecreatefromjpeg($file_path);
        if($im === false) {
            $im = imagecreatefrompng($file_path);
        }
        if($im != false) {
            imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$thumb_path,100);
            try {
                imagedestroy($im);
            } catch (Exception $e) {}
            $tn->generateThumbnail($thumb_path,$thumb_path,200,200);
        }
        if($s3_enabled_g && $settings['aws_s3_type']=='digitalocean') {
            try {
                $s3Client->putObjectAcl([
                    'Bucket' => $s3_bucket_name,
                    'Key' => "viewer/products/thumb/$product_image_gt",
                    'ACL' => 'public-read',
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {}
        }
    }
} else if($force_all_thumb) {
    $s3_enabled = false;
    $query = "SELECT aws_s3_enabled,aws_s3_bucket,aws_s3_key,aws_s3_region,aws_s3_secret FROM svt_settings;";
    $result = $mysqli->query($query);
    if($result) {
        if($result->num_rows==1) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if($row['aws_s3_enabled'] && !empty($row['aws_s3_region']) && !empty($row['aws_s3_key']) && !empty($row['aws_s3_secret']) && !empty($row['aws_s3_bucket'])) {
                $s3_enabled = true;
            }
        }
    }
    $path = dirname(__FILE__).'/../viewer/panoramas/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            $file_ext = $fileinfo->getExtension();
            if($file_ext=='json') continue;
            $thumb_path = str_replace(DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$file_path);
            $lowres_path = str_replace(DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."lowres".DIRECTORY_SEPARATOR,$file_path);
            $preview_path = str_replace(DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."panoramas".DIRECTORY_SEPARATOR."preview".DIRECTORY_SEPARATOR,$file_path);
            list($width, $height, $type, $attr) = getimagesize($file_path);
            $aspct_ratio = $height/$width;
            if(!file_exists($thumb_path)) {
                $tn->generateThumbnail($file_path,$thumb_path,213,120);
            }
            if(!file_exists($lowres_path)) {
                $tn->generateThumbnail($file_path,$lowres_path,1280,1280*$aspct_ratio);
            }
            if(!file_exists($preview_path)) {
                $crop_width = $width * 0.5;
                $crop_height = $height * 0.5;
                $im = @imagecreatefromjpeg($file_path);
                if($im === false) {
                    $im = imagecreatefrompng($file_path);
                }
                if($im != false) {
                    imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$preview_path,100);
                    try {
                        imagedestroy($im);
                    } catch (Exception $e) {}
                    $tn->generateThumbnail($preview_path,$preview_path,213,120);
                }
            }
        }
    }

    $path = dirname(__FILE__).'/../viewer/maps/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            $thumb_path = str_replace(DIRECTORY_SEPARATOR."maps".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."maps".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$file_path);
            if(!file_exists($thumb_path)) {
                list($width, $height, $type, $attr) = getimagesize($file_path);
                $crop_width = $width * 0.5;
                $crop_height = $height * 0.5;
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                $im = false;
                switch($ext) {
                    case 'png':
                        $im = @imagecreatefrompng($file_path);
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $im = @imagecreatefromjpeg($file_path);
                        if($im === false) {
                            $im = @imagecreatefrompng($file_path);
                        }
                        break;
                }
                if($im != false) {
                    imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$thumb_path,100);
                    try {
                        imagedestroy($im);
                    } catch (Exception $e) {}
                    $tn->generateThumbnail($thumb_path,$thumb_path,213,120);
                }
            }
        }
    }

    $path = dirname(__FILE__).'/../viewer/gallery/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            if(@is_array(getimagesize($file_path))){
                $thumb_path = str_replace(DIRECTORY_SEPARATOR."gallery".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."gallery".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$file_path);
                if(!file_exists($thumb_path)) {
                    $tn->generateThumbnail($file_path,$thumb_path,200,200);
                }
            }
        }
    }

    $path = dirname(__FILE__).'/../viewer/content/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            $file_ext = $fileinfo->getExtension();
            if($file_ext=='json') continue;
            if(@is_array(getimagesize($file_path))){
                $thumb_path = str_replace(DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."content".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$file_path);
                if(!file_exists($thumb_path)) {
                    $tn->generateThumbnail($file_path,$thumb_path,200,200);
                }
            }
        }
    }

    $path = dirname(__FILE__).'/../viewer/media/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            if(@is_array(getimagesize($file_path))){
                $thumb_path = str_replace(DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."media".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$file_path);
                if(!file_exists($thumb_path)) {
                    $tn->generateThumbnail($file_path,$thumb_path,200,200);
                }
            }
        }
    }

    $path = dirname(__FILE__).'/../viewer/products/';
    $dir = new DirectoryIterator($path);
    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot() && ($fileinfo->isFile())) {
            $file_path = $fileinfo->getRealPath();
            $file_name = $fileinfo->getBasename();
            $thumb_path = str_replace(DIRECTORY_SEPARATOR."products".DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR."products".DIRECTORY_SEPARATOR."thumb".DIRECTORY_SEPARATOR,$file_path);
            if(!file_exists($thumb_path)) {
                list($width, $height, $type, $attr) = getimagesize($file_path);
                $crop_width = $width * 0.5;
                $crop_height = $height * 0.5;
                $im = @imagecreatefromjpeg($file_path);
                if($im === false) {
                    $im = imagecreatefrompng($file_path);
                }
                if($im != false) {
                    imagejpeg(cropAlign($im, $crop_width, $crop_height, 'center', 'middle',$width,$height),$thumb_path,100);
                    try {
                        imagedestroy($im);
                    } catch (Exception $e) {}
                    $tn->generateThumbnail($thumb_path,$thumb_path,200,200);
                }
            }
        }
    }

}

ob_end_clean();

function cropAlign($image, $cropWidth, $cropHeight, $horizontalAlign, $verticalAlign, $width, $height) {
    $horizontalAlignPixels = calculatePixelsForAlign($width, $cropWidth, $horizontalAlign);
    $verticalAlignPixels = calculatePixelsForAlign($height, $cropHeight, $verticalAlign);
    return imageCrop($image, [
        'x' => $horizontalAlignPixels[0],
        'y' => $verticalAlignPixels[0],
        'width' => $horizontalAlignPixels[1],
        'height' => $verticalAlignPixels[1]
    ]);
}

function calculatePixelsForAlign($imageSize, $cropSize, $align) {
    switch ($align) {
        case 'left':
        case 'top':
            return [0, min($cropSize, $imageSize)];
        case 'right':
        case 'bottom':
            return [max(0, $imageSize - $cropSize), min($cropSize, $imageSize)];
        case 'center':
        case 'middle':
            return [
                max(0, floor(($imageSize / 2) - ($cropSize / 2))),
                min($cropSize, $imageSize),
            ];
        default: return [0, $imageSize];
    }
}

function png2jpg($originalFile, $outputFile, $quality) {
    $image = imagecreatefrompng($originalFile);
    imagejpeg($image, $outputFile, $quality);
    imagedestroy($image);
}