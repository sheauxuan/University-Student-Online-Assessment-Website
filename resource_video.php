<?php
    session_start();
    include 'connection.php';

    $userID = $_SESSION['userid'];

    if(isset($_GET['resource_id'])){
        $resourceID = $_GET['resource_id'];
    }else{
        echo 'No resource id get...';
        $resourceID = "";
    }

    $module_name = "";
    $youtube_link = "";
    $resource_name = "";

    if ($resourceID) {
        $query = "SELECT Modules_Name, Resource_Name, Resource_Link FROM study_resources WHERE Resource_ID = $resourceID";
        $result = mysqli_query($connection, $query);

        if ($row = mysqli_fetch_assoc($result)) {
            $module_name = $row['Modules_Name'];
            $youtube_link = $row['Resource_Link'];
            $resource_name = $row['Resource_Name'];
        }
    }

    date_default_timezone_set('Asia/Kuala_Lumpur');
    $startTimestamp = date('Y-m-d H:i:s');

    $checkQuery = "SELECT Activity_ID FROM activity_summary 
                WHERE User_ID = $userID 
                AND Resource_ID = $resourceID 
                AND Completion_Timestamp IS NULL";

    $result = mysqli_query($connection, $checkQuery);

    if (mysqli_num_rows($result) == 0) {
        $insertQuery = "INSERT INTO activity_summary (User_ID, Resource_ID, Modules_Name, Starting_Timestamp, Completion_Timestamp, Activity_Type) 
                        VALUES ($userID, $resourceID, '$module_name', '$startTimestamp', NULL, 'view')";
        mysqli_query($connection, $insertQuery);
    }

    if (isset($_POST['btnBack'])) {
        $completionTimestamp = date('Y-m-d H:i:s');

        $updateQuery = "UPDATE activity_summary 
                        SET Completion_Timestamp = '$completionTimestamp' 
                        WHERE User_ID = $userID AND Resource_ID = $resourceID 
                        AND Starting_Timestamp IS NOT NULL 
                        AND Completion_Timestamp IS NULL";

        if (mysqli_query($connection, $updateQuery)) {
            header("Location: index.php");
            exit();
        }
    }

    function convertToEmbed($url) {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/|live\/|user\/\S+\/|playlist\?list=))([^?&"\'>]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/" . $matches[1];
        }
        return "";
    }

    $embed_url = convertToEmbed($youtube_link);

    mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KnowWa View Materials</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #fdf3e4;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container { 
            background: green; 
            width: 100%;
            max-width: 70%; 
            margin: 3% auto;  
            padding-left: 3%;    
            padding-right: 3%;  
            padding-top:1%;
            padding-bottom:1%; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border-radius: 15px; 
            text-align: center;
            color:white;
        }

        iframe { 
            width: 100%; 
            height: 400px; 
            border: 2px; 
            border-radius: 5px; 
            padding:3% auto;
            margin: 1% auto;
        }

        .back-btn {
            width: 30%;
            max-width: 150px;
            margin: 20px;
            padding: 10px 20px;
            background-color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 95%;
            text-decoration:underline;
            font-weight:bold;
            color:  #b22222;
            display: inline-block;
        }

        .back-btn:hover { background-color:rgb(240, 252, 243); }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1 style="text-align: left; font-size: 20px;"><?php echo htmlspecialchars($resource_name); ?></h1>
        <div class="video-container">
            <?php if ($embed_url): ?>
                <iframe src="<?php echo htmlspecialchars($embed_url); ?>" allowfullscreen></iframe>
            <?php else: ?>
                <p>No video available for this resource.</p>
            <?php endif; ?>
        </div>

        <form method="POST">
            <button type="submit" class="back-btn" name="btnBack">Back</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
