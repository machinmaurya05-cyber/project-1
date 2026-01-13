<?php
include "includes/auth.php";
include "includes/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request");
}

if (!isset($_POST['ticket_id'], $_POST['status'])) {
    die("Missing Data");
}

$ticket_id = intval($_POST['ticket_id']);
$status = ucfirst(strtolower(trim($_POST['status'])));

$role = strtolower($_SESSION['user']['role']);
$uid  = intval($_SESSION['user']['id']);

/*
|--------------------------------------------------------------------------
| ROLE BASED STATUS PERMISSION
|--------------------------------------------------------------------------
*/
$rolePermissions = [
    'admin'   => ['Open','Assigned','In Progress','Waiting','Resolved','Closed'],
    'it'      => ['Assigned','In Progress','Waiting','Resolved'],
    'support' => ['Assigned','Waiting','Resolved']
];

if (!isset($rolePermissions[$role])) {
    die("Access Denied");
}

if (!in_array($status, $rolePermissions[$role])) {
    die("You are not allowed to set this status");
}

/*
|--------------------------------------------------------------------------
| TICKET EXIST CHECK
|--------------------------------------------------------------------------
*/
$tq = mysqli_query($conn,"SELECT id,department FROM tickets WHERE id='$ticket_id'");
if (mysqli_num_rows($tq) === 0) {
    die("Ticket Not Found");
}

/*
|--------------------------------------------------------------------------
| UPDATE STATUS
|--------------------------------------------------------------------------
*/
mysqli_query($conn,"
    UPDATE tickets 
    SET status='".mysqli_real_escape_string($conn,$status)."'
    WHERE id='$ticket_id'
");

/*
|--------------------------------------------------------------------------
| REDIRECT (SMART)
|--------------------------------------------------------------------------
*/
header("Location: dashboard.php");
exit;
