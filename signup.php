<?php
    session_start();
    include 'connection.php';

    $errors = [];
    $successMessage ="";

    if (isset($_POST['btnSignUp'])) {
        $username = $_POST["txtusername"];
        $gmail = $_POST["txtgmail"];
        $password = $_POST["txtpassword"];
        $confirm_password = $_POST["txtrpassword"];

        if (strpos($gmail, ' ') !== false) {
            $errors[] = "Email cannot contain spaces.";
        }else if (strpos($password, ' ') !== false) {
            $errors[] = "Password cannot contain spaces.";
        }

        if (!preg_match("/^[a-zA-Z0-9]+$/", $username)) {
            $errors[] = "Username should only contain alphabetic characters and digit numbers (no space allowed).";
        } else if (strlen($username) > 21) {
            $errors[] = "Username can be only 20 characters or fewer.";
        } else if (!filter_var($gmail, FILTER_VALIDATE_EMAIL) || !preg_match("/@gmail\.com$/", $gmail)) {
            $errors[] = "Please enter a valid Gmail Address.";
        } else if (strlen($gmail) > 255) {
            $errors[] = "Email must be 254 characters or fewer.";
        } else if (!preg_match("/^(?=.*[a-zA-Z])(?=.*\d).+$/", $password)) {
            $errors[] = "Password must contain both letters and numbers.";
        } else if (strlen($password) > 51) {
            $errors[] = "Password can be only 50 characters or fewer.";
        } else if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } else {
            $cquery = "SELECT * FROM users WHERE Username = '$username'";
            $result = mysqli_query($connection, $cquery);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "Username has been used, please choose another one.";
            }else if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $query = "INSERT INTO users(Username, Email, Password, Role) VALUES ('$username', '$gmail', '$hashed_password', 'student')";
                if (mysqli_query($connection, $query)) {
                    $successMessage = "Account created successfully! Directing to Login Page...";
                    $results = mysqli_query($connection, "SELECT User_ID, Username, Role FROM users WHERE Username = '$username'");
                    if ($results) {
                        while ($row = mysqli_fetch_assoc($results)) {
                            $_SESSION['userid'] = $row['User_ID'];
                            $_SESSION['username'] = $row['Username'];
                            $_SESSION['userRole'] = $row['Role'];

                        }
                    }
                
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 3000);
                    </script>";
                } else {
                    echo 'Error occurred: ' . mysqli_error($connection);
                }
                mysqli_close($connection);
            }       
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVa-Sign Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #fdf3e4;
            color: #333;
        }

        main {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 3% auto; 
        }

        .su-section {
            background-color: #fff;
            padding: 2%;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            max-width: 80%;
        }

        .su-container {
            max-width: 100%;
            width:80% auto;
            margin:auto;
        }

        .su-form {
            display: flex;
            flex-direction: column;
        }

        .su-form label {
            margin-top: 10px;
            font-weight: bold;
        }

        .su-form input {
            margin-top: 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width:100%;
            box-sizing: border-box;
        }

        .su-form p {
            margin-top: 10px;
            font-size: 14px;
        }

        .su-form a {
            color: #b22222;
            text-decoration: none;
        }

        .su-form a:hover {
            text-decoration: underline;
        }

        .submit-btn {
            margin-top: 10%;
            margin-left:30%;
            margin-right:30%;
            padding: 10px 20px;
            background-color: #228b22;
            color: #fff;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #1a7321;
        }

        .error { 
            color: red; 
            text-align: center;
        }

        .success-message {
            text-align: center;
            font-size: 18px;
            color: white;
            background-color: #4CAF50;
            padding: 10px;
            border-radius: 5px;
            width: 50%;
            margin: 20px auto;
            position: relative;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .password-container {
            position: relative;
            display: flex;
            align-items: center;
            text-align: center;
            max-width: 100%;
            width:380px;
        }

        .password-container input {
            flex: 1;
            width: 100%;
            box-sizing: border-box;
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

    <?php if (!empty($successMessage)): ?>
        <div class="success-message" id="successMessage"><?= $successMessage; ?></div>
    <?php endif; ?>

    <main>
        <section class="su-section">
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
            <div class="su-container">
                <h2 style="text-align:center; color:green; margin:0; padding-top:3%; padding-bottom:5%;">Sign Up</h2>
                <form action="#" method="post" class="su-form">
                    <label for="username">Username:</label>
                    <input type="text" name="txtusername" required>
                    <label for="email">Email Address:</label>
                    <input type="email" name="txtgmail" required>
                    <label for="password">Password:</label>
                    <div class="password-container">
                        <input type="password" name="txtpassword" id="password1" required>
                        <span class="eye-icon" 
                            onmousedown="showPassword('password1')" 
                            onmouseup="hidePassword('password1')" 
                            onmouseleave="hidePassword('password1')">👁️</span>
                    </div>
                    <label for="rpassword">Re-enter Password:</label>
                    <div class="password-container">
                        <input type="password" name="txtrpassword" id="password2" required>
                        <span class="eye-icon" 
                            onmousedown="showPassword('password2')" 
                            onmouseup="hidePassword('password2')" 
                            onmouseleave="hidePassword('password2')">👁️</span>
                    </div>
                    <button type="submit" class="submit-btn" name="btnSignUp">Sign Up</button><br>
                </form>
            </div>
        </section>
    </main>

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
