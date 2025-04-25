<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

require_once "connection.php";

$userId = $_SESSION['userid'];

$query = "SELECT m.Modules_Name, m.Module_Image 
          FROM course_enrollment ce
          JOIN modules m ON ce.Modules_Name = m.Modules_Name
          WHERE ce.User_ID = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$enrolledModules = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrolled Modules</title>
    <link rel="stylesheet" href="main.css">
    
    <style>
        body {
            background-color: #fdf6e3;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 85%;
            margin: 20px auto;
            text-align: center;
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            color: #222;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            justify-content: center;
            padding-top: 20px;
        }

        .module-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 15px;
            transition: transform 0.2s ease;
        }

        .module-card:hover {
            transform: translateY(-5px);
        }

        .module-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
        }

        .module-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }

        .remove-btn {
            background-color: red;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .remove-btn:hover {
            background-color: darkred;
        }

        .view-more-btn {
            background-color: #007bff;
            color: white;
            font-size: 16px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 30px;
            display: inline-block;
            text-decoration: none;
        }

        .view-more-btn:hover {
            background-color: #0056b3;
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
    </style>

    <script>
        function removeModule(moduleName) {
            if (!confirm(`Are you sure you want to remove ${moduleName}?`)) return;

            fetch("remove_module.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ module_name: moduleName })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Module removed successfully!");
                    location.reload();
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(error => console.error("Error removing module:", error));
        }

        function filterModules() {
            let filter = document.getElementById("search-modules").value.toLowerCase();
            let modules = document.querySelectorAll(".module-card");

            modules.forEach(module => {
                let title = module.querySelector(".module-title").innerText.toLowerCase();
                module.style.display = title.includes(filter) ? "block" : "none";
            });
        }
		
        function showModuleInfo(moduleName) {
            if (!moduleName) return;

            const decodedModule = decodeURIComponent(moduleName.replace(/\+/g, " "));
            console.log("Fetching Module:", decodedModule);

            fetch(`fetch_module_info.php?name=${encodeURIComponent(decodedModule)}`)
                .then(response => response.json())
                .then(data => {
                    console.log("📄 Server Response:", data);
                    
                    if (data.error) {
                        console.error("Module Fetch Error:", data.error);
                        alert("Module not found: " + decodedModule);
                        return;
                    }

                    const modalTitle = document.getElementById("modal-title");
                    const modalDescription = document.getElementById("modal-description");
                    const quizzesContainer = document.getElementById("modal-quizzes");
                    const materialsContainer = document.getElementById("modal-materials");
                    const modalPopup = document.getElementById("moduleInfoModal");

                    if (!modalTitle || !modalDescription || !quizzesContainer || !materialsContainer || !modalPopup) {
                        console.error("Error: One or more module info modal elements are missing.");
                        return;
                    }

                    modalTitle.innerText = data.module_name || "Module Name";
                    modalDescription.innerText = data.description || "No description available.";

                    quizzesContainer.innerHTML = (data.quizzes && data.quizzes.length)
                        ? data.quizzes.map(q => `
                            <div class="quiz-item" onclick="showQuizInfo('${q.Quiz_Name}')">
                                <img src="${q.Quizz_Image || 'placeholder.jpg'}" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="${q.Quiz_Name}">
                                <div class="quiz-title">${q.Quiz_Name}</div>
                            </div>
                        `).join('')
                        : "<p>No quizzes available.</p>";

                    materialsContainer.innerHTML = (data.studyMaterials && data.studyMaterials.length)
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

        function closeModal() {
            document.getElementById("moduleInfoModal").style.display = "none";
        }

        function switchTab(tabName) {
            document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
            document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));

            document.getElementById(`tab-${tabName}`).classList.add("active");
            document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add("active");
        }

        function showQuizInfo(quizName) {
            if (!quizName) return;

            fetch(`fetch_quiz_info.php?quiz_name=${encodeURIComponent(quizName)}`)
                .then(response => response.json())
                .then(data => {
                    console.log("Server Response:", data);

                    if (data.error) {
                        console.error("Quiz Fetch Error:", data.error);
                        alert("Quiz not found: " + quizName);
                        return;
                    }

                    const quizPopup = document.getElementById("quizPopup");
                    const quizTitle = document.getElementById("quizName");
                    const quizModule = document.getElementById("quizModule");
                    const quizTotalQuestions = document.getElementById("quizTotalQuestions");
                    const quizDescription = document.getElementById("quizDescription");

                    if (!quizPopup || !quizTitle || !quizModule || !quizTotalQuestions || !quizDescription) {
                        console.error("There is an Error: One or more quiz popup elements are missing.");
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
                .catch(error => console.error('an Error fetching quiz info:', error));
        }

        function closeQuizPopup() {
            const quizPopup = document.getElementById("quizPopup");
            if (quizPopup) {
                quizPopup.style.display = "none";
            } else {
                console.error("An Error: Quiz Popup element not found.");
            }
        }

        function showMaterialsInfo(materialsName) {
            if (!materialsName) return;

            console.log("Fetching Study Material:", materialsName);

            fetch(`fetch_materials_info.php?materials_name=${encodeURIComponent(materialsName)}`)
                .then(response => response.json())
                .then(data => {
                    console.log("Parsed Materials Data:", data);

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
                        console.error("An Error: One or more study materials popup elements are missing.");
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
                .catch(error => console.error(' AN Error fetching study materials info:', error));
        }

        function closeStudyMaterialsPopup() {
            document.getElementById('studyMaterialsPopup').style.display = "none";
        }

    </script>
</head>

<body>
    <?php
        include 'header.php';
    ?>

    <main>
        <div class="container">
            <h1 class="section-title">📚 My Enrolled Modules</h1>
            <div class="modules-grid">
                <?php if (!empty($enrolledModules)) : ?>
                    <?php foreach ($enrolledModules as $module) : ?>
                        <div class="module-card" onclick="showModuleInfo('<?php echo urlencode($module['Modules_Name']); ?>')">
                            <?php 
                                $imageSrc = "placeholder.jpg"; 

                                if (!empty($module['Module_Image'])) {
                                    $imageData = base64_encode($module['Module_Image']); 
                                    $imageSrc = "data:image/jpeg;base64,{$imageData}"; 
                                }
                            ?>
                            <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($module['Modules_Name']); ?>">
                            
                            <div class="module-title"><?php echo htmlspecialchars($module['Modules_Name']); ?></div>
                            <button class="remove-btn" onclick="removeModule('<?php echo urlencode($module['Modules_Name']); ?>')">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No enrolled modules found.</p>
                <?php endif; ?>
            </div>

            <div class="button-container">
                <a href="modules.php" class="view-more-btn">View More Modules</a>
            </div>
        </div>

        <div id="moduleInfoModal" class="modal">
            <div class="modal-content">
                <button class="close-btn" onclick="closeModal()">← Back</button>
                <h2 id="modal-title">Module Name</h2>

                <div class="tabs">
                    <button class="tab-btn active" data-tab="description" onclick="switchTab('description')">Description</button>
                    <button class="tab-btn" data-tab="quizzes" onclick="switchTab('quizzes')">Quizzes</button>
                    <button class="tab-btn" data-tab="materials" onclick="switchTab('materials')">Study Materials</button>
                </div>

                <div class="tab-content">
                    <div id="tab-description" class="tab active">
                        <p id="modal-description">Description...</p>
                    </div>
                    <div id="tab-quizzes" class="tab">
                        <div id="modal-quizzes" class="grid-container">No quizzes available.</div>
                    </div>
                    <div id="tab-materials" class="tab">
                        <div id="modal-materials" class="grid-container">No study materials available.</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="quizPopup" class="modal">
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

        <div id="studyMaterialsPopup" class="modal">
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

    <?php
        include 'footer.php';
    ?>
</body>
</html>
