<?php
include '../mongoDB.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <!-- Font Awesome Cdn Link -->
</head>

<body>
  <div class="container">
    <?php include 'nav.php'; ?>

    <section class="main">
      <div class="main-top">
        <h1>ALUMNI ACCOUNT MANAGEMENT PAGE</h1>

        <i class="fas fa-user-cog"></i>
      </div>
      welcome <?php echo $SNAME; ?>
      <div class="attendance">
        <h1>TO DO LIST</h1>
        <div style="position:relative;left:20px;">
          <ul>
            <li>Request more info mail</li>
            <li>main logic for pending page</li>
            
          </ul>
        </div>
      </div>

    </section>
  </div>

</body>

</html>