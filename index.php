<?php
include 'mongoDB.php';

session_start();
if (!empty($_SESSION['SUID'])) {
    $SID = $_SESSION['SUID'];
    header('location:dashboard.php');
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Select the admins collection
    $adminsCollection = $database->admins;

    // Find the user by email
    $admin = $adminsCollection->findOne(['email' => $email]);

    if ($admin) {
        // Check hashed password (you should use a proper hashing algorithm)
        if ($password == $admin['password']) {
            $_SESSION['name'] = $admin['username'];
            $_SESSION['SUID'] = $admin['_id'];

            header("Location:dashboard.php");
            exit;
        } else {
            $isPasswordCorrect = 0;
        }
    } else {
        $isPasswordCorrect = 0;
    }
}

?>
<html lang="en">

<head>
    <title>AIM</title>

    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Datta Able Bootstrap admin template made using Bootstrap 4 and it has huge amount of ready made feature, UI components, pages which completely fulfills any dashboard needs." />
    <meta name="keywords" content="admin templates, bootstrap admin templates, bootstrap 4, dashboard, dashboard templets, sass admin templets, html admin templates, responsive, bootstrap admin templates free download,premium bootstrap admin templates, datta able, datta able bootstrap admin template, free admin theme, free dashboard template" />
    <meta name="author" content="CodedThemes" />

    <!-- Favicon icon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <!-- fontawesome icon -->
    <link rel="stylesheet" href="assets/fonts/fontawesome/css/fontawesome-all.min.css">
    <!-- animation css -->
    <link rel="stylesheet" href="assets/plugins/animation/css/animate.min.css">
    <!-- vendor css -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-content">
            <div class="auth-bg">
                <span class="r"></span>
                <span class="r s"></span>
                <span class="r s"></span>
                <span class="r"></span>
            </div>
            <form method="post">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="feather icon-unlock auth-icon"></i>
                        </div>
                        <h3 class="mb-4">Login</h3>
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Email" name="email" Required>
                        </div>
                        <div class="input-group mb-4">
                            <input type="password" class="form-control" placeholder="password" name="password" Required>
                        </div>
                        <!-- <div class="form-group text-left">
                            <div class="checkbox checkbox-fill d-inline">
                                <input type="checkbox" name="checkbox-fill-1" id="checkbox-fill-a1">
                                <label for="checkbox-fill-a1" class="cr"> Save Details</label>
                            </div>
                        </div> -->
                        <button type="submit" name="login" class="btn btn-primary shadow-2 mb-4">Login</button>
                        <!-- <p class="mb-2 text-muted">Forgot password? <a href="#">Reset</a></p> -->
                        <?php
                        if (isset($_POST['login']) && isset($isPasswordCorrect))
                            echo '<p class="mb-2 text-muted">Invalid Email or Password!</p>';
                        ?>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- Required Js -->
    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>

</body>

</html>