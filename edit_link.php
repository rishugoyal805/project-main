<?php 
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Handle form submission
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sem = (int)$_POST['link_sem'];
    $semTable = "sem" . $sem;
    
    $subject = $_POST['subject'];
    $description = $_POST['link_description'];
    $link_url = $_POST['link'];
    $type = $_POST['type'];

    // Update query with prepared statement
    $stmt = $conn->prepare("UPDATE $semTable SET description = ?, link = ?, type = ? WHERE subject = ?");
    $stmt->bind_param("ssss", $description, $link_url, $type, $subject);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: edit_link.php");
        exit();
    } else {
        $error_message = "Error updating record: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch unique semesters for the dropdown
$semesters = $conn->query("SELECT DISTINCT sem FROM cards ORDER BY sem ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Link</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">  
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadSubjects(sem) {
            if (sem) {
                $.ajax({
                    url: "fetch_subjects.php",
                    type: "GET",
                    data: { sem: sem },
                    success: function (response) {
                        $('#subjectSelect').html(response);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error: " + status + ": " + error);
                    }
                });
            } else {
                $('#subjectSelect').html('<option value="">Select a subject</option>');
            }
        }

        function loadSubjectDetails(subject) {
            if (subject) {
                const sem = $('#link_sem').val(); // Get the selected semester
                $.ajax({
                    url: "edit_link.php", 
                    type: "GET",
                    data: { subject: subject, sem: sem },
                    dataType: 'json',
                    success: function (data) {
                        if (data) {
                            $('#link_description').val(data.description || '');
                            $('#link').val(data.link || '');
                            $('#type').val(data.type || '');
                        } else {
                            clearFields();
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error: " + status + ": " + error);
                    }
                });
            } else {
                clearFields();
            }
        }

        function clearFields() {
            $('#link_description').val('');
            $('#link').val('');
            $('#type').val('');
        }
    </script>
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Edit Data Link</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" class="form">
            <div class="form-group">
                <label for="link_sem">Select Semester:</label>
                <select id="link_sem" name="link_sem" class="form-control" onchange="loadSubjects(this.value)">
                    <option value="">Select a semester</option>
                    <?php foreach ($semesters as $row): ?>
                        <option value="<?php echo htmlspecialchars($row['sem']); ?>"><?php echo htmlspecialchars($row['sem']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subjectSelect">Select Subject:</label>
                <select id="subjectSelect" name="subject" class="form-control" onchange="loadSubjectDetails(this.value)">
                    <option value="">Select a subject</option>
                </select>
            </div>

            <div class="form-group">
                <label for="link_description">Description:</label>
                <textarea id="link_description" name="link_description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="link">Link:</label>
                <input type="url" id="link" name="link" required>
            </div>

            <div class="form-group">
                    <label for="type">Type:</label>
                <select id="type" name="type" required>
                    <option value="college">College</option>
                    <option value="youtube">YouTube</option>
                    <option value="books">Books</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="soption">
                <input type="submit" value="Update Link">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>

<?php
$conn->close();
?>
