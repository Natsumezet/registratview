<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $lname = trim($_POST["lname"] ?? "");
    $fname = trim($_POST["fname"] ?? "");
    $mname = trim($_POST["mname"] ?? "");
    $sex = $_POST["sex"] ?? "";
    $bdate = $_POST["bdate"] ?? "";
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");

    if ($lname === "" || $fname === "") {
        exit("Last name and first name are required.");
    }

    // Convert empty optional fields to NULL
    $mname = $mname === "" ? null : $mname;
    $sex = $sex === "" ? null : $sex;
    $bdate = $bdate === "" ? null : $bdate;
    $email = $email === "" ? null : $email;
    $address = $address === "" ? null : $address;

    $sql = "INSERT INTO student
            (lname, fname, mname, sex, bdate, email, address)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        exit("SQL prepare error: " . $conn->error);
    }

    // Seven columns require seven parameter types
    $stmt->bind_param(
        "sssssss",
        $lname,
        $fname,
        $mname,
        $sex,
        $bdate,
        $email,
        $address
    );

    if ($stmt->execute()) {
        $studid = $conn->insert_id;
        $_SESSION["studid"] = $studid;

        $stmt->close();
        $conn->close();

        header("Location: dashboard.php?studid=" . $studid);
        exit;
    } else {
        exit("Error saving student: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>