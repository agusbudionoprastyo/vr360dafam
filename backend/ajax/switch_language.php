<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
session_start();
$_SESSION['lang']=$_POST['lang'];
session_write_close();