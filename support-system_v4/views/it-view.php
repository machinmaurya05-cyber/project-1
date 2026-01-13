<?php
if (strtolower($_SESSION['user']['role']) !== 'it') {
    die("Access Denied");
}

$uid = intval($_SESSION['user']['id']);
$allowedStatus = ['open','assigned','in progress','waiting','resolved','closed'];

/*
|------------------------------------------------------------------
| BASE CONDITION
|------------------------------------------------------------------
*/
$where = " WHERE t.department='it' ";

/* DEFAULT → CLOSED HIDE */
if (empty($_GET['status'])) {
    $where .= " AND LOWER(t.status) != 'closed' ";
}

/* STATUS FILTER */
if (!empty($_GET['status']) && in_array(strtolower($_GET['status']), $allowedStatus)) {
    $s = mysqli_real_escape_string($conn,$_GET['status']);
    $where .= " AND LOWER(t.status)='$s'";
}

/* SEARCH */
if (!empty($_GET['ticket_no'])) {
    $t = mysqli_real_escape_string($conn,$_GET['ticket_no']);
    $where .= " AND t.ticket_no LIKE '%$t%'";
}
if (!empty($_GET['title'])) {
    $t = mysqli_real_escape_string($conn,$_GET['title']);
    $where .= " AND t.title LIKE '%$t%'";
}
if (!empty($_GET['date'])) {
    $d = mysqli_real_escape_string($conn,$_GET['date']);
    $where .= " AND DATE(t.created_at)='$d'";
}

/*
|------------------------------------------------------------------
| EXCEL EXPORT
|------------------------------------------------------------------
*/
if (isset($_GET['export']) && $_GET['export']=='excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=it_tickets.xls");

    $exp = mysqli_query($conn,"
        SELECT t.ticket_no,t.title,t.priority,t.status,t.created_at
        FROM tickets t
        LEFT JOIN ticket_assign ta ON t.id=ta.ticket_id
        $where AND (ta.it_user_id='$uid' OR ta.it_user_id IS NULL)
    ");

    echo "Ticket No\tTitle\tPriority\tStatus\tCreated At\n";
    while($r=mysqli_fetch_assoc($exp)){
        echo "{$r['ticket_no']}\t{$r['title']}\t{$r['priority']}\t{$r['status']}\t{$r['created_at']}\n";
    }
    exit;
}

/*
|------------------------------------------------------------------
| TICKETS QUERY
|------------------------------------------------------------------
*/
$tickets = mysqli_query($conn,"
SELECT DISTINCT t.*
FROM tickets t
LEFT JOIN ticket_assign ta ON t.id=ta.ticket_id
$where
AND (ta.it_user_id='$uid' OR ta.it_user_id IS NULL)
ORDER BY
CASE
    WHEN t.priority='Critical' THEN 1
    WHEN t.priority='High' THEN 2
    ELSE 3
END,
t.id DESC
");
?>

<style>
body{background:#f5f7fb}
.card{border-radius:12px}
.modal{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.85);
    z-index:9999;
    align-items:center;justify-content:center
}
.modal img{max-width:90%;max-height:90%}
</style>

<h4 class="mb-3">IT Dashboard</h4>

<!-- SEARCH -->
<form method="get" class="row g-2 mb-3">
<div class="col-md-3">
<input class="form-control" name="ticket_no" placeholder="Ticket No">
</div>
<div class="col-md-4">
<input class="form-control" name="title" placeholder="Title">
</div>
<div class="col-md-3">
<input type="date" class="form-control" name="date">
</div>
<div class="col-md-2 d-grid">
<button class="btn btn-primary">Search</button>
</div>
</form>

<a href="?<?= $_SERVER['QUERY_STRING'] ?>&export=excel"
class="btn btn-success btn-sm mb-3">📊 Export Excel</a>

<!-- TABLE -->
<div class="card p-3 shadow-sm">
<table class="table table-bordered align-middle">
<tr>
<th>Ticket</th>
<th>Title</th>
<th>Priority</th>
<th>Attachments</th>
<th>Status</th>
<th>Update</th>
</tr>

<?php while($r=mysqli_fetch_assoc($tickets)){ ?>
<tr class="<?= $r['priority']=='Critical'?'table-danger':'' ?>">

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
  $ext=strtolower(pathinfo($f,PATHINFO_EXTENSION));
  if(in_array($ext,['jpg','jpeg','png','gif','webp'])){
   echo "<img src='../uploads/$f' width='40'
    style='cursor:pointer;border-radius:4px'
    onclick=\"showImage('../uploads/$f')\">";
  }elseif(in_array($ext,['mp4','webm','mov'])){
   echo "<video width='80' controls>
    <source src='../uploads/$f'></video>";
  }
 }
}else echo "—";
?>
</td>

<td><span class="badge bg-info"><?= $r['status'] ?></span></td>

<td>
<form method="post" action="update_status.php">
<input type="hidden" name="ticket_id" value="<?= $r['id'] ?>">
<select name="status" class="form-control form-control-sm">
<option>Assigned</option>
<option>In Progress</option>
<option>Waiting</option>
<option>Resolved</option>
<option>Closed</option>
</select>
<button class="btn btn-sm btn-primary mt-1">Update</button>
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
</script>
