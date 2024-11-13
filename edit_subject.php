<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';
include 'check_admin.php';

$email = $_SESSION['user_email'];

// Fetch semesters for the dropdown
$semesters = [];
$semStmt = $conn->prepare("SELECT DISTINCT sem FROM cards");
$semStmt->execute();
$semStmt->bind_result($semData);
while ($semStmt->fetch()) {
    $semesters[] = $semData;
}
$semStmt->close();

$subjects = [];
$subStmt = $conn->prepare("SELECT DISTINCT subject FROM cards WHERE sem = ? AND added_by = ?");
foreach ($semesters as $sem) {
    $subStmt->bind_param("is", $sem, $email);
    $subStmt->execute();
    $subStmt->bind_result($subject);
    while ($subStmt->fetch()) {
        $subjects[$sem][] = $subject;
    }
}
$subStmt->close();

// Fetch data based on selected semester, subject, and 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fetch_data'])) {
    $sem = $_POST['sem'];
    $subject = $_POST['subject'];

    $stmt = $conn->prepare("SELECT description, image FROM cards WHERE subject = ? AND sem = ? AND added_by = ?");
    $stmt->bind_param("sis", $subject, $sem, $email);
    $stmt->execute();
    $stmt->bind_result($description, $image);

    $dataItems = [];
    while ($stmt->fetch()) {
        $dataItems[] = ['description' => $description, 'image' => $image, 'subject' => $subject, 'sem' => $sem];
    }
    echo json_encode($dataItems);
    $stmt->close();
    exit();
}

// Handle edit submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['Edit_item'])) {
    $newdescription = $_POST['description'];
    $newimage = $_POST['image'];
    $newsubject = $_POST['displaysubject'];
    $newsem = $_POST['displaysem'];

    $stmt = $conn->prepare("UPDATE cards SET description = ?, sem = ?, image = ? WHERE subject = ? AND sem = ? AND added_by = ?");
    $stmt->bind_param("sissis", $newdescription, $newsem, $newimage, $subject, $sem, $email);
    if ($stmt->execute()) {
        echo "<script>alert('Data updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating data');</script>";
    }
    $stmt->close();
}
?>

<!DOC html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject Details</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include "admin_nav.php"; ?>

<div class="ap_container">
    <h1>Edit Subject Details</h1>
    <form method="POST" id="EditForm" class="form">
        <div class="form-group">
            <label for="sem">Select Semester:</label>
            <select name="sem" id="sem" required>
                <option value="">Select a semester</option>
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem; ?>"><?php echo "Semester $sem"; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="subject">Select Subject:</label>
            <select name="subject" id="subject" required>
                <option value="">Select a subject</option>
            </select>
        </div>

        <div id="dataDetails" style="display:none;">
            <div class="form-group">
                <label for="description">Description:</label>
                <input type="text" id="description" name="description" class="form-control">
            </div>
            <div class="form-group">
                <label for="image">Image:</label>
                <input type="text" id="image" name="image" class="form-control">
            </div>
            <div class="form-group">
                <label for="displaysem">Sem:</label>
                <input type="number" id="displaysem" name="displaysem" min="1" max="8" class="form-control">
            </div>
        </div>

        <div class="soption">
            <input type="submit" name="Edit_item" value="Edit Data">
            <a href="admin_panel.php" class="back">Return to Admin</a>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        const subjects = <?php echo json_encode($subjects); ?>;
        let dataItems = [];

        $('#sem').change(function() {
            const sem = $(this).val();
            $('#subject').html('<option value="">Select a subject</option>');
            $('#dataDetails').hide();

            if (sem && subjects[sem]) {
                $.each(subjects[sem], function(index, subject) {
                    $('#subject').append(new Option(subject, subject));
                });
            }
        });

        $('#subject').change(function() {
            const sem = $('#sem').val();
            const subject = $(this).val();
            if (sem && subject) {
                $.post('', { fetch_data: true, sem: sem, subject: subject }, function(response) {
                    dataItems = JSON.parse(response);
                    if (dataItems.length > 0) {
                        const item = dataItems[0];
                        $('#description').val(item.description);
                        $('#image').val(item.image);
                        $('#displaysem').val(item.sem);
                        $('#dataDetails').show();
                    }
                });
            }
        });
    });
</script>

<?php include "footer.php"; ?>
</body>
</html>
