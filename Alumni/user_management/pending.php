<?php
include '../mongoDB.php';

if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}
if ($SDEPT == 'admin') {
    $q_get_requests = ['account_status' => 'request_details'];
} else {
    $q_get_requests = ['account_status' => 'request_details', 'department' => $SDEPT];
}
$usersCollection = $database->users;

$r_get_requests = $usersCollection->find($q_get_requests);

$r_get_requests_array = iterator_to_array($r_get_requests);

$reqs_available = count($r_get_requests_array) > 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>pending Requests</title>
    <link rel="stylesheet" href="userdash.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>

<body>
    <div class="container">
        <?php include 'nav.php'; ?>

        <section class="main">
            <div class="main-top">
                <h1>Request Management</h1>
                <!-- <i class="fas fa-user-cog"></i> -->
            </div>

            <section class="attendance">
                <div class="attendance-list">
                    <h1>user identification</h1>

                    <?php if ($reqs_available) { ?>
                        <table class="table" id="request">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Name</th>
                                    <th>DOB</th>
                                    <th>Batch</th>
                                    <th>Department</th>
                                    <th>program</th>
                                    <th>Email</th>
                                    <th>Request Date</th>
                                    <th>Requested By</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $index = 1;
                                foreach ($r_get_requests_array as $row_req) {
                                    $r_id = (string) $row_req["_id"];
                                    $name = $row_req["name"];
                                    $dob = $row_req["DOB"];
                                    $batch1 = $row_req["batch_from"];
                                    $batch2 = $row_req["batch_to"];
                                    $department = $row_req["department"];
                                    $program = $row_req["program"];
                                    $email = $row_req["email"];
                                    $updation_date = $row_req["updation_date"]->toDateTime()->format('Y-m-d');
                                    $updated_by = $row_req["updated_by"];
                                    $identification = $row_req["identification"];

                                ?>
                                    <form method="post" id="approvalForm" onsubmit="return submitForm()">
                                        <tr>
                                            <td><?php echo $index; ?></td>
                                            <td><?php echo $name; ?></td>
                                            <td><?php echo $dob; ?></td>
                                            <td><?php echo $batch1 . '-' . $batch2; ?></td>
                                            <td><?php echo $department; ?></td>
                                            <td><?php echo $program; ?></td>
                                            <td><?php echo $email; ?></td>
                                            <td><?php echo $updation_date; ?></td>
                                            <td><?php echo $updated_by; ?></td>

                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $r_id; ?>">
                                                <button type="submit" name="approve">approve</button>
                                                <button type="submit" class="button-red" name="delete">delete</button>
                                                <?php if ($identification == "none") : ?>

                                                    <button type="submit" class="button-blue" name="view_details" disabled>View Details</button>
                                                <?php else : ?>
                                                    <button type="submit" class="button-blue" name="view_details" onclick="openImage()">View Details</button>

                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </form>
                                <?php
                                    $index++;
                                }
                                ?>
                            </tbody>
                        </table>
                    <?php } else {
                        echo "There are no users here to provide identification";
                    } ?>
                    <?php
                    if (isset($_POST['approve']) or isset($_POST['disapprove'])or isset($_POST['delete'])) {
                        $r_id = $_POST['id'];
                        $currentDateTime = new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp() * 1000);
                        $usersCollection = $database->users;

                        if (isset($_POST['approve'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'account_status' => 'approved',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        if (isset($_POST['delete'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'account_status' => 'deleted',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        
                        if ($updateResult->getModifiedCount() > 0) {
                            echo "<script>window.location.href='pending.php'</script>";
                        } else {
                            echo "<script>alert('Failed to update user account status.');</script>";
                        }
                    }
                    ?>

                    <script>
                        function openImage() {
                            var imageUrl = "<?php echo $identification;?>";

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