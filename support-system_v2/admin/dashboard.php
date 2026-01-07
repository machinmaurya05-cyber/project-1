<?php
include '../includes/auth.php';
include '../includes/db.php';

if(!isset($_SESSION['user']) || strtolower($_SESSION['user']['role'])!='admin'){
    die('Access denied');
}


// ---------- ASSIGN ----------
if(isset($_POST['assign'])){
    $ticket = $_POST['ticket_id'];
    $user = $_POST['assign_user'];

    mysqli_query($conn,"DELETE FROM ticket_assign WHERE ticket_id='$ticket'");
    
    if($user != ""){
        mysqli_query($conn,"
        INSERT INTO ticket_assign(ticket_id,it_user_id)
        VALUES('$ticket','$user')
        ");

        mysqli_query($conn,"
            UPDATE tickets SET status='Assigned'
            WHERE id='$ticket'
        ");
    }
}


// ---------- CLOSE ----------
if(isset($_GET['close'])){
    $cid = $_GET['close'];
    mysqli_query($conn,"UPDATE tickets SET status='Closed' WHERE id='$cid'");
}



// ---------- USERS ----------
$users = mysqli_query($conn,"
SELECT id,name,role FROM users 
WHERE role IN ('it','support')
");


// ---------- ADVANCED SEARCH ----------
$conditions = " tickets.status!='Closed' ";

if(!empty($_GET['ticket'])){
    $t = $_GET['ticket'];
    $conditions .= " AND tickets.ticket_no LIKE '%$t%' ";
}

if(!empty($_GET['title'])){
    $ttl = $_GET['title'];
    $conditions .= " AND tickets.title LIKE '%$ttl%' ";
}

if(!empty($_GET['status'])){
    $s = $_GET['status'];
    $conditions .= " AND tickets.status='$s' ";
}

if(!empty($_GET['priority'])){
    $p = $_GET['priority'];
    $conditions .= " AND tickets.priority='$p' ";
}

if(!empty($_GET['assigned'])){
    $a = $_GET['assigned'];
    $conditions .= " AND ticket_assign.it_user_id='$a' ";
}

if(!empty($_GET['from']) && !empty($_GET['to'])){
    $from = $_GET['from'];
    $to = $_GET['to'];
    $conditions .= " AND DATE(tickets.created_at) BETWEEN '$from' AND '$to' ";
}



// ---------- FINAL QUERY ----------
$query = "
SELECT tickets.*, ticket_assign.it_user_id, 
users.name as assigned_name, users.role as assigned_role
FROM tickets
LEFT JOIN ticket_assign ON tickets.id = ticket_assign.ticket_id
LEFT JOIN users ON ticket_assign.it_user_id = users.id
WHERE $conditions
ORDER BY tickets.id DESC
";

$t = mysqli_query($conn,$query);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../theme.css" rel="stylesheet">

<style>
.new-ticket{background:#d4ffd4;}
.assigned{background:#fff6cc;}
.inprogress{background:#cfe2ff;}
.resolved{background:#e6ccff;}
</style>

</head>
<body>

<nav class='navbar navbar-dark px-3'>
<span class='text-white fw-bold'>Admin Panel</span>
<a href='../logout.php' class='btn btn-danger btn-sm'>Logout</a>
</nav>

<div class='container mt-4 main-area'>
<h3 class='text-dark'>All Active Tickets</h3>


<!-- 🔍 ADVANCED SEARCH FORM -->
<form method="get" class="card p-3 mb-3">
<div class="row g-2">

<div class="col-md-2">
<input type="text" name="ticket" value="<?php echo $_GET['ticket'] ?? ''; ?>" 
class="form-control" placeholder="Ticket No">
</div>

<div class="col-md-3">
<input type="text" name="title" value="<?php echo $_GET['title'] ?? ''; ?>" 
class="form-control" placeholder="Search Title">
</div>

<div class="col-md-2">
<select name="status" class="form-control">
<option value="">All Status</option>
<option>Open</option>
<option>Assigned</option>
<option>In Progress</option>
<option>Waiting</option>
<option>Resolved</option>
</select>
</div>

<div class="col-md-2">
<select name="priority" class="form-control">
<option value="">All Priority</option>
<option>Normal</option>
<option>High</option>
<option>Critical</option>
</select>
</div>

<div class="col-md-2">
<input type="date" name="from" class="form-control">
</div>

<div class="col-md-2">
<input type="date" name="to" class="form-control">
</div>

<div class="col-md-2">
<select name="assigned" class="form-control">
<option value="">Assigned User</option>
<?php
$u = mysqli_query($conn,"SELECT id,name FROM users WHERE role IN ('it','support')");
while($ux=mysqli_fetch_assoc($u)){
echo "<option value='{$ux['id']}'>{$ux['name']}</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Search</button>
</div>

<div class="col-md-2">
<a href="dashboard.php" class="btn btn-secondary w-100">Reset</a>
</div>

</div>
</form>



<table class='table table-bordered table-striped'>
<tr>
<th>Ticket</th>
<th>Title</th>
<th>Screenshot</th>
<th>Priority</th>
<th>Assigned To</th>
<th>Status</th>
<th>Assign</th>
<th>Close</th>
</tr>

<?php while($r=mysqli_fetch_assoc($t)){

$rowClass = "";

if($r['status']=="Open") $rowClass="new-ticket";
else if($r['status']=="Assigned") $rowClass="assigned";
else if($r['status']=="In Progress" || $r['status']=="Waiting") $rowClass="inprogress";
else if($r['status']=="Resolved") $rowClass="resolved";
?>

<tr class="<?php echo $rowClass; ?>">

<td><?php echo $r['ticket_no'];?></td>
<td><?php echo $r['title'];?></td>

<td>
<?php
if($r['image']){
$imgs = explode(",", $r['image']);
foreach($imgs as $img){
if($img!=""){
echo "<a href='../$img' target='_blank'>
<img src='../$img' width='70' style='margin-right:5px;border-radius:5px'>
</a>";
}}}
else echo "No Image";
?>
</td>

<td><span class="badge bg-primary"><?php echo $r['priority'];?></span></td>

<td>
<?php 
if($r['assigned_name']){
echo "<b>{$r['assigned_name']}</b> ({$r['assigned_role']})";
}else{
echo "<span class='text-danger'>Not Assigned</span>";
}
?>
</td>

<td>
<span class="badge 
<?php
if($r['status']=="Open") echo "bg-success";
else if($r['status']=="Assigned") echo "bg-warning";
else if($r['status']=="In Progress" || $r['status']=="Waiting") echo "bg-info";
else if($r['status']=="Resolved") echo "bg-secondary";
?>">
<?php echo $r['status'];?>
</span>
</td>

<td>
<form method="post" style="display:flex;gap:5px">
<input type="hidden" name="ticket_id" value="<?php echo $r['id'];?>">

<select name="assign_user" class="form-control">
<option value="">Select User</option>

<?php 
mysqli_data_seek($users,0);
while($u=mysqli_fetch_assoc($users)){
echo "<option value='{$u['id']}'>
{$u['name']} ({$u['role']})
</option>";
}
?>
</select>

<button class="btn btn-dark btn-sm" name="assign">Assign</button>
</form>
</td>

<td>
<a onclick="return confirm('Close ticket?')" 
class='btn btn-danger btn-sm' 
href='dashboard.php?close=<?php echo $r['id'];?>'>
Close
</a>
</td>

</tr>
<?php } ?>

</table>

</div>

</body>
</html>
