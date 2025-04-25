<?php
    session_start();
    header('Content-Type: application/json');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    require_once 'connection.php';

    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['user_id']) || !isset($data['module_name'])) {
        echo json_encode(["error" => "Invalid request data."]);
        exit();
    }

    $userId = intval($data['user_id']);
    $moduleName = trim(urldecode($data['module_name'])); 

    $checkModuleQuery = "SELECT Modules_Name FROM modules WHERE BINARY TRIM(Modules_Name) = TRIM(?)";
    $stmt = $connection->prepare($checkModuleQuery);
    $stmt->bind_param("s", $moduleName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("Module not found: " . $moduleName);
        echo json_encode(["error" => "Module not found in the system."]);
        exit();
    }

    $checkEnrollmentQuery = "SELECT * FROM course_enrollment WHERE User_ID = ? AND Modules_Name = ?";
    $stmt = $connection->prepare($checkEnrollmentQuery);
    $stmt->bind_param("is", $userId, $moduleName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["error" => "User is already enrolled in this module."]);
        exit();
    }

    $insertQuery = "INSERT INTO course_enrollment (User_ID, Modules_Name) VALUES (?, ?)";
    $stmt = $connection->prepare($insertQuery);
    $stmt->bind_param("is", $userId, $moduleName);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Module successfully added!"]);
    } else {
        echo json_encode(["error" => "Failed to add module."]);
    }
?>
