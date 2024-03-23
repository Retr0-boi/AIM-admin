<?php
include '../mongoDB.php';
if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}
if ($SDEPT == 'admin') {
    $q_get_visits = [];
} else {
    $q_get_visits = ['department' => $SDEPT];
}
$usersCollection = $database->users;

$visitCollection = $database->visit;
$r_get_visits = $visitCollection->find($q_get_visits);
$r_get_visits_array = iterator_to_array($r_get_visits);
$visits_available = count($r_get_visits_array) > 0;


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Approved Requests</title>
    <link rel="stylesheet" href="userdash.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>

<body>
    <div class="container">
        <?php include 'nav.php'; ?>

        <section class="main">
            <div class="main-top">
                <h1>Campus Visit Notification</h1>
                <!-- <i class="fas fa-user-cog"></i> -->
            </div>

            <section class="attendance">
                <div class="attendance-list">

                    <?php if ($visits_available) { ?>
                        <table class="table" id="request">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Batch</th>
                                    <th>Department</th>
                                    <th>program</th>
                                    <th>Email</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach($r_get_visits_array as $row){
                                    $r_id= (string) $row["_id"];
                                    $date =$row["date"];
                                    $acc_det = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectID($r_id)]);
                                    $name = $acc_det['name'];
                                    $batch1 = $acc_det['batch_from'];
                                    $batch2 = $acc_det['batch_to'];
                                    $department = $acc_det['department'];
                                    $program = $acc_det['program'];
                                    $email = $acc_det['email'];
                                }
                                ?>
                                <tr>
                                            <td><?php echo $name; ?></td>
                                            <td><?php echo $batch1 . '-' . $batch2; ?></td>
                                            <td><?php echo $department; ?></td>
                                            <td><?php echo $program; ?></td>
                                            <td><?php echo $email; ?></td>
                                            <td><?php echo $date; ?></td>
                                        </tr>
                            </tbody>
                        </table>
                    <?php } else {
                        echo "There are no notifications for now";
                    } ?>
                    
                    <script>
                        var tableRows = document.querySelectorAll("#request tbody tr");

                        tableRows.forEach(function(row) {
                            row.addEventListener("mouseover", function() {
                                tableRows.forEach(function(row) {
                                    row.classList.remove("active");
                                });
                                this.classList.add("active");
                            });

                            row.addEventListener("mouseout", function() {
                                this.classList.remove("active");
                            });

                        });
                    </script>
                </div>
            </section>
        </section>
    </div>
</body>

</html>