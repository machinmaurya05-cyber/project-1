<?php
include "includes/auth.php";
include "includes/db.php";

if (strtolower($_SESSION['user']['role']) !== 'customer') {
    die("Access Denied");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['title']) ||
        empty($_POST['description']) ||
        empty($_POST['priority']) ||
        empty($_POST['department'])
    ) {
        $error = "All fields are required";
    } else {

        $user_id    = intval($_SESSION['user']['id']);
        $ticket_no  = "TCK" . time();
        $title      = mysqli_real_escape_string($conn, $_POST['title']);
        $description= mysqli_real_escape_string($conn, $_POST['description']);
        $priority   = mysqli_real_escape_string($conn, $_POST['priority']);
        $department = mysqli_real_escape_string($conn, $_POST['department']);

        // IMPORTANT: User selected department → status Assigned
        $status = "Assigned";
        $images = [];

        if (!is_dir("uploads")) {
            mkdir("uploads", 0777, true);
        }

        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $k => $name) {
                if ($_FILES['attachments']['error'][$k] === 0) {

                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif','webp','mp4','webm','mov'];

                    if (in_array($ext, $allowed)) {
                        $newName = time()."_".uniqid().".".$ext;
                        move_uploaded_file(
                            $_FILES['attachments']['tmp_name'][$k],
                            "uploads/".$newName
                        );
                        $images[] = $newName;
                    }
                }
            }
        }

        $imgStr = implode(",", $images);

        /*
        |--------------------------------------------------------------------------
        | INSERT TICKET
        |--------------------------------------------------------------------------
        */
        mysqli_query($conn,"
            INSERT INTO tickets
            (ticket_no,user_id,title,description,priority,department,status,image,created_at)
            VALUES
            ('$ticket_no','$user_id','$title','$description','$priority','$department','$status','$imgStr',NOW())
        ");

        $ticket_id = mysqli_insert_id($conn);

        /*
        |--------------------------------------------------------------------------
        | AUTO ASSIGN PLACEHOLDER (USER SELECTED DEPARTMENT)
        | it_user_id = 0  → means no real person yet
        |--------------------------------------------------------------------------
        */
        mysqli_query($conn,"
            INSERT INTO ticket_assign (ticket_id,it_user_id)
            VALUES ('$ticket_id','0')
        ");

        header("Location: dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Create Ticket</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#f5f7fb}
.ticket-card{
    max-width:900px;
    margin:auto;
    border-radius:14px;
}
textarea{
    resize:none;
    min-height:130px;
}
.form-label{font-weight:500}
</style>
</head>

<body>

<div class="container mt-4">
<div class="card ticket-card shadow-sm p-4">

<h4 class="mb-4">Create New Ticket</h4>

<?php if($error){ ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php } ?>

<form method="post" enctype="multipart/form-data">

<div class="mb-3">
<label class="form-label">Title</label>
<input type="text" name="title" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Description</label>
<textarea name="description" class="form-control" required></textarea>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Priority</label>
<select name="priority" class="form-select" required>
<option value="">Select Priority</option>
<option>Normal</option>
<option>High</option>
<option>Critical</option>
</select>
</div>

<div class="col-md-6 mb-3">
<label class="form-label">Department</label>
<select name="department" class="form-select" required>
<option value="">Select Department</option>
<option value="support">Support</option>
<option value="it">IT</option>
</select>
</div>
</div>

<div class="mb-4">
<label class="form-label">Attachments (image / video)</label>
<input type="file" name="attachments[]" class="form-control" multiple>
<small class="text-muted">Screenshots or short videos allowed</small>
</div>

<div class="d-flex gap-2">
<button class="btn btn-primary px-4">Create Ticket</button>
<a href="dashboard.php" class="btn btn-secondary px-4">Back</a>
</div>

</form>

</div>
</div>

</body>
</html>
