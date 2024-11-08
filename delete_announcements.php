<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];

    // Delete the announcement
    $stmt = $conn->prepare("DELETE FROM announcements WHERE title = ?");
    if ($stmt) {
        $stmt->bind_param("s", $title);
        if ($stmt->execute()) {
            echo "<script>alert('Announcement deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting announcement: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Announcement</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css"> 
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Delete Announcement</h1>
        <form method="POST" class="form" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
            <div class="form-group">
                <label for="title">Title of the Announcement to Delete:</label>
                <input type="text" id="title" name="title" required class="form-control">
            </div>

            <div class="soption">
                <input type="submit" value="Delete Announcement">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>
