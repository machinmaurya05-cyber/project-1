<?php
include "includes/auth.php";
include "includes/db.php";

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    die("Access Denied");
}

/*
|--------------------------------------------------------------------------
| VALIDATE REQUEST
|--------------------------------------------------------------------------
*/
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Invalid Ticket");
}

/*
|--------------------------------------------------------------------------
| CHECK TICKET EXISTS
|--------------------------------------------------------------------------
*/
$q = mysqli_query($conn,"
    SELECT id, priority, status 
    FROM tickets 
    WHERE id='$id'
");

if (mysqli_num_rows($q) !== 1) {
    die("Ticket Not Found");
}

$ticket = mysqli_fetch_assoc($q);

/*
|--------------------------------------------------------------------------
| BUSINESS RULES
|--------------------------------------------------------------------------
*/
if ($ticket['priority'] === 'Critical') {
    header("Location: dashboard.php");
    exit;
}

if (in_array(strtolower($ticket['status']), ['closed','resolved'])) {
    die("Cannot mark closed/resolved ticket as critical");
}

/*
|--------------------------------------------------------------------------
| UPDATE PRIORITY
|--------------------------------------------------------------------------
*/
mysqli_query($conn,"
    UPDATE tickets 
    SET priority='Critical'
    WHERE id='$id'
");

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/
header("Location: dashboard.php");
exit;
