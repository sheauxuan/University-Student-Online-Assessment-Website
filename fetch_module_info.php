<?php
    header('Content-Type: application/json; charset=UTF-8');
    ob_clean();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (!file_exists('connection.php')) {
        echo json_encode(["error" => "Database connection file is missing."]);
        exit;
    }

    include 'connection.php'; 

    if (!isset($connection)) {
        echo json_encode(["error" => "Database connection failed."]);
        exit;
    }

    $moduleName = $_GET['name'];

    function sanitize($value) {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    $stmt = $connection->prepare("SELECT Description FROM modules WHERE Modules_Name = ?");
    $stmt->bind_param("s", $moduleName);
    $stmt->execute();
    $result = $stmt->get_result();
    $module = $result->fetch_assoc();

    if (!$module) {
        error_log("Module not found: " . $moduleName);
        echo json_encode(["error" => "Module not found."]);
        exit;
    }

    $stmt = $connection->prepare("SELECT Quiz_Name, Quizz_Image FROM quizzes WHERE Modules_Name = ?");
    $stmt->bind_param("s", $moduleName);
    $stmt->execute();
    $quizzesResult = $stmt->get_result();
    $quizzes = [];

    while ($row = $quizzesResult->fetch_assoc()) {
        $quizzes[] = [
            "Quiz_Name" => sanitize($row["Quiz_Name"]),
            "Quizz_Image" => !empty($row["Quizz_Image"]) ? "data:image/png;base64," . base64_encode($row["Quizz_Image"]) : "http://localhost/RWWWD/default-placeholder.jpg"
        ];
    }

    $stmt = $connection->prepare("SELECT Resource_Name, Resource_Image FROM study_resources WHERE Modules_Name = ?");
    $stmt->bind_param("s", $moduleName);
    $stmt->execute();
    $materialsResult = $stmt->get_result();
    $studyMaterials = [];

    while ($row = $materialsResult->fetch_assoc()) {
        $studyMaterials[] = [
            "Resource_Name" => sanitize($row["Resource_Name"]),
            "Resource_Image" => !empty($row["Resource_Image"]) ? "data:image/png;base64," . base64_encode($row["Resource_Image"]) : "http://localhost/RWWWD/default-placeholder.jpg"
        ];
    }

    $response = [
        "module_name" => sanitize($moduleName),
        "description" => sanitize($module["Description"] ?? "No description available."),
        "quizzes" => $quizzes,
        "studyMaterials" => $studyMaterials
    ];

    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($json === false) {
        error_log("JSON Encoding Error: " . json_last_error_msg());
        die(json_encode(["error" => "JSON Encoding Error: " . json_last_error_msg()]));
    }

    echo $json;
    $connection->close();
?>
