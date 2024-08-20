<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
$tab = $_POST['tab'];
$page = $_POST['page'];
$_SESSION['tab_'.$page] = $tab;
session_write_close();