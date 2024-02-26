<?php
include '../mongoDB.php';
if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}
if ($SDEPT == 'admin') {
    $q_job_get_requests = ['status' => 'approved', 'type' => 'job'];
} else {
    $q_job_get_requests = ['status' => 'approved', 'type' => 'job','department' => $SDEPT];
}


// $sortCriteria = ['created_at' => 1];
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
                                    <th>approved on</th>
                                    <th>approved by</th>
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
                                    $approved_by = $row_req["updated_by"];
                                    $approved_on = $row_req["updation_date"]->toDateTime()->format('Y-m-d');

                                ?>
                                    <form method="post" id="approvalForm" onsubmit="return submitForm()">
                                        <tr>
                                            <td><?php echo $index; ?></td>  
                                            <td><?php echo $subject; ?></td>
                                            <td><?php echo $job_details; ?></td>
                                            <td><button style="max-width: 200px;"><a href="<?php echo $link; ?>" target="_blank"></a>view link</button></td>
                                            <td><?php echo $approved_on; ?></td>
                                            <td><?php echo $approved_by; ?></td>

                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $r_id; ?>">
                                                <button type="submit" class="button-red" name="disapprove">Disapprove</button>
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
                    $updateResult = null;

                    if (isset($_POST['disapprove'])) {
                        
                        $r_id = $_POST['id'];
                        $currentDateTime = new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp() * 1000);

                        
                        if (isset($_POST['disapprove'])) {
                            $updateResult = $postCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'status' => 'waiting',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        
                        if ($updateResult->getModifiedCount() > 0) {
                            echo "<script>window.location.href='job_approved.php'</script>";
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