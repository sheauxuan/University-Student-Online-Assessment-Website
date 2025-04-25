<?php
    include 'connection.php'; 

    header('Content-Type: application/json');

    if (!isset($_GET['user_id'])) {
        echo json_encode(["error" => "User ID is required."]);
        exit();
    }

    $user_id = intval($_GET['user_id']);

    $query = "SELECT ce.Modules_Name, m.Module_Image 
            FROM course_enrollment ce
            JOIN modules m ON ce.Modules_Name = m.Modules_Name
            WHERE ce.User_ID = ?";
    $stmt = $connection->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
?>
