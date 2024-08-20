<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
if((($_SERVER['SERVER_ADDR']==$_SESSION['demo_server_ip']) && ($_SERVER['REMOTE_ADDR']!=$_SESSION['demo_developer_ip']) && ($_SESSION['id_user']==$_SESSION['demo_user_id'])) || ($_SESSION['svt_si']!=session_id())) {
    die();
}
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$id_product = (int)$_POST['id'];
$name = strip_tags($_POST['name']);
$price = $_POST['price'];
if(empty($price)) $price=0;
if($price<0) $price=0;
$price = (float)$price;
$description = $_POST['description'];
if($description=='<p><br></p>') $description="";
$link = strip_tags($_POST['link']);
$purchase_type = strip_tags($_POST['purchase_type']);
$button_icon = strip_tags($_POST['button_icon']);
$button_text = strip_tags($_POST['button_text']);
$button_background = strip_tags($_POST['button_background']);
$button_color = strip_tags($_POST['button_color']);
if($purchase_type!='cart') {
    $custom_currency = strip_tags($_POST['custom_currency']);
} else {
    $custom_currency = null;
}
$array_lang = json_decode($_POST['array_lang'],true);
$query = "UPDATE svt_products SET name=?,price=?,description=?,link=?,purchase_type=?,button_icon=?,button_text=?,custom_currency=?,button_background=?,button_color=? WHERE id=?;";
if($smt = $mysqli->prepare($query)) {
    $smt->bind_param('sdssssssssi',$name,$price,$description,$link,$purchase_type,$button_icon,$button_text,$custom_currency,$button_background,$button_color,$id_product);
    $result = $smt->execute();
    if ($result) {
        save_input_langs($array_lang,'svt_products_lang','id_product',$id_product);
        ob_end_clean();
        echo json_encode(array("status"=>"ok"));
    } else {
        ob_end_clean();
        echo json_encode(array("status"=>"error"));
    }
} else {
    ob_end_clean();
    echo json_encode(array("status"=>"error"));
}