<?php
    session_start();
    header('Content-Type: application/json; charset=UTF-8');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $database_file = __DIR__ . '/connection.php';
    if (!file_exists($database_file)) {
        echo json_encode(["error" => "Database connection file not found."]);
        exit;
    }

    include($database_file);

    if (!isset($connection)) {
        echo json_encode(["error" => "Database connection failed."]);
        exit;
    }

    $user_id = $_SESSION['userid'] ?? null;
    if (!$user_id) {
        echo json_encode(["error" => "User not logged in."]);
        exit;
    }

    $quiz_name = isset($_GET['quiz_name']) ? urldecode($_GET['quiz_name']) : '';
    if (empty($quiz_name)) {
        echo json_encode(["error" => "Quiz name is required."]);
        exit;
    }

    $query = "SELECT * FROM quizzes WHERE Quiz_Name = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $quiz_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $quizData = $result->fetch_assoc();
        $quiz_id = $quizData['Quiz_ID'];

        $attemptQuery = "SELECT COUNT(*) AS hasAttemptQuiz 
                        FROM activity_summary 
                        INNER JOIN users ON users.User_ID = activity_summary.User_ID 
                        INNER JOIN quizzes ON quizzes.Quiz_ID = activity_summary.Quiz_ID 
                        WHERE activity_summary.User_ID = ? 
                        AND (Activity_Type = 'on-going' OR Activity_Type = 'completed')
                        AND activity_summary.Quiz_ID = ?";
                        
        $stmt_attempt = $connection->prepare($attemptQuery);
        $stmt_attempt->bind_param("ii", $user_id, $quiz_id);
        $stmt_attempt->execute();
        $attemptResult = $stmt_attempt->get_result();
        $attemptData = $attemptResult->fetch_assoc();

        $quizData['hasAttemptQuiz'] = ($attemptData && $attemptData['hasAttemptQuiz'] > 0) ? true : false;

        array_walk_recursive($quizData, function (&$value) {
            if (is_string($value)) {
                $value = mb_convert_encoding($value, "UTF-8", "auto"); 
            }
        });

        $json = json_encode($quizData, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(["error" => "JSON Encoding Error: " . json_last_error_msg()]);
            exit;
        }

        echo $json;
    } else {
        echo json_encode(["error" => "Quiz not found."]);
    }
?>
