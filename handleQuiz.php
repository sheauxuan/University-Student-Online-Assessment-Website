<?php
    session_start();
    include "connection.php";

    if (!isset($_SESSION['userid']) || !isset($_SESSION['quiz_id']) || !isset($_POST['answerSelection'])) {
        echo json_encode([
            "message" => "Invalid request.",
            "color" => "red",
            "quiz_completed" => false,
            "final" => false
        ]);
        exit;
    }

    $user_id = $_SESSION['userid'];
    $quiz_id = $_SESSION['quiz_id'];
    $selected_answer = $_POST['answerSelection'];
    $isSubjective = isset($_POST['isSubjective']) && $_POST['isSubjective'] == "true";

    $all_questions = [];
    $query = "SELECT * FROM questions WHERE Quiz_ID = '$quiz_id' ORDER BY Question_ID";
    $results = mysqli_query($connection, $query);
    while ($row = mysqli_fetch_assoc($results)) {
        $all_questions[] = $row;
    }
    $total_questions = count($all_questions);

    if ($total_questions == 0) {
        echo json_encode([
            "message" => "No questions found.",
            "color" => "red",
            "quiz_completed" => false,
            "final" => false
        ]);
        exit;
    }

    if (!isset($_SESSION['question_number'])) {
        $_SESSION['question_number'] = 0;
    }

    if (!isset($_SESSION['countOfCorrectAnswer'])) {
        $_SESSION['countOfCorrectAnswer'] = 0;
    }

    $current_question = $all_questions[$_SESSION['question_number']];
    $correct_answer = isset($current_question['Correct_Answer']) ? $current_question['Correct_Answer'] : "";
    $similarity_threshold = isset($current_question['Similarity_Threshold']) ? (int)$current_question['Similarity_Threshold'] : 80;

    if ($isSubjective) {
        similar_text($selected_answer, $correct_answer, $similarity_percentage);
        if ($similarity_percentage >= $similarity_threshold) {
            $_SESSION['countOfCorrectAnswer']++;
            $popout_message = "Correct Answer!";
            $popout_color = "lightgreen";
        } else {
            $popout_message = "Wrong Answer...";
            $popout_color = "lightcoral";
        }
    } else {
        if ($selected_answer == $correct_answer) {
            $_SESSION['countOfCorrectAnswer']++;
            $popout_message = "Correct Answer!";
            $popout_color = "lightgreen";
        } else {
            $popout_message = "Wrong Answer...";
            $popout_color = "lightcoral";
        }
    }

    if ($_SESSION['question_number'] == $total_questions - 1) {
        $quiz_completed = true;
        $question_number = $_SESSION['question_number']+1; 
        $completion_percentage = ($question_number / $total_questions) * 100;
        
        if (isset($current_question) && is_array($current_question)) {
            $correct_answer = $current_question['Correct_Answer'];
        } else {
            echo json_encode([
                "message" => "Error: Current question is not valid.",
                "color" => "red",
                "quiz_completed" => false,
                "final" => false
            ]);
            exit;
        }
        
        $query2 = "SELECT * FROM progress WHERE User_ID = $user_id AND Quiz_ID = $quiz_id";
        $results2 = mysqli_query($connection, $query2);
        if (!mysqli_num_rows($results2) > 0) {
            $new_query = "INSERT INTO progress (User_ID, Quiz_ID, Question_Answered, Completion_Percentage, Starting_Timestamp, Completion_Timestamp)
                        VALUES ($user_id, $quiz_id, $question_number+1, $completion_percentage, NOW(), NOW())";
            mysqli_query($connection, $new_query);
        } else {
            $new_progress_query = "UPDATE progress
                                    SET Question_Answered = $question_number+1,
                                        Completion_Percentage = $completion_percentage,
                                        Completion_Timestamp = NULL
                                    WHERE User_ID = $user_id AND Quiz_ID = $quiz_id";
            mysqli_query($connection, $new_progress_query);
        }
        
        $query3 = "SELECT * FROM activity_summary
                WHERE User_ID = $user_id AND Quiz_ID = $quiz_id 
                    AND (Activity_Type = 'on-going' OR Activity_Type = 'completed')";
        $results3 = mysqli_query($connection, $query3);
        if (mysqli_num_rows($results3) > 0) {
            $update_activity = "UPDATE activity_summary
                                SET Activity_Type = 'on-going',
                                    Completion_Timestamp = NOW()
                                WHERE User_ID = $user_id AND Quiz_ID = $quiz_id
                                AND (Activity_Type = 'on-going' OR Activity_Type = 'completed')";
            mysqli_query($connection, $update_activity);
        } else {
            $modules_name = isset($_SESSION['modules_name']) ? $_SESSION['modules_name'] : '';
            $new_activity_progress = "INSERT INTO activity_summary
                                    (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp)
                                    VALUES ('$modules_name', $quiz_id, NULL, $user_id, 'on-going', NOW(), NOW())";
            mysqli_query($connection, $new_activity_progress);
        }
        
    } else {
        $quiz_completed = false;
        $question_number = $_SESSION['question_number'] + 1;
        $completion_percentage = ($question_number / $total_questions) * 100;
        
        if (isset($current_question) && is_array($current_question)) {
            $correct_answer = $current_question['Correct_Answer'];
        } else {
            echo json_encode([
                "message" => "Error: Current question is not valid.",
                "color" => "red",
                "quiz_completed" => false,
                "final" => false
            ]);
            exit;
        }
        
        if ($_SESSION['question_number'] < $total_questions - 1) {
            $_SESSION['question_number']++;
        }

        $query2 = "SELECT * FROM progress WHERE User_ID = $user_id AND Quiz_ID = $quiz_id";
        $results2 = mysqli_query($connection, $query2);
        if (!mysqli_num_rows($results2) > 0) {
            $new_query = "INSERT INTO progress (User_ID, Quiz_ID, Question_Answered, Completion_Percentage, Starting_Timestamp, Completion_Timestamp)
                        VALUES ($user_id, $quiz_id, $question_number, $completion_percentage, NOW(), NULL)";
            mysqli_query($connection, $new_query);
        } else {
            $new_progress_query = "UPDATE progress
                                    SET Question_Answered = $question_number,
                                        Completion_Percentage = $completion_percentage,
                                        Completion_Timestamp = NULL
                                    WHERE User_ID = $user_id AND Quiz_ID = $quiz_id";
            mysqli_query($connection, $new_progress_query);
        }
        
        $query3 = "SELECT * FROM activity_summary WHERE User_ID = $user_id AND Quiz_ID = $quiz_id
                AND (Activity_Type = 'on-going' OR Activity_Type = 'completed')";
        $results3 = mysqli_query($connection, $query3);
        if (mysqli_num_rows($results3) > 0) {
            $update_activity = "UPDATE activity_summary
                                SET Activity_Type = 'on-going',
                                    Starting_Timestamp = NOW(),
                                    Completion_Timestamp = NULL
                                WHERE User_ID = $user_id AND Quiz_ID = $quiz_id
                                AND (Activity_Type = 'on-going' OR Activity_Type = 'completed')";
            mysqli_query($connection, $update_activity);
        } else {
            $modules_name = isset($_SESSION['modules_name']) ? $_SESSION['modules_name'] : '';
            $new_activity_progress = "INSERT INTO activity_summary
                                    (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp)
                                    VALUES ('$modules_name', $quiz_id, NULL, $user_id, 'on-going', NOW(), NULL)";
            mysqli_query($connection, $new_activity_progress);
        }
    }

    if ($_SESSION['question_number'] == $total_questions - 1) {
        $activity_table_query = "SELECT * FROM activity_summary
                                WHERE Quiz_ID = $quiz_id AND User_ID = $user_id AND Activity_Type = 'on-going'";
        $activity_table_result = mysqli_query($connection, $activity_table_query);
        if (mysqli_num_rows($activity_table_result) > 0) {
            $update_activity_progress = "UPDATE activity_summary
                                        SET Activity_Type = 'completed',
                                            Completion_Timestamp = NOW()
                                        WHERE User_ID = $user_id AND Quiz_ID = $quiz_id AND Activity_Type = 'on-going'";
            mysqli_query($connection, $update_activity_progress);
        }
        
        $update_progress_table = "SELECT * FROM progress
                                WHERE User_ID = $user_id AND Quiz_ID = $quiz_id AND Completion_Timestamp IS NULL";
        $results5 = mysqli_query($connection, $update_progress_table);
        if (mysqli_num_rows($results5) > 0) {
            $update_progress_table2 = "UPDATE progress
                                    SET Completion_Timestamp = NOW()
                                    WHERE User_ID = $user_id AND Quiz_ID = $quiz_id AND Completion_Timestamp IS NULL";
            mysqli_query($connection, $update_progress_table2);
        }

        $getDuration = "SELECT TIMESTAMPDIFF(SECOND, Starting_Timestamp, Completion_Timestamp) AS durationSecond
                        FROM activity_summary
                        WHERE Quiz_ID = $quiz_id AND User_ID = $user_id AND Completion_Timestamp IS NOT NULL";
        $results7 = mysqli_query($connection, $getDuration);
        if (mysqli_num_rows($results7) > 0) {
            while ($row = mysqli_fetch_assoc($results7)) {
                $duration = $row['durationSecond'];
            }
        } else {
            $duration = 0;
        }
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        $timeDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        $_SESSION['$time'] = $timeDuration;

        $totalScore = $_SESSION['countOfCorrectAnswer'] * 100;
        $_SESSION['$totalScore'] = $totalScore;
        $checkUserExistLeaderboard = "SELECT * FROM quiz_leaderboard WHERE Quiz_ID = $quiz_id AND User_ID = $user_id";
        $results8 = mysqli_query($connection, $checkUserExistLeaderboard);
        if (mysqli_num_rows($results8) > 0) {
            $updateLeaderboardTable = "UPDATE quiz_leaderboard
                                    SET Score = $totalScore, Duration = '$timeDuration'
                                    WHERE Quiz_ID = $quiz_id AND User_ID = $user_id";
            mysqli_query($connection, $updateLeaderboardTable);
        } else {
            $insertLeaderboardTable = "INSERT INTO quiz_leaderboard
                                    (Quiz_ID, User_ID, Score, Duration)
                                    VALUES ($quiz_id, $user_id, $totalScore, '$timeDuration')";
            mysqli_query($connection, $insertLeaderboardTable);
        }

    }

    if ($quiz_completed) {
        echo json_encode([
            "message" => $popout_message,
            "color" => $popout_color,
            "quiz_completed" => true,
            "final" => true
        ]);

        $_SESSION['quiz_completed'] = true;  
        exit;
    } else {
        echo json_encode([
            "message" => $popout_message,
            "color" => $popout_color,
            "quiz_completed" => false,
            "final" => false
        ]);
    }
    exit;
?>
