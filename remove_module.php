<?php
    session_start();
    require_once 'connection.php';

    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['module_name']) || !isset($_SESSION['userid'])) {
        echo json_encode(["error" => "Invalid request."]);
        exit();
    }

    $userId = $_SESSION['userid'];
    $moduleName = trim(urldecode($data['module_name']));

    $query = "DELETE FROM course_enrollment WHERE User_ID = ? AND Modules_Name = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("is", $userId, $moduleName);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Failed to remove module."]);
    }
?>
