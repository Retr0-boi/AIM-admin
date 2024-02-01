<?php
include '../mongoDB.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Process the form data
    if (isset($_GET['r_id']) && isset($_GET['name'])) {
        $r_id = $_GET['r_id'];
        $name = $_GET['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Image Upload Form with Preview</title>
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
        <form  method="get" enctype="multipart/form-data" onsubmit="return validateForm()">
            <!-- Hidden input fields for r_id and name -->
            <input type="hidden" name="r_id" value="<?php echo $_GET['r_id']; ?>">
            <input type="hidden" name="name" value="<?php echo $_GET['name']; ?>">

            <!-- Input field for choosing an image -->
            <label for="image">Choose an image:</label>
            <input type="file" name="image" id="image" onchange="previewImage()" required>

            <!-- Image preview container -->
            <img id="image-preview" src="#" alt="Image Preview">

            <!-- Submit button -->
            <input type="submit" value="Upload Image" name="upload">
        </form>
        <?php 
        if(isset($_GET['upload'])){
            echo "<script>alert('$r_id');</script>";
            echo "<script>alert('$name');</script>";
        }
        ?>
        <script>
            // JavaScript function to preview the selected image
            function previewImage() {
                var preview = document.getElementById('image-preview');
                var fileInput = document.getElementById('image');
                var file = fileInput.files[0];
                var reader = new FileReader();

                reader.onload = function (e) {
                    preview.src = e.target.result;
                };

                if (file) {
                    reader.readAsDataURL(file);
                }
            }

            // JavaScript function to validate the form before submission
            function validateForm() {
                // You can add additional validation logic here
                return true; // Return true to submit the form, or false to prevent submission
            }
        </script>
    </div>
</body>

</html>
