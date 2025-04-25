<?php
    session_start();
    require_once 'connection.php';

    if (!isset($connection)) {
        die("Database connection failed!!!");
    }

    if(isset($_GET['resetSessionVariable']) && ($_GET['resetSessionVariable'] == "true")){
        unset($_SESSION['question_number']);
        unset($_SESSION['quiz_id']);
        unset($_SESSION['quiz_name']);
        unset($_SESSION['modules_name']);
        unset($_SESSION['countOfCorrectAnswer']);
    }
    
    $isGuest = !isset($_SESSION['userid']); 
    $userId = $isGuest ? null : $_SESSION['userid']; 
    $userRole = $isGuest ? 'guest' : $_SESSION['userRole']; 

    $suggestedModulesQuery = "SELECT Modules_Name, Module_Image FROM modules ORDER BY RAND() LIMIT 5";
    $suggestedStmt = mysqli_prepare($connection, $suggestedModulesQuery);
    mysqli_stmt_execute($suggestedStmt);
    $suggestedResult = mysqli_stmt_get_result($suggestedStmt);
    $suggestedModules = [];

    while ($row = mysqli_fetch_assoc($suggestedResult)) {
        $row['Module_Image'] = !empty($row['Module_Image']) ? "data:image/jpeg;base64," . base64_encode($row['Module_Image']) : 'placeholder.jpg';
        $suggestedModules[] = $row;
    }
    mysqli_stmt_close($suggestedStmt);

    $recentModules = [];
    
    if (!$isGuest) {
        $recentModulesQuery = "SELECT DISTINCT a.Modules_Name, m.Module_Image 
                            FROM activity_summary a
                            JOIN modules m ON a.Modules_Name = m.Modules_Name
                            WHERE a.User_ID = ? 
                            ORDER BY a.Completion_Timestamp DESC 
                            LIMIT 3";
        $recentStmt = mysqli_prepare($connection, $recentModulesQuery);
        mysqli_stmt_bind_param($recentStmt, "i", $userId);
        mysqli_stmt_execute($recentStmt);
        $recentResult = mysqli_stmt_get_result($recentStmt);
    
        while ($row = mysqli_fetch_assoc($recentResult)) {
            $row['Module_Image'] = !empty($row['Module_Image']) ? "data:image/jpeg;base64," . base64_encode($row['Module_Image']) : 'placeholder.jpg';
            $recentModules[] = $row;
        }
        mysqli_stmt_close($recentStmt);
    }

    $playfulQuizQuery = "
        SELECT q.Quiz_Name, q.Quizz_Image 
        FROM activity_summary a
        JOIN quizzes q ON a.Quiz_ID = q.Quiz_ID
        JOIN users u ON a.User_ID = u.User_ID
        WHERE u.Role = 'student' AND a.Activity_Type = 'created'
        ORDER BY a.Starting_Timestamp DESC
        LIMIT 5
    ";
    $playfulQuizStmt = mysqli_prepare($connection, $playfulQuizQuery);
    mysqli_stmt_execute($playfulQuizStmt);
    $playfulQuizResult = mysqli_stmt_get_result($playfulQuizStmt);
    $playfulQuizzes = [];

    while ($row = mysqli_fetch_assoc($playfulQuizResult)) {
        $row['Quizz_Image'] = !empty($row['Quizz_Image']) ? "data:image/jpeg;base64," . base64_encode($row['Quizz_Image']) : 'placeholder.jpg';
        $playfulQuizzes[] = $row;
    }
    mysqli_stmt_close($playfulQuizStmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowVA - Home</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <div class="dashboard-container">
        <div class="left-container">
            <h1>
                    Welcome to KnowVA,  
                    <?php 
                        echo isset($_SESSION['username']) 
                        ? htmlspecialchars($_SESSION['username']) . "! 🎉" 
                        : "Guest! 👋"; 
                    ?>
            </h1>
                <section class="modules-section">
                    <h2>📌 Suggested Modules</h2>
                    <div class="module-section">
                        <?php foreach ($suggestedModules as $module) : ?>
                            <div class="module module-item" onclick="showModuleInfo('<?php echo urlencode($module['Modules_Name']); ?>')">
                                <img src="<?php echo $module['Module_Image']; ?>" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="<?php echo htmlspecialchars($module['Modules_Name']); ?>">
                                <div class="module-title"><?php echo htmlspecialchars($module['Modules_Name']); ?></div>
                                <button class="add-module-btn" onclick="event.stopPropagation(); addModule('<?php echo urlencode($module['Modules_Name']); ?>')">Add</button>	
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="modules-section">
                    <h2>🕘 Recently Accessed Modules</h2>
                    <div class="module-section">
                        <?php foreach ($recentModules as $module) : ?>
                            <div class="module module-item" onclick="showModuleInfo('<?php echo urlencode($module['Modules_Name']); ?>')">
                                <img src="<?php echo $module['Module_Image']; ?>" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="<?php echo htmlspecialchars($module['Modules_Name']); ?>">
                                <div class="module-title"><?php echo htmlspecialchars($module['Modules_Name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="right-container">
                <div class="create-quiz-container">
                <?php if (isset($_SESSION['userRole'])) : ?>
                    <?php if ($_SESSION['userRole'] === 'admin') : ?>
                        <button class="create-quiz-btn" onclick="window.location.href='after_create_button.php'">
                            Create New Module/ Quiz/Materials!
                        </button>
                    <?php else : ?>
                        <button class="create-quiz-btn" onclick="window.location.href='create_quiz.php'">
                            Create Your Own Quiz!
                        </button>
                    <?php endif; ?>
                <?php endif;?>
            </div>

            <h2>🎮 Playful Quiz</h2>
            <section class="playful-quiz-container">
                <div class="quiz-section">
                    <?php foreach ($playfulQuizzes as $quiz) : ?>
                        <div class="quiz-item2" onclick="showQuizInfo('<?php echo urlencode($quiz['Quiz_Name']); ?>')">
                            <img src="<?php echo $quiz['Quizz_Image']; ?>" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="<?php echo htmlspecialchars($quiz['Quiz_Name']); ?>">
                            <div class="quiz-title"><?php echo htmlspecialchars($quiz['Quiz_Name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <div id="moduleInfoModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">← Back</button>
            <h2 id="modal-title">Module Name</h2>
            <div class="tabs">
                <button class="tab-btn active" data-tab="description" onclick="switchTab('description')">Description</button>
                <button class="tab-btn" data-tab="quizzes" onclick="switchTab('quizzes')">Quiz</button>
                <button class="tab-btn" data-tab="materials" onclick="switchTab('materials')">Study Materials</button>
            </div>
            <div class="tab-content">
                <div id="tab-description" class="tab active">
                    <p id="modal-description">Description...</p>
                </div>
                <div id="tab-quizzes" class="tab">
                    <div id="modal-quizzes">No quizzes available.</div>
                </div>
                <div id="tab-materials" class="tab">
                    <div id="modal-materials">No study materials available.</div>
                </div>
            </div>
        </div>
    </div>

    <div id="quizPopup" class="modal" style="display: none;">
        <div class="quiz-container">
            <div class="quiz-header">
                <button class="back-btn" onclick="closeQuizPopup()">←</button>
                <h2 id="quizName">Quiz Name</h2>
            </div>

            <div class="quiz-meta">
                <p id="quizModule">Module Name</p>
                <p id="quizTotalQuestions">Total Questions: XX</p>
            </div>

            <div class="quiz-content">
                <div class="quiz-description-container">
                    <p class="quiz-description-title">Quiz Description:</p>
                    <p class="quiz-description" id="quizDescription">Quiz description goes here...</p>
                </div>
                <div class="quiz-buttons">
                    <button id="startQuizBtn">Start Quiz</button>
                    <button id="reviewQuizBtn" style="display: none;">Review</button>
                </div>
            </div>
        </div>
    </div>

    <div id="studyMaterialsPopup" class="modal" style="display: none;">
        <div class="study-container">
            <div class="study-header">
                <button class="back-btn" onclick="closeStudyMaterialsPopup()">←</button>
                <h2 id="materialsName">Study Material Name</h2>
            </div>

            <div class="study-meta">
                <p id="materialsModule">Module Name</p>
            </div>

            <div class="study-content">
                <div class="study-description-container">
                    <p class="study-description-title">Materials Description:</p>
                    <p class="study-description" id="materialsDescription">Description goes here...</p>
                </div>
                <div class="study-buttons">
                    <button id="viewMaterialsBtn">View Materials</button>
                </div>
            </div>
        </div>
    </div>

</main>

<?php include 'footer.php'; ?>

<script>

    window.userID = <?php echo json_encode($_SESSION['userid']); ?>;

    function showModuleInfo(moduleName) {
        if (!moduleName) return;

        const decodedModule = decodeURIComponent(moduleName.replace(/\+/g, " "));

        <?php if (isset($_SESSION['userid'])) : ?>
            window.userId = <?php echo json_encode($_SESSION['userid']); ?>;
        <?php else : ?>
            window.userId = null;
        <?php endif; ?>

        fetch(`fetch_module_info.php?name=${encodeURIComponent(decodedModule)}`)
            .then(response => response.json())
            .then(data => {
                console.log("Server Response:", data);
                
                if (data.error) {
                    console.error("an Module Fetch Error Occured:", data.error);
                    alert("Module not found: " + decodedModule);
                    return;
                }

                const modalTitle = document.getElementById("modal-title");
                const modalDescription = document.getElementById("modal-description");
                const quizzesContainer = document.getElementById("modal-quizzes");
                const materialsContainer = document.getElementById("modal-materials");
                const modalPopup = document.getElementById("moduleInfoModal");

                if (!modalTitle || !modalDescription || !quizzesContainer || !materialsContainer || !modalPopup) {
                    console.error("error about one or more module info modal elements are missing.");
                    return;
                }

                modalTitle.innerText = data.module_name || "Module Name";
                modalDescription.innerText = data.description || "No description available.";

                quizzesContainer.innerHTML = data.quizzes && data.quizzes.length 
                    ? data.quizzes.map(q => `
                        <div class="quiz-item" onclick="showQuizInfo('${q.Quiz_Name}')">
                            <img src="${q.Quizz_Image || 'placeholder.jpg'}" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="${q.Quiz_Name}">
                            <div class="quiz-title">${q.Quiz_Name}</div>
                        </div>
                    `).join('')
                    : "<p>No quizzes available.</p>";

                materialsContainer.innerHTML = data.studyMaterials && data.studyMaterials.length 
                    ? data.studyMaterials.map(m => `
                        <div class="study-item" onclick="showMaterialsInfo('${m.Resource_Name}')">
                            <img src="${m.Resource_Image || 'placeholder.jpg'}" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="${m.Resource_Name}">
                            <div class="study-title">${m.Resource_Name}</div>
                        </div>
                    `).join('')
                    : "<p>No study materials available.</p>";

                modalPopup.style.display = "flex";
            })
            .catch(error => console.error('Error fetching module info:', error));
    }

    function showQuizInfo(quizName) {
        if (!quizName) return;

        const decodedQuizName = decodeURIComponent(quizName);
        console.log("Fetching Quiz:", decodedQuizName);

        fetch(`fetch_quiz_info.php?quiz_name=${encodeURIComponent(decodedQuizName)}`)
            .then(response => response.json())
            .then(data => {
                console.log("Server Response:", data);

                if (data.error) {
                    console.error("Quiz Fetch Error:", data.error);
                    alert("Quiz not found: " + decodedQuizName);
                    return;
                }

                const quizPopup = document.getElementById("quizPopup");
                const quizTitle = document.getElementById("quizName");
                const quizModule = document.getElementById("quizModule");
                const quizTotalQuestions = document.getElementById("quizTotalQuestions");
                const quizDescription = document.getElementById("quizDescription");

                if (!quizPopup || !quizTitle || !quizModule || !quizTotalQuestions || !quizDescription) {
                    console.error("Error: One or more quiz popup elements are missing.");
                    return;
                }

                let startQuizBtn = document.getElementById('startQuizBtn');
                startQuizBtn.onclick = () => {
                    window.location.href = `startQuiz.php?quiz_id=${encodeURIComponent(data.Quiz_ID)}&quiz_name=${encodeURIComponent(data.Quiz_Name)}&modules_name=${encodeURIComponent(data.Modules_Name)}`;
                };

                document.getElementById('reviewQuizBtn').style.display = data.hasAttemptQuiz ? "block" : "none";
                let reviewQuizBtn = document.getElementById('reviewQuizBtn');
                reviewQuizBtn.onclick = () => {
                    window.location.href = `leaderboard.php?quiz_id=${encodeURIComponent(data.Quiz_ID)}&quiz_name=${encodeURIComponent(data.Quiz_Name)}&modules_name=${encodeURIComponent(data.Modules_Name)}`
                };
                
                quizTitle.innerText = data.Quiz_Name || "Unknown Quiz";
                quizModule.innerText = `Module: ${data.Modules_Name || "Unknown Module"}`;
                quizTotalQuestions.innerText = `Total Questions: ${data.Total_Question || "0"}`;
                quizDescription.innerText = data.Quiz_Description || "No description available.";
                quizPopup.style.display = "flex";
            })
            .catch(error => console.error('Error fetching quiz info:', error));
    }

    function switchTab(tabName) {
        console.log("Switching tab to:", tabName);

        document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
        document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));

        const selectedTab = document.getElementById(`tab-${tabName}`);
        const selectedTabBtn = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);

        if (!selectedTab || !selectedTabBtn) {
            console.error("Error about Tab or button not found for:", tabName);
            return;
        }

        selectedTab.classList.add("active");
        selectedTabBtn.classList.add("active");
    }

    function showMaterialsInfo(materialsName) {
        if (!materialsName) return;

        console.log("Fetching Study Material:", materialsName);

        fetch(`fetch_materials_info.php?materials_name=${encodeURIComponent(materialsName)}`)
            .then(response => response.json())
            .then(data => {
                console.log("📘 Parsed Materials Data:", data);

                if (data.error) {
                    alert("!!! " + data.error);
                    return;
                }

                const studyPopup = document.getElementById('studyMaterialsPopup');
                const materialsNameElement = document.getElementById('materialsName');
                const materialsModuleElement = document.getElementById('materialsModule');
                const materialsDescriptionElement = document.getElementById('materialsDescription');
                const viewMaterialsBtn = document.getElementById('viewMaterialsBtn');

                if (!studyPopup || !materialsNameElement || !materialsModuleElement || !materialsDescriptionElement || !viewMaterialsBtn) {
                    console.error("Error about one or more study materials popup elements are missing.");
                    return;
                }

                materialsNameElement.innerText = data.Resource_Name || "Unknown Material";
                materialsModuleElement.innerText = `Module: ${data.Modules_Name || "Unknown Module"}`;
                materialsDescriptionElement.innerText = data.Resource_Description || "No description available.";

                if (data.Resource_Link) {
                    viewMaterialsBtn.style.display = "block";
                    viewMaterialsBtn.onclick = () => window.location.href = `resource_video.php?resource_id=${encodeURIComponent(data.Resource_ID)}`;
                } else {
                    viewMaterialsBtn.style.display = "none";
                }

                studyPopup.style.display = "flex";
            })
            .catch(error => console.error('Error fetching study materials info:', error));
    }

    function closeStudyMaterialsPopup() {
        document.getElementById('studyMaterialsPopup').style.display = "none";
    }

    function closeQuizPopup() {
        const quizPopup = document.getElementById("quizPopup");
        if (quizPopup) {
            quizPopup.style.display = "none";
        } else {
            console.error("Quiz Popup element not found.");
        }
    }

    function closeModal() {
        document.getElementById("moduleInfoModal").style.display = "none";
    }
    function closeQuizPopup() {
        document.getElementById("quizPopup").style.display = "none";
    }

    function addModule(moduleName) {

        if (!window.userID || window.userID === null || window.userID === "null" || window.userID === 0) {
            alert("Error: User ID is missing. Please log in.");
            return;
        }

        let requestData = { module_name: moduleName, user_id: window.userID};

        fetch("add_module.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(requestData)
        })
        .then(response => response.text())  
        .then(text => {
            console.log("Raw Server Response:", text);  

            try {
                let data = JSON.parse(text);  
                if (data.success) {
                    alert("Module added successfully!");
                } else {
                    alert("!!! " + data.error);
                }
            } catch (error) {
                console.error("JSON Parse Error:", error, text); 
                alert("!!! Server did not return valid JSON. Check PHP logs.");
            }
        })
        .catch(error => console.error("Error adding module:", error));
    }

		function getUserId() {
			return localStorage.getItem("user_id") || sessionStorage.getItem("user_id") || 0;
		}
</script>

</body>
</html>
