<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    ob_start();

    include 'connection.php';

    if ($connection->connect_error) {
        ob_end_clean();
        echo json_encode(["error" => "Database connection failed: " . $connection->connect_error]);
        exit;
    }

    $modulesQuery = "SELECT Modules_Name, Module_Image FROM modules";
    $modulesResult = $connection->query($modulesQuery);
    $modules = [];

    if ($modulesResult && $modulesResult->num_rows > 0) {
        while ($row = $modulesResult->fetch_assoc()) {
            if (!empty($row['Module_Image'])) {
                $row['Module_Image'] = "data:image/jpeg;base64," . base64_encode($row['Module_Image']);
            } else {
                $row['Module_Image'] = "placeholder.jpg";
            }
            $row['category'] = "Module";
            $modules[] = $row;
        }
    }

    $quizzesQuery = "SELECT Quiz_Name, Quizz_Image FROM quizzes";
    $quizzesResult = $connection->query($quizzesQuery);
    $quizzes = [];

    if ($quizzesResult && $quizzesResult->num_rows > 0) {
        while ($row = $quizzesResult->fetch_assoc()) {
            if (!empty($row['Quizz_Image'])) {
                $row['Quizz_Image'] = "data:image/jpeg;base64," . base64_encode($row['Quizz_Image']);
            } else {
                $row['Quizz_Image'] = "placeholder.jpg";
            }
            $row['category'] = "Quiz";
            $quizzes[] = $row;
        }
    }

    $resourcesQuery = "SELECT Resource_Name, Resource_Image FROM study_resources";
    $resourcesResult = $connection->query($resourcesQuery);
    $resources = [];

    if ($resourcesResult && $resourcesResult->num_rows > 0) {
        while ($row = $resourcesResult->fetch_assoc()) {
            if (!empty($row['Resource_Image'])) {
                $row['Resource_Image'] = "data:image/jpeg;base64," . base64_encode($row['Resource_Image']);
            } else {
                $row['Resource_Image'] = "placeholder.jpg";
            }
            $row['category'] = "Study Material";
            $resources[] = $row;
        }
    }

    $allData = array_merge($modules, $quizzes, $resources);

    ob_end_clean();
    echo json_encode($allData, JSON_PRETTY_PRINT);

    $connection->close();
?>
