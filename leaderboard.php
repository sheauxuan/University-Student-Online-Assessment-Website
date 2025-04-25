<?php
    session_start();
    include 'connection.php';

    unset($_SESSION['question_number']);
    unset($_SESSION['countOfCorrectAnswer']);
    unset($_SESSION['quiz_completed']); 

    $score = null;
    $currentUser = null;
    $time = null;

    if(isset($_GET['quiz_id'])){
        $quiz_id = $_GET['quiz_id'];
        $_SESSION['quiz_id'] = $quiz_id;
        $skipLeaderboardDataQuery = true;
    } elseif(isset($_SESSION['quiz_id'])) {
        $quiz_id = $_SESSION['quiz_id'];
        $skipLeaderboardDataQuery = false;
    }    

    if(!isset($_SESSION['userid'])){
        echo 'userid is not set!';
    }else{
        $user_id = $_SESSION['userid'];
    }

    if(!$skipLeaderboardDataQuery){
        $getLeaderboardData = "SELECT * FROM quiz_leaderboard
                            WHERE Quiz_ID = $quiz_id AND User_ID = $user_id";
    
        $result = mysqli_query($connection, $getLeaderboardData);
        if(mysqli_num_rows($result) > 0){
            while($row = mysqli_fetch_assoc($result)){
                $score = $row['Score'];
                $time = $row['Duration'];
            }
        }
    }
    
    $sql = "SELECT l.User_ID, u.Username, l.Score, l.Duration
        FROM quiz_leaderboard l
        JOIN users u ON l.User_ID = u.User_ID
        WHERE l.Quiz_ID = '$quiz_id'
        ORDER BY l.Score DESC, l.Duration ASC
        LIMIT 5";
$result = mysqli_query($connection, $sql);


$leaderboard = [];
$prevScore = $prevDuration = null;
$currentRank = 0;
$counter = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $counter++;
    if ($row['Score'] !== $prevScore || $row['Duration'] !== $prevDuration) {
        $currentRank = $counter;
    }
    $leaderboard[] = [
        'Rank' => $currentRank,
        'Username' => $row['Username'],
        'Score' => $row['Score'],
        'Duration' => $row['Duration']
    ];
    $prevScore = $row['Score'];
    $prevDuration = $row['Duration'];
    if ($currentRank >= 5) break;
}

    if(!is_null($score) && !is_null($time)) {
        $userSql = "SELECT u.Username, l.Score, l.Duration, 
                        (SELECT COUNT(*) + 1 FROM quiz_leaderboard l2 
                            WHERE l2.Quiz_ID = l.Quiz_ID AND (l2.Score > l.Score OR (l2.Score = l.Score AND l2.Duration < l.Duration))) AS `user_rank`
                    FROM quiz_leaderboard l
                    JOIN users u ON l.User_ID = u.User_ID
                    WHERE l.User_ID = '$user_id' AND l.Quiz_ID = '$quiz_id' AND l.Score = '$score' AND l.Duration = '$time'";
        $userResult = mysqli_query($connection, $userSql);
        $currentUser = mysqli_fetch_assoc($userResult);
    }

    $questionsSql = "SELECT * FROM questions WHERE Quiz_ID = '$quiz_id'";
    $questionsResult = mysqli_query($connection, $questionsSql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVa Leaderboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fdf3e4;
            color: white;
            text-align: center;
            margin: 0;
        }
 
        .leaderboard-container, .review-container {
            max-width: 45%;
            margin: 3% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
 
        .leaderboard-container {
            background: green;
            color: white;
        }
 
        .review-container {
            max-width: 70%;
            background: rgb(199, 211, 197);
            color: black;
            padding: 2%;
        }
 
        h1 { color:white; }
 
        h2 {
            color: #b22222;
            margin:0;
            text-align:center;
            text-decoration:underline;
        }
 
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            text-align: left;
        }
 
        th, td {
            padding: 12px;
        }
 
        th {
            border-bottom: 1px solid #ddd;
            border-top:1px solid #ddd;
        }
 
        td {
            border: none;
        }
 
        .back-button {
            min-width: 55px;
            width:8%;
            background-color: green;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border: none;
            border-radius: 20px;
            display: inline-block;
            cursor: pointer;
            font-size: 100%;
            text-decoration: underline;
        }
 
        .back-button:hover { background-color: rgb(39, 92, 50); }
 
        .current-user {
            background-color: #b22222;
            font-weight: bold;
            padding: 10px;
            margin-top: 15px;
            border: none;
        }
 
        .correct {
            background-color: #c8e6c9;
            font-weight: bold;
            margin: 1%;
        }
 
        .answer-box {
            padding: 1%;
            background-color: white;
            border-radius: 5px;
            margin: 1.5%;
            text-align: left;
        }
 
        .review-container div {
            text-align: left;
        }
 
        .correct-answer {
            background-color: rgb(67, 115, 66);
            color: white;
        }
 
        .review-questions div { margin: 10px 0; }
 
        hr {
            border: 10%;
            border-top: 1px solid #ddd;
            margin: 3% 0;
        }
 
 
        @media screen and (max-width: 1024px) {
            .leaderboard-container {
                max-width: 70%;
            }
            .review-container {
                max-width: 90%;
            }
        }
 
        @media screen and (max-width: 768px) {
            .leaderboard-container{
                max-width: 80%;
            }
            .review-container {
                max-width: 90%;
            }
        }
 
        @media screen and (max-width: 480px) {
            .leaderboard-container, .review-container {
                max-width: 95%;
                padding: 10px;
            }
 
            th, td {
                padding: 8px;
            }
 
            .back-button {
                width: 30%;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
 
    <div class="leaderboard-container">
        <h1>Leaderboard</h1>
        <table>
            <tr>
                <th>Rank</th>
                <th>Username</th>
                <th>Score</th>
                <th>Time</th>
            </tr>
            <?php
            foreach ($leaderboard as $entry) {
                echo "<tr>";
                echo "<td>{$entry['Rank']}</td>";
                echo "<td>{$entry['Username']}</td>";
                echo "<td>{$entry['Score']}</td>";
                echo "<td>{$entry['Duration']}s</td>";
                echo "</tr>";
            }
            ?>
 
            <tr><td colspan="4">&nbsp;</td></tr>
 
            <?php if (!is_null($currentUser)):?>
                <tr class="current-user">
                    <td><?php echo $currentUser['user_rank']; ?></td>
                    <td><?php echo $currentUser['Username']; ?></td>
                    <td><?php echo $currentUser['Score']; ?></td>
                    <td><?php echo $currentUser['Duration']; ?>s</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    <br>
    <h2>Review Questions</h2>
    <div class="review-container">
        <?php while ($question = mysqli_fetch_assoc($questionsResult)) {
            echo '<div style="margin-left:2%;"><strong>Question:</strong> ' . $question['Question'] . '</div>';
            if ($question['Question_Type'] === 'objective') {
                echo "<div>";
                for ($i = 1; $i <= 4; $i++) {
                    $answer = $question["Answer_$i"];
                    $correctClass = ($answer === $question['Correct_Answer']) ? 'correct-answer' : '';
                    if ($answer) {
                        echo "<div class='answer-box $correctClass'>{$answer}</div>";
                    }
                }
                echo "</div>";
            } else {
                echo "<div class='answer-box correct-answer'><strong>Correct Answer:</strong> <br><br> {$question['Correct_Answer']}</div>";
            }
            echo "<hr>";
        } ?>
    </div>
   
    <a href="index.php" class="back-button" name="btnback">Back</a>
    <br><br>
   
    <?php include 'footer.php' ;?>
</body>
</html>