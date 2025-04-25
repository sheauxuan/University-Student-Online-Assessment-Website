<?php
    session_start();
    include 'connection.php';

    $role = isset($_SESSION['userRole']) ? $_SESSION['userRole'] : '';

    if(isset($_GET['resetSessionVariable']) && ($_GET['resetSessionVariable'] == "true")){
		unset($_SESSION['question_number']);
		unset($_SESSION['quiz_id']);
		unset($_SESSION['quiz_name']);
		unset($_SESSION['modules_name']);
        unset($_SESSION['countOfCorrectAnswer']);
	}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA: Activity page</title>
    <link rel="stylesheet" href="main.css">
    <style>

        h3{
            color: #1a3b1d;
        }
        .main-section-frame{
            background-color:#1a3b1d;
            margin: 5%;
            border-radius: 20px;
        }

        .activity-categories{
            display: flex;
            padding-top: 1.5%;
            justify-content: center;
            margin-bottom: 20px;
        }

        .activity-categories button{
            margin: 0 10px;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            background: none;
            color: #ffffff;
            font-weight: bold;
            font-size: large;
        }

		.activity-categories button:hover{
            text-decoration: underline;
        }

        .category-btn.active{
            text-decoration: underline;
            color: rgb(255, 255, 255);
        }

        .activityQuizSection {
            display: flex;
            align-items: stretch;
            flex-direction: column;
            text-align: center;
            gap: 10px;
            margin: 2%;
            padding-bottom: 2%;
        }

        .activityQuiz-container {
            max-width: 100%;
            background-color: rgb(174, 206, 177);
            padding: 20px;
            justify-items: center;
            border-radius: 10px;
        }

        #categoryContent{
            justify-items: center;
            display: grid;
            grid-template-columns: auto auto auto; 
        }

        .activity-categories {
            gap: 5%;
        }

        .quiz-box, .module-box, .study-resource-box {
            max-width: 80%;
            max-height: 80%;
            align-items: center;
            gap: 1%;
        }

        .quiz-img, .module-img, .resource-img {
            width: 80%;
            height: 80%;
            object-fit: cover;
            border-radius: 10px;
        }

        .quiz-btn, .module-btn, .resource-btn {
            margin: 5%;
            width: 80%;
            height: 170px;
            background: linear-gradient(145deg, #ffebd2, #ffe2c2);
            border: 1px solid #ffd3a9;
            box-shadow: 3px 3px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }    

        .quiz-btn:hover, .module-btn:hover, .resource-btn:hover {
            color: #ff8d02;
            transform: scale(1.1);
        }

        .quiz-name, .module-name, .resource-name {
            color: #1a3b1d;
            font-size: medium;
            font-weight: bold;
            text-align: center;
            margin-top: -0.1%;
        }

        .quiz-container, .module-container, .resource-container {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .editQuizBtn, .editModuleBtn, .editMaterialBtn {
            background-color: #1a3b1d;
            width: auto;
            height: auto;
            top: 55%;
            left: 61%;
            position: absolute;
            border-radius: 30px;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .editQuizBtn:hover, .editModuleBtn:hover, .editMaterialBtn:hover {
            transform: scale(1.1);
            background-color: rgb(40, 89, 44);
        }

        .filterWrap{
            display: flex; 
            justify-content: flex-start;
            width: auto;
            padding: 10px;
            box-sizing: border-box;
        }

        .filter {
            position: relative;
            display: inline-block;

        }

        .filterBtn {
            background-color: #ffffff;
            border: 1px solid #ccc;
            padding: 5px 50px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }

        .filterContent {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            min-width: 150px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 100;
        }

        .filterContent button {
            width: 100%;
            padding: 10px;
            text-align: left;
            background-color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .filterContent button:hover {
            background-color: #f1f1f1;
        }

        @media (max-width: 679px){
            .activity-categories{
                display: flex;
                padding-top: 1.5%;
                justify-content: center;
                margin-bottom: 20px;
                gap: 1%;
            }

            .activity-categories button{
                margin: 0 10px;
                padding: 10px 20px;
                border: none;
                cursor: pointer;
                background: none;
                color: #ffffff;
                font-weight: bold;
                font-size: small;
            }

            .quiz-btn, .module-btn, .resource-btn {
                width: 75%;
                height: 140px;

            }    

            .quiz-name, .module-name, .resource-name {
                color: #1a3b1d;
                font-size: smaller;
                font-weight: bold;
                text-align: center;
                margin-top: -0.1%;
            }

            .quiz-container, .module-container, .resource-container {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                height: 200px;
            }

            .editQuizBtn, .editModuleBtn, .editResourceBtn {
                background-color: #1a3b1d;
                width: auto;
                height: auto;
                top: 50%;
                left: 61%;
                position: absolute;
                border-radius: 30px;
                border: none;
                font-size: 0.8rem;
                cursor: pointer;
            }

        }

    </style>

</head>
<body>
    <?php 
        include 'header.php';
    ?>          

    <main>
        <div class="main-section-frame">
            <div class="activity-categories">
                <button class="category-btn" id="on-going"  onclick="loadData('on-going')">On-Going</button>
                <button class="category-btn" id="completed"  onclick="loadData('completed')">Completed</button>
                <button class="category-btn" id="created" onclick="loadData('created')">Created</button>
                <button class="category-btn" id="reviewed-materials" onclick="loadData('reviewed-materials')">Reviewed Materials</button>  
            </div>

            <script>
                var currentPage = window.location.pathname;
                if(currentPage.includes("activity.php")){
                    document.getElementById("on-going").classList.add("active");
                }
            </script>
    
            <div class="activityQuizSection">
                <div class=filterWrap>
                    <div class="filter">
                        <button class="filterBtn" onClick="openFilter()">Filter ▼</button>
                        <div class="filterContent" id="filterContent" style="display: none;">
                            <button onclick="filterContent('modules')">Modules</button>
                            <button onclick="filterContent('quiz')">Quiz</button>
                            <button onclick="filterContent('resources')">Study Resources</button>
                        </div>
                    </div>
                </div>
                <div class="activityQuiz-container">     
                    <div id="categoryContent"></div>
                </div>
            </div>

            <script>
                function loadData(categories){
                    const xhttp = new XMLHttpRequest();
                    xhttp.onreadystatechange = function () {
                        if (this.readyState === 4 && this.status === 200) {
                            document.getElementById("categoryContent").innerHTML = this.responseText;
                        }
                    };

                    xhttp.open("GET", "loadCategoryQuiz.php?category=" + categories, true);
                    xhttp.send();

                    const buttons = document.querySelectorAll(".category-btn");
                    buttons.forEach(button => button.classList.remove("active")); 
                    document.getElementById(categories).classList.add("active"); 

                    console.log("Role: <?php echo $role; ?>");

                    if (categories === "created" && "<?php echo $role; ?>" === "admin") {
                        document.querySelector(".filter").style.display = "block";  
                    } else {
                        document.querySelector(".filter").style.display = "none";
                    }
                }    

                function openFilter() {
                    var dropdown = document.getElementById("filterContent");
                    if (dropdown.style.display === "block") {
                        dropdown.style.display = "none";
                    } else {
                        dropdown.style.display = "block";
                    }
                }

                function filterContent(filterType) {
                    const xhttp = new XMLHttpRequest();

                    xhttp.onreadystatechange = function () {
                        if (this.readyState === 4 && this.status === 200) {
                            const contentDiv = document.getElementById("categoryContent");
                            contentDiv.innerHTML = this.responseText;

                            if (!contentDiv.innerHTML.trim()) {
                                contentDiv.innerHTML = `<h3>No ${filterType.charAt(0).toUpperCase() + filterType.slice(1)} available.</h3>`;
                            }
                        }
                    };

                    xhttp.open("GET", "loadCategoryQuiz.php?category=created&filter=" + filterType, true);
                    xhttp.send();
                }

                window.onclick = function(event) {
                    if (!event.target.matches('.filterBtn')) {
                        var dropdown = document.getElementById("filterContent");
                        if (dropdown && dropdown.style.display === "block") {
                            dropdown.style.display = "none";
                        }
                    }
                };

                window.onload = function(){
                    loadData('on-going');
                }

            </script> 

            
        </div>
    </main>
    <?php
        include 'footer.php';
    ?> 

</body>
</html> 