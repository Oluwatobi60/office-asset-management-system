<?php 
  session_start();

  require_once'includes/conn.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/ae360af17e.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="container"><!-- container start -->
  <form action="" class="form-login" method="POST"><!-- form-login start -->
    <h2 class="form-login-heading">Admin Login</h2>

    <div class="form-group mb-2">
    <input type="email" class="form-control" placeholder="Email Address" name="admin_email" required>
    </div>
   
    <div class="form-group mb-2">
    <input type="password" class="form-control" placeholder="Password" name="admin_pass" required>
    </div>
   
    <button type="submit" class="btn btn-lg btn-primary d-block w-100" name="admin_login">
      Login
    </button>
    <span>If you're a new admin register here <a href="adminregister.php">Register</a></span>
  </form><!-- form-login end -->
</div><!-- container end -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>



<?php
if(isset($_POST['admin_login'])){

  $admin_email = mysqli_real_escape_string($conn,$_POST['admin_email']);
  $admin_pass = mysqli_real_escape_string($conn,$_POST['admin_pass']);

  $get_admin = "SELECT * FROM admins WHERE admin_email='$admin_email' AND admin_pass='$admin_pass'";

  $run_admin = mysqli_query($conn,$get_admin);

  $count = mysqli_num_rows($run_admin);

  if($count==1){
    $_SESSION['admin_email']=$admin_email;

    echo "<script>alert('Logged in. Welcome Back')</script>";

    echo "<script>window.open('admindashboard.php','_self')</script>";
  }else{
    echo "<script>alert('Email or Password is Wrong !')</script>";
  }


}

?>