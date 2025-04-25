<?php
    header('Content-Type: application/json'); 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include('connection.php');

    $materials_name = $_GET['materials_name'] ?? '';
    if (empty($materials_name)) {
        echo json_encode(["error" => "Materials name is required."]);
        exit;
    }

    $query = "SELECT Resource_ID, Resource_Name, Modules_Name, Resource_Description, Resource_Image, Resource_Link FROM study_resources WHERE Resource_Name = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $materials_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $materialsData = $result->fetch_assoc();

        if (!empty($materialsData['Resource_Image'])) {
            $materialsData['Resource_Image'] = "data:image/jpeg;base64," . base64_encode($materialsData['Resource_Image']);
        }

        echo json_encode($materialsData);
    } else {
        echo json_encode(["error" => "Study material not found."]);
    }
    exit;
?>
