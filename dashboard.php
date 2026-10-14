<?php
session_start();
require_once "db.php";

// Check whether a student ID was provided
$studid = $_GET["studid"] ?? $_SESSION["studid"] ?? null;

if (!$studid || !ctype_digit((string)$studid)) {
    exit("Invalid student ID. Please register first.");
}

// Retrieve the student's information
$sql = "SELECT * FROM student WHERE studid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studid);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    exit("Student record not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px #ddd;
        }

        h1 {
            color: #1d4ed8;
        }

        .info {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .label {
            font-weight: bold;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background: #1d4ed8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>Student Dashboard</h1>
    <p>Student registration was successful!</p>

    <div class="info">
        <span class="label">Student ID:</span>
        <?= htmlspecialchars((string)$student["studid"]) ?>
    </div>

    <div class="info">
        <span class="label">Last Name:</span>
        <?= htmlspecialchars($student["lname"] ?? "") ?>
    </div>

    <div class="info">
        <span class="label">First Name:</span>
        <?= htmlspecialchars($student["fname"] ?? "") ?>
    </div>

    <div class="info">
        <span class="label">Middle Name:</span>
        <?= htmlspecialchars($student["mname"] ?? "") ?>
    </div>

    <div class="info">
        <span class="label">Sex:</span>
        <?= htmlspecialchars($student["sex"] ?? "") ?>
    </div>

    <div class="info">
        <span class="label">Birth Date:</span>
        <?= htmlspecialchars($student["bdate"] ?? "") ?>
    </div>

    <div class="info">
        <span class="label">Address:</span>
        <?= htmlspecialchars($student["address"] ?? "") ?>
    </div>

    <a class="button" href="index.php">Register Another Student</a>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>