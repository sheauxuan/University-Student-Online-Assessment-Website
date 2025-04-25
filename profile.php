<?php
    session_start();
    include 'connection.php';

    if(isset($_SESSION['userid'])){
        $userID = $_SESSION['userid'];
    }

    $errors = [];
    $successMessage = "";

    $query = "SELECT Username, Email, Password FROM users WHERE User_ID = '$userID'";
    $result = mysqli_query($connection, $query);
    $user = mysqli_fetch_assoc($result);

    $quizQuery = "SELECT COUNT(*) as total_quiz FROM progress WHERE User_ID = '$userID' AND Completion_Percentage = 100.00";
    $quizResult = mysqli_query($connection, $quizQuery);
    $quizData = mysqli_fetch_assoc($quizResult);

    $query_module_completed_count = "SELECT COUNT(*) AS completed_modules FROM (
        SELECT quiz_progress.Modules_Name FROM (
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
            )
    ) AS completed_module_count";
    $moduleResult = mysqli_query($connection, $query_module_completed_count);
    $moduleData = mysqli_fetch_assoc($moduleResult);

    $materialQuery = "SELECT COUNT(*) as total_material FROM activity_summary WHERE User_ID = '$userID' AND Resource_ID IS NOT NULL AND Activity_Type = 'view'";
    $materialResult = mysqli_query($connection, $materialQuery);
    $materialData = mysqli_fetch_assoc($materialResult);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $username = trim($_POST["txtusername"]);
        $gmail = trim($_POST["txtgmail"]);
        $current_password = trim($_POST["txtcurrentpassword"]);
        $new_password = trim($_POST["txtpassword"]);
        $confirm_password = trim($_POST["txtconfirmpassword"]);

        if (!preg_match("/^[a-zA-Z0-9]+$/", $username)) {
            $errors[] = "Username should only contain letters and numbers.";
        } elseif (strlen($username) > 20) {
            $errors[] = "Username can only be 20 characters or fewer.";
        } else{
            $cquery = "SELECT * FROM users WHERE Username = '$username'";
            $result = mysqli_query($connection, $cquery);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Username has been used, please choose another one.";
            }
        }

        if (!filter_var($gmail, FILTER_VALIDATE_EMAIL) || !preg_match("/@gmail\.com$/", $gmail)) {
            $errors[] = "Please enter a valid Gmail address.";
        } elseif (strlen($gmail) > 254) {
            $errors[] = "Email must be 254 characters or fewer.";
        }

        $isPasswordChange = ($current_password !== "" || $new_password !== "" || $confirm_password !== "");

        if ($isPasswordChange) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $errors[] = "All password fields must be filled to change the password.";
            } elseif (!password_verify($current_password, $user['Password'])) {
                $errors[] = "Current password is incorrect.";
            } elseif (!preg_match("/^(?=.*[a-zA-Z])(?=.*\d).+$/", $new_password)) {
                $errors[] = "New password must contain both letters and numbers.";
            } elseif (strlen($new_password) > 50) {
                $errors[] = "New password can only be 50 characters or fewer.";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match.";
            }
        }

        if (empty($errors)) {
            $updateQuery = "UPDATE users SET Username = '$username', Email = '$gmail'";

            if ($isPasswordChange) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $updateQuery .= ", Password = '$hashed_password'";
            }

            $updateQuery .= " WHERE User_ID = '$userID'";

            if (mysqli_query($connection, $updateQuery)) {
                $_SESSION['username'] = $username;
                $successMessage = "Profile updated successfully!";
            } else {
                $errors[] = "Error updating profile: " . mysqli_error($connection);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVa - Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #fdf3e4;
            color: #333;
        }
 
        h2{
            margin-top: 2%;
            margin-bottom:1%;
 
        }
        .main {
            font-family: Arial, sans-serif;
            background-color: #fdf3e4;
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
 
        .container {
            width: 40%;
            padding: 20px;
            text-align: center;
        }
       
        hr{
            height: 5px;
            width: 58%;;
            background-color:green;
            border: none;
        }
 
        .stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            width: 50%;
            margin-bottom: 20px;
            margin-top: 3%;
        }
 
        .box {
            background: green;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 70%;
        }
 
        .box h2 {
            margin: 0;
            color: white;
            font-size: 150%;
        }
 
        .box p {
            margin: 5px 0 0;
            font-size: 100%;
            margin-top:10%;
            color:white;
        }
 
        label {
            display: block;
            text-align: left;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom:8px;
            font-size: 100%;
            color:green;
        }
 
        input {
            margin-top: 5px;
            padding: 10px;
            border: 2px solid #ddd;
            border-color:green;
            border-radius: 6px;
            width:100%;
            box-sizing: border-box;
        }
 
        .btn-save {
            margin-top: 5%;
            padding: 10px 20px;
            background-color: #228b22;
            color: #fff;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            width: 30%;
            min-width:80px;
            text-align: center;
        }
 
        .btn-save:hover {
            background-color: #1a7321;
        }
 
        .success-message, .error {
            padding: 10px;
            margin: 15px auto;
            width: 80%;
            border-radius: 5px;
        }
 
        .success-message {
            color: green;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
 
        .error {
            color: red;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
 
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
            text-align: center;
            max-width: 100%;    
        }
 
        .password-container input {
            flex: 1;
            width: 100%;
        }
 
        .eye-icon {
            position: absolute;
            right: 10px;
            cursor: pointer;
            user-select: none;
        }
 
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main">
   
        <div class="stats">
            <div class="box"> <h2><?php echo $quizData['total_quiz']; ?></h2> <p>Total Quiz Done</p> </div>
            <div class="box"> <h2><?php echo $moduleData['completed_modules']; ?></h2> <p>Total Module Done</p> </div>
            <div class="box"> <h2><?php echo $materialData['total_material']; ?></h2> <p>Total Materials Review</p> </div>
        </div>
        <br><hr>
       
        <div class="container">
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?= $successMessage; ?></div>
            <?php endif; ?>
 
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
 
            <form method="post">
                <label>Username:</label>
                <input type="text" name="txtusername" value="<?= htmlspecialchars($user['Username']); ?>" required>
 
                <label>Email Address:</label>
                <input type="email" name="txtgmail" value="<?= htmlspecialchars($user['Email']); ?>" required>
 
                <label>Current Password:</label>
                <div class="password-container">
                    <input type="password" name="txtcurrentpassword" id="password1">
                    <span class="eye-icon"
                        onmousedown="showPassword('password1')"
                        onmouseup="hidePassword('password1')"
                        onmouseleave="hidePassword('password1')">👁️</span>
                </div>
 
                <label>New Password (Optional):</label>
                <div class="password-container">
                    <input type="password" name="txtpassword" id="password2" >
                    <span class="eye-icon"
                        onmousedown="showPassword('password2')"
                        onmouseup="hidePassword('password2')"
                        onmouseleave="hidePassword('password2')">👁️</span>
                </div>
 
                <label>Confirm New Password (Optional):</label>
                <div class="password-container">
                    <input type="password" name="txtconfirmpassword" id="password3" >
                    <span class="eye-icon"
                        onmousedown="showPassword('password3')"
                        onmouseup="hidePassword('password3')"
                        onmouseleave="hidePassword('password3')">👁️</span>
                </div>
 
                <button type="submit" class="btn-save" name="btnSave" >Save</button>
            </form>
        </div>
    </div>  
    <?php include 'footer.php'; ?>
 
    <script>
        function showPassword(fieldId) {
            var passwordField = document.getElementById(fieldId);
            passwordField.type = 'text';
        }
 
        function hidePassword(fieldId) {
            var passwordField = document.getElementById(fieldId);
            passwordField.type = 'password';
        }
 
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                var successMessage = document.getElementById("successMessage");
                if (successMessage) {
                    successMessage.style.animation = "fadeOut 1s";
                    setTimeout(() => successMessage.remove(), 1000);
                }
            }, 3000)
        });
    </script>
 
</body>
</html>
 