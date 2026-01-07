<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// user login check
if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit;
}

// normalize role safely
$_SESSION['user']['role'] = strtolower(trim($_SESSION['user']['role']));
?>
