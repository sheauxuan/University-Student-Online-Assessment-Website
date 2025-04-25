<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
 
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Almendra+SC&display=swap');
 
        header {
            background-color: #b22222;
            color: #fff;
            padding: 15px 20px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
        }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 20px;
        }
        .logo {
            font-family: 'Almendra SC', serif;
            font-size: 32px;
            color: #fff;
            margin: 5px;
        }
        .search-container {
            flex-grow: 1;
            display: flex;
            max-width: 75%;
        }
 
        .search-bar {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 20px;
            box-sizing: border-box;
        }
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-buttons button {
            padding: 10px 15px;
            font-weight: bold;
            border: none;
            margin-bottom: 3%;
            border-radius: 20px;
            cursor: pointer;
        }
        .profile-button {
            padding: 1%;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0;
        }
        .profile-button img {
            width: 35px;
            height: 35px;
        }
        .logout-button{
            background-color:rgba(255, 154, 22, 0.97);
            color:white;
            cursor: pointer;
            white-space: nowrap;
        }

        .logout-button:hover ,.profile-button:hover{
            background-color:rgba(255, 193, 118, 0.97);
        }
 
        .navigation {
            margin-top: 1%;
            text-align: center;
        }
        .navigation a {
            margin: 0 15px;
            text-decoration: none;
            color: #fff;
            font-weight: bold;
        }
        .navigation a:hover {
            text-decoration: none;
            color: red;
            font-size: 22px;
            padding: 6px;
            border-radius: 38px;
            margin-bottom: -10px;
            transition: 0.5s;
        }
    </style>
 
</head>
<body>
    <header>
        <div class="logo-section">
            <div class="logo">KnowVA</div>
            <form action="modules.php" method="GET" class="search-container">
                <input type="text" id="search-bar" class="search-bar" name="userSearch" placeholder="Search modules, quizzes, materials...">
            </form>
            <div class="header-buttons">
                <?php
                    if(isset($_SESSION['userid'])){
                        echo '<button class="logout-button" onclick="window.location.href=\'logout.php\';">Log Out</button>';
                        echo '<a href="profile.php" class="profile-button"> <img src="images/profile.png" alt="profile"></a>';
                    }
                    else{
                        echo '<button class="login" onclick="window.location.href=\'login.php\';" style="background-color:rgba(255, 154, 22, 0.97); color:white;">Login/SignUp</button>';
                    }
                ?>
            </div>
        </div>
        <nav class="navigation">
            <a href="index.php?resetSessionVariable=true" class="navigation-links" id="index">Home</a>
            <a href="activity.php?resetSessionVariable=true" class="navigation-links" id="activity">Activity</a>
            <a href="added_modules.php?resetSessionVariable=true" class="navigation-links" id="modules">Modules</a>
            <a href="achievements.php" class="navigation-links" id="achievements">Achievements</a>
        </nav>
    </header>
 
 
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let searchInput = document.getElementById("search-bar");
 
            if (searchInput) {
                searchInput.addEventListener("keyup", function () {
                    let filter = searchInput.value.toLowerCase();
                    let allItems = document.querySelectorAll(".module, .quiz-item, .study-item");
 
                    allItems.forEach(item => {
                        let titleElement = item.querySelector(".module-title");
                        if (titleElement) {
                            let title = titleElement.innerText.toLowerCase();
                            if (title.includes(filter)) {
                                item.style.display = "block";
                            } else {
                                item.style.display = "none";
                            }
                        }
                    });
                });
            }
        });
    </script>
   
</body>
</html>