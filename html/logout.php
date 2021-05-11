<?php
session_start();

//ログアウト
$_SESSION = array();
session_destroy();
header("location:/login.php"); 
exit;
?>