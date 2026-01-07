
<?php
include '../includes/auth.php';
include '../includes/db.php';
if($_SESSION['user']['role']!='admin') die('Access denied');

$id=$_GET['id'];
$ticket=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM tickets WHERE id='$id'"));

if(isset($_POST['assign'])){
 $member=$_POST['member'];
 mysqli_query($conn,"INSERT INTO ticket_assign(ticket_id,it_user_id) VALUES('$id','$member')");
 mysqli_query($conn,"UPDATE tickets SET status='Assigned' WHERE id='$id'");
 $msg='Assigned Successfully';
}

$members=mysqli_query($conn,"SELECT * FROM users WHERE role='it' OR role='support'");
?>
<!DOCTYPE html>
<html>
<head>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<link href='../theme.css' rel='stylesheet'>
</head>
<body>

<nav class='navbar navbar-dark px-3'>
<span class='text-white fw-bold'>Assign Ticket</span>
<a href='../logout.php' class='btn btn-danger btn-sm'>Logout</a>
</nav>

<div class='container mt-4 main-area'>
<h3 class='text-dark'>Assign Ticket</h3>
<div class='card p-3'>
<?php echo $msg??''; ?>

<p>
<b>Ticket:</b> <?php echo $ticket['ticket_no'];?> |
<b><?php echo $ticket['title'];?></b>
</p>

<?php if($ticket['image']) echo "<img src='../{$ticket['image']}' width='120'>"; ?>

<form method='post'>
<select class='form-control mb-2' name='member'>
<?php while($r=mysqli_fetch_assoc($members)){ ?>
<option value='<?php echo $r['id'];?>'>
<?php echo $r['name'];?> (<?php echo strtoupper($r['role']); ?>)
</option>
<?php } ?>
</select>
<button class='btn btn-primary' name='assign'>Assign</button>
</form>

</div>
</div>
</body>
</html>
