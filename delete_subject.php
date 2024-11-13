<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$email = $_SESSION['user_email'];

// Fetch unique semesters for dropdown
$semesters = [];
$semQuery = $conn->query("SELECT DISTINCT sem FROM cards ORDER BY sem ASC");
while ($row = $semQuery->fetch_assoc()) {
    $semesters[] = $row['sem'];
}

// Fetch subjects based on the selected semester and user
$selectedSemester = isset($_POST['sem']) ? $_POST['sem'] : '';
$subjects = [];
if ($selectedSemester) {
    $stmt = $conn->prepare("SELECT subject FROM cards WHERE sem = ? AND added_by = ?");
    $stmt->bind_param("is", $selectedSemester, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    $stmt->close();
}

// Handle deletion
$message = "";
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['sem'])) {
    $sem = (int)$_POST['sem'];
    $subject = $_POST['id'];
    $table = 'sem'.$sem;
    // First, delete from sem1 table where the subject matches
    $stmt1 = $conn->prepare("DELETE FROM $table WHERE subject = ?");
    $stmt1->bind_param("s", $subject);
    $stmt1->execute();

    if($stmt1->execute()){
        $stmt = $conn->prepare("DELETE FROM cards WHERE subject = ? AND sem = ? AND added_by = ?");
        $stmt->bind_param("sis", $subject, $sem, $email);
        if ($stmt->execute() ) {
            $success = true;
            $message = "The subject has been successfully deleted.";
        } else {
            $message = "Error deleting subject: " . $stmt->error;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Subject</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Delete Subject</h1>
        <form method="POST">
            <div class="form-group">
                <label for="sem">Select Semester:</label>
                <select id="sem" name="sem" onchange="this.form.submit()" required>
                    <option value="">Select a semester</option>
                    <?php foreach ($semesters as $semester): ?>
                        <option value="<?php echo $semester; ?>" <?php if ($semester == $selectedSemester) echo 'selected'; ?>>
                            Semester <?php echo $semester; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selectedSemester): ?>
                <div class="form-group">
                    <label for="subjectSelect">Select Subject:</label>
                    <select id="subjectSelect" name="id" required>
                        <option value="">Select a subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject); ?>"><?php echo htmlspecialchars($subject); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="soption">
                <input type="submit" value="Delete Subject">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>

    <?php include "footer.php"; ?>

    <!-- Display alert after form submission if there's a message -->
    <?php if (!empty($message)): ?>
        <script>
            alert("<?php echo $message; ?>");
            <?php if ($success): ?>
                window.location.href = 'delete_subject.php'; // Redirect after successful deletion
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>
</html>