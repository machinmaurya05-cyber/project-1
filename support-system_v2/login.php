<?php
session_start();
include "includes/db.php";

if(isset($_POST['login'])){
    $mobile = $_POST['mobile'];
    $pass = md5($_POST['password']);

    $q = mysqli_query($conn,"SELECT * FROM users WHERE mobile='$mobile' AND password='$pass'");
    
    if(mysqli_num_rows($q)==1){
        $u = mysqli_fetch_assoc($q);
        $_SESSION['user'] = $u;

        if($u['role']=="admin") header("Location: admin/dashboard.php");
        else if($u['role']=="it") header("Location: it/dashboard.php");
        else if($u['role']=="support") header("Location: support/dashboard.php");
        else header("Location: customer/create_ticket.php");
    }
    else{
        $msg = "Invalid Mobile or Password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background: linear-gradient(135deg,#0a2fb6,#0b74ff);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* TOP BRAND TEXT */
.brand{
    color:white;
    position:absolute;
    top:25px;
    left:25px;
    font-size:22px;
    font-weight:700;
}

/* MAIN LOGIN BOX */
.login-box{
    width: 430px;
    background:white;
    border-radius:18px;
    padding:35px;
    box-shadow:0 20px 40px rgba(0,0,0,.25);
    border-top:6px solid #b07535;   /* light brown touch */
}

.title{
    font-size:28px;
    font-weight:700;
    color:#0a2fb6;
}

/* INPUTS */
.form-control{
    border-radius:10px;
    height:45px;
    font-size:15px;
}

.form-control:focus{
    border:2px solid #0b74ff;
    box-shadow:none;
}

/* LOGIN BUTTON */
.btn-primary{
    border-radius:10px;
    height:45px;
    background:#0b74ff;
    font-size:17px;
    font-weight:600;
}

</style>

</head>

<body>

<div class="brand">Support System</div>

<div class="login-box">

<p class="title mb-3">Login</p>

<form method="post">

<input class="form-control mb-3" name="mobile" placeholder="Mobile">

<input class="form-control mb-3" type="password" name="password" placeholder="Password">

<button class="btn btn-primary w-100" name="login">Login</button>

<p class="text-danger mt-2">
<?php echo $msg ?? ''; ?>
</p>

</form>

</div>

</body>
</html>
