<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/vendor/amazon-aws-sdk/aws-autoloader.php");
require_once(__DIR__."/../backend/functions.php");

$s3_enabled = false;
$query = "SELECT aws_s3_type,aws_s3_enabled,aws_s3_bucket,aws_s3_key,aws_s3_region,aws_s3_secret,aws_s3_accountid FROM svt_settings;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        switch($row['aws_s3_type']) {
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
                            $s3_enabled = true;
                        } catch (Aws\Exception\S3Exception $e) {}
                    }
                }
                break;
        }
    }
}
if($s3_enabled) {
    $objects = $s3Client->listObjects([
        'Bucket' => $s3_bucket_name,
    ])->get('Contents');
    $privateObjects = array_filter($objects, function ($object) use ($s3Client, $s3_bucket_name) {
        $acl = $s3Client->getObjectAcl([
            'Bucket' => $s3_bucket_name,
            'Key' => $object['Key'],
        ])->get('Grants');
        foreach ($acl as $grant) {
            if ($grant['Grantee']['Type'] === 'Group' && $grant['Grantee']['URI'] === 'http://acs.amazonaws.com/groups/global/AllUsers') {
                return false;
            }
        }
        return true;
    });
    foreach ($privateObjects as $object) {
        $s3Client->putObjectAcl([
            'Bucket' => $s3_bucket_name,
            'Key' => $object['Key'],
            'ACL' => 'public-read',
        ]);
    }
}

ob_end_clean();