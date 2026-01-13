<?php
include "includes/auth.php";
include "includes/db.php";

if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) != 'admin') {
    die("Access Denied");
}

$ticket_id   = intval($_POST['ticket_id'] ?? 0);
$assign_user = intval($_POST['assign_user'] ?? 0);

if ($ticket_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| REMOVE OLD ASSIGNMENT
|--------------------------------------------------------------------------
*/
mysqli_query($conn,"DELETE FROM ticket_assign WHERE ticket_id='$ticket_id'");

/*
|--------------------------------------------------------------------------
| IF USER SELECTED → ASSIGN (NO DEPARTMENT CHECK)
|--------------------------------------------------------------------------
*/
if ($assign_user > 0) {

    $u = mysqli_query($conn,"
        SELECT id, role FROM users 
        WHERE id='$assign_user'
        AND role IN ('it','support')
    ");

    if (mysqli_num_rows($u) === 1) {

        mysqli_query($conn,"
            INSERT INTO ticket_assign(ticket_id,it_user_id)
            VALUES('$ticket_id','$assign_user')
        ");

        // Status auto update
        mysqli_query($conn,"
            UPDATE tickets 
            SET status='Assigned'
            WHERE id='$ticket_id'
        ");
    }
}

header("Location: dashboard.php");
exit;
