<?php
include "includes/auth.php";
include "includes/db.php";

/*
|--------------------------------------------------------------------------
| AUTH & SESSION CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$role = strtolower($_SESSION['user']['role']);
$allowed = ['admin','support','it','customer'];
if (!in_array($role, $allowed)) {
    die("Access Denied");
}

/*
|--------------------------------------------------------------------------
| GLOBAL FILTERS (Future: Search / Export / Timeline)
|--------------------------------------------------------------------------
*/
$statusFilter = '';
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $statusFilter = mysqli_real_escape_string($conn, $_GET['status']);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6fa;
}
.card{
    border-radius:10px;
    box-shadow:0 4px 10px rgba(0,0,0,.1);
}

/* Critical Ticket Highlight */
.critical{
    background:#ffd6d6!important;
    animation: blink 1s infinite;
}
@keyframes blink{
    50%{background:#ffb3b3;}
}

/* Clickable dashboard cards */
.stat-card{
    cursor:pointer;
    transition:.2s;
}
.stat-card:hover{
    transform:scale(1.03);
}
</style>

</head>
<body>

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="text-white fw-bold">
        Support System Dashboard (<?= strtoupper($role); ?>)
    </span>
    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</nav>

<div class="container mt-4">

<?php
/*
|--------------------------------------------------------------------------
| ROLE BASED VIEW LOAD (FINAL)
|--------------------------------------------------------------------------
*/
switch ($role) {
    case 'admin':
        include "views/admin-view.php";
        break;

    case 'support':
        include "views/support-view.php";
        break;

    case 'it':
        include "views/it-view.php";
        break;

    case 'customer':
        include "views/customer-view.php";
        break;

    default:
        die("Invalid Role");
}
?>

</div>

<!-- FEATURE #1 : STATUS CARD CLICK HANDLER -->
<script>
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('click', function () {
        const status = this.getAttribute('data-status');
        if (status) {
            window.location.href = "tickets.php?status=" + status;
        }
    });
});
</script>

</body>
</html>
