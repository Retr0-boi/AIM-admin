<?php
include 'backend/dbconnection.php';
$q_get_requests_S3 = "SELECT * FROM acc_requests WHERE status='3'";
$r_get_requests_S3 = $conn->query($q_get_requests_S3);
if ($r_get_requests_S3->num_rows != 0) {
  $reqs_available_S3=true;
}
else{
  $reqs_available_S3=false;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Approval Requests</title>
    <link rel="stylesheet" href="userdash.css" />
    <!-- Font Awesome Cdn Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>

<body>
    <div class="container">
        <?php include 'nav.php'; ?>

        <section class="main">
            <div class="main-top">
                <h1>Request Management</h1>
                <i class="fas fa-user-cog"></i>
            </div>


            <section class="attendance">
                <div class="attendance-list">
                    <h1>Approvals required</h1>

                    <?php

                    if ($reqs_available_S3 == true) {

                        // echo "<script>alert('is this working');</script>";
                        $index = 1;
                    ?>
                        <table class="table" id="request">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Name</th>
                                    <th>DOB</th>
                                    <th>Batch</th>
                                    <th>Department</th>
                                    <th>Specialization</th>
                                    <th>Email</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                while ($row_req = $r_get_requests_S3->fetch_assoc()) {
                                    $r_id = $row_req["r_id"];
                                    $name = $row_req["name"];
                                    $dob = $row_req["DOB"];
                                    $batch1 = $row_req["batch_from"];
                                    $batch2 = $row_req["batch_to"];
                                    $department = $row_req["department"];
                                    $specialization = $row_req["specialization"];
                                    $email = $row_req["email"];

                                ?>

                                    <form method="post" id="approvalForm" onsubmit="return submitForm()">

                                        <tr>
                                            <td><?php echo $index; ?></td>
                                            <td><?php echo $name; ?></td>
                                            <td><?php echo $dob; ?></td>
                                            <td><?php echo $batch1 . '-' . $batch2; ?></td>
                                            <td><?php echo $department; ?></td>
                                            <td><?php echo $specialization; ?></td>
                                            <td><?php echo $email; ?></td>
                                            <td>
                                                <input type="text" name="id" value="<?php echo $r_id; ?>" hidden>
                                                <button type="submit" name="approve">approve</button>
                                                <button type="submit" class="button-red" name="disapprove">disapprove</button>
                                                <?php
                                                $q_check_id = "SELECT identification FROM acc_requests WHERE r_id = '$r_id'";
                                                $r_check_id = $conn->query($q_check_id);
                                                $row_id = $r_check_id->fetch_assoc();
                                                $img_link = $row_id['identification'];
                                                if ($img_link == null) : ?>

                                                    <button type="submit" class="button-blue" name="view_details" disabled>View Details</button>
                                                <?php else : ?>
                                                    <button type="submit" class="button-blue" name="view_details" onclick="openImage()">View Details</button>

                                                <?php endif; ?>

                                            </td>
                                        </tr>
                                    </form>

                                <?php
                                    $index++;
                                } ?>
                            </tbody>
                        </table>
                    <?php } else {
                        echo "uh oh this place seems empty";
                    } ?>

                    <?php
                    if (isset($_POST['approve']) or isset($_POST['disapprove'])) {
                        $r_id = $_POST['id'];
                        $q_select_req = "SELECT * FROM acc_requests WHERE r_id = '$r_id'";
                        $r_select_req = $conn->query($q_select_req);
                        $row = $r_select_req->fetch_assoc();
                        $r_id = $row['r_id'];
                        $username = $row['name'];
                        $dob = $row['DOB'];
                        $batch_from = $row['batch_from'];
                        $batch_to = $row['batch_to'];
                        $department = $row['department'];
                        $specialization = $row['specialization'];
                        $password = $row['password'];
                        $email = $row['email'];


                        if (isset($_POST['approve'])) {
                            $q_approve = "CALL ApproveRequest($r_id, '$SNAME', '$username', '$password', '$email', '$dob', '$batch_from', '$batch_to', '$department', '$specialization')";

                            if (!($conn->query($q_approve))) {
                                echo $conn->error;
                            } else {
                                echo "<script>window.location.href='requests.php'</script>";
                                exit();
                            }
                        }
                        if (isset($_POST['disapprove'])) {
                            $q_disapprove = "UPDATE acc_requests SET status='2',updation_date=NOW(),updated_by='$SNAME' WHERE r_id='$r_id'";
                            if (!($conn->query($q_disapprove))) {
                                echo $conn->error;
                            } else {
                                echo "<script>window.location.href='requests.php'</script>";
                                exit();
                            }
                        }
                    }

                    ?>
                    <script>
                        function openImage() {
                            var imageUrl = "<?php echo $img_link;?>";

                            // Open the image in a new window or tab
                            window.open(imageUrl, "_blank");
                        }


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