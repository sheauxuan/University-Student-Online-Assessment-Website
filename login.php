<?php
    session_start();
    include 'connection.php';

    $successMessage ="";
    $errorMessage = "";

    if(isset($_POST['btnLogin'])){
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $query = "SELECT User_ID, Username, Password, Role FROM users WHERE LOWER(Username) = LOWER(?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $hashedPassword = $row['Password'];

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['userid'] = $row['User_ID'];
                $_SESSION['username'] = $row['Username'];
                $_SESSION['userRole'] = $row['Role'];

                $successMessage = "Login successful! Redirecting to Home Page...";
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 3000);
                    </script>";
            } else {
                $errorMessage = "Incorrect username or password! Please try again.";
            }
        } else {
            $errorMessage = "Incorrect username or password! Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVa-Login</title>
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

        .auth-section {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .auth-container {
            max-width: 400px;
            margin: 0 auto;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
        }

        .auth-form label {
            margin-top: 10px;
            font-weight: bold;
        }

        .auth-form input {
            margin-top: 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .auth-form p {
            margin-top: 10px;
            font-size: 14px;
        }

        .auth-form a {
            color: #b22222;
            text-decoration: none;
        }

        .auth-form a:hover {
            text-decoration: underline;
        }

        .submit-btn {
            margin-top: 20px;
            margin-left:20%;
            margin-right:20%;
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

        .success-message, .error-message {
            text-align: center;
            font-size: 18px;
            padding: 2%;
            border-radius: 5px;
            width: 50%;
            margin: 3% auto;
            position: relative;
            animation: fadeIn 0.5s;
        }

        .success-message {
            color: white;
            background-color: #4CAF50;
        }

        .error-message {
            color: white;
            background-color: #d9534f;
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
        }

        .password-container input {
            flex: 1;
        }

        .eye-icon {
            position: absolute;
            right: 10px;
            cursor: pointer;
        }

    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <?php if (!empty($successMessage)) : ?>
        <div class="success-message" id="successMessage">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)) : ?>
        <div class="error-message" id="errorMessage">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <main>
        <section class="auth-section">
            <div class="auth-container">
                <h2 style="color:green;">Login</h2>
                <form action="#" method="post" class="auth-form">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                    <label for="password">Password:</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" required>
                        <span class="eye-icon" 
                            onmousedown="showPassword('password')" 
                            onmouseup="hidePassword('password')" 
                            onmouseleave="hidePassword('password')">👁️</span>
                    </div>
                    <p>
                        <a href="signup.php">Do not have an account? Click to <u>Sign up!</u></a>
                    </p>
                    <button type="submit" class="submit-btn" name="btnLogin">Login</button>
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
