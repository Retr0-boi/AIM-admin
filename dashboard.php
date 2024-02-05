<?php
session_start();
$SNAME = $_SESSION['name'];
if (empty($_SESSION['SUID'])) {
  header('location:index.php');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>AIM</title>
  <link rel="stylesheet" href="userdash.css" />
  <!-- Font Awesome Cdn Link -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>

<body>
  <div class="container">
    <nav>
      <ul>
        <li class="active"><a href="#" class="logo">
            <img src="assets/images/favicon.ico">
            <span class="nav-item">Admin</span>
          </a></li>
        <!-- <li class="active"><a href="#">
            <i class="fas fa-menorah"></i>
            <span class="nav-item">Dashboard</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-comment"></i>
            <span class="nav-item">Message</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-database"></i>
            <span class="nav-item">Report</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-item">Detils</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-cog"></i>
            <span class="nav-item">Setting</span>
          </a></li>

        <li><a href="#" class="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-item">Log out</span>
          </a></li> -->
        <li><a href="logout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-item">Log out</span>
          </a></li>
      </ul>
    </nav>


    <section class="main">
      <div class="main-top">
        <h1>AIM ALUMNI ADMIN OPERATIONS</h1>
        <!-- <i class="fas fa-user-cog"></i> -->
      </div>

      <div class="users">
        <div class="card">
          <!-- <img src="#img#"> -->
          <i class="fa-solid fa-users"></i>
          <h4>User Management</h4>
          <a href="Alumni/user_management/dash.php">user management</a>
        </div>
        <div class="card">
          <!-- <img src="#img#"> -->
          <i class="fa-regular fa-image"></i>
          <h4>Post Management</h4>
          <a href="Alumni/user_management/dash.php">post management</a>
        </div>
        <div class="card">
          <!-- <img src="#img#"> -->
          <i class="fa-solid fa-comment-dots"></i>
          <h4>Requests Management</h4>
          <a href="Alumni/user_management/dash.php">user requests</a>
        </div>
        <div class="card">
          <!-- <img src="#img#"> -->
          <i class="fa-solid fa-bell"></i>
          <h4>Notifications</h4>
          <a href="Alumni/user_management/dash.php">notifications</a>
        </div>

      </div>
      <div class="main-top">
        <h1>AIM PLACEMENT ADMIN DASHBOARD</h1>
        <!-- <i class="fas fa-user-cog"></i> -->
      </div>
      <div class="users">

      </div>
    </section>
  </div>

</body>

</html>