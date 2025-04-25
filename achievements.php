<?php
    session_start();
    include 'connection.php';

    if (!isset($_SESSION['userid'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Achievement</title>
            <style>
    
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    background-color: #fdf3e4;
                    color: #333;
                }
    
                .message-box {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    background-color: green;
                    color: white;
                    margin: 5% auto;
                    padding: 2%;
                    border-radius: 8px;
                    text-align: center;
                    font-size: 18px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    max-width: 70%;
                }
    
                footer {
                    position: fixed;
                    bottom: 0;
                    background-color: #b22222;
                    color: #fff;
                    text-align: center;
                    padding: 20px;
                    margin: 0;
                    margin-top: 3%;
                }
    
                .footer-content p {
                    margin: 0.5% 10% 1% 10%;
                    font-size: 80%;
                    font-family: times;
                }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = "login.php";
                }, 5000);
            </script>
        </head>
        <body>
            <?php include 'header.php'; ?>
            <div class="message-box">
                You must log in to access this page. Redirecting to login...
            </div>
            <?php include 'footer.php'; ?>
        </body>
        </html>
        <?php
        exit();
    }
    
    $userID = $_SESSION['userid'];
    
    $badges = [
        'Badge_1' => true,
        'Badge_2' => false,
        'Badge_3' => false,
        'Badge_4' => false,
        'Badge_5' => false,
        'Badge_6' => false,
        'Badge_7' => false,
        'Badge_8' => false
    ];
    
    $query = "SELECT COUNT(*) AS completed_quizzes
            FROM progress
            WHERE User_ID = '$userID' AND Completion_Percentage = 100.00 ";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row['completed_quizzes'] >= 3) $badges['Badge_2'] = true;
    
    $query = "SELECT COUNT(*) AS reviewed_materials
            FROM activity_summary
            WHERE User_ID = '$userID' AND Resource_ID IS NOT NULL";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row['reviewed_materials'] >= 3) $badges['Badge_3'] = true;
    
    $query = "SELECT Modules_Name FROM quizzes q
            WHERE Quiz_ID IN (SELECT Quiz_ID FROM progress
                WHERE User_ID = '$userID' AND Completion_Percentage = 100.00)
            GROUP BY Modules_Name
            HAVING COUNT(*) = (SELECT COUNT(*) FROM quizzes AS q2
                WHERE q2.Modules_Name = q.Modules_Name
            )";
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) > 0) $badges['Badge_4'] = true;
    
    $query = "SELECT Modules_Name FROM study_resources s
            WHERE Resource_ID IN ( SELECT Resource_ID
                FROM activity_summary WHERE User_ID = '$userID')
            GROUP BY Modules_Name
            HAVING COUNT(*) = (SELECT COUNT(*) FROM study_resources AS s2
                WHERE s2.Modules_Name = s.Modules_Name)";
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) > 0) $badges['Badge_5'] = true;
    
    $query_module_master = "SELECT quiz_progress.Modules_Name FROM (
        SELECT q.Modules_Name, COUNT(*) AS completed_quizzes
        FROM quizzes q
        JOIN progress p ON q.Quiz_ID = p.Quiz_ID
        WHERE p.User_ID = '$userID' AND p.Completion_Percentage = 100.00
        GROUP BY q.Modules_Name
    ) AS quiz_progress
    JOIN (
        SELECT a.Modules_Name, COUNT(DISTINCT a.Resource_ID) AS reviewed_materials
        FROM activity_summary a
        WHERE a.User_ID = '$userID' AND a.Activity_Type = 'view' AND a.Resource_ID IS NOT NULL
        GROUP BY a.Modules_Name
    ) AS material_progress
    ON quiz_progress.Modules_Name = material_progress.Modules_Name
    WHERE
        quiz_progress.completed_quizzes = (
            SELECT COUNT(*) FROM quizzes q2
            WHERE q2.Modules_Name = quiz_progress.Modules_Name
        )
        AND material_progress.reviewed_materials = (
            SELECT COUNT(DISTINCT sm.Resource_ID)
            FROM study_resources sm
            WHERE sm.Modules_Name = material_progress.Modules_Name
        )";
    
    $result = mysqli_query($connection, $query_module_master);
    if (mysqli_num_rows($result) > 0) {
        $badges['Badge_6'] = true;
    }
    
    $query = "SELECT COUNT(*) AS full_scores
            FROM quiz_leaderboard l
            JOIN quizzes q ON l.Quiz_ID = q.Quiz_ID
            WHERE l.User_ID = '$userID' AND l.score = (q.Total_Question * 100)" ;
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row['full_scores'] >= 10) $badges['Badge_7'] = true;
    
    if ($badges['Badge_4']) {
        $query = "SELECT q.Modules_Name FROM quiz_leaderboard l
                JOIN quizzes q ON l.Quiz_ID = q.Quiz_ID WHERE l.User_ID = '$userID'
                    AND l.Score = (q.Total_Question * 100)
                GROUP BY q.Modules_Name HAVING COUNT(*) = (SELECT COUNT(*)
                                    FROM quizzes
                                    WHERE Modules_Name = q.Modules_Name)";
    
        $result = mysqli_query($connection, $query);
        if (mysqli_num_rows($result) > 0) {
            $badges['Badge_8'] = true;
        }
    }
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievement Page</title>
    <style>
       
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #fdf3e4;
            color: #333;
        }
 
        h2 {
            color: #b22222;
        }
       
        .container {
            max-width:70%;
            margin: 3% auto;
            margin-top:1%;
            padding: 15px;
        }
 
        .badges{
            margin-bottom: 3%;
            background-color: #b22222;
            border-radius: 8px;
        }
 
        .badge-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr); 
            gap: 2vw; 
            padding: 2vw;
            box-sizing: border-box;
        }
 
        .badge {
            display: flex;
            align-items: center;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
            overflow: hidden;
            width: 100%;
            transition: transform 0.2s ease-in-out;
        }
 
        .badge:hover {
            transform: scale(1.03); 
        }
 
        .badge-img {
            flex: 0 0 30%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 8px;
            overflow: hidden;
            width: 100%;  
        }
 
        .badge-img img {
            width: 100%;
            height: auto;
            object-fit: contain;
            filter: grayscale(100%);
            opacity: 0.6;
        }
 
        .badge-earned img {
            filter: grayscale(0%);
            opacity: 1;
        }
 
        .badge-text {
            flex: 1;
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content:center;
            text-align: left;
        }
 
        .badge-text h3 {
            margin: 0 0 5px;
            font-size: 110%;
            color:green; 
            font-weight: bold;
        }
 
        .badge-text p {
            margin: 0;
            font-size: 90%;
            padding-right: 5%;
            color: #333;
        }
 
    </style>
