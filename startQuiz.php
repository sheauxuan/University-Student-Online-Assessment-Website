<?php
    session_start();
    include "connection.php";

    if (!isset($_SESSION['userid'])) {
        echo "<script>
                alert('Please login first before starting a quiz.');
                window.location.href = 'login.php';
            </script>";
    } else {
        $user_id = $_SESSION['userid'];
    }

    if (isset($_POST['quiz_id'])) {
        $_SESSION['quiz_id'] = $_POST['quiz_id'];
    }
    if (isset($_SESSION['quiz_id'])) {
        $quiz_id = $_SESSION['quiz_id'];
    } else if (isset($_GET['quiz_id'])){
        $quiz_id = $_GET['quiz_id'];
        $_SESSION['quiz_id'] = $quiz_id;
    } 
    else {
        echo 'Quiz ID is not set';
        exit;
    }

    if (isset($_POST['quiz_name'])) {
        $_SESSION['quiz_name'] = $_POST['quiz_name'];
    }
    if (isset($_SESSION['quiz_name'])) {
        $quiz_name = $_SESSION['quiz_name'];
    } else if (isset($_GET['quiz_name'])){
    $quiz_name = $_GET['quiz_name'];
    $_SESSION['quiz_name'] = $quiz_name;
    } 
    else {
        echo 'Quiz name is not set';
        exit;
    }

    if (isset($_POST['modules_name'])) {
        $_SESSION['modules_name'] = $_POST['modules_name'];
    }
    if (isset($_SESSION['modules_name'])) {
        $modules_name = $_SESSION['modules_name'];
    } else if (isset($_GET['modules_name'])){
        $modules_name = $_GET['modules_name'];
        $_SESSION['modules_name'] = $modules_name;
    } 
    else {
        echo 'Modules name is not set';
        exit;
    }

    $all_questions = [];
    $query = "SELECT * FROM questions WHERE Quiz_ID = '$quiz_id' ORDER BY Question_ID";
    $results = mysqli_query($connection, $query);
    while ($row = mysqli_fetch_assoc($results)) {
        $all_questions[] = $row;
    }
    $total_questions = count($all_questions);
    if ($total_questions == 0) {
        echo 'No questions found.';
        exit;
    }

    if (!isset($_SESSION['question_number']) || isset($_SESSION['quiz_completed'])) {
        unset($_SESSION['quiz_completed']);  
        $_SESSION['question_number'] = 0;
    }

    $query_progress = "SELECT Question_Answered FROM progress WHERE User_ID = $user_id AND Quiz_ID = $quiz_id";
    $result_progress = mysqli_query($connection, $query_progress);

    if ($row = mysqli_fetch_assoc($result_progress)) {
        $_SESSION['question_number'] = $row['Question_Answered'];
    } else {
        $_SESSION['question_number'] = 0; 
    }

    if ($_SESSION['question_number'] >= $total_questions) {
        $_SESSION['question_number'] = 0; 
    }

    $current_question = $all_questions[$_SESSION['question_number']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz</title>
    <link rel="stylesheet" href="main.css">
    <style>

    h1 {
        color: white;
    }
    
    .mainStartQuizSection {
        border: none;
        border-radius: 10px;
        width: 100%;
        max-width: 80%;
        max-height: 100%;
        padding: 3%;
        margin: 2% auto;
        background-color: #4f7942;
    }

    .questionSection {
        background-color: #1a3b1d;
        padding: 3%;
        margin: 1%;
    }

    .answerContainer {
        display: grid;
        grid-template-columns: repeat(2, minmax(200px, 1fr)); 
        gap: 15px; 
        padding: 15px;
        width: 90%;
        margin: 10px auto;
        justify-content: center;
        align-items: center;
    }

    .answerContainer textarea {
        grid-column: span 2; 
        width: 100%; 
        height: 100px; 
        padding: 10px;
        font-size: 1rem;
        border-radius: 5px;
        font-family: Arial, sans-serif; 
    }

    .answerContainer button {
        grid-column: span 1;
        width: 100%;
        padding: 10px 20px;
        background-color: green;
        color: white;
        font-size: 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-align: start;
    }

    #submitSubjective{
        grid-column: span 2;
        width: 100%;
        padding: 10px 20px;
        background-color: green;
        color: white;
        font-size: 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
    }

    .answer-btn:hover{
        background-color: palegoldenrod;
    }

    .popout{
      font-size: 16px;
      font-weight: bold;
      width: 400px;
      padding: 30px;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      border-radius: 20px;
      display: none;
    }

    .backButton{
      width: 4%;
      height: 4%;
      border-radius: 10px;
      border: none;
      cursor: pointer;
    }

    @media (max-width: 1041px){
        .backButtonimg{
            width: 30px;
            height: 30px;
        }

        .backButton{
            width: 35px;
            height: 35px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
        }
    }

    @media (max-width: 679px){
        .answer-btn{
            display: flex;
            align-items: center; 
            justify-content: start; 
            background-color: lightcoral;
            padding: 15px;
            font-size: smaller;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            width: 90%; 
            max-width: 80%; 
            min-height: 120px; 
            box-sizing: border-box;
            margin: 0 auto;
        }

        .answerContainer textarea {
            margin-left: 8%;
            grid-column: span 2; 
            width: 100%; 
            max-width: 330px;
            height: 80px; 
            padding: 10px;
            font-size: 0.8rem;
            border-radius: 5px;
            font-family: Arial, sans-serif; 
        }

        #submitSubjective{
            grid-column: span 2;
            width: 100%;
            max-width: 330px;
            padding: 10px 20px;
            background-color: green;
            color: white;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            justify-self: center;
        }
    }

    @media (max-width: 534px){
        .answerContainer button {
            grid-column: span 1;
            width: 70%;
            padding: 8px 15px;
            background-color: green;
            color: white;
            font-size: 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: start;
            gap: 12px;
            justify-self: end;
        }

        .answer-btn{
            display: flex;
            align-items: center; 
            justify-content: start; 
            padding: 10px;
            font-size: smaller;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            width: 80%; 
            max-width: 80%; 
            min-height: 100px; 
            box-sizing: border-box;
            margin: 0 auto;
        }

        .answerContainer {
            display: grid;
            grid-template-columns: repeat(2, minmax(120 px, 1fr)); 
            gap: 10px; 
            padding: 10px;
            width: 90%;
            margin: 10px auto;
            justify-content: center;
            align-items: center;
        }

        

    }

    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>
    <main>
    <div class="mainStartQuizSection">
        <div class="questionSection">
            <button class="backButton" onclick="window.location.href='index.php?resetSessionVariable=true'">
                <img class="backButtonImg" src="images/back icon.png" alt="back button icon">
            </button>
            <?php echo "<h1>$quiz_name</h1><br>"; ?>
            <h1><?php
            $display_question_number = $_SESSION['question_number'] + 1;
            echo $display_question_number . '. ' . $current_question['Question'];
            ?></h1>
            <div class="answerContainer">
            <?php if ($current_question['Question_Type'] === 'objective'): ?>
                <button type="button" class="answer-btn" data-value="<?php echo $current_question['Answer_1']; ?>">A. <?php echo $current_question['Answer_1']; ?></button>
                <button type="button" class="answer-btn" data-value="<?php echo $current_question['Answer_2']; ?>">B. <?php echo $current_question['Answer_2']; ?></button>
                <button type="button" class="answer-btn" data-value="<?php echo $current_question['Answer_3']; ?>">C. <?php echo $current_question['Answer_3']; ?></button>
                <button type="button" class="answer-btn" data-value="<?php echo $current_question['Answer_4']; ?>">D. <?php echo $current_question['Answer_4']; ?></button>
            <?php else: ?>
                <textarea id="subjectiveAnswer" rows="4" cols="50" placeholder="Type your answer here..."></textarea>
                <button type="button" id="submitSubjective">Submit</button>
            <?php endif; ?>
            </div>
        </div>
        </div>
        <div id="popout" class="popout"></div>
    </main>
  
    <script>
        document.addEventListener("DOMContentLoaded", function () {
        const buttons = document.querySelectorAll(".answer-btn");
        const popout = document.getElementById("popout");
        const submitSubjective = document.getElementById("submitSubjective");
        const quizId = "<?php echo isset($quiz_id) ? $quiz_id : ''; ?>"; 

        buttons.forEach(button => {
            button.addEventListener("click", function () {
                const selectedAnswer = this.getAttribute("data-value");
                submitAnswer(selectedAnswer, false);
            });
        });

        if (submitSubjective) {
            submitSubjective.addEventListener("click", function () {
                const subjectiveAnswer = document.getElementById("subjectiveAnswer").value.trim();
                if (subjectiveAnswer === "") {
                    alert("Please enter an answer.");
                    return;
                }
                submitAnswer(subjectiveAnswer, true);
            });
        }

        function submitAnswer(answer, isSubjective) {
            fetch("handleQuiz.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `answerSelection=${encodeURIComponent(answer)}&quiz_id=<?php echo $quiz_id; ?>&isSubjective=${isSubjective}`
            })
            .then(response => response.text())
            .then(text => {
                try {
                    return JSON.parse(text);  
                } catch (error) {
                    console.error("Response is not valid JSON:", text);
                    throw new Error("Invalid JSON response");
                }
            })
            .then(data => {
                console.log(data);

                popout.textContent = data.message;
                popout.style.backgroundColor = data.color;
                popout.style.display = "block";

                if (data.quiz_completed) {
                    setTimeout(() => {
                        window.location.href = "leaderboard.php";
                    }, 1200);
                } else {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                }
            })
            .catch(error => console.error("Error:", error));
            
        }
    });

    </script>
    
    <?php 
        include 'footer.php';
    ?>
</body>
</html>
