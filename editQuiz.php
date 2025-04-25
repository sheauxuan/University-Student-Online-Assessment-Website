<?php
    session_start(); 
    include('connection.php'); 

    date_default_timezone_set('Asia/Kuala_Lumpur');
    $connection->query("SET time_zone = '+08:00'");

    if (!isset($_SESSION['start_time_quiz'])) {
        $_SESSION['start_time_quiz'] = time();
    }
    $start_time = date('Y-m-d H:i:s', $_SESSION['start_time_quiz']);
    
    $query = "SELECT Modules_Name FROM modules";
    $result = mysqli_query($connection, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($connection));
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (isset($_POST['delete_question_id'])) {
            $deleteQuestionID = $_POST['delete_question_id'];
            
            $getQuizIDQuery = "SELECT Quiz_ID FROM questions WHERE Question_ID = ?";
            $stmt = mysqli_prepare($connection, $getQuizIDQuery);
            mysqli_stmt_bind_param($stmt, "i", $deleteQuestionID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $quizID);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (isset($quizID)) {
                $getModuleQuery = "SELECT Modules_Name FROM quizzes WHERE Quiz_ID = ?";
                $stmt = mysqli_prepare($connection, $getModuleQuery);
                mysqli_stmt_bind_param($stmt, "i", $quizID);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $editModulesName);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                $deleteQuery = "DELETE FROM questions WHERE Question_ID = ?";
                $stmt = mysqli_prepare($connection, $deleteQuery);
                mysqli_stmt_bind_param($stmt, "i", $deleteQuestionID);

                if (mysqli_stmt_execute($stmt)) {
                    $updateQuizQuery = "UPDATE quizzes SET Total_Question = Total_Question - 1 WHERE Quiz_ID = ?";
                    $stmt = mysqli_prepare($connection, $updateQuizQuery);
                    mysqli_stmt_bind_param($stmt, "i", $quizID);

                    if (mysqli_stmt_execute($stmt)) {
                        $completion_time = date('Y-m-d H:i:s'); 
                        $user_id = $_SESSION['userid'];
                        $activity_type = "edit";  
    
                        $activity_query = "INSERT INTO activity_summary 
                            (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp) 
                            VALUES (?, ?, NULL, ?, ?, ?, ?)";
    
                        $stmt = mysqli_prepare($connection, $activity_query);
                        mysqli_stmt_bind_param($stmt, "siisss", $editModulesName, $quizID, $user_id, $activity_type, $start_time, $completion_time);
    
                        if (mysqli_stmt_execute($stmt)) {
                            echo "success";
                        } else {
                            echo "Error inserting activity summary: " . mysqli_error($connection);
                        }
                        
                    } else {
                        echo "Error updating Total_Question: " . mysqli_stmt_error($stmt);
                    }

                    mysqli_stmt_close($stmt);
         
                } else {
                    echo "Error deleting question: " . mysqli_stmt_error($stmt);
                }

            } else {
                echo "Quiz ID not found for the question!";
            }
            exit();
        }

        if (!empty($_POST['delete_quiz_id'])) {
            $quizID = $_POST['delete_quiz_id'];
            
            $deleteProgressQuery = "DELETE FROM progress WHERE Quiz_ID = ?";
            $stmt = mysqli_prepare($connection, $deleteProgressQuery);
            mysqli_stmt_bind_param($stmt, "i", $quizID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $deleteActivityQuery = "DELETE FROM activity_summary WHERE Quiz_ID = ?";
            $stmt = mysqli_prepare($connection, $deleteActivityQuery);
            mysqli_stmt_bind_param($stmt, "i", $quizID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $deleteQuery = "DELETE FROM questions WHERE Quiz_ID = ?";
            $stmt = mysqli_prepare($connection, $deleteQuery);
            mysqli_stmt_bind_param($stmt, "i", $quizID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $deleteQuery = "DELETE FROM quizzes WHERE Quiz_ID = ?";
            $stmt = mysqli_prepare($connection, $deleteQuery);
            mysqli_stmt_bind_param($stmt, "i", $quizID);

            if (mysqli_stmt_execute($stmt)) {
                echo "success";
            } else {
                echo "Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
            exit();
        }

        if (isset($_POST['quizID']) && !empty($_POST['quizID'])) {
            $quizID = $_POST['quizID'];
        } else {
            echo "No quiz ID received!";
            exit();
        }

        if (isset($_POST['publish'])) {

            $quiz_name = $_POST['Quiz_name'];
            $quiz_description = $_POST['Quiz_description'];
            $module_name = $_POST['Modules_Name'];
            $total_question = $_POST['Total_Question'] ?? null;
    
            if (!empty($_FILES['quiz_image']['tmp_name'])) {
                $quiz_image = file_get_contents($_FILES['quiz_image']['tmp_name']);
            
                $update_quiz_query = "UPDATE quizzes SET Quizz_Image = ? WHERE Quiz_ID = ?";
                $stmt = mysqli_prepare($connection, $update_quiz_query);
                
                mysqli_stmt_bind_param($stmt, "bi", $null, $quizID);
                mysqli_stmt_send_long_data($stmt, 0, $quiz_image);
            
                if (!mysqli_stmt_execute($stmt)) {
                    echo "Error updating image: " . mysqli_error($connection);
                }
            
                mysqli_stmt_close($stmt); 
            } else {
                $query = "SELECT Quizz_Image FROM quizzes WHERE Quiz_ID = ?";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $quizID);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $quiz_image = ($row) ? $row['Quizz_Image'] : null;
                mysqli_stmt_close($stmt);
                
            }
    
            $update_quiz_query = "UPDATE quizzes 
                SET Modules_Name = ?, 
                    Quiz_Name = ?, 
                    Quiz_Description = ?, 
                    Total_Question = ?
                WHERE Quiz_ID = ?";
    
            $stmt = mysqli_prepare($connection, $update_quiz_query);
            mysqli_stmt_bind_param($stmt, "sssii", $module_name, $quiz_name, $quiz_description, $total_question, $quizID);
    
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Quiz has been updated successfully!')</script>";
                $editModulesName = $module_name;
            } else {
                echo "<script>alert('Error updating quiz:')</script>" . mysqli_error($connection);
            }
    
            mysqli_stmt_close($stmt);
    
            if (isset($_POST['question']) && isset($_POST['question_type'])) {
                $questions = $_POST['question'];
                $question_types = $_POST['question_type'];
                $answers1 = $_POST['answer1'] ?? [];
                $answers2 = $_POST['answer2'] ?? [];
                $answers3 = $_POST['answer3'] ?? [];
                $answers4 = $_POST['answer4'] ?? [];
                $correct_answers = $_POST['correct_answer'] ?? [];
                $subjective_answers = $_POST['subjective_answer'] ?? [];
                $questionIDs = $_POST['question_id'] ?? [];
    
                for ($i = 0; $i < $total_question; $i++) {
                    $questionID = $questionIDs[$i] ?? null;
                    $question = $questions[$i];
                    $question_type = $question_types[$i];
                
                    if (!empty($questionID)) {
                        $check_question_query = "SELECT Question_ID FROM questions WHERE Question_ID = ?";
                        $stmt = mysqli_prepare($connection, $check_question_query);
                        mysqli_stmt_bind_param($stmt, "i", $questionID);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_store_result($stmt);
                        $exists = mysqli_stmt_num_rows($stmt) > 0;
                        mysqli_stmt_close($stmt);
                    } else {
                        $exists = false;
                    }
                
                    if ($question_type == 'objective') {
                        $answer1 = $answers1[$i] ?? '';
                        $answer2 = $answers2[$i] ?? '';
                        $answer3 = $answers3[$i] ?? '';
                        $answer4 = $answers4[$i] ?? '';
                        $correct_answer = $correct_answers[$i] ?? '';
                
                        if ($exists) {
                            $update_query = "UPDATE questions 
                                SET Question = ?, Question_Type = ?, Answer_1 = ?, Answer_2 = ?, 
                                    Answer_3 = ?, Answer_4 = ?, Correct_Answer = ? 
                                WHERE Question_ID = ?";
                            $stmt = mysqli_prepare($connection, $update_query);
                            mysqli_stmt_bind_param($stmt, "sssssssi", 
                                $question, $question_type, $answer1, $answer2, 
                                $answer3, $answer4, $correct_answer, $questionID);
                        } else {
                            $insert_query = "INSERT INTO questions 
                                (Quiz_ID, Question, Question_Type, Answer_1, Answer_2, Answer_3, Answer_4, Correct_Answer) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt = mysqli_prepare($connection, $insert_query);
                            mysqli_stmt_bind_param($stmt, "isssssss", 
                                $quizID, $question, $question_type, $answer1, $answer2, 
                                $answer3, $answer4, $correct_answer);
                        }
                    } elseif ($question_type == 'subjective') {
                        $subjective_answer = $subjective_answers[$i] ?? '';
                
                        if ($exists) {
                            $update_query = "UPDATE questions 
                                SET Question = ?, Question_Type = ?, Correct_Answer = ? 
                                WHERE Question_ID = ?";
                            $stmt = mysqli_prepare($connection, $update_query);
                            mysqli_stmt_bind_param($stmt, "sssi", 
                                $question, $question_type, $subjective_answer, $questionID);
                        } else {
                            $insert_query = "INSERT INTO questions 
                                (Quiz_ID, Question, Question_Type, Correct_Answer) 
                                VALUES (?, ?, ?, ?)";
                            $stmt = mysqli_prepare($connection, $insert_query);
                            mysqli_stmt_bind_param($stmt, "isss", 
                                $quizID, $question, $question_type, $subjective_answer);
                        }
                    }
                
                    if (!mysqli_stmt_execute($stmt)) {
                        echo "Error updating/inserting question: " . mysqli_error($connection) . "<br>";
                    }
                
                    mysqli_stmt_close($stmt);
                }                
            }

            $completion_time = date('Y-m-d H:i:s'); 
            $user_id = $_SESSION['userid'];
            $activity_type = "edit";
    
            $activity_query = "INSERT INTO activity_summary 
                (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp) 
                VALUES (?, ?, NULL, ?, ?, ?, ?)";
    
            $stmt = mysqli_prepare($connection, $activity_query);
            mysqli_stmt_bind_param($stmt, "siisss", $module_name, $quizID, $user_id, $activity_type, $start_time, $completion_time);
            
            mysqli_stmt_close($stmt);
        }
            
    } else {
        echo "Invalid request method!";
    }


    if (isset($quizID)) {
        $editQuizID = $quizID; 
        $getEditQuiz = "SELECT * FROM quizzes WHERE Quiz_ID = $editQuizID";
        $result1 = mysqli_query($connection, $getEditQuiz);
        if (mysqli_num_rows($result1) > 0) {
            while ($row = mysqli_fetch_assoc($result1)) {
                $editModulesName = $row['Modules_Name'];
                $editQuizName = $row['Quiz_Name'];
                $editQuizDescription = $row['Quiz_Description'];
                $editQuizImage = $row['Quizz_Image'];
            }
        }

        $editQuestions = [];
        $getEditQuestion = "SELECT * FROM questions WHERE Quiz_ID = $editQuizID";
        $result2 = mysqli_query($connection, $getEditQuestion);
        if (mysqli_num_rows($result2) > 0) {
            while ($row = mysqli_fetch_assoc($result2)) {
                $editQuestions[] = $row;
            }
        }

        $questionsInJson = json_encode($editQuestions);

        $editQuizImageInBase64 = base64_encode($editQuizImage);
        $editQuizImageSrc = "data:image/png;base64," . $editQuizImageInBase64;
    }
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA: Edit Quiz</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Almendra+SC&display=swap">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            background-color: #fdf3e4;
            color: #333;
        }
 
        .main {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
 
        .section {
            width: 100%;
            max-width: 800px;
            margin: 10px 0;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
 
        .input-group {
            margin-bottom: 20px; 
        }
 
        .input-group label {
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold;
        }
 
        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: Arial, sans-serif; 
        }
 
        #publish-btn {
            background-color: green;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #publish-btn:hover {
            background-color: darkgreen;
        }
 
        #delete-quiz-btn {
            background-color: red;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #delete-quiz-btn:hover {
            background-color: darkred;
        }

        .edit-quiz {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
 
        .edit-quiz input, .edit-quiz textarea, .edit-quiz select {
            width: 90%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
 
        .quiz-image {
            display: flex;
            align-items: center;
            gap: 10px;
        }
 
        .quiz-image img {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
 
        .quiz-image button {
            padding: 10px 20px;
            background-color: tomato;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
 
        .quiz-image button:hover {
            background-color: red;
        }
 
        #delete-image-btn {
            background: none;
            border: none;
            color: rgb(21, 2, 2);
            font-size: 30px;
            cursor: pointer;
        }
 
        #delete-image-btn:hover {
            color: darkred;
        }
 
        .question {
            background-color: #f9f9f9; 
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); 
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .question input {
            flex-grow: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .options {
            width: 100%;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
 
        .options .left, .options .right {
            width: 45%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
 
        .add-question {
            display: flex;
            justify-content: center;
        }
 
        .add-question button {
            padding: 10px 20px;
            background-color: tomato;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
 
        .add-question button:hover {
            background-color: red;
        }
 
        .dynamic-question {
            position: relative;
            padding: 20px;

        }

        .delete-question-btn {
            position: absolute;
            top: 15%;
            right: 5%;
            background: transparent;
            border: none;
            color: red;
            font-size: 16px;
            cursor: pointer;
        }

        .delete-question-btn:hover {
            color: darkred;
        }
 
        .option {
            cursor: pointer;
        }
 
        .option:focus {
            outline: none;
        }
 
        h2 {
            color: red; 
        }
    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>
 
    <main class="main">
        <div class="section">
            <h2>Edit Quiz</h2>
            <form class="edit-quiz" method="POST" action="editQuiz.php" enctype="multipart/form-data">

            <input type="hidden" name="quiz_id" value="<?php echo $quizID; ?>">

            <div class="input-group">
                <label for="Quiz_name">Quiz Name:</label>
                <input type="text" name="Quiz_name" placeholder="Quiz Name" value="<?php echo $editQuizName;?>">
            </div>
           
            <div class="input-group">
                <label for="Modules_Name">Select Module:</label>
                <select name="Modules_Name">
                    <option value="" disabled>Select a Module</option> 
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        $selected = ($editModulesName == $row['Modules_Name']) ? "selected" : "";
                        echo '<option value="' . $row['Modules_Name'] . '" ' . $selected . '>' . $row['Modules_Name'] . '</option>';
                    }
                    ?>
                </select>
            </div>
 
            <div class="input-group">
                <label for="Quiz_description">Quiz Description:</label>
                <textarea name="Quiz_description" placeholder="Quiz Description"><?php echo $editQuizDescription; ?></textarea>
            </div>
 
            <div class="input-group quiz-image">
                <img id="quiz-image-preview" src="" alt="Quiz Display Image"/>
                <button type="button" id="insert-image-btn">Insert Image</button>
                <button type="button" id="delete-image-btn" title="Remove Image">&#128465;</button>
                <input type="file" name="quiz_image" id="image-input" style="display: none;" accept="image/*">
            </div>
                <div id="questions-container">
                </div>
 
                <div class="add-question">
                    <button type="button" id="add-question-btn">Add Question</button>
                </div>
 
                <div class="actions">
                    <button type="button" id="delete-quiz-btn">Delete Quiz</button>
                    <button type="submit" id="publish-btn" name="publish">Publish</button>
                    <input type="hidden" name="quizID" value="<?php echo $quizID; ?>">
                </div>
            </form>
        </div>
    </main>
 
    <?php
        include 'footer.php';
    ?>
 
    <script>
        const editQuizImageSrc = "<?php echo $editQuizImageSrc ?>";

        const insertImageBtn = document.getElementById('insert-image-btn');
        const imageInput = document.getElementById('image-input');
        const quizImagePreview = document.getElementById('quiz-image-preview');
        const deleteImageBtn = document.getElementById('delete-image-btn');
        const questionsContainer = document.getElementById('questions-container');
        const addQuestionBtn = document.getElementById('add-question-btn');
        const deleteAllBtn = document.getElementById('delete-all-btn');
        const quizForm = document.querySelector('.edit-quiz');

        console.log("quizForm:", quizForm);
 
        if (editQuizImageSrc && editQuizImageSrc !== "data:image/png;base64,") {
            quizImagePreview.src = editQuizImageSrc;
        }

        insertImageBtn.addEventListener('click', () => {
            imageInput.click(); 
        });
 
        imageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    quizImagePreview.src = e.target.result; 
                };
                reader.readAsDataURL(file); 
            }
        });
 
        deleteImageBtn.addEventListener('click', () => {
            quizImagePreview.src = ""; 
            imageInput.value = ""; 
        });
 
    addQuestionBtn.addEventListener('click', createQuestionBlock);
    const questionData = <?php echo $questionsInJson; ?>;

        function createQuestionBlock(questionData = null) {
            const questionBlock = document.createElement('div');
            questionBlock.classList.add('dynamic-question');

            let questionID;
            if (questionData && questionData.Question_ID) {
                questionID = questionData.Question_ID; 
            } else {
                questionID = "new"; 
            }
            const questionText = questionData ? questionData.Question : "";
            const answer1 = questionData ? questionData.Answer_1 : "";
            const answer2 = questionData ? questionData.Answer_2 : "";
            const answer3 = questionData ? questionData.Answer_3 : "";
            const answer4 = questionData ? questionData.Answer_4 : "";
            const correctAnswer = questionData ? questionData.Correct_Answer : "";
            const editQuestionType = questionData ? questionData.Question_Type : "objective";
 
            questionBlock.innerHTML = `
                <div class="question">
                    <input type="text" name="question[]" placeholder="Question" value="${questionText}">
                    <div class="options" ${editQuestionType === 'subjective' ? 'style="display: none;"' : ''}>
                        <div class="left">
                            <input type="text" name="answer1[]" class="option" placeholder="A." value="${answer1}">
                            <input type="text" name="answer2[]" class="option" placeholder="B." value="${answer2}">
                        </div>
                        <div class="right">
                            <input type="text" name="answer3[]" class="option" placeholder="C." value="${answer3}">
                            <input type="text" name="answer4[]" class="option" placeholder="D." value="${answer4}">
                        </div>
                    </div>
                    <textarea name="subjective_answer[]" class="subjective-answer" placeholder="Answer here..." 
                        style="${editQuestionType === 'subjective' ? 'display: block;' : 'display: none;'}">${editQuestionType === 'subjective' ? correctAnswer : ''}</textarea>
                    <select name="question_type[]" class="question-type">
                        <option value="objective" ${editQuestionType === 'objective' ? 'selected' : ''}>Type: Multiple Choice</option>
                        <option value="subjective" ${editQuestionType === 'subjective' ? 'selected' : ''}>Type: Subjective</option>
                    </select>
                    <input type="hidden" name="question_id[]" value="${questionID}">
                    <input type="hidden" class="correct_answer" name="correct_answer[]" value="${correctAnswer}">
                    <button type="button" class="delete-question-btn" data-id="${questionID}">❌</button>
                </div>
            `;
        
            const options = questionBlock.querySelector('.options');
            const subjectiveAnswer = questionBlock.querySelector('.subjective-answer');
            const questionType = questionBlock.querySelector('.question-type');

            questionType.addEventListener('change', function () {
                if (questionType.value === 'subjective') {
                    options.style.display = 'none';
                    subjectiveAnswer.style.display = 'block';

                    let correctAnswerInput = questionBlock.querySelector('.correct_answer');
                    if (!correctAnswerInput) {
                        correctAnswerInput = document.createElement('input');
                        correctAnswerInput.type = 'hidden';
                        correctAnswerInput.name = 'correct_answer[]';
                        correctAnswerInput.classList.add('correct_answer');
                        questionBlock.appendChild(correctAnswerInput);
                    }

                    correctAnswerInput.value = subjectiveAnswer.value.trim();

                    subjectiveAnswer.addEventListener('input', function () {
                        correctAnswerInput.value = this.value.trim();
                    });
                } else {
                    options.style.display = 'flex';
                    subjectiveAnswer.style.display = 'none';
                }
            });
        
            const optionInputs = questionBlock.querySelectorAll('.option');
        optionInputs.forEach(option => {
            if (option.value === correctAnswer) {
                option.style.backgroundColor = 'peachpuff';
            }

            option.addEventListener('dblclick', function () {
                console.log(option.value);
                optionInputs.forEach(opt => opt.style.backgroundColor = '');
                option.style.backgroundColor = 'peachpuff';
                let correctAnswerInput = questionBlock.querySelector('.correct_answer');

                if (!correctAnswerInput) {
                    correctAnswerInput = document.createElement('input');
                    correctAnswerInput.type = 'hidden';
                    correctAnswerInput.name = 'correct_answer[]';  
                    correctAnswerInput.classList.add('correct_answer');  
                    questionBlock.appendChild(correctAnswerInput);  
                }
            
                correctAnswerInput.value = option.value;
            });
        });
        
        const deleteQuestionBtn = questionBlock.querySelector('.delete-question-btn');
        deleteQuestionBtn.addEventListener('click', function () {
            const questionID = deleteQuestionBtn.getAttribute('data-id');

            if (!questionID || questionID === "new") {
                questionBlock.remove(); 
                return;
            }

            if (confirm("Are you sure you want to delete this question?")) {
                const quizID = document.querySelector("input[name='quizID']").value;
                fetch("editQuiz.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "delete_question_id=" + encodeURIComponent(questionID) +
                            "&quizID=" + encodeURIComponent(quizID)
                })
                .then(response => response.text())
                .then(data => {
                    console.log("Server Response:", data); 
                    if (data.trim() === "success") {
                        questionBlock.remove(); 
                        alert("Question deleted successfully!");
                    } else {
                        alert("Error deleting question. " + data.trim());
                    }
                })
                .catch(error => console.error("Error:", error));
            }
        });

        questionsContainer.appendChild(questionBlock);
        }

        if (Array.isArray(questionData)) {
            questionData.forEach(question => createQuestionBlock(question));
        } else {
            console.error("Invalid questions data:", questionData);
        }
 

        document.addEventListener("DOMContentLoaded", function () {
            const deleteQuizBtn = document.getElementById('delete-quiz-btn');

            deleteQuizBtn.addEventListener('click', function () {
                const quizID = document.querySelector("input[name='quizID']").value;

                console.log("Quiz ID before deletion:", quizID);

                if (!quizID) {
                    alert("Quiz ID is missing!");
                    return;
                }

                if (confirm("Are you sure you want to delete this quiz?")) {
                    fetch("editQuiz.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: "delete_quiz_id=" + encodeURIComponent(quizID)
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            alert("Quiz deleted successfully!");
                            window.location.href = "index.php"; 
                        } else {
                            alert(data.message); 
                        }
                    })
                    .catch(error => console.error("Error:", error));
                }
            });
        });

        const publishBtn = document.getElementById('publish-btn');
        publishBtn.addEventListener('click', function (event) {
            let isValid = true;
            let errorMessage = "Please fill in all required fields:\n";
        
            const quizName = document.querySelector('input[name="Quiz_name"]').value.trim();
            const moduleName = document.querySelector('select[name="Modules_Name"]').value.trim();
            const quizDescription = document.querySelector('textarea[name="Quiz_description"]').value.trim();
            const quizImage = document.getElementById('image-input').files.length; 
            const quizImagePreview = document.getElementById('quiz-image-preview');
        
            if (!quizName) {
                isValid = false;
                errorMessage += "- Quiz Name is required.\n";
            }
        
            if (!moduleName) {
                isValid = false;
                errorMessage += "- Please select a Module.\n";
            }
        
            if (!quizDescription) {
                isValid = false;
                errorMessage += "- Quiz Description is required.\n";
            }
        
            if (quizImage === 0 && !quizImagePreview.src) {
                isValid = false;
                errorMessage += "- Please upload an image for the quiz.\n";
            }
        
            const totalQuestions = document.querySelectorAll('.dynamic-question').length;
            if (totalQuestions < 5) {
                isValid = false;
                errorMessage += "- At least five questions are required.\n";
            }

            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
                return;
            }
            
            console.log("Total Questions:", totalQuestions);
            const totalQuestionInput = document.createElement('input');
            totalQuestionInput.type = 'hidden';
            totalQuestionInput.name = 'Total_Question';
            totalQuestionInput.value = totalQuestions;
            quizForm.appendChild(totalQuestionInput);
            console.log("Hidden field appended:", totalQuestionInput);

            document.querySelectorAll('.dynamic-question').forEach((questionElement, index) => {
                const questionText = questionElement.querySelector('input[name="question[]"]').value.trim();
                const questionType = questionElement.querySelector('.question-type').value.trim();
        
                if (!questionText) {
                    isValid = false;
                    errorMessage += `- Question ${index + 1} is missing.\n`;
                }
        
                if (!questionType) {
                    isValid = false;
                    errorMessage += `- Question ${index + 1} type is not selected.\n`;
                }
        
                if (questionType === 'objective') {
                    const answer1 = questionElement.querySelector('input[name="answer1[]"]').value.trim();
                    const answer2 = questionElement.querySelector('input[name="answer2[]"]').value.trim();
                    const answer3 = questionElement.querySelector('input[name="answer3[]"]').value.trim();
                    const answer4 = questionElement.querySelector('input[name="answer4[]"]').value.trim();
                    const correctAnswer = questionElement.querySelector('.correct_answer')?.value.trim() || "";
        
                    if (!answer1 || !answer2 || !answer3 || !answer4) {
                        isValid = false;
                        errorMessage += `- Question ${index + 1} has missing answer choices.\n`;
                    }
        
                    if (!correctAnswer) {
                        isValid = false;
                        errorMessage += `- Question ${index + 1} does not have a correct answer selected.\n`;
                    }
        
                    if (isValid) {
                        appendHiddenFields('question[]', questionText);
                        appendHiddenFields('question_type[]', questionType);
                        appendHiddenFields('answer1[]', answer1);
                        appendHiddenFields('answer2[]', answer2);
                        appendHiddenFields('answer3[]', answer3);
                        appendHiddenFields('answer4[]', answer4);
                        appendHiddenFields('correct_answer[]', correctAnswer);
                    }
        
                } else if (questionType === 'subjective') {
                    const subjectiveAnswer = questionElement.querySelector('.subjective-answer')?.value.trim() || "";
        
                    if (!subjectiveAnswer) {
                        isValid = false;
                        errorMessage += `- Question ${index + 1} is missing an answer.\n`;
                    }
        
                    if (isValid) {
                        appendHiddenFields('question[]', questionText);
                        appendHiddenFields('question_type[]', questionType);
                        appendHiddenFields('subjective_answer[]', subjectiveAnswer);
                        appendHiddenFields('answer1[]', "");
                        appendHiddenFields('answer2[]', "");
                        appendHiddenFields('answer3[]', "");
                        appendHiddenFields('answer4[]', "");
                    }
                }
            });
        
            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
                return;
            }
            quizForm.submit();
        });
        
        function appendHiddenFields(name, value) {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = name;
            hiddenInput.value = value;
            quizForm.appendChild(hiddenInput);
        }
    </script>
</body>
</html>