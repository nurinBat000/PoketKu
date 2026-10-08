<?php
session_start();
include "db.php";

if (!isset($_POST["login"])) {
    header("Location: index.php");
    exit();
}

$userID = trim($_POST["userID"]);
$password = trim($_POST["password"]);

if ($userID == "" || $password == "") {
    echo "<script>
        alert('Please enter ID and password.');
        window.location.href='index.php';
    </script>";
    exit();
}

$stmt = $conn->prepare("
    SELECT
        adminID,
        adminName,
        adminPassword
    FROM admin
    WHERE adminID = ?
");

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();

    if ($password === $admin["adminPassword"]) {
        $_SESSION["adminID"] = $admin["adminID"];
        $_SESSION["adminName"] = $admin["adminName"];

        $stmt->close();
        $conn->close();

        header("Location: adminDashboard.php");
        exit();
    }
}

$stmt->close();

$stmt = $conn->prepare("
    SELECT
        userID,
        userName,
        userPassword
    FROM `user`
    WHERE userID = ?
");

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if ($password === $user["userPassword"]) {
        $_SESSION["userID"] = $user["userID"];
        $_SESSION["userName"] = $user["userName"];

        $stmt->close();
        $conn->close();

        header("Location: userDashboard.php");
        exit();
    }
}

$stmt->close();
$conn->close();

echo "<script>
    alert('Invalid ID or password.');
    window.location.href='index.php';
</script>";
?>