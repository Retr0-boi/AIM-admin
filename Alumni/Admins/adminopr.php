<?php
include '../mongoDB.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>ADMIN OPERATIONS</title>
  <!-- Font Awesome Cdn Link -->
</head>

<body>
  <div class="container">
    <?php include 'nav.php'; ?>

    <section class="main">
      <div class="main-top">
        <h1>ALBERTIANS ADMIN MANAGEMENT</h1>
        <!-- <i class="fas fa-user-cog"></i> -->
      </div>
      welcome <?php echo $SNAME; ?>
    </section>
  </div>

</body>

</html>