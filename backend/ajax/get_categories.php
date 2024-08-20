<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
if($_SESSION['svt_si']!=session_id()) {
    die();
}
ob_start();
require_once("../../db/connection.php");
require_once("../functions.php");
session_write_close();
$html = "";
foreach (get_categories() as $category) {
    $html .= "<tr id='ca_tr_".$category['id']."'>";
    $html .= "<td>".$category['id']."</td>";
    $html .= "<td><input id='cat_".$category['id']."' type='text' class='form-control' value='".$category['name']."'></td>";
    $html .= "<td><button onclick='edit_category(".$category['id'].");' class='btn btn-sm btn-warning'><i class='far fa-edit'></i> "._("save")."</button> <button onclick='modal_delete_category(".$category['id'].");' class='btn btn-sm btn-danger'><i class='far fa-trash-alt'></i> "._("delete")."</button></td>";
    $html .= "</tr>";
}
ob_end_clean();
echo json_encode(array("status"=>"ok","html"=>$html));