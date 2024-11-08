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

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data_item'])) {
    $dataToDelete = $_POST['data_item'];
    $stmt = $conn->prepare("DELETE FROM news WHERE data = ?");
    if ($stmt) {
        $stmt->bind_param("s", $dataToDelete);
        if ($stmt->execute()) {
            echo "<script>alert('News deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting news: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete News</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css"> 
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Delete News</h1>
        
        <form method="POST" id="deleteForm">
            <label for="data_item">Select News Data to Delete:</label>
            <select name="data_item" required>
                <option value="">Select a news item</option>
                <?php foreach ($newsItems as $newsData): ?>
                    <option value="<?php echo htmlspecialchars($newsData); ?>"><?php echo htmlspecialchars($newsData); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Delete News">
        </form>
    </div>
</body>
</html>
