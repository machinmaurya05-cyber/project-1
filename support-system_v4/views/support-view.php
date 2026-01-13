<?php
if (strtolower($_SESSION['user']['role']) != 'support') {
    die("Access Denied");
}

/*
|--------------------------------------------------------------------------
| FILTERS (STATUS + SEARCH)
|--------------------------------------------------------------------------
*/
$where = " WHERE 1 ";
$allowedStatus = ['open','assigned','in progress','waiting','resolved','closed'];

$where .= " AND t.department='support'";

if (!empty($_GET['status']) && in_array(strtolower($_GET['status']), $allowedStatus)) {
    $s = mysqli_real_escape_string($conn, $_GET['status']);
    $where .= " AND LOWER(t.status)='".strtolower($s)."'";
}

if (!empty($_GET['ticket_no'])) {
    $t = mysqli_real_escape_string($conn, $_GET['ticket_no']);
    $where .= " AND t.ticket_no LIKE '%$t%'";
}

if (!empty($_GET['title'])) {
    $t = mysqli_real_escape_string($conn, $_GET['title']);
    $where .= " AND t.title LIKE '%$t%'";
}

if (!empty($_GET['date'])) {
    $d = mysqli_real_escape_string($conn, $_GET['date']);
    $where .= " AND DATE(t.created_at)='$d'";
}

/*
|--------------------------------------------------------------------------
| EXCEL EXPORT
|--------------------------------------------------------------------------
*/
if (isset($_GET['export']) && $_GET['export']=='excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=support_tickets.xls");

    $exp = mysqli_query($conn,"
        SELECT t.ticket_no,t.title,t.priority,t.status,t.created_at
        FROM tickets t $where
    ");

    echo "Ticket No\tTitle\tPriority\tStatus\tCreated At\n";
    while($r=mysqli_fetch_assoc($exp)){
        echo "{$r['ticket_no']}\t{$r['title']}\t{$r['priority']}\t{$r['status']}\t{$r['created_at']}\n";
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| TICKETS
|--------------------------------------------------------------------------
*/
$tickets = mysqli_query($conn,"
SELECT t.*
FROM tickets t
$where
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
.modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.85);
    z-index:9999;
    align-items:center;
    justify-content:center;
}
.modal-img{
    max-width:90%;
    max-height:90%;
    object-fit:contain;
}
</style>

<h4 class="mb-3">Support Dashboard</h4>

<!-- ================= SEARCH ================= -->
<form method="get" action="dashboard.php" class="row g-2 mb-3">

<?php if(!empty($_GET['status'])){ ?>
<input type="hidden" name="status" value="<?= $_GET['status'] ?>">
<?php } ?>

<div class="col-md-3">
    <input type="text" name="ticket_no" class="form-control" placeholder="Ticket No">
</div>

<div class="col-md-4">
    <input type="text" name="title" class="form-control" placeholder="Ticket Title">
</div>

<div class="col-md-3">
    <input type="date" name="date" class="form-control">
</div>

<div class="col-md-2 d-grid">
    <button class="btn btn-primary">Search</button>
</div>

</form>

<a href="?<?= $_SERVER['QUERY_STRING'] ?>&export=excel"
class="btn btn-success btn-sm mb-3">📊 Export Excel</a>

<!-- ================= TABLE ================= -->
<div class="card p-3 shadow-sm">
<table class="table table-bordered align-middle">
<tr>
<th>Ticket</th>
<th>Title</th>
<th>Priority</th>
<th>Attachments</th>
<th>Status</th>
<th>Update Status</th>
<th>Assigned</th>
</tr>

<?php while($r = mysqli_fetch_assoc($tickets)){ ?>
<tr class="<?= ($r['priority']=='Critical')?'table-danger':''; ?>">

<td><?= $r['ticket_no']; ?></td>
<td><?= $r['title']; ?></td>

<td>
<span class="badge bg-<?= $r['priority']=='Critical'?'danger':($r['priority']=='High'?'warning':'primary') ?>">
<?= strtoupper($r['priority']) ?>
</span>
</td>

<!-- ATTACHMENTS -->
<td>
<?php
if ($r['image']) {
    foreach (explode(",", $r['image']) as $f) {
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','gif','webp'])) {
            echo "<img src='../uploads/$f'
            style='width:35px;cursor:pointer'
            onclick=\"showImage('../uploads/$f')\">";
        } elseif (in_array($ext,['mp4','webm','mov'])) {
            echo "<video width='80' controls>
            <source src='../uploads/$f'></video>";
        }
    }
} else echo "—";
?>
</td>

<td><span class="badge bg-info"><?= $r['status']; ?></span></td>

<!-- STATUS UPDATE -->
<td>
<form method="post" action="update_status.php">
<input type="hidden" name="ticket_id" value="<?= $r['id']; ?>">
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

<!-- ASSIGNED -->
<td>
<?= $r['status']=='Assigned'
 ? "<b>Support Team</b>"
 : "<span class='text-danger'>Not Assigned</span>"; ?>
</td>

</tr>
<?php } ?>
</table>
</div>

<!-- IMAGE MODAL -->
<div id="imgModal" class="modal-overlay" onclick="this.style.display='none'">
    <img id="modalImg" class="modal-img">
</div>

<script>
function showImage(src){
    const modal = document.getElementById('imgModal');
    const img = document.getElementById('modalImg');

    img.onload = function(){
        modal.style.display = 'flex';
    };
    img.src = src;
}
</script>
