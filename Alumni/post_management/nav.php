<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AIM/ALumni/mongoDB.php';
if (!isset($_SESSION['dept'])) {
  session_start();
  $SNAME = $_SESSION['name'];
  $SDEPT = $_SESSION['dept'];
}
if (empty($_SESSION['SUID'])) {
  header('location:../../index.php');
}
if ($SDEPT == 'admin')
  $q_get_job_requests_count = ['status' => 'waiting'];

else
  $q_get_job_requests_count = ['status' => 'waiting', 'department' => $SDEPT];


$usersCollection = $database->users;
$postCollection = $database->posts;
$r_get_job_requests_count = $postCollection->find($q_get_job_requests_count);
$r_get_job_requests_array_count = iterator_to_array($r_get_job_requests_count);
$reqs_job_available_count = count($r_get_job_requests_array_count);



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

    <li class="<?php if ($current_page == "job_requests.php") {
                  echo "active";
                } ?>">
      <a href="job_requests.php" style="position: relative; display: inline-block;">
        <i class="fas fa-business-time"></i>
        <?php if ($reqs_job_available_count > 0) {
          echo '<span class="notification-badge">' . $reqs_job_available_count . '</span>';
        } ?>
        <span class="nav-item">Job Requests</span></a>
    </li>

    <li class="<?php if ($current_page == "job_approved.php") {
                  echo "active";
                } ?>"><a href="job_approved.php">
        <i class="fas fa-check"></i><span class="nav-item">Approved Jobs</span>
      </a></li>
      <li class="<?php if ($current_page == "job_disapproved.php") {
                  echo "active";
                } ?>"><a href="job_disapproved.php">
        <i class="fas fa-times"></i><span class="nav-item">Disapproved Jobs</span>
      </a></li>
    <li class="<?php if ($current_page == "posts.php") {
                  echo "active";
                } ?>"><a href="posts.php">
        <i class="fas fa-images"></i><span class="nav-item">Posts</span>
      </a></li><li class="<?php if ($current_page == "deleted_posts.php") {
                  echo "active";
                } ?>"><a href="deleted_posts.php">
        <i class="fas fa-recycle"></i><span class="nav-item">Deleted Posts</span>
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

    <li><a href="../../logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i>
        <span class="nav-item">Log out</span>
      </a></li>

  </ul>
</nav>