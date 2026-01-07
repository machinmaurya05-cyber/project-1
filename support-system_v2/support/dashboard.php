<?php
include "../includes/auth.php";
include "../includes/db.php";

// ---- SAFE ROLE CHECK ----
if (!isset($_SESSION['user'])) {
    die("Access denied - not logged in");
}

$role = strtolower(trim($_SESSION['user']['role']));

if ($role != 'support') {
    die("Access denied - Support panel only");
}

$uid = $_SESSION['user']['id'];

// -------- STATUS UPDATE --------
if (isset($_POST['update_status'])) {
    $tid = $_POST['ticket_id'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE tickets SET status='$status' WHERE id='$tid'");
}


// ================== ADVANCE SEARCH FILTER SYSTEM ==================

$conditions = " ticket_assign.it_user_id='$uid' AND tickets.status!='Closed' ";

// Ticket Number
if (!empty($_GET['ticket'])) {
    $ticket = $_GET['ticket'];
    $conditions .= " AND tickets.ticket_no LIKE '%$ticket%' ";
}

// Title Search
if (!empty($_GET['title'])) {
    $title = $_GET['title'];
    $conditions .= " AND tickets.title LIKE '%$title%' ";
}

// Status Filter
if (!empty($_GET['status'])) {
    $status = $_GET['status'];
    $conditions .= " AND tickets.status='$status' ";
}

// Priority Filter
if (!empty($_GET['priority'])) {
    $priority = $_GET['priority'];
    $conditions .= " AND tickets.priority='$priority' ";
}

// Date Range Filter
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $from = $_GET['from'];
    $to = $_GET['to'];
    $conditions .= " AND DATE(tickets.created_at) BETWEEN '$from' AND '$to' ";
}

// Final Query
$q = mysqli_query($conn,"
SELECT tickets.* FROM tickets
JOIN ticket_assign 
ON tickets.id = ticket_assign.ticket_id
WHERE $conditions
ORDER BY tickets.id DESC
");

?>

<!DOCTYPE html>
<html>

<head>
<title>Support Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../theme.css" rel="stylesheet">

</head>

<body>

<nav class="navbar navbar-dark px-3">
<span class="text-white fw-bold">Support Panel</span>
<a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
</nav>

<div class="container mt-4 main-area">

<h3 class="text-dark">Assigned Tickets</h3>

<!-- ================= FILTER UI ================= -->
<div class="card p-3 mb-3">
<form method="get" class="row g-2">

<div class="col-md-3">
<input type="text" name="ticket" class="form-control" placeholder="Ticket No (RP-123456)"
value="<?php echo $_GET['ticket'] ?? '' ?>">
</div>

<div class="col-md-3">
<input type="text" name="title" class="form-control" placeholder="Search Title"
value="<?php echo $_GET['title'] ?? '' ?>">
</div>

<div class="col-md-2">
<select name="status" class="form-control">
<option value="">All Status</option>
<option <?php if(($_GET['status']??'')=="Assigned") echo "selected"; ?>>Assigned</option>
<option <?php if(($_GET['status']??'')=="In Progress") echo "selected"; ?>>In Progress</option>
<option <?php if(($_GET['status']??'')=="Waiting") echo "selected"; ?>>Waiting</option>
<option <?php if(($_GET['status']??'')=="Resolved") echo "selected"; ?>>Resolved</option>
<option <?php if(($_GET['status']??'')=="Closed") echo "selected"; ?>>Closed</option>
</select>
</div>

<div class="col-md-2">
<select name="priority" class="form-control">
<option value="">All Priority</option>
<option <?php if(($_GET['priority']??'')=="Normal") echo "selected"; ?>>Normal</option>
<option <?php if(($_GET['priority']??'')=="High") echo "selected"; ?>>High</option>
<option <?php if(($_GET['priority']??'')=="Critical") echo "selected"; ?>>Critical</option>
</select>
</div>

<div class="col-md-2">
<input type="date" name="from" class="form-control"
value="<?php echo $_GET['from'] ?? '' ?>">
</div>

<div class="col-md-2">
<input type="date" name="to" class="form-control"
value="<?php echo $_GET['to'] ?? '' ?>">
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Search</button>
</div>

<div class="col-md-2">
<a href="dashboard.php" class="btn btn-secondary w-100">Reset</a>
</div>

</form>
</div>


<table class="table table-bordered table-striped">
<tr>
<th>Ticket</th>
<th>Title</th>
<th>Status</th>
<th>Image</th>
<th>Action</th>
</tr>

<?php while ($t = mysqli_fetch_assoc($q)) { ?>
<tr>

<td><?php echo $t['ticket_no']; ?></td>

<td><?php echo $t['title']; ?></td>

<td>
<span class="badge bg-primary"><?php echo $t['status']; ?></span>
</td>

<td>
<?php 
if ($t['image']) {

$imgs = explode(",", $t['image']);

foreach ($imgs as $img) {
if ($img != "") {
echo "
<a href='../$img' target='_blank'>
<img src='../$img' width='70' style='margin-right:5px;border-radius:5px;cursor:pointer'>
</a>";
}
}

} else {
echo "No Image";
}
?>
</td>

<td>
<form method="post" style="display:flex;gap:5px">

<input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">

<select name="status" class="form-control">
<option <?php if ($t['status']=="Assigned") echo "selected"; ?>>Assigned</option>
<option <?php if ($t['status']=="In Progress") echo "selected"; ?>>In Progress</option>
<option <?php if ($t['status']=="Waiting") echo "selected"; ?>>Waiting</option>
<option <?php if ($t['status']=="Resolved") echo "selected"; ?>>Resolved</option>
<option <?php if ($t['status']=="Closed") echo "selected"; ?>>Closed</option>
</select>

<button class="btn btn-primary btn-sm" name="update_status">Update</button>

</form>
</td>

</tr>
<?php } ?>

</table>

</div>

</body>
</html>
