<?php
include '../mongoDB.php';
$r_id = $_GET['r_id'];
$name = $_GET['name'];
$usersCollection = $database->users;
$destinationDirectory = 'assets/identification/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>IDENTIFICATION</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        label {
            margin-bottom: 10px;
        }

        input[type="file"] {
            margin-bottom: 20px;
        }

        input[type="submit"] {
            padding: 10px;
            background-color: #4caf50;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        #image-preview {
            max-width: 100%;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Form for uploading image -->
        <form method="post" enctype="multipart/form-data">
            <!-- Hidden input fields for r_id and name -->
            <input type="hidden" name="r_id" value="<?php echo $r_id ?>">
            <input type="hidden" name="name" value="<?php echo $name ?>">

            <!-- Input field for choosing an image -->
            <label for="image">Choose an image:</label>
            <input type="file" name="image" id="image" onchange="previewImage()" required>

            <!-- Image preview container -->
            <img id="image-preview" src="#" alt="Image Preview" style="height: 300px;width: 500px;">
            <br>
            <!-- Submit button -->
            <input type="submit" value="Upload Image" name="upload">
        </form>
        <?php
        if (isset($_POST['upload'])) {
            if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
                $image_name = $_FILES['image']['name'];
                $image_tmp = $_FILES['image']['tmp_name'];
                $image_name_with_id = $r_id . '_' . $image_name;
                $destination = $destinationDirectory . $image_name_with_id;
                // $destination = 'assets/identification/' . $image_name;
                if (!is_dir($destinationDirectory)) {
                    mkdir($destinationDirectory, 0755, true);
                }
                if (move_uploaded_file($image_tmp, $destination)) {
                    $updateResult = $usersCollection->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId($r_id)],
                        ['$set' => [
                            'identification' => $destination
                        ]]
                    );
                    if ($updateResult->getModifiedCount() > 0) {
                        echo "<script>alert('uploaded successfully.');</script>";
                        echo "<script>window.close();</script>";
                    } else {
                        echo "<script>alert('Failed to update user account please contact the support team or the administrators.');</script>";
                    }
                } else {
                    echo "<script>alert('Failed to upload the file.');</script>";
                }
            }
        }
        ?>
        <script>
            function previewImage() {
                var preview = document.getElementById('image-preview');
                var fileInput = document.getElementById('image');
                var file = fileInput.files[0];
                var reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                };

                if (file) {
                    reader.readAsDataURL(file);
                }
            }
        </script>
    </div>
</body>

</html>