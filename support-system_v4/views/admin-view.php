<?php
if (strtolower($_SESSION['user']['role']) !== 'admin') {
    die("Access Denied");
}

/*
|--------------------------------------------------------------------------
| FILTER LOGIC
|--------------------------------------------------------------------------
*/
$where = " WHERE 1 ";
$allowedStatus = ['open','assigned','in progress','waiting','resolved','closed'];

/* STATUS FILTER */
if (!empty($_GET['status']) && in_array($_GET['status'], $allowedStatus)) {
    $where .= " AND LOWER(t.status)='{$_GET['status']}'";
}

/* EMERGENCY FILTER */
if (!empty($_GET['priority']) && $_GET['priority']==='critical') {
    $where .= " AND t.priority='Critical' AND LOWER(t.status)!='closed'";
}

/*
|--------------------------------------------------------------------------
| STATS (NO FILTER)
|--------------------------------------------------------------------------
*/
function cnt($c,$w=''){
    return mysqli_fetch_row(mysqli_query($c,"SELECT COUNT(*) FROM tickets $w"))[0];
}

$total     = cnt($conn);
$open      = cnt($conn,"WHERE LOWER(status)='open'");
$assigned  = cnt($conn,"WHERE LOWER(status)='assigned'");
$progress  = cnt($conn,"WHERE LOWER(status)='in progress'");
$resolved  = cnt($conn,"WHERE LOWER(status)='resolved'");
$emergency = cnt($conn,"WHERE priority='Critical' AND LOWER(status) NOT IN ('resolved','closed')");

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/
$users = mysqli_query($conn,"SELECT id,name,role FROM users WHERE role IN ('it','support')");

/*
|--------------------------------------------------------------------------
| TICKETS
|--------------------------------------------------------------------------
*/
$tickets = mysqli_query($conn,"
SELECT t.*,u.name assigned_name,u.role assigned_role
FROM tickets t
LEFT JOIN ticket_assign ta ON t.id=ta.ticket_id
LEFT JOIN users u ON u.id=ta.it_user_id
$where
ORDER BY t.id DESC
");
?>

<style>
body{background:#f5f7fb}
.card{border-radius:12px}
.stat-card{cursor:pointer;transition:.2s}
.stat-card:hover{transform:translateY(-4px)}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center}
.modal img{max-width:90%;max-height:90%}
</style>

<!-- ================= STATS ================= -->
<div class="row text-center mb-4">
<?php
$cards=[
 ['Total',$total,'dark',''],
 ['Open',$open,'success','open'],
 ['Assigned',$assigned,'warning','assigned'],
 ['In Progress',$progress,'info','in progress'],
 ['Resolved',$resolved,'secondary','resolved'],
 ['Emergency',$emergency,'danger','critical']
];
foreach($cards as $c){
 echo "
 <div class='col'>
  <div class='card p-3 stat-card text-white bg-{$c[2]}' data-key='{$c[3]}'>
   {$c[0]}<br><b>{$c[1]}</b>
  </div>
 </div>";
}
?>
</div>

<!-- ================= LIVE SEARCH ================= -->
<div class="row g-2 mb-3">
 <div class="col-md-3">
  <input id="searchTicket" class="form-control" placeholder="Search Ticket No">
 </div>
 <div class="col-md-4">
  <input id="searchTitle" class="form-control" placeholder="Search Title">
 </div>
</div>

<!-- ================= TABLE ================= -->
<div class="card p-3 shadow-sm">
<table class="table table-bordered align-middle" id="ticketTable">
<tr class="table-light">
<th>Ticket</th>
<th>Title</th>
<th>Priority</th>
<th>Attachments</th>
<th>Assigned</th>
<th>Status</th>
<th>Update Status</th>
<th>Update Priority</th>
<th>Assign</th>
</tr>

<?php while($r=mysqli_fetch_assoc($tickets)){ ?>
<tr>
<td><?= htmlspecialchars($r['ticket_no']) ?></td>
<td><?= htmlspecialchars($r['title']) ?></td>

<td>
<span class="badge bg-<?= $r['priority']=='Critical'?'danger':($r['priority']=='High'?'warning':'primary') ?>">
<?= strtoupper($r['priority']) ?>
</span>
</td>

<td>
<?php
if($r['image']){
 foreach(explode(",",$r['image']) as $f){
  $f=htmlspecialchars($f);
  echo "<img src='uploads/$f' width='40'
   style='cursor:pointer;border-radius:4px'
   onclick=\"showImage('uploads/$f')\">";
 }
}else echo "—";
?>
</td>

<td>
<?php
if($r['assigned_name']){
 echo "<b>{$r['assigned_name']}</b><br><small>(".strtoupper($r['assigned_role']).")</small>";
}elseif($r['status']=='Assigned'){
 echo "<span class='badge bg-secondary'>".strtoupper($r['department'])." Team</span>";
}else{
 echo "<span class='text-danger'>Not Assigned</span>";
}
?>
</td>

<td><span class="badge bg-info"><?= $r['status'] ?></span></td>

<!-- UPDATE STATUS -->
<td>
<form method="post" action="update_status.php">
<input type="hidden" name="ticket_id" value="<?= $r['id'] ?>">
<select name="status" class="form-control form-control-sm">
<option>Open</option>
<option>Assigned</option>
<option>In Progress</option>
<option>Waiting</option>
<option>Resolved</option>
<option>Closed</option>
</select>
<button class="btn btn-sm btn-primary mt-1 w-100">Update</button>
</form>
</td>

<!-- UPDATE PRIORITY -->
<td>
<form method="post" action="set_critical.php">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<button class="btn btn-sm btn-danger w-100">Make Critical</button>
</form>
</td>

<!-- ASSIGN -->
<td>
<form method="post" action="assign_ticket.php">
<input type="hidden" name="ticket_id" value="<?= $r['id'] ?>">
<select name="assign_user" class="form-control form-control-sm">
<option value="">Select Team</option>
<?php mysqli_data_seek($users,0);
while($u=mysqli_fetch_assoc($users)){
 echo "<option value='{$u['id']}'>{$u['name']} ({$u['role']})</option>";
} ?>
</select>
<button class="btn btn-sm btn-dark mt-1 w-100">Assign</button>
</form>
</td>
</tr>
<?php } ?>
</table>
</div>

<!-- IMAGE MODAL -->
<div id="imgModal" class="modal" onclick="this.style.display='none'">
 <img id="modalImg">
</div>

<script>
function showImage(src){
 document.getElementById('modalImg').src = src+'?'+Date.now();
 document.getElementById('imgModal').style.display='flex';
}

/* LIVE SEARCH */
['searchTicket','searchTitle'].forEach(id=>{
 document.getElementById(id).addEventListener('keyup',()=>{
  let tn=searchTicket.value.toLowerCase(),
      tt=searchTitle.value.toLowerCase();
  document.querySelectorAll('#ticketTable tr').forEach((r,i)=>{
   if(i==0) return;
   r.style.display =
    r.cells[0].innerText.toLowerCase().includes(tn) &&
    r.cells[1].innerText.toLowerCase().includes(tt)
    ? '' : 'none';
  });
 });
});

/* STAT CLICK */
document.querySelectorAll('.stat-card').forEach(c=>{
 c.onclick=()=>{
  let k=c.dataset.key;
  if(k==='critical') location.href='dashboard.php?priority=critical';
  else if(k) location.href='dashboard.php?status='+k;
  else location.href='dashboard.php';
 };
});
</script>
