<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Verify admin status
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

// Fetch all titles for the dropdown
$titles = [];
$titleStmt = $conn->prepare("SELECT title FROM announcements");
$titleStmt->execute();
$titleStmt->bind_result($title);
while ($titleStmt->fetch()) {
    $titles[] = $title;
}
$titleStmt->close();

// Fetch selected announcement details if a title is submitted
$selectedAnnouncement = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['title'])) {
    $selectedTitle = $_POST['title'];
    $stmt = $conn->prepare("SELECT title, content, image, start_date, end_date FROM announcements WHERE title = ?");
    $stmt->bind_param("s", $selectedTitle);
    $stmt->execute();
    $stmt->bind_result($selectedAnnouncement['title'], $selectedAnnouncement['content'], $selectedAnnouncement['image'], $selectedAnnouncement['start_date'], $selectedAnnouncement['end_date']);
    $stmt->fetch();
    $stmt->close();
}

// Update the announcement when "Update Announcement" is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $currentTitle = $_POST['current_title']; // Existing title
    $newTitle = $_POST['new_title']; // Updated title from form
    $newContent = $_POST['content'];
    $newImage = $_POST['image'];
    $newStartDate = $_POST['start_date'];
    $newEndDate = $_POST['end_date'];

    // Prepare update statement
    $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, image = ?, start_date = ?, end_date = ? WHERE title = ?");
    if ($stmt) {
        $stmt->bind_param("ssssss", $newTitle, $newContent, $newImage, $newStartDate, $newEndDate, $currentTitle);
        if ($stmt->execute()) {
            echo "<script>alert('Announcement updated successfully.');</script>";
        } else {
            echo "<script>alert('Error updating announcement: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Announcement</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css"> 
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Edit Announcement</h1>
        
        <!-- Dropdown to select title -->
        <form method="POST" class="form" id="titleForm">
            <div class="form-group">
                <label for="title">Select Title:</label>
                <select name="title" id="title" class="form-control" onchange="document.getElementById('titleForm').submit();">
                    <option value="">Select a title</option>
                    <?php foreach ($titles as $title): ?>
                        <option value="<?php echo htmlspecialchars($title); ?>" <?php echo isset($selectedTitle) && $selectedTitle == $title ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        
        <?php if (!empty($selectedAnnouncement)): ?>
        <form method="POST" class="form" id="editForm">
            <input type="hidden" name="current_title" value="<?php echo htmlspecialchars($selectedAnnouncement['title']); ?>">

            <div class="form-group">
                <label for="new_title">New Title:</label>
                <input type="text" id="new_title" name="new_title" required class="form-control" value="<?php echo htmlspecialchars($selectedAnnouncement['title']); ?>">
            </div>

            <div class="form-group">
                <label for="content">Content:</label>
                <textarea id="content" name="content" required class="form-control"><?php echo htmlspecialchars($selectedAnnouncement['content']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="image">Image URL:</label>
                <input type="text" id="image" name="image" class="form-control" value="<?php echo htmlspecialchars($selectedAnnouncement['image']); ?>">
            </div>

            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required value="<?php echo htmlspecialchars($selectedAnnouncement['start_date']); ?>">
            </div>

            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($selectedAnnouncement['end_date']); ?>">
            </div>

            <div class="soption">
                <input type="submit" name="update" value="Update Announcement">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>
