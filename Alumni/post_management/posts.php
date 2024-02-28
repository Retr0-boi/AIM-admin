<?php
include '../mongoDB.php';

if (!isset($_SESSION['dept'])) {
    session_start();
    $SNAME = $_SESSION['name'];
    $SDEPT = $_SESSION['dept'];
}

if ($SDEPT == 'admin') {
    $q_job_get_requests = ['type' => 'post'];
} else {
    $q_job_get_requests = ['type' => 'post', 'department' => $SDEPT];
}
// $sortCriteria = ['created_at' => 1];
$postCollection = $database->posts;
$userCollection = $database->users;
$deletedPostCollection = $database->deleted_posts;
$r_job_get_requests = $postCollection->find($q_job_get_requests);
$r_job_get_requests_array = iterator_to_array($r_job_get_requests);
$job_reqs_available = count($r_job_get_requests_array) > 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Approval Requests</title>
    <link rel="stylesheet" href="post.css" />

    <link rel="stylesheet" href="userdash.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>

<body>
    <div class="container">
        <?php include 'nav.php'; ?>

        <section class="main">
            <div class="main-top">
                <h1>User Posts</h1>
                <i class="fas fa-user-cog"></i>
            </div>


            <section class="attendance">
                <?php if ($job_reqs_available) {
                    foreach ($r_job_get_requests_array as $row_req) {
                        $r_id = (string) $row_req["_id"];
                        $subject = $row_req["subject"];
                        $contents = $row_req["content"];
                        $department = $row_req["department"];
                        $posted_by = $row_req["posted_by"];
                        // $username = null;
                        
                        // $userdata = $userCollection->find(['_id' => $posted_by]);
                        // foreach ($userdata as $user) {
                        //     $username = $user['name'];
                        // }
                        if (isset($row_req["image"]))
                            $image = $row_req["image"];

                ?>
                        <div class="post-card-main">
                            <div class="post-card">
                                <div class="post-card-subject">
                                    <?php echo $subject; ?>
                                    <span class="dept"> <?php echo $department; ?></span>
                                </div>
                                <?php if (isset($row_req["image"])) { ?>

                                    <div class="post-card-image">

                                        <img src="<?php echo $image; ?>">
                                    </div>
                                <?php } ?>

                                <div class="post-card-content">
                                    <p>test conten uwu</p>
                                </div>
                                <div class="post-card-button">
                                    <form method="post" id="approvalForm" onsubmit="return submitForm()">
                                        <input type="hidden" name="id" value="<?php echo $r_id; ?>">
                                        <button type="submit" name="delete">Delete</button>
                                    </form>
                                </div>

                            </div>
                        </div>




                    <?php

                    }
                    ?>
                <?php } else {
                    echo "There are no posts for now";
                } ?>
                <?php
                $updateResult = null;

                if (isset($_POST['delete'])) {
                    $objectId = new MongoDB\BSON\ObjectId($_POST['id']);

                    $document = $postCollection->findOne(['_id' => $objectId]);

                    if ($document) {
                        $deletedPostCollection->insertOne($document);

                        $postCollection->deleteOne(['_id' => $objectId]);

                        echo "<script>window.location.href='posts.php'</script>";
                    } else {
                        echo "<script>alert('Failed to delete post.');</script>";
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
            </section>
        </section>


        </section>
    </div>
</body>

</html>