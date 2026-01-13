<?php
session_start();
include "includes/db.php";

$error = "";

if($_SERVER['REQUEST_METHOD']=='POST'){
    $mobile = $_POST['mobile'];
    $password = $_POST['password'];

    $q = mysqli_query($conn,"
        SELECT * FROM users 
        WHERE mobile='$mobile' 
        AND password=MD5('$password')
        LIMIT 1
    ");

    if(mysqli_num_rows($q)==1){
        $_SESSION['user'] = mysqli_fetch_assoc($q);
        header("Location: dashboard.php");
        exit;
    }else{
        $error = "Invalid mobile or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f4f6fa;">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-4">

<div class="card p-4">
<h4 class="text-center mb-3">Support System Login</h4>

<?php if($error){ ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php } ?>

<form method="post">
<input class="form-control mb-2" name="mobile" placeholder="Mobile Number" required>
<input type="password" class="form-control mb-2" name="password" placeholder="Password" required>

<button class="btn btn-primary w-100">Login</button>
</form>

</div>
</div>
</div>
</div>

</body>
</html>
