<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once(__DIR__."/../backend/functions.php");
require_once(__DIR__."/../db/connection.php");
require(__DIR__."/../backend/vendor/amazon-aws-sdk/aws-autoloader.php");
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\Exception\S3Exception;

if(get_user_role($_SESSION['id_user'])!='administrator') {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
    die();
}

if(isset($_POST['aws_s3_secret'])) {
    $aws_s3_type = $_POST['aws_s3_type'];
    $aws_s3_vt_auto = $_POST['aws_s3_vt_auto'];
    $aws_s3_secret = $_POST['aws_s3_secret'];
    $aws_s3_key = $_POST['aws_s3_key'];
    $aws_s3_accountid = $_POST['aws_s3_accountid'];
    $aws_s3_custom_domain = $_POST['aws_s3_custom_domain'];
    $aws_s3_region = $_POST['aws_s3_region'];
    $aws_s3_bucket = $_POST['aws_s3_bucket'];
    $query = "UPDATE svt_settings SET aws_s3_region=?,aws_s3_bucket=?,aws_s3_vt_auto=?,aws_s3_type=?,aws_s3_accountid=?,aws_s3_custom_domain=?;";
    if($smt = $mysqli->prepare($query)) {
        $smt->bind_param('ssisss',$aws_s3_region,$aws_s3_bucket,$aws_s3_vt_auto,$aws_s3_type,$aws_s3_accountid,$aws_s3_custom_domain);
        $smt->execute();
    }
    if($aws_s3_secret!="keep_aws_s3_secret") {
        $query = "UPDATE svt_settings SET aws_s3_secret=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$aws_s3_secret);
            $smt->execute();
        }
    }
    if($aws_s3_key!="keep_aws_s3_key") {
        $query = "UPDATE svt_settings SET aws_s3_key=?;";
        if($smt = $mysqli->prepare($query)) {
            $smt->bind_param('s',$aws_s3_key);
            $smt->execute();
        }
    }
}

$settings = get_settings();
$aws_s3_type = $settings['aws_s3_type'];
$aws_s3_secret = $settings['aws_s3_secret'];
$aws_s3_key = $settings['aws_s3_key'];
$aws_s3_accountid = $settings['aws_s3_accountid'];
$aws_s3_region = $settings['aws_s3_region'];
$aws_s3_bucket = $settings['aws_s3_bucket'];

switch($aws_s3_type) {
    case 'aws':
        $s3Config = [
            'region' => $aws_s3_region,
            'version' => 'latest',
            'credentials' => [
                'key'    => $aws_s3_key,
                'secret' => $aws_s3_secret
            ]
        ];
        break;
    case 'r2':
        if(empty($aws_s3_accountid)) {
            $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>"Missing account id"));
            die();
        }
        $credentials = new Aws\Credentials\Credentials($aws_s3_key, $aws_s3_secret);
        $s3Config = [
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => "https://".$aws_s3_accountid.".r2.cloudflarestorage.com",
            'credentials' => $credentials
        ];
        break;
    case 'digitalocean':
        $s3Config = [
            'region' => 'us-east-1',
            'version' => 'latest',
            'endpoint' => "https://$aws_s3_region.digitaloceanspaces.com",
            'use_path_style_endpoint' => false,
            'credentials' => [
                'key'    => $aws_s3_key,
                'secret' => $aws_s3_secret
            ]
        ];
        break;
}

$s3Client = new S3Client($s3Config);

if(!$s3Client->doesBucketExist($aws_s3_bucket)) {
    try {
        $result = $s3Client->createBucket([
            'Bucket' => $aws_s3_bucket,
        ]);
    } catch (AwsException $e) {
        $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
        die();
    }
} else {
    if(!doesFolderExists($aws_s3_bucket,$s3Client,'viewer/panoramas/')) {
        $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
        ob_end_clean();
        echo json_encode(array("status"=>"error","msg"=>_("Bucket already exists, choose a new name")));
        die();
    }
}

