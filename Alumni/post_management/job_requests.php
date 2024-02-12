<?php
include '../mongoDB.php';

$q_get_requests = ['account_status' => 'waiting'];
$usersCollection = $database->users;
$r_get_requests = $usersCollection->find($q_get_requests);
$r_get_requests_array = iterator_to_array($r_get_requests);
$reqs_available = count($r_get_requests_array) > 0;

$q_job_get_requests = ['status' => 'waiting'];
$postCollection = $database->posts;
$r_job_get_requests = $postCollection->find($q_job_get_requests);
$r_job_get_requests_array = iterator_to_array($r_job_get_requests);
$job_reqs_available = count($r_job_get_requests_array) > 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Approval Requests</title>
    <link rel="stylesheet" href="userdash.css" />
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

                    <?php if ($job_reqs_available) { ?>
                        <table class="table" id="request">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>subject</th>
                                    <th>job details</th>
                                    <th>link</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $index = 1;
                                foreach ($r_job_get_requests_array as $row_req) {
                                    $r_id = (string) $row_req["_id"];
                                    $subject = $row_req["subject"];
                                    $job_details = $row_req["job_details"];
                                    $link = $row_req["link"];
                                ?>
                                    <form method="post" id="approvalForm" onsubmit="return submitForm()">
                                        <tr>
                                            <td><?php echo $index; ?></td>
                                            <td><?php echo $subject; ?></td>
                                            <td><?php echo $job_details; ?></td>
                                            <td><button style="max-width: 200px;"><a href="<?php echo $link; ?>" target="_blank"></a>view form</button></td>
                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $r_id; ?>">
                                                <button type="submit" name="approve">Approve</button>
                                                <button type="submit" class="button-red" name="disapprove">Disapprove</button>
                                                <!-- <button type="submit" class="button-blue" name="request_details">Request more details</button> -->
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
                        echo "There are no pending job approval requests for now";
                    } ?>
                    <?php
                    if (isset($_POST['approve']) or isset($_POST['disapprove']) or isset($_POST['request_details'])) {
                        $r_id = $_POST['id'];
                        $currentDateTime = new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp() * 1000);
                        $usersCollection = $database->users;

                        if (isset($_POST['approve'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'status' => 'approved',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        if (isset($_POST['disapprove'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'status' => 'disapproved',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        if (isset($_POST['request_details'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'status' => 'request_details',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        if ($updateResult->getModifiedCount() > 0) {
                            echo "<script>window.location.href='requests.php'</script>";
                        } else {
                            echo "<script>alert('Failed to update user account status.');</script>";
                        }
                    }
                    ?>

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