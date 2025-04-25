<?php
    session_start();
    include('connection.php'); 

    if (isset($_SESSION['userRole'])) {
        $role = $_SESSION['userRole']; 
    } else {
        $role = 'guest'; 
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #fdf3e4;
            color: #333;
        }

        body {
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
        }
        
        .content {
            background-color: white;
            text-align: center;
            padding: 50px;
            margin: 20px auto;
            width: 30%;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 300px;
        }

        .content h2 {
            margin-top: 0;
            color: #333;
        }

        @media (max-width: 768px) {
        .content h2 {
            font-size: medium; 
        }
    }

        .content a {
            display: inline-block;
            margin: 15px auto;
            padding: 15px 20px;
            font-size: 18px;
            background-color: #b22222;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .content a:hover {
            background-color: #8b1a1a;
        }
    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>

    <div class="content">
        <h2><strong>Create New Module/Quiz/Materials!</strong></h2>
        <p>Create:</p>
        <a href="create_module.php" class="button">New Module</a>
        <a href="create_quiz.php" class="button">New Quiz</a>
        <a href="create_study_material.php" class="button">New Study Material</a>
    </div>

    <?php
        include 'footer.php';
    ?>
</body>
</html>
