<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Verify if the user is an admin
$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT is_admin FROM admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($isAdmin);
    $stmt->fetch();
    if (!$isAdmin) {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
$stmt->close();

// Fetch all news data for the dropdown
$newsItems = [];
$newsStmt = $conn->prepare("SELECT data FROM news");
$newsStmt->execute();
$newsStmt->bind_result($newsData);
while ($newsStmt->fetch()) {
    $newsItems[] = $newsData;
}
$newsStmt->close();

// Fetch selected news item details if a data item is submitted
$selectedNews = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data_item'])) {
    $selectedData = $_POST['data_item'];
    $stmt = $conn->prepare("SELECT data, link, end_date FROM news WHERE data = ?");
    $stmt->bind_param("s", $selectedData);
    $stmt->execute();
    $stmt->bind_result($selectedNews['data'], $selectedNews['link'], $selectedNews['end_date']);
    $stmt->fetch();
    $stmt->close();
}

// Update the news item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $currentData = $_POST['current_data'];
    $newData = $_POST['data'];
    $newLink = $_POST['link'];
    $newEndDate = $_POST['end_date'];

    // Update news details
    $stmt = $conn->prepare("UPDATE news SET data = ?, link = ?, end_date = ? WHERE data = ?");
    if ($stmt) {
        $stmt->bind_param("ssss", $newData, $newLink, $newEndDate, $currentData);
        if ($stmt->execute()) {
            echo "<script>alert('News updated successfully.');</script>";
        } else {
            echo "<script>alert('Error updating news: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit News</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css"> 
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Edit News</h1>
        
        <form method="POST" id="selectForm">
            <label for="data_item">Select News Data:</label>
            <select name="data_item" onchange="document.getElementById('selectForm').submit();">
                <option value="">Select a news item</option>
                <?php foreach ($newsItems as $newsData): ?>
                    <option value="<?php echo htmlspecialchars($newsData); ?>" <?php echo isset($selectedData) && $selectedData == $newsData ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($newsData); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        
        <?php if (!empty($selectedNews)): ?>
        <form method="POST" class="form">
            <input type="hidden" name="current_data" value="<?php echo htmlspecialchars($selectedNews['data']); ?>">

            <label for="data">New Data:</label>
            <textarea id="data" name="data" required><?php echo htmlspecialchars($selectedNews['data']); ?></textarea>

            <label for="link">Link:</label>
            <input type="text" id="link" name="link" value="<?php echo htmlspecialchars($selectedNews['link']); ?>">

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($selectedNews['end_date']); ?>">

            <input type="submit" name="update" value="Update News">
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
