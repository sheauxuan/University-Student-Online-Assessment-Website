<?php
	session_start();

	if (!isset($_SESSION['userid'])) {
		header("Location: login.php");
		exit();
	}

	if(isset($_GET['resetSessionVariable']) && ($_GET['resetSessionVariable'] == "true")){
		unset($_SESSION['question_number']);
		unset($_SESSION['quiz_id']);
		unset($_SESSION['quiz_name']);
		unset($_SESSION['modules_name']);
		unset($_SESSION['countOfCorrectAnswer']);
	}

	$userId = $_SESSION['userid'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules Page</title>
    <link rel="stylesheet" href="main.css">
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modulesContainer = document.querySelector("#modules-container");
            const modal = document.getElementById("moduleInfoModal");
            const title = document.getElementById("modal-title");
            const description = document.getElementById("modal-description");
            const quizzes = document.getElementById("modal-quizzes");
            const materials = document.getElementById("modal-materials");
			const quizPopup = document.getElementById("quizPopup");
			const searchInput = document.querySelector("#search-bar");
			const filterDropdown = document.querySelector("#filter-dropdown");
			const urlParams = new URLSearchParams(window.location.search);

			<?php $searchQuery = isset($_GET['userSearch']) ? $_GET['userSearch'] : ""; ?>

			let searchQuery = <?php echo json_encode($searchQuery); ?>;
			if (searchQuery && searchInput) {
				searchInput.value = searchQuery;
				filterItems();
			}
			
			window.userId = <?php echo isset($_SESSION['userid']) ? json_encode($_SESSION['userid']) : 'null'; ?>;
			const quizId = urlParams.get('quiz_id');
			const quizName = urlParams.get('quiz_name');
			if (quizId && quizName) {
				showQuizInfo(quizName);
			}

			function filterItems() {
				let filter = searchInput.value.toLowerCase();
				let category = filterDropdown.value;
				let allItems = document.querySelectorAll(".module, .quiz-item, .study-item");
			
				allItems.forEach(item => {
					let title = item.querySelector(".module-title").innerText.toLowerCase();
					let matchesSearch = title.includes(filter);
					let matchesCategory =
						category === "default" ||
						(category === "modules" && item.classList.contains("module-item")) ||  
						(category === "quizzes" && item.classList.contains("quiz-item")) ||
						(category === "materials" && item.classList.contains("study-item"));
			
					if (matchesSearch && matchesCategory) {
						item.style.display = "block";
					} else {
						item.style.display = "none";
					}
				});
			}
			if (filterDropdown) filterDropdown.addEventListener("change", filterItems);

			if (searchInput) {
				searchInput.addEventListener("keyup", function () {
					let filter = searchInput.value.toLowerCase();
					let allItems = document.querySelectorAll(".module, .quiz-item, .study-item");
		
					allItems.forEach(item => {
						let title = item.querySelector(".module-title").innerText.toLowerCase();
						if (title.includes(filter)) {
							item.style.display = "block";
						} else {
							item.style.display = "none";
						}
					});
				});
			}

            function fetchModules() {
                fetch('fetch_modules.php')
                    .then(response => response.json())
                    .then(data => {
                        if (!Array.isArray(data)) {
                            console.error("Invalid JSON response:", data);
                            modulesContainer.innerHTML = `<p style="color: red;">Invalid data received. Please check PHP output.</p>`;
                            return;
                        }
                        console.log("Fetched Modules Data:", data);
                        renderModules(data);

						if (searchQuery) {
							filterItems();
						}
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        modulesContainer.innerHTML = `<p style="color: red;">Failed to load data. Check console.</p>`;
                    });
            }
			
			function renderModules(data) {
				modulesContainer.innerHTML = "";
				let modulesHTML = "";
				let quizzesHTML = "";
				let materialsHTML = "";
			
				data.forEach(item => {
					let imageSrc = item.Module_Image || item.Quizz_Image || item.Resource_Image;
					if (!imageSrc || !imageSrc.startsWith("data:image/")) {
						console.warn("Invalid image detected, using placeholder:", item);
						imageSrc = "placeholder.jpg";
					}
			
					if (item.category === "Module") {
						modulesHTML += `
							<div class="module module-item" onclick="showModuleInfo('${encodeURIComponent(item.Modules_Name || '')}')">
								<img src="${imageSrc}" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="${item.Modules_Name}">
								<div class="module-title">${item.Modules_Name}</div>
								<button class="add-module-btn" onclick="event.stopPropagation(); addModule('${encodeURIComponent(item.Modules_Name)}')">Add</button>
							</div>
						`;
					} else if (item.category === "Quiz") {
						quizzesHTML += `
							<div class="module quiz-item" onclick="showQuizInfo('${item.Quiz_Name}')">
								<img src="${imageSrc}" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="${item.Quiz_Name}">
								<div class="module-title">${item.Quiz_Name}</div>
							</div>
						`;
					} else if (item.category === "Study Material") {
						materialsHTML += `
							<div class="module study-item" onclick="showMaterialsInfo('${item.Resource_Name}')">
								<img src="${imageSrc}" onerror="this.onerror=null;this.src='placeholder.jpg';" alt="${item.Resource_Name}">
								<div class="module-title">${item.Resource_Name}</div>
							</div>
						`;
					}
				});
			
				if (modulesHTML) modulesContainer.innerHTML += `<h2>Modules</h2><div class="module-section">${modulesHTML}</div>`;
				if (quizzesHTML) modulesContainer.innerHTML += `<h2>Quizzes</h2><div class="module-section">${quizzesHTML}</div>`;
				if (materialsHTML) modulesContainer.innerHTML += `<h2>Study Materials</h2><div class="module-section">${materialsHTML}</div>`;
			}

            window.showModuleInfo = function (moduleName) {
                if (!moduleName || moduleName === "undefined") return;

                const decodedModuleName = decodeURIComponent(moduleName);
                console.log("Fetching data for module:", decodedModuleName);

                fetch(`fetch_module_info.php?name=${encodeURIComponent(decodedModuleName)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data || data.error) {
                            console.error("Error from server:", data.error || "Invalid JSON response");
                            if (description) description.innerText = "Error: " + (data.error || "Failed to load data.");
                            return;
                        }

                        console.log("Module Data:", data);

                        if (title) title.innerText = data.module_name || "Module Name";
                        if (description) description.innerText = data.description || "No description available.";

                        if (quizzes) {
                            quizzes.innerHTML = (data.quizzes && data.quizzes.length)
                                ? data.quizzes.map(q => `
                                    <div class="module quiz-item" onclick="showQuizInfo('${q.Quiz_Name}')">
                                        <img src="${q.Quizz_Image || 'placeholder.jpg'}" alt="${q.Quiz_Name}">
                                        <div class="module-title">${q.Quiz_Name}</div>
                                    </div>
                                `).join('')
                                : "<p>No quizzes available.</p>";
                        }

                        if (materials) {
                            materials.innerHTML = (data.studyMaterials && data.studyMaterials.length)
                                ? data.studyMaterials.map(m => `
                                    <div class="module study-item" onclick="showMaterialsInfo('${m.Resource_Name}')">
                                        <img src="${m.Resource_Image || 'placeholder.jpg'}" alt="${m.Resource_Name}">
                                        <div class="module-title">${m.Resource_Name}</div>
                                    </div>
                                `).join('')
                                : "<p>No study materials available.</p>";
                        }

                        modal.style.display = "flex";
                        switchTab("description");
                    })
                    .catch(error => {
                        console.error('Error fetching module info:', error);
                        if (description) description.innerHTML = `<p style="color: red;">Failed to load module info.</p>`;
                    });

            };
			
			const moduleName = urlParams.get('module_name');
			if (moduleName) {
				showModuleInfo(moduleName);
			}

				window.closeModal = function () {
					console.log("Closing Module Info Popup");
					const modal = document.getElementById('moduleInfoModal');
					if (modal) {
						modal.style.display = "none";
					} else {
						console.error("Error: Module Info Modal not found.");
					}
				};
		
				window.switchTab = function (tabName) {
					document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
					document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));
		
					document.getElementById(`tab-${tabName}`).classList.add("active");
					document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add("active");
				};
		
				fetchModules();
			});


            window.switchTab = function (tabName) {
                document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));
                document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));

                document.getElementById(`tab-${tabName}`).classList.add("active");
                document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add("active");
            };


		function showQuizInfo(quizName) {
			console.log("Clicked Quiz:", quizName);
		
			fetch(`fetch_quiz_info.php?quiz_name=${encodeURIComponent(quizName)}`)
				.then(response => response.text()) 
				.then(text => {
					console.log("Raw Server Response:", text); 
		
					try {
						const data = JSON.parse(text); 
						console.log("🔍 Parsed Quiz Data:", data);
		
						if (data.error) {
							alert("!!! " + data.error);
							return;
						}

						document.getElementById('quizName').innerText = data.Quiz_Name || "Unknown Quiz";
						document.getElementById('quizModule').innerText = data.Modules_Name || "Unknown Module";
						document.getElementById('quizTotalQuestions').innerText = `Total Questions: ${data.Total_Question || "0"}`;
						document.getElementById('quizDescription').innerText = data.Quiz_Description || "No description available.";

						let quizImage = document.getElementById('quizImage');
						if (quizImage) {
							quizImage.src = (data.Quizz_Image && data.Quizz_Image !== "-") ? data.Quizz_Image : "placeholder.jpg";
							quizImage.onerror = () => { quizImage.src = "placeholder.jpg"; }; 
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

						document.getElementById('quizPopup').style.display = "block";
		
					} catch (error) {
						console.error("JSON Parse Error: Invalid response from server.", text);
						alert("!!! Server did not return valid JSON. Please check your PHP file.");
					}
				})
				.catch(error => console.error('Error fetching quiz info:', error));
		}

	
		function closeQuizPopup() {
			if (quizPopup) {
				console.log("Closing Quiz Info Popup");
				quizPopup.style.display = "none";
			}
		}	
	
		function showMaterialsInfo(materialsName) {
			console.log("Clicked Study Material:", materialsName);
		
			fetch(`fetch_materials_info.php?materials_name=${encodeURIComponent(materialsName)}`)
				.then(response => response.text()) 
				.then(text => {
					console.log("Raw Server Response:", text); 
		
					try {
						const data = JSON.parse(text); 
						console.log("Parsed Materials Data:", data);
		
						if (data.error) {
							alert(data.error);
							return;
						}
		
						document.getElementById('materialsName').innerText = data.Resource_Name || "Unknown Material";
						document.getElementById('materialsModule').innerText = data.Modules_Name || "Unknown Module";
						document.getElementById('materialsDescription').innerText = data.Resource_Description || "No description available.";
		
						let viewButton = document.getElementById('viewMaterialsBtn');
						if (viewButton) {
							if (data.Resource_Link) {
								viewButton.style.display = "block";
								viewButton.onclick = () => window.location.href = `resource_video.php?resource_id=${encodeURIComponent(data.Resource_ID)}`;
							} else {
								viewButton.style.display = "none";
							}
						}

						document.getElementById('studyMaterialsPopup').style.display = "block";
		
					} catch (error) {
						console.error("JSON Parse Error: Invalid response from server.", text);
						alert("!!! Server did not return valid JSON. Please check your PHP file.");
					}
				})
				.catch(error => console.error('Error fetching study materials info:', error));
		}
		
		function closeStudyMaterialsPopup() {
			console.log("🔙 Closing Study Materials Popup");
			document.getElementById('studyMaterialsPopup').style.display = "none";
		}
		
		function addModule(moduleName) {
			console.log("Trying to add module:", moduleName, "for user:", window.userId);

			if (!window.userId || window.userId === null) {
				alert("!!! Error: User ID is missing. Please log in.");
				return;
			}

			let requestData = { module_name: moduleName, user_id: window.userId };

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
						alert("✅ Module added successfully!");
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
</head>
<body>

    <?php
		include 'header.php';
	?>

    <main>
        <section style="padding: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center;">
				<h2>Welcome to the Modules Page,<?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
				<div class="dropdown-container">
					<label for="filter-dropdown">Filter by Category:</label>
					<select id="filter-dropdown">
						<option value="default">Default</option>
						<option value="modules">All Modules</option>
						<option value="quizzes">All Quizzes</option>
						<option value="materials">All Study Materials</option>
					</select>
				</div>
			</div>
		    <div id="modules-container"></div>	
        </section>
    </main>

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
					<p class="quiz-description" id="quizDescription">
						Quiz description goes here...
					</p>
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
					<p class="study-description" id="materialsDescription">
						Description of the study materials goes here...
					</p>
				</div>
				<div class="study-buttons">
					<button id="viewMaterialsBtn">View Materials</button>
				</div>
			</div>
		</div>
	</div>
	
    <?php
		include 'footer.php';
	?>

</body>
</html>
