<?php
    session_start();
    date_default_timezone_set('Asia/Kuala_Lumpur');
    include('connection.php');
    
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $connection->query("SET time_zone = '+08:00'");
    if (!isset($_SESSION['start_time_module'])) {
        $_SESSION['start_time_module'] = time();
    }
    $start_time = $_SESSION['start_time_module'];
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $module_name = $_POST['module_name'];
        $module_description = $_POST['module_description'];
    
        if (isset($_FILES['module_image']) && $_FILES['module_image']['error'] === 0) {
            if ($_FILES['module_image']['size'] > 5000000) { 
                echo "<script>alert('File is too large. Maximum allowed size is 5MB.'); window.history.back();</script>";
                exit();
            }
            $module_image = file_get_contents($_FILES['module_image']['tmp_name']);
        }
    
        $stmt = $connection->prepare("INSERT INTO modules (Modules_Name, Description, Module_Image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $module_name, $module_description, $module_image);
    
        try {
            $stmt->execute();
        
            $completion_time = time();
            $user_id = $_SESSION['userid'];
            $activity_type = "created";
    
            $activity_query = "INSERT INTO activity_summary (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp)
                            VALUES (?, NULL, NULL, ?, ?, ?, ?)";
    
            $activity_stmt = $connection->prepare($activity_query);
            $activity_stmt->bind_param('sisss', $module_name, $user_id, $activity_type, date('Y-m-d H:i:s', $start_time), date('Y-m-d H:i:s', $completion_time));
            $activity_stmt->execute();
            $activity_stmt->close();
    
            echo "<script>alert('Module created successfully!'); window.location.href='create_module.php';</script>";
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                echo "<script>alert('Module name already exists! Please enter a different name.'); window.history.back();</script>";
            } else {
                echo "<script>alert('Something went wrong. Please try again.'); window.history.back();</script>";
            }
        }
    
        $stmt->close();
    }
    
    $connection->close();
?>

 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA - Create Module</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            background-color: #fdf3e4;
            color: #333;
        }
 
        .main {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
 
        .create-module-container {
            width: 100%;
            max-width: 800px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
 
        .create-module-container h2 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #b22222;
        }
 
        .input-group {
            margin-bottom: 20px;
        }
 
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
 
        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
 
        .insert-image-button {
            padding: 5px 15px;
            background-color: #ff6347;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
 
       
        .module-image {
            display: flex;
            align-items: center;
            gap: 10px;
        }
 
        .module-image img {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
 
        .module-image button {
            padding: 10px 15px;
            background-color: tomato;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
 
        .module-image button:hover {
            background-color: red;
        }
 
        #delete-image-btn {
            background: none;
            border: none;
            color: rgb(21, 2, 2);
            font-size: 30px;
            cursor: pointer;
        }
 
        #delete-image-btn:hover {
            color: darkred;
        }
 
        .action-buttons {
            margin-top: 20px;
        }
 
        #publish-btn {
            background-color: green;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #publish-btn:hover {
            background-color: darkgreen;
        }
 
        #delete-all-btn {
            background-color: red;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #delete-all-btn:hover {
            background-color: darkred;
        }
 
    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>
    
    <div class="main">
        <div class="create-module-container">
            <h2>Create Module</h2>
            <form id="module-form" action="create_module.php" method="POST" enctype="multipart/form-data">
    
                <div class="input-group">
                    <label for="module-name">Module Name:</label>
                    <input type="text" id="module-name" name="module_name" placeholder="Enter module name" >
                </div>
    
                <div class="input-group">
                    <label for="module-description">Module Description:</label>
                    <textarea id="module-description" name="module_description" rows="4" placeholder="Enter module description" ></textarea>
                </div>
    
                <div class="module-image">
                    <img id="module-image-preview" src="" alt="Insert Module Image" />
                    <button type="button" id="insert-image-btn">Insert Image</button>
                    <button type="button" id="delete-image-btn" title="Remove Image">&#128465;</button>
                    <input type="file" name="module_image" id="image-input" style="display: none;" accept="image/*">
                </div>
    
                <div class="action-buttons">
                    <button type="button" id="delete-all-btn">Delete All</button>
                    <button type="submit" id="publish-btn" name="publish">Publish</button>
                </div>
    
            </form>
        </div>
    </div>
    
    <?php
        include 'footer.php';
    ?>
    
    <script>
        const insertImageBtn = document.getElementById('insert-image-btn');
        const imageInput = document.getElementById('image-input');
        const moduleImagePreview = document.getElementById('module-image-preview');
        const deleteImageBtn = document.getElementById('delete-image-btn');
        const deleteAllBtn = document.getElementById('delete-all-btn');
        const moduleForm = document.getElementById('module-form');
    
        insertImageBtn.addEventListener('click', function() {
            imageInput.click();
        });
    
    
        imageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    moduleImagePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    
        deleteImageBtn.addEventListener('click', () => {
            moduleImagePreview.src = "";
            imageInput.value = "";
        });
    
        deleteAllBtn.addEventListener('click', () => {
            moduleForm.reset();
    
            moduleImagePreview.src = '';
            alert('All fields will be cleared');
        });
    
        document.addEventListener('DOMContentLoaded', function () {
        const publishBtn = document.getElementById('publish-btn');
        const imageInput = document.getElementById('image-input');
        const moduleForm = document.getElementById('module-form');
    
        publishBtn.addEventListener('click', function (event) {
            event.preventDefault();
    
            const moduleName = document.getElementById('module-name').value.trim();
            const moduleDescription = document.getElementById('module-description').value.trim();
            const imageFile = imageInput.files.length > 0;
    
            let errorMessage = "Please fill in the following required fields:\n";
    
            if (!moduleName) errorMessage += "- Module Name is required.\n";
            if (!moduleDescription) errorMessage += "- Module Description is required.\n";
            if (!imageFile) errorMessage += "- Please upload a Module Image.\n";
    
            if (!moduleName || !moduleDescription || !imageFile) {
                alert(errorMessage);
                return;
            }
    
            moduleForm.submit();
        });

    });
    </script>
</body>
</html>