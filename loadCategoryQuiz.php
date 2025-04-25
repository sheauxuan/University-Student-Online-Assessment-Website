<?php
    session_start();
    include("connection.php");

    if(isset($_SESSION['userid']) && isset($_GET['category'])){

        $category = $_GET['category'];
        $userid = $_SESSION['userid'];

        if($category == "on-going"){
            $query3 = "SELECT quizzes.Quiz_ID, Quiz_Name, activity_summary.User_ID, Quizz_Image FROM quizzes 
            INNER JOIN activity_summary 
            ON quizzes.Quiz_ID = activity_summary.Quiz_ID
            WHERE Activity_Type = 'on-going' AND activity_summary.User_ID = $userid";
        }
        elseif($category == "completed"){
            $query3 = "SELECT DISTINCT quizzes.Quiz_ID, Quiz_Name, activity_summary.User_ID, Quizz_Image FROM quizzes 
            INNER JOIN activity_summary 
            ON quizzes.Quiz_ID = activity_summary.Quiz_ID
            WHERE Activity_Type = 'completed' AND activity_summary.User_ID = $userid";
        }
        elseif($category == "created"){
            $filter = isset($_GET['filter']) ? $_GET['filter'] : ""; 

            if($filter == "quiz"){
                $query3 = "SELECT quizzes.Quiz_ID, Quiz_Name, Quizz_Image FROM quizzes
                                    INNER JOIN activity_summary ON quizzes.Quiz_ID = activity_summary.Quiz_ID
                                    INNER JOIN users ON activity_summary.User_ID = users.User_ID
                                    WHERE Activity_Type = 'created' AND activity_summary.User_ID = $userid";
            }else if($filter == "modules"){
                $query3 = "SELECT DISTINCT modules.Modules_Name, Module_Image FROM modules
                                    INNER JOIN activity_summary ON modules.Modules_Name = activity_summary.Modules_Name
                                    INNER JOIN users ON activity_summary.User_ID = users.User_ID
                                    WHERE Activity_Type = 'created' AND activity_summary.User_ID = $userid";
            }else if($filter == "resources"){
                $query3 = "SELECT study_resources.Resource_ID, Resource_Name, Resource_Image FROM study_resources
                                    INNER JOIN activity_summary ON study_resources.Resource_ID = activity_summary.Resource_ID
                                    INNER JOIN users ON activity_summary.User_ID = users.User_ID
                                    WHERE Activity_Type = 'created' AND activity_summary.User_ID = $userid";
            }else{
                $query3 = "SELECT quizzes.Quiz_ID, Quiz_Name, Quizz_Image FROM quizzes
                                    INNER JOIN activity_summary ON quizzes.Quiz_ID = activity_summary.Quiz_ID
                                    INNER JOIN users ON activity_summary.User_ID = users.User_ID
                                    WHERE Activity_Type = 'created' AND activity_summary.User_ID = $userid";
            }
        }elseif($category == "reviewed-materials"){
            $query3 = "SELECT study_resources.Resource_ID, Resource_Name, Resource_Image FROM study_resources
                                    INNER JOIN activity_summary ON study_resources.Resource_ID = activity_summary.Resource_ID
                                    INNER JOIN users ON activity_summary.User_ID = users.User_ID
                                    WHERE Activity_Type = 'view' AND activity_summary.User_ID = $userid";
        }
        else{
            echo 'error occurred';
        }

        $results3 = mysqli_query($connection, $query3);

        if(mysqli_num_rows($results3) > 0){
            while($row = mysqli_fetch_assoc($results3)){
                if ($category == "created" && isset($_GET['filter'])) {
                    $filter = $_GET['filter'];
        
                    if ($filter == "quiz") {
                        $quizID = $row['Quiz_ID'];
                        $quizName = $row['Quiz_Name'];
                        $quizImage = isset($row['Quizz_Image']) ? base64_encode($row['Quizz_Image']) : null;
                        $quizImageSrc = $quizImage ? "data:image/png;base64," . $quizImage : "default-quiz.png"; 
                        
                        echo '
                            <div class="quiz-box" data-type="quiz">
                                <div class="quiz-container">
                                    <a href="modules.php?quiz_id=' . $quizID . '&quiz_name=' . urlencode($quizName) . '" class="quiz-btn">
                                        <img src="' . $quizImageSrc . '" alt="Quiz Image" class="quiz-img">
                                    </a>
                                    <p class="quiz-name">' . $quizName . '</p>
                                    <form action="editQuiz.php" method="POST">
                                        <input type="hidden" name="quizID" value="' . $quizID . '">
                                        <button type="submit" class="editQuizBtn">✏️</button>
                                    </form>
                                </div>
                            </div>';
                    } 
                    elseif ($filter == "modules") {
                        $modulesName = $row['Modules_Name'];
                        $moduleImage = isset($row['Module_Image']) ? base64_encode($row['Module_Image']) : null;
                        $moduleImageSrc = $moduleImage ? "data:image/png;base64," . $moduleImage : "default-module.png";
        
                        echo '
                            <div class="module-box" data-type="module">
                                <div class="module-container">
                                    <a href="modules.php?module_name=' . urlencode($modulesName) . '" class="module-btn">
                                        <img src="' . $moduleImageSrc . '" alt="Module Image" class="module-img">
                                    </a>
                                    <p class="module-name">' . $modulesName . '</p>
                                    <form action="editModule.php" method="GET">
                                        <input type="hidden" name="modulesName" value="' . $modulesName . '"">
                                        <button type="submit" class="editModuleBtn">✏️</button>
                                    </form>
                                </div>
                            </div>';

                    }
                    elseif ($filter == "resources") {
                        $resourceID = $row['Resource_ID'];
                        $resourceName = $row['Resource_Name'];
                        $resourceImage = isset($row['Resource_Image']) ? base64_encode($row['Resource_Image']) : null;
                        $resourceImageSrc = $resourceImage ? "data:image/png;base64," . $resourceImage : "default-resource.png";
        
                        echo '<div class="study-resource-box" data-type="resource">
                                <div class="resource-container">
                                    <a href="resource_video.php?resource_id=' . $resourceID . '" class="resource-btn">
                                        <img src="' . $resourceImageSrc . '" alt="Study Resource Image" class="resource-img">
                                    </a>
                                    <p class="resource-name">' . $resourceName . '</p>
                                    <form action="editStudyMaterial.php" method="GET">
                                        <input type="hidden" name="resourceID" value="' . $resourceID . '">
                                        <button class="editMaterialBtn">✏️</button>
                                    </form>
                                </div>
                            </div>';

                    }
                } elseif ($category == "reviewed-materials") { 
                    $resourceID = $row['Resource_ID'];
                    $resourceName = $row['Resource_Name'];
                    $resourceImage = isset($row['Resource_Image']) ? base64_encode($row['Resource_Image']) : null;
                    $resourceImageSrc = $resourceImage ? "data:image/png;base64," . $resourceImage : "default-resource.png";
                
                    echo '<div class="study-resource-box" data-type="resource">
                            <div class="resource-container">
                                <a href="resource_video.php?resource_id=' . $resourceID . '" class="resource-btn">
                                    <img src="' . $resourceImageSrc . '" alt="Study Resource Image" class="resource-img">
                                </a>
                                <p class="resource-name">' . $resourceName . '</p>
                            </div>
                        </div>';
                } else if ($category == "on-going") {
                    $quizID = $row['Quiz_ID'];
                    $quizName = $row['Quiz_Name'];
                    $quizImage = isset($row['Quizz_Image']) ? base64_encode($row['Quizz_Image']) : null;
                    $quizImageSrc = $quizImage ? "data:image/png;base64," . $quizImage : "default-quiz.png"; 
                    
                    echo '
                        <div class="quiz-box" data-type="quiz">
                            <div class="quiz-container">
                                <a href="modules.php?quiz_id=' . $quizID . '&quiz_name=' . urlencode($quizName) . '" class="quiz-btn">
                                    <img src="' . $quizImageSrc . '" alt="Quiz Image" class="quiz-img">
                                </a>
                                <p class="quiz-name">' . $quizName . '</p>
                            </div>
                        </div>';
                } else if ($category == "completed") {
                    $quizID = $row['Quiz_ID'];
                    $quizName = $row['Quiz_Name'];
                    $quizImage = isset($row['Quizz_Image']) ? base64_encode($row['Quizz_Image']) : null;
                    $quizImageSrc = $quizImage ? "data:image/png;base64," . $quizImage : "default-quiz.png"; 
                    
                    echo '
                        <div class="quiz-box" data-type="quiz">
                            <div class="quiz-container">
                                <a href="modules.php?quiz_id=' . $quizID . '&quiz_name=' . urlencode($quizName) . '" class="quiz-btn">
                                    <img src="' . $quizImageSrc . '" alt="Quiz Image" class="quiz-img">
                                </a>
                                <p class="quiz-name">' . $quizName . '</p>
                            </div>
                        </div>';
                } else {
                    $quizID = $row['Quiz_ID'];
                    $quizName = $row['Quiz_Name'];
                    $quizImage = isset($row['Quizz_Image']) ? base64_encode($row['Quizz_Image']) : null;
                    $quizImageSrc = $quizImage ? "data:image/png;base64," . $quizImage : "default-quiz.png"; 
                    
                    echo '
                        <div class="quiz-box" data-type="quiz">
                            <div class="quiz-container">
                                <a href="modules.php?quiz_id=' . $quizID . '&quiz_name=' . urlencode($quizName) . '" class="quiz-btn">
                                    <img src="' . $quizImageSrc . '" alt="Quiz Image" class="quiz-img">
                                </a>
                                <p class="quiz-name">' . $quizName . '</p>
                                <form action="editQuiz.php" method="POST">
                                    <input type="hidden" name="quizID" value="' . $quizID . '">
                                    <button type="submit" class="editQuizBtn">✏️</button>
                                </form>
                            </div>
                        </div>';
                }
            } 
        }else {
            echo '<h3>Nothing here yet...</h3>';
        }    
    }   
    else{
        echo '<h3>Please Login to View Your Quizzes!</h3>';
    }

?>