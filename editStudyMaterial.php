<?php
    session_start(); 
    date_default_timezone_set('Asia/Kuala_Lumpur');
    include('connection.php'); 

    if (!isset($_SESSION['start_time_material'])) {
        $_SESSION['start_time_material'] = time();
    }
    $start_time = $_SESSION['start_time_material'];

    if(isset($_GET['resourceID'])){
        $resourceID = $_GET['resourceID'];
    }else{
        echo 'No resourceID get...';
    }

    $getEditStudyMaterial = "SELECT Modules_Name, Resource_Name, Resource_Description, Resource_Image, Resource_Link FROM study_resources
                            WHERE Resource_ID = $resourceID";
    $result = mysqli_query($connection, $getEditStudyMaterial);
    if(mysqli_num_rows($result) > 0){
        while($rows = mysqli_fetch_assoc($result)){
            $old_module_name = $rows['Modules_Name'];
            $old_resource_name = $rows['Resource_Name'];
            $old_resource_description = $rows['Resource_Description'];
            $old_resource_image = $rows['Resource_Image'];
            $old_resource_image_base64 = base64_encode($old_resource_image);
            $old_resource_image_src = "data:image/png;base64," . $old_resource_image_base64;
            $old_resource_link = $rows['Resource_Link'];
        }
    }

    $getModulesNameQuery = "SELECT Modules_Name FROM modules";
    $modulesResult = mysqli_query($connection, $getModulesNameQuery);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (isset($_POST['delete_study_material'])) {
            if (isset($_POST['resourceID'])) {
                $resourceID = $_POST['resourceID'];

                $deleteActivityQuery = "DELETE FROM activity_summary WHERE Resource_ID = ?";
                if ($stmt = $connection->prepare($deleteActivityQuery)) {
                    $stmt->bind_param('i', $resourceID);
                    $stmt->execute();
                    $stmt->close();
                }
        
                $deleteQuery = "DELETE FROM study_resources WHERE Resource_ID = ?";
                if ($stmt = $connection->prepare($deleteQuery)) {
                    $stmt->bind_param('i', $resourceID);
                    if ($stmt->execute()) {
                        echo "<script>alert('Study Material Deleted Successfully!'); window.location.href = 'index.php';</script>";
                    } else {
                        echo "<script>alert('Error deleting study material. Please try again.'); window.history.back();</script>";
                    }
                } else {
                    echo "<script>alert('Error preparing query. Please try again.'); window.history.back();</script>";
                }
            }
        }

        $material_name = $_POST['Material_name'];
        $module_name = $_POST['Modules_Name'];
        $material_description = $_POST['Material_description'];
        $resource_link = $_POST['Resource_link'];
        $material_image = null;
    
        if (isset($_FILES['material_image']) && $_FILES['material_image']['error'] === 0) {
            if ($_FILES['material_image']['size'] > 5000000) {
                die("Error: File size exceeds 5MB limit.");
            }
            $material_image = file_get_contents($_FILES['material_image']['tmp_name']);
        } else {
            $material_image = $old_resource_image;
        }
    
        $updateResource = "UPDATE study_resources 
            SET Modules_Name = ?, Resource_Name = ?, Resource_Description = ?, Resource_Image = ?, Resource_Link = ? 
            WHERE Resource_ID = ?";
    
        $stmt = $connection->prepare($updateResource);
        $stmt->bind_param("sssbsi", $module_name, $material_name, $material_description, $material_image, $resource_link, $resourceID);
        $stmt->send_long_data(3, $material_image); 
        $executeUpdate = $stmt->execute();
    
        if ($executeUpdate) {

            $start_timestamp = date('Y-m-d H:i:s', $_SESSION['start_time_material']);
            $completion_timestamp = date('Y-m-d H:i:s');
            $user_id = $_SESSION['userid'];
            $activity_type = "edit";
    
            $activity_query = "INSERT INTO activity_summary (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp)
                            VALUES (?, NULL, ?, ?, ?, ?, ?)";
    
            if ($activity_stmt = mysqli_prepare($connection, $activity_query)) {
                mysqli_stmt_bind_param($activity_stmt, 'siisss', $module_name, $resourceID, $user_id, $activity_type, $start_timestamp, $completion_timestamp);
                mysqli_stmt_execute($activity_stmt);
                mysqli_stmt_close($activity_stmt);
            }
    
            echo "<script>alert('Study Material Updated successfully!');
            window.location.href = 'editStudyMaterial.php?resourceID=" . $resourceID . "';</script>";

        } else {
            echo "<script>alert('Failed to edit study material. Please try again.');</script>";

        }
    
        $stmt->close();
    }
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA: Edit Study Material</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Almendra+SC&display=swap">
    <style>

        .main {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .section {
            width: 80%;
            max-width: 700px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
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
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: Arial, sans-serif; 
        }

        .create-study-material {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .material-image {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .material-image img {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .material-image button {
            padding: 10px 15px;
            background-color: tomato;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .material-image button:hover {
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
 
        #delete-study-material-btn {
            background-color: red;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #delete-study-material-btn:hover {
            background-color: darkred;
        }

        h2 {
            color: red;
        }

    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>

    <main class="main">
        <div class="section">
            <h2>Edit Study Material</h2>
            <form class="create-study-material" method="POST" action="" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="Material_name">Study Material Name:</label>
                    <input type="text" name="Material_name" placeholder="Enter study material name" value="<?php echo $old_resource_name; ?>">
                </div>

                <div class="input-group">
                    <label for="Modules_Name">Select Module:</label>
                    <select name="Modules_Name" >
                        <option value="" disabled>Select Module</option>
                        <?php
                        while ($row = mysqli_fetch_assoc($modulesResult)) {
                            $selected = ($old_module_name == $row['Modules_Name']) ? "selected" : "";
                            echo '<option value="' . $row['Modules_Name'] . '" ' . $selected . '>' . $row['Modules_Name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="Material_description">Study Material Description:</label>
                    <textarea name="Material_description" placeholder="Enter study material description"><?php echo $old_resource_description; ?></textarea>
                </div>

                <div class="input-group material-image">
                    <img id="material-image-preview" src="" alt="Insert Study Material Image" />
                    <button type="button" id="insert-image-btn">Insert Image</button>
                    <button type="button" id="delete-image-btn" title="Remove Image">&#128465;</button>
                    <input type="file" name="material_image" id="image-input" style="display: none;" accept="image/*">
                </div>

                <div class="input-group">
                    <label for="Resource_link">Resource Link:</label>
                    <input type="text" name="Resource_link" placeholder="Enter resource link" value="<?php echo $old_resource_link; ?>">
                </div>

                <div class="input-group">
                    <button type="button" id="delete-study-material-btn">Delete Study Material</button>
                    <button type="submit" id="publish-btn" name="publish">Publish</button>
                </div>
            </form>
        </div>
        </main>
    <?php
        include 'footer.php';
    ?>

    <script>
        const old_resource_image_src = "<?php echo $old_resource_image_src ?>";

        const insertImageBtn = document.getElementById('insert-image-btn');
        const imageInput = document.getElementById('image-input');
        const materialImagePreview = document.getElementById('material-image-preview'); 
        const deleteImageBtn = document.getElementById('delete-image-btn');
        const deleteAllBtn = document.getElementById('delete-all-btn');

        if (old_resource_image_src && old_resource_image_src !== "data:image/png;base64,") {
            materialImagePreview.src = old_resource_image_src;
        }

        insertImageBtn.addEventListener('click', function() {
            imageInput.click(); 
        });

        imageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    materialImagePreview.src = e.target.result; 
                    materialImagePreview.style.display = 'block'; 
                };
                reader.readAsDataURL(file); 
            }
        });

        deleteImageBtn.addEventListener('click', () => {
            materialImagePreview.src = ""; 
            imageInput.value = ""; 
        });


    document.addEventListener('DOMContentLoaded', function () {
        const deleteStudyMaterialBtn = document.getElementById('delete-study-material-btn');

        deleteStudyMaterialBtn.addEventListener('click', function () {
            const confirmation = confirm("Are you sure you want to delete this study material?");

            if (confirmation) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'resourceID';
                input.value = <?php echo $resourceID; ?>; 
                form.appendChild(input);

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_study_material';
                deleteInput.value = '1'; 
                form.appendChild(deleteInput);

                document.body.appendChild(form);
                form.submit();
            }
        });

        const publishBtn = document.getElementById('publish-btn'); 
        const imageInput = document.getElementById('image-input'); 
        const studyMaterialForm = document.querySelector('.create-study-material'); 

        publishBtn.addEventListener('click', function (event) {
            const materialNameInput = document.querySelector('input[name="Material_name"]');
            const selectedModuleInput = document.querySelector('select[name="Modules_Name"]');
            const materialDescriptionInput = document.querySelector('textarea[name="Material_description"]');
            const resourceLinkInput = document.querySelector('input[name="Resource_link"]');

            const materialName = materialNameInput.value.trim();
            const selectedModule = selectedModuleInput.value;
            const materialDescription = materialDescriptionInput.value.trim();
            const resourceLink = resourceLinkInput.value.trim();

            let errorMessage = "Please fill in the following required fields:\n";
            let hasError = false;

            if (!materialName) {
                errorMessage += "- Study Material Name is required.\n";
                hasError = true;
            }

            if (!selectedModule) {
                errorMessage += "- Please select a Module.\n";
                hasError = true;
            }

            if (!materialDescription) {
                errorMessage += "- Study Material Description is required.\n";
                hasError = true;
            }

            if (!resourceLink) {
                errorMessage += "- Resource Link is required.\n";
                hasError = true;
            }

            if (hasError) {
                alert(errorMessage);
                event.preventDefault();
            } else {
                studyMaterialForm.submit();
            }
        });
    });

    </script>
</body>
</html>