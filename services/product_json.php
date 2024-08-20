<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__."/../db/connection.php");
require_once(__DIR__."/../backend/functions.php");
$id_product = $_GET['id'];
$data = array();
$currentPath = $_SERVER['PHP_SELF'];
$pathInfo = pathinfo($currentPath);
$hostName = $_SERVER['HTTP_HOST'];
if (is_ssl()) { $protocol = 'https'; } else { $protocol = 'http'; }
$url = $protocol."://".$hostName.$pathInfo['dirname']."/";
$base_url = str_replace("viewer/","",$url);
$query = "SELECT * FROM svt_products WHERE id=$id_product LIMIT 1;";
$result = $mysqli->query($query);
if($result) {
    if($result->num_rows==1) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $price = $row['price'];
        $data['id'] = $id_product;
        $data['price'] = $price;
        $data['url'] = $url."product_json.php?id=$id_product";
    }
}
ob_end_clean();
echo json_encode($data);
