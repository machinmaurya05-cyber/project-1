<?php
if (strtolower($_SESSION['user']['role']) != 'customer') {
    die("Access Denied");
}

$uid = $_SESSION['user']['id'];

/*
|--------------------------------------------------------------------------
| FILTERS (STATUS + ADVANCED SEARCH)
|--------------------------------------------------------------------------
*/
$where = " WHERE 1 ";
$allowedStatus = ['open','assigned','in progress','waiting','resolved','closed'];

/* Customer can see only his own tickets */
$where .= " AND user_id='".intval($uid)."'";

/* STATUS FILTER (from dashboard cards) */
if (!empty($_GET['status'])) {
    $status = strtolower(mysqli_real_escape_string($conn, $_GET['status']));
    if (in_array($status, $allowedStatus)) {
        $where .= " AND LOWER(status)='$status'";
    }
}

/* TICKET NO SEARCH */
if (!empty($_GET['ticket_no'])) {
    $ticketNo = mysqli_real_escape_string($conn, $_GET['ticket_no']);
    $where .= " AND ticket_no LIKE '%$ticketNo%'";
}

/* TITLE SEARCH */
if (!empty($_GET['title'])) {
    $title = mysqli_real_escape_string($conn, $_GET['title']);
    $where .= " AND title LIKE '%$title%'";
}

/* DATE SEARCH */
if (!empty($_GET['date'])) {
    $date = mysqli_real_escape_string($conn, $_GET['date']);
    $where .= " AND DATE(created_at)='$date'";
}

/*
|--------------------------------------------------------------------------
| TICKETS QUERY
|--------------------------------------------------------------------------
*/
$tickets = mysqli_query($conn,"
SELECT *
FROM tickets
$where
ORDER BY created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>My Tickets</h4>
    <a href="create_ticket.php" class="btn btn-primary">
        + Create New Ticket
    </a>
</div>

<!-- ================= SEARCH ================= -->
<form method="get" class="row g-2 mb-3">

<?php if(!empty($_GET['status'])){ ?>
<input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status']); ?>">
<?php } ?>

<div class="col-md-3">
    <input type="text" name="ticket_no" class="form-control"
           value="<?= $_GET['ticket_no'] ?? '' ?>"
           placeholder="Ticket No">
</div>

<div class="col-md-4">
    <input type="text" name="title" class="form-control"
           value="<?= $_GET['title'] ?? '' ?>"
           placeholder="Ticket Title">
</div>

<div class="col-md-3">
    <input type="date" name="date" class="form-control"
           value="<?= $_GET['date'] ?? '' ?>">
</div>

<div class="col-md-2 d-grid">
    <button class="btn btn-primary">Search</button>
</div>

</form>

<!-- ================= TABLE ================= -->
<div class="card p-3">
<table class="table table-bordered table-striped align-middle">
<tr>
<th>Ticket</th>
<th>Title</th>
<th>Priority</th>
<th>Attachments</th>
<th>Status</th>
</tr>

<?php if(mysqli_num_rows($tickets)==0){ ?>
<tr>
<td colspan="5" class="text-center text-muted">
No tickets found
</td>
</tr>
<?php } ?>

<?php while($r = mysqli_fetch_assoc($tickets)){ ?>
<tr class="<?= ($r['priority']=='Critical')?'critical':''; ?>">

<td><?= $r['ticket_no']; ?></td>
<td><?= $r['title']; ?></td>

<td>
<?php
if ($r['priority']=='Critical')
    echo "<span class='badge bg-danger'>CRITICAL</span>";
elseif ($r['priority']=='High')
    echo "<span class='badge bg-warning text-dark'>High</span>";
else
    echo "<span class='badge bg-primary'>Normal</span>";
?>
</td>

<!-- ATTACHMENTS -->
<td>
<?php
if ($r['image']) {
    foreach (explode(",", $r['image']) as $f) {
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','gif','webp'])) {
            echo "<img src='uploads/$f'
            style='width:40px;margin:3px;cursor:pointer;border-radius:4px'
            onclick=\"showImage('uploads/$f')\">";
        } elseif (in_array($ext,['mp4','webm','mov'])) {
            echo "<video width='80' controls style='margin:3px'>
            <source src='uploads/$f'></video>";
        }
    }
} else {
    echo "—";
}
?>
</td>

<td><?= $r['status']; ?></td>

</tr>
<?php } ?>
</table>
</div>

<!-- ================= IMAGE MODAL ================= -->
<div id="imgModal" onclick="this.style.display='none'"
style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,.85);z-index:9999">
<img id="modalImg"
style="margin:auto;display:block;max-width:90%;max-height:90%;margin-top:5%">
</div>

<script>
function showImage(src){
    document.getElementById('modalImg').src = src;
    document.getElementById('imgModal').style.display = 'block';
}
</script>
