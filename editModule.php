<?php
    session_start();
    include('connection.php');
    error_log("Received POST: " . json_encode($_POST));

    date_default_timezone_set('Asia/Kuala_Lumpur');
    $connection->query("SET time_zone = '+08:00'");

    if (!isset($_SESSION['start_time_module'])) {
        $_SESSION['start_time_module'] = time();
    }

    $start_time = date('Y-m-d H:i:s', $_SESSION['start_time_module']);

    try {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST['old_module_name'])) {
                $module_name = $_POST['old_module_name'];
            } elseif (!empty($_POST['new_module_name'])) {
                $module_name = $_POST['new_module_name'];
            } elseif (!empty($_POST['module_name'])) {
                $module_name = $_POST['module_name'];
            }
        } elseif (!empty($_GET['modulesName'])) {
            $module_name = $_GET['modulesName'];
        }
    
        error_log("Received Module Name: " . ($module_name ?? "NULL"));
    
        if (!$module_name) {
            throw new Exception("Module name not specified.");
        }
    
        $getModuleQuery = "SELECT Modules_Name, Description, Module_Image FROM modules WHERE Modules_Name = ?";
        $stmt = $connection->prepare($getModuleQuery);
        $stmt->bind_param("s", $module_name);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            throw new Exception("Module not found.");
        }
    
        $module = $result->fetch_assoc();
        $module_name = $module['Modules_Name'];
        $module_description = trim($module['Description']);
        $module_image = $module['Module_Image'];
        $old_module_image_base64 = base64_encode($module_image);
        $editModuleImageSrc = !empty($module_image) ? "data:image/png;base64," . $old_module_image_base64 : "";
    
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['delete_module'])) {
                $connection->begin_transaction();
                
                $delete_activity_query = "DELETE FROM activity_summary WHERE Modules_Name = ?";
                $stmt = $connection->prepare($delete_activity_query);
                $stmt->bind_param('s', $module_name);
                $stmt->execute();
                
                $deleteResourcesQuery = "DELETE FROM study_resources WHERE Modules_Name = ?";
                $stmt = $connection->prepare($deleteResourcesQuery);
                $stmt->bind_param("s", $module_name);
                $stmt->execute();
                
                $delete_query = "DELETE FROM modules WHERE Modules_Name = ?";
                $stmt = $connection->prepare($delete_query);
                $stmt->bind_param('s', $module_name);
                if ($stmt->execute()) {
                    $connection->commit();
                    echo json_encode(['success' => true, 'message' => 'Module deleted successfully.']);
                } else {
                    throw new Exception("Failed to delete module.");
                }
                exit;
            }
    
            if (isset($_POST['update_module'])) {
                try {
                    $connection->begin_transaction();
            
                    $old_module_name = $_POST['old_module_name'];
                    $new_module_name = $_POST['new_module_name'];
                    $module_description = $_POST['module_description'];
                    $module_image = null;
                    $user_id = $_SESSION['userid'];
            
                    if (isset($_FILES['module_image']) && $_FILES['module_image']['error'] === 0) {
                        $file_type = mime_content_type($_FILES['module_image']['tmp_name']);
                        if (!in_array($file_type, ['image/png', 'image/jpeg'])) {
                            throw new Exception("Invalid file type. Only PNG and JPEG are allowed.");
                        }
                        $module_image = file_get_contents($_FILES['module_image']['tmp_name']);
                    }

                    $connection->query("SET FOREIGN_KEY_CHECKS = 0");

                    $update_module_query = "UPDATE modules SET Modules_Name = ?, Description = ?, Module_Image = ? WHERE Modules_Name = ?";
                    $stmt1 = $connection->prepare($update_module_query);
                    $stmt1->bind_param("ssss", $new_module_name, $module_description, $module_image, $old_module_name);

                    if ($module_image !== null) {
                        $stmt1->send_long_data(2, $module_image); 
                    }

                    if (!$stmt1->execute()) {
                        throw new Exception("Failed to update module: " . $stmt1->error);
                    }
                    $stmt1->close();

                    $update_resources_query = "UPDATE study_resources SET Modules_Name = ? WHERE Modules_Name = ?";
                    $stmt2 = $connection->prepare($update_resources_query);
                    $stmt2->bind_param("ss", $new_module_name, $old_module_name);
                    if (!$stmt2->execute()) {
                        throw new Exception("Failed to update study_resources: " . $stmt2->error);
                    }
                    $stmt2->close();

                    $update_activity_query = "UPDATE activity_summary SET Modules_Name = ? WHERE Modules_Name = ?";
                    $stmt3 = $connection->prepare($update_activity_query);
                    $stmt3->bind_param("ss", $new_module_name, $old_module_name);
                    if (!$stmt3->execute()) {
                        throw new Exception("Failed to update activity_summary: " . $stmt3->error);
                    }
                    $stmt3->close();

                    $start_time = $_SESSION['start_time_module'] ?? time();
                    $start_time_formatted = date('Y-m-d H:i:s', $start_time);
                    $completion_time = date('Y-m-d H:i:s');
                    $activity_type = "edit";

                    $log_activity_query = "INSERT INTO activity_summary (Modules_Name, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp)
                                        VALUES (?, ?, ?, ?, ?)";
                    $stmt4 = $connection->prepare($log_activity_query);
                    $stmt4->bind_param("sisss", $new_module_name, $user_id, $activity_type, $start_time_formatted, $completion_time);
                    if (!$stmt4->execute()) {
                        throw new Exception("Failed to log activity: " . $stmt4->error);
                    }
                    $stmt4->close();

                    $connection->query("SET FOREIGN_KEY_CHECKS = 1");

                    $connection->commit();
                    echo json_encode(['success' => true, 'message' => 'Module published successfully.']);
                    exit;
                } catch (Exception $e) {
                    $connection->rollback();
                    $connection->query("SET FOREIGN_KEY_CHECKS = 1"); 
                    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                    exit;
                }
            }    
        }
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA: Edit Module</title>
    <link rel="stylesheet" href="main.css">
    <style>
        .main {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .edit-module-container {
            width: 100%;
            max-width: 800px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
 
        .edit-module-container h2 {
            font-size: 24px;
            margin-bottom: 15px;
            color: red;
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
 
        .insert-image-button:hover {
            background-color: #e55342;
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
 
        #publishBtn {
            background-color: green;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #publishBtn:hover {
            background-color: darkgreen;
        }

        #delete-module-btn {
            background-color: red;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #delete-module-btn:hover {
            background-color: darkred;
        }
 
    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>
    
    <div class="main">
        <div class="edit-module-container">
            <h2>Edit Module</h2>
            <form action="editModule.php" method="POST" enctype="multipart/form-data">
    
                <div class="input-group">
                    <label for="module-name">Module Name:</label>
                    <input type="text" id="module-name" name="new_module_name" placeholder="Enter module name" value="<?php echo $module_name; ?>">
                    <input type="hidden" name="old_module_name" value="<?php echo $module_name ?>">
                </div>
        
                <div class="input-group">
                    <label for="module-description">Module Description:</label>
                    <textarea id="module-description" name="new_module_description" rows="4" placeholder="Enter module description"><?php echo $module_description; ?></textarea>
                </div>
        
                <div class="module-image">
                    <img id="module-image-preview" src="" alt="Module Display Image" />
                    <button type="button" id="insert-image-btn">Insert Image</button>
                    <button type="button" id="delete-image-btn" title="Remove Image">&#128465;</button>
                    <input type="file" name="module_image" id="image-input" style="display: none;" accept="image/*">
                </div>
        
                <div class="action-buttons">
                    <button type="button" id="delete-module-btn">Delete Module</button>
                    <input type="hidden" name="module-name" value="<?php echo $module_name ?>">
                    <button class="publish" id="publishBtn" name="update_module">Publish</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
        include 'footer.php';
    ?>

    <script>
        const editModuleImageSrc = "<?php echo $editModuleImageSrc ?>";
        const insertImageBtn = document.getElementById('insert-image-btn');
        const imageInput = document.getElementById('image-input');
        const moduleImagePreview = document.getElementById('module-image-preview');
        const deleteImageBtn = document.getElementById('delete-image-btn');
    
        if (editModuleImageSrc && editModuleImageSrc !== "data:image/png;base64,") {
            moduleImagePreview.src = editModuleImageSrc;
        }

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
    
        document.addEventListener('DOMContentLoaded', function () {
            const deleteModuleBtn = document.getElementById('delete-module-btn');
            const moduleName = document.querySelector('input[name="old_module_name"]').value; 

            if (!moduleName) {
                console.error("Module name is not defined.");
            }
            deleteModuleBtn.addEventListener('click', () => {
                if (confirm("Are you sure you want to delete this module?")) {
                    let formData = new URLSearchParams();
                    formData.append("delete_module", "true");
                    formData.append("module_name", moduleName);
                    console.log("Deleting module:", moduleName);

                    fetch('editModule.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Server Response:", data);
                        if (!data.success) {
                            alert(data.message); 
                        } else {
                            console.log('Success:', data);
                            window.location.href = "index.php"; 
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred. Please try again.");
                    });
                }   
            });

            const publishBtn = document.getElementById('publishBtn');
    
            publishBtn.addEventListener('click', function (event) {
                event.preventDefault(); 

                const newModuleName = document.getElementById('module-name').value.trim();
                const moduleDescription = document.getElementById('module-description').value.trim();
                const oldModuleName = document.querySelector('input[name="old_module_name"]').value.trim();
                const imageFile = imageInput.files[0]; 

                if (!newModuleName || !moduleDescription || (!imageFile && !editModuleImageSrc)) {
                    alert("All fields are required!");
                    return;
                }

                const formData = new FormData();
                formData.append('new_module_name', newModuleName);
                formData.append('old_module_name', oldModuleName);
                formData.append('module_description', moduleDescription);
                formData.append('update_module', 'true');

                if (imageFile) {
                    formData.append('module_image', imageFile);
                } else {
                    formData.append('use_existing_image', 'true');
                }

                fetch('editModule.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message); 
                    } else {
                        alert(data.message); 
                        window.location.href = 'editModule.php?modulesName=' + encodeURIComponent(newModuleName);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('An error occurred. Please try again.');
                });

            });
    
            function appendHiddenField(form, name, value) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = name;
                hiddenInput.value = value;
                form.appendChild(hiddenInput);
            }
        });
    </script>
</body>
</html>