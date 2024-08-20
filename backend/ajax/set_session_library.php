<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
$w = $_POST['w'];
$_SESSION['library_type'] = $w;
session_write_close();