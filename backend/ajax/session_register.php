<?php
session_start();
$_SESSION['username_reg']=$_SESSION['username_log'];
$_SESSION['email_reg']=$_SESSION['email_log'];
$_SESSION['password_reg']=$_SESSION['password_log'];
unset($_SESSION['username_log']);
unset($_SESSION['email_log']);
unset($_SESSION['password_log']);