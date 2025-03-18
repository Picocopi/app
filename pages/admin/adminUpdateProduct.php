<?php 
// session_start();
include '../../_header.php'; 
ob_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #drop_zone {
            border: 2px dashed #ccc;
            border-radius: 10px;
            width: 100%;
            height: 200px;
            text-align: center;
            line-height: 200px;
            color: #ccc;
            font-size: 20px;
        }
        #drop_zone.dragover {
            border-color: #000;
            color: #000;
        }
        .image-preview {
            display: inline-block;
            margin: 10px;
        }
        .image-preview img {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <h1>Update Product</h1>
    <?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "TESTING1";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
        
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['product_id'])) {
        $product_id = $_GET['product_id'];
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $product_name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'];
        $status = $_POST['status'];
        $discount = $_POST['discount'];
        $weight = $_POST['weight'];
        $length = $_POST['length'];
        $width = $_POST['width'];
        $height = $_POST['height'];
        $brand = $_POST['brand'];
        $color = $_POST['color'];
        $rating = $_POST['rating'];
        $reviews_count = $_POST['reviews_count'];

        // Calculate discounted price
        $discounted_price = $price - ($price * ($discount / 100));

        // Handle file uploads
        $image_urls = [];
        $upload_dir = "img/"; // Path relative to your project root (accessible in the browser)
        $target_dir = __DIR__ . "/../../img/"; // Absolute server path to prevent issues

        // Debugging: Log the POST data
        echo "<pre>POST Data: " . print_r($_POST, true) . "</pre>";

        // Debugging: Log the FILES data
        echo "<pre>FILES Data: " . print_r($_FILES, true) . "</pre>";

        // Filter out duplicate file names
        $unique_files = array_unique($_FILES['image_url']['name']);

        // Debugging: Log unique files
        echo "<pre>Unique Files: " . print_r($unique_files, true) . "</pre>";

        foreach ($unique_files as $key => $image_name) {
            if ($_FILES['image_url']['error'][$key] == 0) {
                $target_file = $target_dir . basename($image_name);
                $relative_path = $upload_dir . basename($image_name);

                if (!in_array($relative_path, $image_urls)) {
                    if (move_uploaded_file($_FILES['image_url']['tmp_name'][$key], $target_file)) {
                        $image_urls[] = $relative_path;
                    } else {
                        echo "Error moving file: $image_name<br>";
                    }
                }
            }
        }

        // Debugging: Log the final image URLs array
        echo "<pre>Final Image URLs Array: " . print_r($image_urls, true) . "</pre>";

        // If no new images are uploaded, retain the existing images
        if (empty($image_urls)) {
            $image_urls_json = $product['image_url'];
        } else {
            $image_urls_json = json_encode($image_urls, JSON_UNESCAPED_SLASHES);
        }

        // Debugging: Log the JSON-encoded image URLs
        echo "Image URLs JSON: $image_urls_json<br>";

        // Prepare and bind
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image_url = ?, status = ?, discount = ?, discounted_price = ?, weight = ?, length = ?, width = ?, height = ?, brand = ?, color = ?, rating = ?, reviews_count = ?, updated_at = NOW() WHERE product_id = ?");
        if (!$stmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        }
        $stmt->bind_param("ssdisdssdddddssdii", $product_name, $description, $price, $stock, $category, $image_urls_json, $status, $discount, $discounted_price, $weight, $length, $width, $height, $brand, $color, $rating, $reviews_count, $product_id);

        // Execute the statement
        if ($stmt->execute()) {
            echo "Product updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();

        // Re-fetch the product details after update
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
    }

    $conn->close();
    ?>

    <form id="updateProductForm" action="adminUpdateProduct.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
        <label for="name">Product Name:</label><br>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea><br><br>

        <label for="price">Price:</label><br>
        <input type="number" step="0.01" id="price" name="price" value="<?php echo $product['price']; ?>" required><br><br>

        <label for="stock">Stock:</label><br>
        <input type="number" id="stock" name="stock" value="<?php echo $product['stock']; ?>" required><br><br>

        <label for="category">Category:</label><br>
        <select id="category" name="category">
            <option value="Sofas & armchairs" <?php if ($product['category'] == 'Sofas & armchairs') echo 'selected'; ?>>Sofas & armchairs</option>
            <option value="Tables & chairs" <?php if ($product['category'] == 'Tables & chairs') echo 'selected'; ?>>Tables & chairs</option>
            <option value="Storage & organisation" <?php if ($product['category'] == 'Storage & organisation') echo 'selected'; ?>>Storage & organisation</option>
            <option value="Office furniture" <?php if ($product['category'] == 'Office furniture') echo 'selected'; ?>>Office furniture</option>
            <option value="Beds & mattresses" <?php if ($product['category'] == 'Beds & mattresses') echo 'selected'; ?>>Beds & mattresses</option>
            <option value="Textiles" <?php if ($product['category'] == 'Textiles') echo 'selected'; ?>>Textiles</option>
            <option value="Rugs & mats & flooring" <?php if ($product['category'] == 'Rugs & mats & flooring') echo 'selected'; ?>>Rugs & mats & flooring</option>
            <option value="Home decoration" <?php if ($product['category'] == 'Home decoration') echo 'selected'; ?>>Home decoration</option>
            <option value="Lightning" <?php if ($product['category'] == 'Lightning') echo 'selected'; ?>>Lightning</option>
        </select><br><br>

        <label for="image_url">Image URL:</label><br>
        <input type="file" id="image_url" name="image_url[]" accept="image/*" multiple><br><br>
        <div id="drop_zone">Drag and drop images here</div>
        <div id="imagePreview"></div><br><br>

        <label for="status">Status:</label><br>
        <select id="status" name="status">
            <option value="active" <?php if ($product['status'] == 'active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if ($product['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
            <option value="discontinued" <?php if ($product['status'] == 'discontinued') echo 'selected'; ?>>Discontinued</option>
        </select><br><br>

        <label for="discount">Discount(%):</label><br>
        <input type="number" step="0.01" id="discount" name="discount" value="<?php echo $product['discount']; ?>"><br><br>

        <label for="discounted_price">Discounted Price:</label><br>
        <input type="text" id="discounted_price" name="discounted_price" value="<?php echo $product['discounted_price']; ?>" readonly><br><br>

        <label for="weight">Weight:</label><br>
        <input type="number" step="0.01" id="weight" name="weight" value="<?php echo $product['weight']; ?>"><br><br>

        <label for="length">Length:</label><br>
        <input type="number" step="0.01" id="length" name="length" value="<?php echo $product['length']; ?>"><br><br>

        <label for="width">Width:</label><br>
        <input type="number" step="0.01" id="width" name="width" value="<?php echo $product['width']; ?>"><br><br>

        <label for="height">Height:</label><br>
        <input type="number" step="0.01" id="height" name="height" value="<?php echo $product['height']; ?>"><br><br>

        <label for="brand">Brand:</label><br>
        <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>"><br><br>

        <label for="color">Color:</label><br>
        <input type="text" id="color" name="color" value="<?php echo htmlspecialchars($product['color']); ?>"><br><br>

        <label for="rating">Rating:</label><br>
        <input type="number" step="0.01" id="rating" name="rating" value="<?php echo $product['rating']; ?>"><br><br>

        <label for="reviews_count">Reviews Count:</label><br>
        <input type="number" id="reviews_count" name="reviews_count" value="<?php echo $product['reviews_count']; ?>"><br><br>

        <input type="submit" value="Update Product">
    </form>
    <button onclick="window.location.href='adminProduct.php'">Back to Product List</button>

    <script>
        $(document).ready(function() {
            let previousImageSrc = ""; // To store the previous image source

            function handleFiles(files) {
                $('#imagePreview').empty();
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var img = $('<img>').attr('src', e.target.result);
                        var preview = $('<div>').addClass('image-preview').append(img);
                        $('#imagePreview').append(preview);
                        console.log('File read:', e.target.result); // Log the file data
                    }
                    reader.readAsDataURL(file);
                }
            }

            // Display existing images
            var existingImages = <?php echo json_encode(json_decode($product['image_url'], true)); ?>;
            if (Array.isArray(existingImages)) {
                existingImages.forEach(function(imageUrl) {
                    var img = $('<img>').attr('src', imageUrl);
                    var preview = $('<div>').addClass('image-preview').append(img);
                    $('#imagePreview').append(preview);
                    console.log('Existing image:', imageUrl); // Log the existing image URL
                });
            }

            // Drag and drop functionality
            var dropZone = $('#drop_zone');
            var fileInput = $('#image_url');

            dropZone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.addClass('dragover');
                console.log('Drag over'); // Log the drag over event
            });

            dropZone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('dragover');
                console.log('Drag leave'); // Log the drag leave event
            });

            dropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('dragover');
                var files = e.originalEvent.dataTransfer.files;
                handleFiles(files);
                var dataTransfer = new DataTransfer();
                for (var i = 0; i < files.length; i++) {
                    dataTransfer.items.add(files[i]);
                }
                fileInput[0].files = dataTransfer.files;
                console.log('Files dropped:', files); // Log the dropped files
            });

            $("#image_url").change(function() {
                handleFiles(this.files);
                console.log('Files selected:', this.files); // Log the selected files
            });

            $("#updateProductForm").submit(function(e) {
                e.preventDefault(); // Prevent default form submission
                console.log('Form submission intercepted'); // Log form submission interception
                var formData = new FormData(this);
                var files = $('#image_url')[0].files;
                for (var i = 0; i < files.length; i++) {
                    formData.append('image_url[]', files[i]);
                }
                console.log('Form data:', formData); // Log the form data
                $.ajax({
                    url: $(this).attr("action"),
                    type: $(this).attr("method"),
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Form submitted successfully:', response); // Log the success response
                        // window.location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error: " + error); // Log the error
                        console.log('XHR:', xhr); // Log the XHR object
                        console.log('Status:', status); // Log the status
                        console.log('Error:', error); // Log the error message
                    }
                });
            });

            var fileInput = $('#image_url');

            // Prevent duplicate files in the input
            fileInput.on('change', function() {
                var files = Array.from(this.files);
                var uniqueFiles = [];
                var fileNames = new Set();

                files.forEach(file => {
                    if (!fileNames.has(file.name)) {
                        uniqueFiles.push(file);
                        fileNames.add(file.name);
                    }
                });

                var dataTransfer = new DataTransfer();
                uniqueFiles.forEach(file => dataTransfer.items.add(file));
                this.files = dataTransfer.files;

                console.log('Unique files:', this.files);
            });
        });
    </script>
</body>
</html>