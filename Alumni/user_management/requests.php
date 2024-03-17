<?php
include '../mongoDB.php';

if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}
if ($SDEPT == 'admin')
    $q_get_requests = ['account_status' => 'waiting'];
else
    $q_get_requests = ['account_status' => 'waiting', 'department' => $SDEPT];

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
                <!-- <i class="fas fa-user-cog"></i> -->
            </div>

            <section class="attendance">
                <div class="attendance-list">
                    <h1>Approvals required</h1>

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
                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $r_id; ?>">
                                                <input type="hidden" name="gmail" value="<?php echo $email; ?>">
                                                <input type="hidden" name="name" value="<?php echo $name; ?>">
                                                <button type="submit" name="approve">Approve</button>
                                                <button type="submit" class="button-red" name="disapprove">Disapprove</button>
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
                        echo "There are no pending user account creation requests for now";
                    } ?>
                    <?php
                    if (isset($_POST['approve']) or isset($_POST['disapprove']) or isset($_POST['request_details'])) {
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
                        if (isset($_POST['disapprove'])) {
                            $updateResult = $usersCollection->updateOne(
                                ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                                ['$set' => [
                                    'account_status' => 'disapproved',
                                    'updation_date' => $currentDateTime,
                                    'updated_by' => $SNAME
                                ]]
                            );
                        }
                        if (isset($_POST['request_details'])) {

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
                            $mail->Subject = 'Albertians App account approval';
                            $mail->Body = "Hello, " . $name . ". We saw your account creation request on the Albertians app,\n
                                            inorder for us to approve it you must kindly provide us with the proof that you studied here.\n
                                            Please Provide us with the nescessary document.\n
                                            Use this link to upload the nescessary document:\t http://localhost/AIM/Alumni/user_management/identification.php?r_id=".$r_id."&name=".$name;
                            $mail->send();

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