<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if(($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip'])) {
    //DEMO CHECK
    die();
}
ini_set("memory_limit",-1);
ini_set('max_execution_time', 9999);
ini_set('max_input_time', 9999);
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
require(__DIR__."/../backend/vendor/amazon-aws-sdk/aws-autoloader.php");
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Exception\S3Exception;
use Aws\CommandPool;
use Aws\CommandInterface;
use Aws\ResultInterface;
use GuzzleHttp\Promise\PromiseInterface;
$debug = false;
$settings = get_settings();
$user_info = get_user_info($_SESSION['id_user']);
$user_role = get_user_role($_SESSION['id_user']);
if(!empty($user_info['language'])) {
    set_language($user_info['language'],$settings['language_domain']);
} else {
    set_language($settings['language'],$settings['language_domain']);
}
session_write_close();
if($user_role!='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>_("unauthorized")));
    exit;
}
$id_vt = $_POST['id_virtualtour'];
$array_file_uploaded = array();
$s3Client = null;
$aws_s3_type = $settings['aws_s3_type'];
$aws_s3_accountid = $settings['aws_s3_accountid'];
$aws_s3_secret = $settings['aws_s3_secret'];
$aws_s3_key = $settings['aws_s3_key'];
$aws_s3_region = $settings['aws_s3_region'];
$aws_s3_bucket = $settings['aws_s3_bucket'];
switch($aws_s3_type) {
    case 'aws':
        $s3Config = [
            'region' => $aws_s3_region,
            'version' => 'latest',
            'retries' => [
                'mode' => 'standard',
                'max_attempts' => 5
            ],
            'credentials' => [
                'key'    => $aws_s3_key,
                'secret' => $aws_s3_secret
            ]
        ];
        break;
    case 'r2':
        $credentials = new Aws\Credentials\Credentials($aws_s3_key, $aws_s3_secret);
        $s3Config = [
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => "https://".$aws_s3_accountid.".r2.cloudflarestorage.com",
            'retries' => [
                'mode' => 'standard',
                'max_attempts' => 5
            ],
            'credentials' => $credentials
        ];
        break;
    case 'digitalocean':
        $s3Config = [
            'region' => 'us-east-1',
            'version' => 'latest',
            'endpoint' => "https://$aws_s3_region.digitaloceanspaces.com",
            'use_path_style_endpoint' => false,
            'retries' => [
                'mode' => 'standard',
                'max_attempts' => 5
            ],
            'credentials' => [
                'key'    => $aws_s3_key,
                'secret' => $aws_s3_secret
            ]
        ];
        break;
}
$s3Client = new S3Client($s3Config);
if(!$s3Client->doesBucketExist($aws_s3_bucket)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error","msg"=>"bucket not exist"));
    exit;
}
$uploadPromises = [];
$code = '';
$query = "SELECT code,song,logo,nadir_logo,background_image,background_image_mobile,background_video,background_video_mobile,intro_desktop,intro_mobile,markers_id_icon_library,pois_id_icon_library,presentation_video,presentation_stop_id_room,dollhouse_glb FROM svt_virtualtours WHERE id=$id_vt LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $code = $row['code'];
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
        if($presentation_video!='') $presentation_video = basename($presentation_video);
        $dollhouse_glb = $row['dollhouse_glb'];
    }
}
if(empty($code)) {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    exit;
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
upload_file($aws_s3_bucket,$s3Client,$song,'content');
upload_file($aws_s3_bucket,$s3Client,$logo,'content');
upload_file($aws_s3_bucket,$s3Client,$nadir_logo,'content');
upload_file($aws_s3_bucket,$s3Client,$background_image,'content');
upload_file($aws_s3_bucket,$s3Client,$background_image,'content/thumb');
upload_file($aws_s3_bucket,$s3Client,$background_video,'content');
upload_file($aws_s3_bucket,$s3Client,$background_image_mobile,'content');
upload_file($aws_s3_bucket,$s3Client,$background_video_mobile,'content');
upload_file($aws_s3_bucket,$s3Client,$intro_desktop,'content');
upload_file($aws_s3_bucket,$s3Client,$intro_mobile,'content');
upload_file($aws_s3_bucket,$s3Client,$presentation_video,'content');
upload_file($aws_s3_bucket,$s3Client,$dollhouse_glb,'content');
upload_file($aws_s3_bucket,$s3Client,$id_vt."_slideshow.mp4",'gallery');
$query = "SELECT id,image FROM svt_gallery WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_gallery = $row['id'];
            $image = $row['image'];
            upload_file($aws_s3_bucket,$s3Client,$image,'gallery');
            upload_file($aws_s3_bucket,$s3Client,$image,'gallery/thumb');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT map FROM svt_maps WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $map = $row['map'];
            upload_file($aws_s3_bucket,$s3Client,$map,'maps');
            upload_file($aws_s3_bucket,$s3Client,$map,'maps/thumb');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$array_id_rooms = array();
$query = "SELECT id,panorama_image,panorama_video,panorama_json,thumb_image,logo FROM svt_rooms WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_room = $row['id'];
            array_push($array_id_rooms,$id_room);
            $panorama_image = $row['panorama_image'];
            $panorama_name = explode(".",$panorama_image)[0];
            $panorama_video = $row['panorama_video'];
            $panorama_json = $row['panorama_json'];
            $thumb_image = $row['thumb_image'];
            $logo = $row['logo'];
            upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas');
            upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/lowres');
            upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/mobile');
            upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/original');
            upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/preview');
            upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/thumb');
            upload_file($aws_s3_bucket,$s3Client,$panorama_video,'videos');
            upload_file($aws_s3_bucket,$s3Client,$panorama_json,'panoramas');
            upload_file($aws_s3_bucket,$s3Client,$thumb_image,'panoramas/thumb_custom');
            upload_file($aws_s3_bucket,$s3Client,$logo,'content');
            if(file_exists(dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name/")) {
                upload_dir($aws_s3_bucket,$s3Client,dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name/","viewer/panoramas/multires/$panorama_name/");
            }
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$array_id_pois = array();
$id_rooms = implode(",",$array_id_rooms);
if(!empty($id_rooms)) {
    $query = "SELECT panorama_image FROM svt_rooms_alt WHERE id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $panorama_image = $row['panorama_image'];
                $panorama_name = explode(".",$panorama_image)[0];
                upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas');
                upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/lowres');
                upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/mobile');
                upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/original');
                upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/preview');
                upload_file($aws_s3_bucket,$s3Client,$panorama_image,'panoramas/thumb');
                if(file_exists(dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name/")) {
                    upload_dir($aws_s3_bucket,$s3Client,dirname(__FILE__)."/../viewer/panoramas/multires/$panorama_name/","viewer/panoramas/multires/$panorama_name/");
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT id,content,embed_type,embed_content FROM svt_pois WHERE id_room IN ($id_rooms);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id_poi = $row['id'];
                array_push($array_id_pois,$id_poi);
                $content = $row['content'];
                $embed_type = $row['embed_type'];
                $embed_content = $row['embed_content'];
                if (strpos($content, 'content/') === 0) {
                    $content_file = basename($content);
                    upload_file($aws_s3_bucket,$s3Client,$content_file,'content');
                }
                if (strpos($content, 'media/') === 0) {
                    $content_file = basename($content);
                    upload_file($aws_s3_bucket,$s3Client,$content_file,'media');
                }
                if(strpos($content,'pointclouds/') === 0) {
                    $path_pc = dirname($content);
                    if(file_exists(dirname(__FILE__)."/../viewer/$path_pc/")) {
                        upload_dir($aws_s3_bucket,$s3Client,dirname(__FILE__)."/../viewer/$path_pc/","viewer/$path_pc/");
                    }
                }
                switch($embed_type) {
                    case 'image':
                    case 'video':
                    case 'video_chroma':
                    case 'object3d':
                        if (strpos($embed_content, 'content') === 0) {
                            $content_file = basename($embed_content);
                            upload_file($aws_s3_bucket,$s3Client,$content_file,'content');
                        }
                        if (strpos($embed_content, 'media') === 0) {
                            $content_file = basename($embed_content);
                            upload_file($aws_s3_bucket,$s3Client,$content_file,'media');
                        }
                        break;
                    case 'video_transparent':
                        if (strpos($embed_content, ',') !== false) {
                            $array_contents = explode(",",$embed_content);
                            foreach ($array_contents as $content) {
                                if (strpos($content, 'content') === 0) {
                                    $content_file = basename($content);
                                    upload_file($aws_s3_bucket,$s3Client,$content_file,'content');
                                }
                                if (strpos($content, 'media') === 0) {
                                    $content_file = basename($content);
                                    upload_file($aws_s3_bucket,$s3Client,$content_file,'media');
                                }
                            }
                        } else {
                            if (strpos($embed_content, 'content') === 0) {
                                $content_file = basename($embed_content);
                                upload_file($aws_s3_bucket,$s3Client,$content_file,'content');
                            }
                            if (strpos($embed_content, 'media') === 0) {
                                $content_file = basename($embed_content);
                                upload_file($aws_s3_bucket,$s3Client,$content_file,'media');
                            }
                        }
                        break;
                }
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT image FROM svt_icons WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            upload_file($aws_s3_bucket,$s3Client,$image,'icons');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT file FROM svt_media_library WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            upload_file($aws_s3_bucket,$s3Client,$file,'media');
            upload_file($aws_s3_bucket,$s3Client,$file,'media/thumb');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT file FROM svt_music_library WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            upload_file($aws_s3_bucket,$s3Client,$file,'content');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$query = "SELECT file FROM svt_sound_library WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $file = $row['file'];
            upload_file($aws_s3_bucket,$s3Client,$file,'content');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
$id_pois = implode(",",$array_id_pois);
if(!empty($id_pois)) {
    $query = "SELECT image FROM svt_poi_embedded_gallery WHERE id_poi IN ($id_pois);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                upload_file($aws_s3_bucket,$s3Client,$image,'gallery');
                upload_file($aws_s3_bucket,$s3Client,$image,'gallery/thumb');
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT image FROM svt_poi_gallery WHERE id_poi IN ($id_pois);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                upload_file($aws_s3_bucket,$s3Client,$image,'gallery');
                upload_file($aws_s3_bucket,$s3Client,$image,'gallery/thumb');
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
    $query = "SELECT image FROM svt_poi_objects360 WHERE id_poi IN ($id_pois);";
    $result = $mysqli->query($query);
    if($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $image = $row['image'];
                upload_file($aws_s3_bucket,$s3Client,$image,'objects360');
            }
        }
    }
    $mysqli->close();
    $mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    if (mysqli_connect_errno()) {
        echo mysqli_connect_error();
        exit();
    }
    $mysqli->query("SET NAMES 'utf8';");
}
$query = "SELECT image FROM svt_product_images WHERE id_product IN (SELECT id FROM svt_products WHERE id_virtualtour=$id_vt);";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $image = $row['image'];
            upload_file($aws_s3_bucket,$s3Client,$image,'products');
            upload_file($aws_s3_bucket,$s3Client,$image,'products/thumb');
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
if(file_exists(dirname(__FILE__)."/../video360/$id_vt/")) {
    upload_dir($aws_s3_bucket,$s3Client,dirname(__FILE__)."/../video360/$id_vt/","video360/$id_vt/");
}
$query = "SELECT id FROM svt_video_projects WHERE id_virtualtour=$id_vt;";
$result = $mysqli->query($query);
if($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $id_video_project = $row['id'];
            if(file_exists(dirname(__FILE__)."/../video/$id_vt"."_".$id_video_project.".mp4")) {
                $uploadPromises[] = upload_file_promise($aws_s3_bucket, $s3Client, dirname(__FILE__)."/../video/$id_vt"."_".$id_video_project.".mp4", "video/".$id_vt."_".$id_video_project.".mp4");
            }
        }
    }
}
$mysqli->close();
$mysqli = new mysqli(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
if (mysqli_connect_errno()) {
    echo mysqli_connect_error();
    exit();
}
$mysqli->query("SET NAMES 'utf8';");
if(file_exists(dirname(__FILE__)."/../video/assets/$id_vt/")) {
    upload_dir($aws_s3_bucket,$s3Client,dirname(__FILE__)."/../video/assets/$id_vt/","video/assets/$id_vt/");
}

$pool = new CommandPool($s3Client, $uploadPromises, [
    'concurrency' => 50,
    'before' => function (CommandInterface $cmd, $iterKey) {
        gc_collect_cycles();
    },
    'fulfilled' => function (ResultInterface $result, $iterKey, PromiseInterface $aggregatePromise) {
        global $debug;
        if($debug) {
            file_put_contents('log_move_to_s3.txt',"Completed {$iterKey}: {$result}".PHP_EOL,FILE_APPEND);
        }
    },
    'rejected' => function (AwsException $reason, $iterKey, PromiseInterface $aggregatePromise) {
        global $debug;
        if($debug) {
            file_put_contents('log_move_to_s3.txt',"Failed {$iterKey}: {$reason}".PHP_EOL,FILE_APPEND);
        }
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>"upload error: ".$reason));
        exit;
    },
]);

$promise = $pool->promise();
$promise->wait();
$promise->then(function() {
    global $mysqli,$id_vt;
    $mysqli->query("UPDATE svt_virtualtours SET aws_s3=1 WHERE id=$id_vt;");
    require_once("clean_images.php");
    ob_end_clean();
    echo json_encode(array("status"=>"ok"));
    exit;
});

function upload_file($s3_bucket_name,$s3Client,$file,$dir) {
    global $array_file_uploaded,$uploadPromises;
    if(!empty($file)) {
        if(!in_array($dir.'/'.$file,$array_file_uploaded)) {
            $source = dirname(__FILE__)."/../viewer/$dir/$file";
            if(file_exists($source)) {
                $uploadPromises[] = upload_file_promise($s3_bucket_name, $s3Client, $source, 'viewer/'.$dir.'/'.$file);
                array_push($array_file_uploaded,$dir.'/'.$file);
            }
        }
    }
}

function upload_dir($s3_bucket_name,$s3Client,$source,$dest) {
    global $uploadPromises;
    if(!empty($source)) {
        if(file_exists($source)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                $filePath = $file->getPathname();
                if ($file->isDir()) {
                    continue;
                }
                $dest_file = $dest.$iterator->getSubPath().'/'.$file->getFileName();
                $uploadPromises[] = upload_file_promise($s3_bucket_name, $s3Client, $filePath, $dest_file);
            }
        }
    }
}

function upload_file_promise($bucket, $s3Client, $filePath, $destPath) {
    global $aws_s3_type,$debug;
    $destPath = str_replace("//","/",$destPath);
    if($debug) {
        file_put_contents('log_move_to_s3.txt',$filePath." -> ".$destPath.PHP_EOL,FILE_APPEND);
    }
    switch($aws_s3_type) {
        case 'digitalocean':
            $promise = $s3Client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key'    => $destPath,
                'SourceFile'   => $filePath,
                'ACL' => 'public-read',
            ]);
            break;
        default:
            $promise = $s3Client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key'    => $destPath,
                'SourceFile'   => $filePath
            ]);
            break;
    }
    return $promise;
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                    rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                else
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
            }
        }
        rmdir($dir);
    }
}
