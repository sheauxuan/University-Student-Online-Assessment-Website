<?php
    session_start(); 
    date_default_timezone_set('Asia/Kuala_Lumpur');
    include('connection.php'); 
    $connection->query("SET time_zone = '+08:00'");
    if (!isset($_SESSION['start_time_quiz'])) {
        $_SESSION['start_time_quiz'] = time();
    }
    $start_time = $_SESSION['start_time_quiz'];

    $query = "SELECT Modules_Name FROM modules";
    $result = mysqli_query($connection, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($connection));
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['publish'])) {
        $quiz_name = $_POST['Quiz_name'];
        $quiz_description = $_POST['Quiz_description'];
        $module_name = $_POST['Modules_Name'];
        $total_question = $_POST['Total_Question'];

        if (isset($_FILES['quiz_image']) && $_FILES['quiz_image']['error'] === 0) {
            if ($_FILES['quiz_image']['size'] > 5000000) { 
                echo "<script>alert('File is too large. Maximum allowed size is 5MB.'); window.location.href='create_quiz.php';</script>";
                exit();
            } else {
                $quiz_image = file_get_contents($_FILES['quiz_image']['tmp_name']);
            }
        } else {
            $quiz_image = null; 
        }

        $query = "INSERT INTO quizzes (Modules_Name, Quiz_Name, Quiz_Description, Total_Question, Quizz_Image)
                VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($connection, $query)) {
            mysqli_stmt_bind_param($stmt, 'sssis', $module_name, $quiz_name, $quiz_description, $total_question, $quiz_image);

            if (mysqli_stmt_execute($stmt)) {
                $quiz_id = mysqli_insert_id($connection);

                if (isset($_POST['question']) && isset($_POST['question_type'])) {
                    $questions = $_POST['question'];
                    $question_types = $_POST['question_type'];

                    $answers1 = isset($_POST['answer1']) ? $_POST['answer1'] : [];
                    $answers2 = isset($_POST['answer2']) ? $_POST['answer2'] : [];
                    $answers3 = isset($_POST['answer3']) ? $_POST['answer3'] : [];
                    $answers4 = isset($_POST['answer4']) ? $_POST['answer4'] : [];
                    $correct_answers = isset($_POST['correct_answer']) ? $_POST['correct_answer'] : [];
                    $subjective_answers = isset($_POST['subjective_answer']) ? $_POST['subjective_answer'] : [];

                    for ($i = 0; $i < $total_question; $i++) {
                        $question = $questions[$i];
                        $question_type = $question_types[$i];
                        $correct_answer = isset($correct_answers[$i]) ? $correct_answers[$i] : '';

                        if ($question_type == 'objective') {
                            $answer1 = isset($answers1[$i]) ? $answers1[$i] : '';
                            $answer2 = isset($answers2[$i]) ? $answers2[$i] : '';
                            $answer3 = isset($answers3[$i]) ? $answers3[$i] : '';
                            $answer4 = isset($answers4[$i]) ? $answers4[$i] : '';

                            $insert_question_query = "INSERT INTO questions (Quiz_id, Question, Question_Type, Answer_1, Answer_2, Answer_3, Answer_4, Correct_Answer)
                                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                            if ($stmt_question = mysqli_prepare($connection, $insert_question_query)) {
                                mysqli_stmt_bind_param($stmt_question, 'isssssss', $quiz_id, $question, $question_type, $answer1, $answer2, $answer3, $answer4, $correct_answer);

                                if (!mysqli_stmt_execute($stmt_question)) {
                                    echo "<script>alert('Error inserting question.'); window.location.href='create_quiz.php';</script>";
                                    exit();
                                }
                                mysqli_stmt_close($stmt_question);
                            }
                        } elseif ($question_type == 'subjective') {
                            $insert_question_query = "INSERT INTO questions (Quiz_id, Question, Question_type, Correct_Answer)
                                                    VALUES (?, ?, ?, ?)";

                            if ($stmt_question = mysqli_prepare($connection, $insert_question_query)) {
                                mysqli_stmt_bind_param($stmt_question, 'isss', $quiz_id, $question, $question_type, $subjective_answers[$i]);

                                if (!mysqli_stmt_execute($stmt_question)) {
                                    echo "<script>alert('Error inserting question.'); window.location.href='create_quiz.php';</script>";
                                    exit();
                                }
                                mysqli_stmt_close($stmt_question);
                            }
                        }
                    }
                }

                $completion_time = time();
                $user_id = $_SESSION['userid'];
                $activity_type = "created";

                $activity_query = "INSERT INTO activity_summary (Modules_Name, Quiz_ID, Resource_ID, User_ID, Activity_Type, Starting_Timestamp, Completion_Timestamp)
                                VALUES (?, ?, NULL, ?, ?, ?, ?)";

                if ($activity_stmt = mysqli_prepare($connection, $activity_query)) {
                    mysqli_stmt_bind_param($activity_stmt, 'siisss', $module_name, $quiz_id, $user_id, $activity_type, date('Y-m-d H:i:s', $start_time), date('Y-m-d H:i:s', $completion_time));

                    if (!mysqli_stmt_execute($activity_stmt)) {
                        echo "<script>alert('Error inserting activity summary.'); window.location.href='create_quiz.php';</script>";
                        exit();
                    }
                    mysqli_stmt_close($activity_stmt);
                }

                echo "<script>alert('Quiz has been published successfully!'); window.location.href='create_quiz.php';</script>";
                exit();
            } else {
                echo "<script>alert('Failed to publish quiz. Please try again.'); window.location.href='create_quiz.php';</script>";
                exit();
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "<script>alert('Database error: Unable to process your request.'); window.location.href='create_quiz.php';</script>";
            exit();
        }
    }
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA CreateQuiz</title>
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
            flex-grow: 1; /
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
 
        #delete-all-btn {
            background-color: red;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
 
        #delete-all-btn:hover {
            background-color: darkred;
        }
 
 
        .create-quiz {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
 
        .create-quiz input, .create-quiz textarea, .create-quiz select {
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


        .styled-remove {
            background-color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease-in-out;
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
            margin-top: 20px;
        }
 
        .option {
            cursor: pointer;
        }
 
        .option:focus {
            outline: none;
        }
 
        h2 {
            color:  #b22222
        }
    </style>
</head>
<body>
    <?php
        include 'header.php';
    ?>
 
    <main class="main">
        <div class="section">
            <h2>Create Quiz</h2>
            <form class="create-quiz" method="POST" action="" enctype="multipart/form-data">
            <div class="input-group">
                <label for="Quiz_name">Quiz Name:</label>
                <input type="text" name="Quiz_name" placeholder="Enter quiz name">
            </div>
           
            <div class="input-group">
                <label for="Modules_Name">Select Module:</label>
                <select name="Modules_Name">
                    <option value="">Select Module</option>
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . $row['Modules_Name'] . '">' . $row['Modules_Name'] . '</option>';
                    }
                    ?>
                </select>
            </div>
 
            <div class="input-group">
                <label for="Quiz_description">Quiz Description:</label>
                <textarea name="Quiz_description" placeholder="Enter quiz description"></textarea>
            </div>
 
            <div class="input-group quiz-image">
                <img id="quiz-image-preview" src="" alt="Insert Quiz Image" />
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
                    <button type="button" id="delete-all-btn">Delete All</button>
                    <button type="submit" id="publish-btn" name="publish">Publish</button>
                </div>
            </form>
        </div>
    </main>
 
    <?php
        include 'footer.php';
    ?>
 
    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const insertImageBtn = document.getElementById('insert-image-btn');
        const imageInput = document.getElementById('image-input');
        const quizImagePreview = document.getElementById('quiz-image-preview');
        const deleteImageBtn = document.getElementById('delete-image-btn');
        const questionsContainer = document.getElementById('questions-container');
        const addQuestionBtn = document.getElementById('add-question-btn');
        const deleteAllBtn = document.getElementById('delete-all-btn');
        const quizForm = document.querySelector('.create-quiz');
        const publishBtn = document.getElementById('publish-btn');
        if (document.querySelectorAll('.dynamic-question').length === 0) {
            for (let i = 0; i < 4; i++) {
                createQuestionBlock();
            }
        }

        insertImageBtn.addEventListener('click', () => {
            imageInput.click();
        });

        imageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    quizImagePreview.src = e.target.result;
                    quizImagePreview.style.display = "block";
                };
                reader.readAsDataURL(file);
            }
        });

        deleteImageBtn.addEventListener('click', () => {
            quizImagePreview.src = ""; 
            imageInput.value = ""; 
        });

        deleteAllBtn.addEventListener('click', () => {
            const inputs = quizForm.querySelectorAll('input[type="text"], textarea, select');
            inputs.forEach(input => input.value = '');

            quizImagePreview.src = '';
            imageInput.value = '';

            questionsContainer.innerHTML = '';

            for (let i = 0; i < 5; i++) {
                createQuestionBlock();
            }

            alert('All fields will be cleared');
        });

        addQuestionBtn.addEventListener('click', createQuestionBlock);

        function createQuestionBlock() {
            const questionCount = document.querySelectorAll('.dynamic-question').length;
            const questionBlock = document.createElement('div');
            questionBlock.classList.add('dynamic-question');

            questionBlock.innerHTML = `
            <div class="question">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="text" name="question[]" placeholder="Question" style="flex-grow: 1;">
                        ${questionCount >= 5 ? `<button type="button" class="remove-question styled-remove">❌</button>` : ''}
                    </div>
                    <div class="options">
                        <div class="left">
                            <input type="text" name="answer1[]" class="option" placeholder="A.">
                            <input type="text" name="answer2[]" class="option" placeholder="B.">
                        </div>
                        <div class="right">
                            <input type="text" name="answer3[]" class="option" placeholder="C.">
                            <input type="text" name="answer4[]" class="option" placeholder="D.">
                        </div>
                    </div>
                    <textarea name="subjective_answer[]" class="subjective-answer" placeholder="Answer here..." style="display: none; font-family: Arial, sans-serif;"></textarea>
                    <select name="question_type[]" class="question-type">
                        <option value="objective">Type: Multiple Choice</option>
                        <option value="subjective">Type: Subjective</option>
                    </select>
                </div>
            `;

            const options = questionBlock.querySelector('.options');
            const subjectiveAnswer = questionBlock.querySelector('.subjective-answer');
            const questionType = questionBlock.querySelector('.question-type');

            questionType.addEventListener('change', function () {
                options.style.display = questionType.value === 'subjective' ? 'none' : 'flex';
                subjectiveAnswer.style.display = questionType.value === 'subjective' ? 'block' : 'none';
            });

            const optionInputs = questionBlock.querySelectorAll('.option');
            optionInputs.forEach(option => {
                option.addEventListener('blur', function () {
                    let currentAnswer = option.value.trim();
                    if (!currentAnswer) return;
                    let duplicate = false;

                    optionInputs.forEach(opt => {
                        if (opt !== option && opt.value.trim().toLowerCase() === currentAnswer.toLowerCase()) {
                            duplicate = true;
                        }
                    });

                    if (duplicate) {
                        alert("Duplicate answer choices are not allowed!");
                        option.value = ""; 
                    }
                });
                option.addEventListener('dblclick', function () {
                    if (!option.value.trim()) {
                        alert("Please enter an answer first.");
                        return;
                    }

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
                    correctAnswerInput.value = option.value.trim();
                });
            });

            const removeButton = questionBlock.querySelector('.remove-question');
            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    questionBlock.remove();
                    updateTotalQuestions();
                    updateRemoveButtons();
                });
            }

            questionsContainer.appendChild(questionBlock);
            updateTotalQuestions();
        }

        function updateTotalQuestions() {
            const totalQuestions = document.querySelectorAll('.dynamic-question').length;
            let totalQuestionInput = document.querySelector('input[name="Total_Question"]');

            if (!totalQuestionInput) {
                totalQuestionInput = document.createElement('input');
                totalQuestionInput.type = 'hidden';
                totalQuestionInput.name = 'Total_Question';
                quizForm.appendChild(totalQuestionInput);
            }

            totalQuestionInput.value = totalQuestions;
        }

        function updateRemoveButtons() {
            const questionBlocks = document.querySelectorAll('.dynamic-question');
            questionBlocks.forEach((questionBlock, index) => {
                const removeBtn = questionBlock.querySelector('.remove-question');
                if (index < 5 && removeBtn) {
                    removeBtn.remove();
                } else if (index >= 5 && !removeBtn) {
                    const newRemoveBtn = document.createElement('button');
                    newRemoveBtn.type = 'button';
                    newRemoveBtn.classList.add('remove-question');
                    newRemoveBtn.innerHTML = '❌ Remove';
                    newRemoveBtn.addEventListener('click', function () {
                        questionBlock.remove();
                        updateTotalQuestions();
                        updateRemoveButtons();
                    });
                    questionBlock.appendChild(newRemoveBtn);
                }
            });
        }
        


        publishBtn.addEventListener('click', function (event) {
            let isValid = true;
            let errorMessage = "Please fill in all required fields:\n";

            const quizName = document.querySelector('input[name="Quiz_name"]').value.trim();
            const moduleName = document.querySelector('select[name="Modules_Name"]').value.trim();
            const quizDescription = document.querySelector('textarea[name="Quiz_description"]').value.trim();
            const quizImage = document.getElementById('image-input').files.length;

            if (!quizName) { isValid = false; errorMessage += "- Quiz Name is required.\n"; }
            if (!moduleName) { isValid = false; errorMessage += "- Please select a Module.\n"; }
            if (!quizDescription) { isValid = false; errorMessage += "- Quiz Description is required.\n"; }
            if (quizImage === 0) { isValid = false; errorMessage += "- Please upload an image for the quiz.\n"; }

            const totalQuestions = document.querySelectorAll('.dynamic-question').length;
            if (totalQuestions < 5) {
                isValid = false;
                errorMessage += "- At least five questions are required.\n";
            }

            document.querySelectorAll('.dynamic-question').forEach((questionElement, index) => {
                const questionText = questionElement.querySelector('input[name="question[]"]').value.trim();
                const questionType = questionElement.querySelector('.question-type').value.trim();

                if (!questionText) {
                    isValid = false;
                    errorMessage += `- Question ${index + 1} is missing.\n`;
                }

                if (questionType === 'objective') {
                    const correctAnswer = questionElement.querySelector('.correct_answer')?.value.trim() || "";
                    if (!correctAnswer) {
                        isValid = false;
                        errorMessage += `- Question ${index + 1} does not have a correct answer selected.\n`;
                    }
                } else if (questionType === 'subjective') {
                    const subjectiveAnswer = questionElement.querySelector('.subjective-answer')?.value.trim() || "";
                    if (!subjectiveAnswer) {
                        isValid = false;
                        errorMessage += `- Question ${index + 1} is missing an answer.\n`;
                    }
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            } else {
                quizForm.submit();
            }
        });

        createQuestionBlock();
    });
 
</script>
</body>
</html>