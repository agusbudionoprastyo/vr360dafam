<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();
$_SESSION['theme_color'] = $_POST['theme_color'];
$_SESSION['sidebar_color_1'] = $_POST['sidebar_color_1'];
$_SESSION['sidebar_color_2'] = $_POST['sidebar_color_2'];
$_SESSION['theme_color_dark'] = $_POST['theme_color_dark'];
$_SESSION['sidebar_color_1_dark'] = $_POST['sidebar_color_1_dark'];
$_SESSION['sidebar_color_2_dark'] = $_POST['sidebar_color_2_dark'];
session_write_close();
