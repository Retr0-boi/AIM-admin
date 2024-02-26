<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AIM/ALumni/mongoDB.php';
if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}
if ($SDEPT == 'admin')
    $q_get_requests_count = ['account_status' => 'waiting'];
else
    $q_get_requests_count = ['account_status' => 'waiting', 'department' => $SDEPT];

// $q_get_requests_count = ['account_status' => 'waiting'];
$usersCollection = $database->users;
$r_get_requests_count = $usersCollection->find($q_get_requests_count);
$r_get_requests_array_count = iterator_to_array($r_get_requests_count);
$reqs_available_count = count($r_get_requests_array_count);

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
        <li class="<?php if ($current_page == "adminopr.php") {
                        echo "active";
                    } ?>"><a href="adminopr.php"><span class="logo">
                    <img src="/AIM/assets/images/favicon.ico"><span class="nav-item" style="margin-top:0px;">Home</span>
                </span></a></span>
        </li>

        <li class="<?php if ($current_page == "admin.php") {
                        echo "active";
                    } ?>"><a href="admin.php">
                <i class="fas fa-user-tie"></i><span class="nav-item">Add/Remove Admins</span>
            </a>
        </li>

        <li class="<?php if ($current_page == "HOD.php") {
                        echo "active";
                    } ?>"><a href="HOD.php">
                <i class="fas fa-user-plus"></i><span class="nav-item">Add/Remove HODs</span>
            </a>
        </li>

        <li class="<?php if ($current_page == "view.php") {
                        echo "active";
                    } ?>"><a href="view.php">
                <i class="fas fa-users"></i><span class="nav-item">View Admins/HODs</span>
            </a>
        </li>

       

       
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