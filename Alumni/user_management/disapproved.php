<?php
include '../mongoDB.php';

if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}
if ($SDEPT == 'admin') {
    $q_get_requests = ['account_status' => 'disapproved'];
} else {
    $q_get_requests = ['account_status' => 'disapproved', 'department' => $SDEPT];
}

$usersCollection = $database->users;

$r_get_requests = $usersCollection->find($q_get_requests);

$r_get_requests_array = iterator_to_array($r_get_requests);

$reqs_available = count($r_get_requests_array) > 0;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Disapproved Requests</title>
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
                    <h1>Disapproved Requests</h1>

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
                                    <th>Disapproval Date</th>
                                    <th>Disapproved By</th>
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
                                                <button type="submit" class="button-blue" name="request_details">Request more details</button>
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
                        echo "There are no disapproved requests for now";
                    } ?>
                    <?php
                    if (isset($_POST['approve']) or isset($_POST['delete']) or isset($_POST['request_details'])) {
                        $r_id = $_POST['id'];
                        $gmail = $_POST['gmail'];
                        $name = $_POST['name'];

                        $currentDateTime = new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp() * 1000);
                        $usersCollection = $database->users;

                        if (isset($_POST['approve'])) {
                            $mail = new PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'albertiansapp@gmail.com';
                            $mail->Password = 'xxqywccphwudrhip';
                            $mail->SMTPSecure = 'ssl';
                            $mail->Port = 465;
                            $mail->setFrom('albertiansapp@gmail.com');
                            $mail->addAddress($gmail);
                            $mail->isHTML(true);
                            $mail->Subject = 'Albertians App account approved';
                            $mail->Body = "Hello, " . $name . " Your account creation request on the Albertians app has been approved,\n
                                            you can now use your registered email and password to log into the application.";
                            $mail->send();
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
                        if (isset($_POST['request_details'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'account_status' => 'request_details',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        if ($updateResult->getModifiedCount() > 0) {
                            echo "<script>window.location.href='disapproved.php'</script>";
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