</head>
<body>
    <?php include 'header.php'; ?>  
 
    <main>
        <div class="container">
 
            <h2 style="color: #b22222; text-align : center;">Badge Collected</h2>
            <div class="badges">
                <div class="badge-container">
                   
                <div class="badge <?= $badges['Badge_1'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/starter_strong.png" alt="Badge_1">
                        </div>
                        <div class="badge-text">
                            <h3>Starting Strong</h3><br>
                            <p>Successfully Create An Account</p>
                        </div>
                    </div>
           
                    <div class="badge <?= $badges['Badge_2'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/quiz_beginner.png" alt="Badge_2">
                        </div>
                        <div class="badge-text">
                            <h3>Quiz Beginner</h3><br>
                            <p>Complete 3 Quizzes</p>
                        </div>
                    </div>
           
                    <div class="badge <?= $badges['Badge_3'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/study_beginner.png" alt="Badge_3">
                        </div>
                        <div class="badge-text">
                            <h3>Study Beginner</h3><br>
                            <p>Review 3 Study Materials</p>
                        </div>
                    </div>
           
                    <div class="badge <?= $badges['Badge_4'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/quiz_explorer.png" alt="Badge_4">
                        </div>
                        <div class="badge-text">
                            <h3>Quiz Explorer</h3><br>
                            <p>Complete all Quizzes in a Module</p>
                        </div>
                    </div>
           
                    <div class="badge <?= $badges['Badge_5'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/study_explorer.png" alt="Badge_5">
                        </div>
                        <div class="badge-text">
                            <h3>Study Explorer</h3><br>
                            <p>Review all Study Materials in a Module</p>
                        </div>
                    </div>
           
                    <div class="badge <?= $badges['Badge_6'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/module_handler.png" alt="Badge_6">
                        </div>
                        <div class="badge-text">
                            <h3>Module Handler</h3><br>
                            <p>Complete all Study Materials & Quizzes in a Module</p>
                        </div>
                    </div>
       
                    <div class="badge <?= $badges['Badge_7'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/quiz_hero.png" alt="Badge_7">
                        </div>
                        <div class="badge-text">
                            <h3>Quiz Hero</h3><br>
                            <p>Score 100% in 10 Quizzes</p>
                        </div>
                    </div>
       
                    <div class="badge <?= $badges['Badge_8'] ? 'badge-earned' : '' ?>">
                        <div class="badge-img">
                            <img src="images/perfect_acheiver.png" alt="Badge_8">
                        </div>
                        <div class="badge-text">
                            <h3>Perfect Achiever</h3><br>
                            <p>Score 100% in all Quizzes of a Module</p>
                        </div>
                    </div>
                </div>
            </div>    
        </div>
    </main>
 
    <?php include 'footer.php'; ?>
 
</body>
</html>