switch($aws_s3_type) {
    case 'aws':
        try {
            $s3_acl = $s3Client->getBucketAcl([
                'Bucket' => $aws_s3_bucket
            ]);
        } catch (AwsException $e) {
            $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            die();
        }
        try {
            $result = $s3Client->deletePublicAccessBlock([
                'Bucket' => $aws_s3_bucket,
            ]);
        } catch (AwsException $e) {
            $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            die();
        }
        switch($aws_s3_region) {
            case 'us-gov-east-1':
            case 'us-gov-west-1':
                $bucketPolicy = '{
                        "Version": "2012-10-17",
                        "Statement": [
                            {
                                "Sid": "AllowPublicRead",
                                "Effect": "Allow",
                                "Principal": "*",
                                "Action": [
                                    "s3:GetObject"
                                ],
                                "Resource": [
                                    "arn:aws-us-gov:s3:::' . $aws_s3_bucket . '/*"
                                ]
                            }
                        ]
                    }';
                break;
            case 'cn-north-1':
            case 'cn-northwest-1':
                $bucketPolicy = '{
                        "Version": "2012-10-17",
                        "Statement": [
                            {
                                "Sid": "AllowPublicRead",
                                "Effect": "Allow",
                                "Principal": "*",
                                "Action": [
                                    "s3:GetObject"
                                ],
                                "Resource": [
                                    "arn:aws-cn:s3:::' . $aws_s3_bucket . '/*"
                                ]
                            }
                        ]
                    }';
                break;
            default:
                $bucketPolicy = '{
                    "Version": "2012-10-17",
                    "Statement": [
                        {
                            "Sid": "AllowPublicRead",
                            "Effect": "Allow",
                            "Principal": "*",
                            "Action": [
                                "s3:GetObject"
                            ],
                            "Resource": [
                                "arn:aws:s3:::' . $aws_s3_bucket . '/*"
                            ]
                        }
                    ]
                }';
                break;
        }
        try {
            $s3Client->putBucketPolicy([
                'Bucket' => $aws_s3_bucket,
                'Policy' => $bucketPolicy,
            ]);
        } catch (AwsException $e) {
            $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            die();
        }
        try {
            $result = $s3Client->putBucketCors([
                'Bucket' => $aws_s3_bucket,
                'CORSConfiguration' => [
                    'CORSRules' => [
                        [
                            'AllowedHeaders' => ['*'],
                            'AllowedMethods' => ['GET','HEAD'],
                            'AllowedOrigins' => ['*'],
                            'ExposeHeaders' => ['Access-Control-Allow-Origin']
                        ],
                    ],
                ]
            ]);
        } catch (AwsException $e) {
            $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            die();
        }
        break;
    case 'r2':
    case 'digitalocean':
        try {
            $result = $s3Client->putBucketCors([
                'Bucket' => $aws_s3_bucket,
                'CORSConfiguration' => [
                    'CORSRules' => [
                        [
                            'AllowedHeaders' => ['*'],
                            'AllowedMethods' => ['GET','HEAD'],
                            'AllowedOrigins' => ['*'],
                            'ExposeHeaders' => ['Access-Control-Allow-Origin']
                        ],
                    ],
                ]
            ]);
        } catch (AwsException $e) {
            $mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=0;");
            ob_end_clean();
            echo json_encode(array("status"=>"error","msg"=>$e->getMessage()));
            die();
        }
        break;
}

check_directory_s3($s3Client,$aws_s3_bucket,"viewer/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/content/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/content/thumb/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/gallery/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/gallery/thumb/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/icons/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/media/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/media/thumb/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/maps/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/videos/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/lowres/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/mobile/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/multires/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/original/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/preview/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/thumb/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/panoramas/thumb_custom/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/objects360/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/pointclouds/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/products/");
check_directory_s3($s3Client,$aws_s3_bucket,"viewer/products/thumb/");
check_directory_s3($s3Client,$aws_s3_bucket,"video360/");
check_directory_s3($s3Client,$aws_s3_bucket,"video/");
check_directory_s3($s3Client,$aws_s3_bucket,"video/assets/");

$mysqli->query("UPDATE svt_settings SET `aws_s3_enabled`=1;");
ob_end_clean();
echo json_encode(array("status"=>"ok"));

function doesFolderExists($s3_bucket_name,$s3Client,$folder) {
    $result = $s3Client->listObjects([
        'Bucket' => $s3_bucket_name,
        'Prefix' => $folder,
    ]);
    if (isset($result['Contents'])) {
        return true;
    } else {
        return false;
    }
}