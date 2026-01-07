<?php
include '../includes/auth.php';
include '../includes/db.php';

if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) != 'customer') {
    die("Access denied");
}

if (isset($_POST['submit'])) {

    $uid = $_SESSION['user']['id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $cat = $_POST['category'];
    $pri = $_POST['priority'];
    $dept = $_POST['department'];  
    $ticket_no = 'RP-' . time();

    $uploaded_files = [];

    // MULTIPLE IMAGE UPLOAD
    if (isset($_FILES['images'])) {

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {

            if ($_FILES['images']['name'][$key] != "") {

                $filename = time() . rand(1000, 9999) . $_FILES['images']['name'][$key];
                $path = "uploads/" . $filename;

                move_uploaded_file($tmp, "../" . $path);

                $uploaded_files[] = $path;
            }
        }
    }

    // Join all uploaded images
    $all_images = implode(",", $uploaded_files);

    // INSERT TICKET
    mysqli_query($conn, "
    INSERT INTO tickets(ticket_no,user_id,category,title,description,priority,image,department) 
    VALUES('$ticket_no','$uid','$cat','$title','$desc','$pri','$all_images','$dept')
    ");

    // GET LAST INSERTED TICKET ID
    $ticket_id = mysqli_insert_id($conn);

    // FIND ANY USER FROM SELECTED DEPARTMENT
    $u = mysqli_query($conn,"SELECT id FROM users WHERE role='$dept' LIMIT 1");
    $user = mysqli_fetch_assoc($u);

    if($user){
        $assignUser = $user['id'];

        // AUTO INSERT INTO ticket_assign
        mysqli_query($conn,"
        INSERT INTO ticket_assign(ticket_id,it_user_id)
        VALUES('$ticket_id','$assignUser')
        ");

        // UPDATE STATUS TO ASSIGNED
        mysqli_query($conn,"
        UPDATE tickets SET status='Assigned'
        WHERE id='$ticket_id'
        ");
    }

    $msg = 'Ticket Created Successfully 👍 Ticket No: ' . $ticket_no;
}
?>
<!DOCTYPE html>
<html>

<head>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<link href='../theme.css' rel='stylesheet'>
</head>

<body>

<nav class='navbar navbar-dark px-3'>
    <span class="text-white fw-bold">Customer</span>
    <a href='../logout.php' class='btn btn-danger btn-sm'>Logout</a>
</nav>

<div class='container mt-4'>
<h3 class="text-dark">Create Ticket</h3>

<div class='card p-3'>

<b class="text-success">
<?php echo $msg ?? ''; ?>
</b>

<form method='post' enctype='multipart/form-data'>

<input class='form-control mb-2' name='title' placeholder='Issue Title' required>

<input class='form-control mb-2' name='category' placeholder='Category' required>

<select class='form-control mb-2' name='priority'>
<option>Normal</option>
<option>High</option>
<option>Critical</option>
</select>

<select class='form-control mb-2' name='department' required>
<option value="">Select Department</option>
<option value="it">IT Team</option>
<option value="support">Support Team</option>
</select>

<label><b>Upload Screenshots (You can select multiple)</b></label>
<input class='form-control mb-2' type='file' name='images[]' multiple>

<textarea class='form-control mb-2' name='description' 
placeholder="Explain your problem..." required></textarea>

<button class='btn btn-success' name='submit'>Submit</button>

</form>

</div>
</div>

</body>
</html>
