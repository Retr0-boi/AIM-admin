<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AIM/ALumni/mongoDB.php';


$q_get_requests_count = ['account_status' => 'waiting'];
$usersCollection = $database->users;
$r_get_requests_count = $usersCollection->find($q_get_requests_count);
$r_get_requests_array_count = iterator_to_array($r_get_requests_count);
$reqs_available_count = count($r_get_requests_array_count);

session_start();
$SNAME = $_SESSION['name'];
if (empty($_SESSION['SUID'])) {
  header('location:../../index.php');
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
<link rel="stylesheet" href="userdash.css" />
<link rel="stylesheet" href="notification.css" />

<nav>
  <ul>
    <li class="<?php if ($current_page == "dash.php") {
                  echo "active";
                } ?>"><a href="dash.php"><span class="logo">
          <img src="/AIM/assets/images/favicon.ico"><span class="nav-item" style="margin-top:0px;">Home</span>
        </span></a></span>
    </li>

    <li class="<?php if ($current_page == "requests.php") {
                  echo "active";
                } ?>">
      <a href="requests.php" style="position: relative; display: inline-block;">
        <i class="fas fa-bell"></i>
        <?php if ($reqs_available_count > 0) {
          echo '<span class="notification-badge">' . $reqs_available_count . '</span>';
        } ?>
        <span class="nav-item">Requests</span></a>
    </li>

    <li class="<?php if ($current_page == "approved.php") {
                  echo "active";
                } ?>"><a href="approved.php">
        <i class="fas fa-check"></i><span class="nav-item">Approved</span>
      </a></li>
    <li class="<?php if ($current_page == "disapproved.php") {
                  echo "active";
                } ?>"><a href="disapproved.php">
        <i class="fas fa-times"></i><span class="nav-item">Disapproved</span>
      </a></li>
    <li class="<?php if ($current_page == "pending.php") {
                  echo "active";
                } ?>"><a href="pending.php">
        <i class="fas fa-clock"></i><span class="nav-item">Pending</span>
      </a></li>
    <li class="<?php if ($current_page == "locked.php") {
                  echo "active";
                } ?>"><a href="locked.php">
        <i class="fas fa-lock"></i><span class="nav-item">Locked Accounts</span>
      </a></li>
    <li class="<?php if ($current_page == "deleted.php") {
                  echo "active";
                } ?>"><a href="deleted.php">
        <i class="fas fa-trash"></i><span class="nav-item">Deleted Accounts</span>
      </a></li>

    <!-- <li><a href="#">
            <i class="fas fa-comment"></i>
            <span class="nav-item">Message</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-database"></i>
            <span class="nav-item">Report</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-chart-bar"></i>
            <span class="nav-item">Details</span>
          </a></li>
        <li><a href="#">
            <i class="fas fa-cog"></i>
            <span class="nav-item">Setting</span>
          </a></li>-->
    <li><a href="/AIM/dashboard.php" class="go-back">
        <i class="fas fa-arrow-left "></i>
        <span class="nav-item">GO BACK</span>
      </a></li>
    <li><a href="../logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i>
        <span class="nav-item">Log out</span>
      </a></li>

  </ul>
</nav>