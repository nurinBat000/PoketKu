<?php
session_start();
include "db.php";

if (!isset($_SESSION["adminID"])) {
    header("Location: index.php");
    exit();
}

$userID = "";

if (isset($_GET["id"])) {
    $userID = trim($_GET["id"]);
}

if ($userID == "") {
    header("Location: adminDashboard.php");
    exit();
}

$message = "";
$messageType = "";

if (isset($_POST["update"])) {
    $userName = trim($_POST["userName"]);
    $userPhoneNo = trim($_POST["userPhoneNo"]);
    $userEmail = trim($_POST["userEmail"]);
    $userPassword = trim($_POST["userPassword"]);

    if ($userName == "" || $userPhoneNo == "" || $userEmail == "") {
        $message = "Please complete all required fields.";
        $messageType = "error";

    } elseif (!preg_match("/^0\d{9,10}$/", $userPhoneNo)) {
        $message = "Phone number must start with 0 and contain 10 or 11 digits.";
        $messageType = "error";

    } elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($userPassword != "" && !preg_match("/^\d{4}$/", $userPassword)) {
        $message = "Password must be exactly 4 digits.";
        $messageType = "error";

    } else {
        $stmt = $conn->prepare("
            SELECT userID
            FROM `user`
            WHERE userEmail = ?
                AND userID != ?
        ");

        $stmt->bind_param(
            "ss",
            $userEmail,
            $userID
        );

        $stmt->execute();
        $emailResult = $stmt->get_result();

        if ($emailResult->num_rows > 0) {
            $message = "Email is already used by another student.";
            $messageType = "error";
            $stmt->close();

        } else {
            $stmt->close();

            if ($userPassword == "") {
                $stmt = $conn->prepare("
                    UPDATE `user`
                    SET
                        userName = ?,
                        userPhoneNo = ?,
                        userEmail = ?
                    WHERE userID = ?
                ");

                $stmt->bind_param(
                    "ssss",
                    $userName,
                    $userPhoneNo,
                    $userEmail,
                    $userID
                );

            } else {
                $stmt = $conn->prepare("
                    UPDATE `user`
                    SET
                        userName = ?,
                        userPhoneNo = ?,
                        userEmail = ?,
                        userPassword = ?
                    WHERE userID = ?
                ");

                $stmt->bind_param(
                    "sssss",
                    $userName,
                    $userPhoneNo,
                    $userEmail,
                    $userPassword,
                    $userID
                );
            }

            if ($stmt->execute()) {
                $message = "Student information updated successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to update student information.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

$stmt = $conn->prepare("
    SELECT
        userID,
        userName,
        userPhoneNo,
        userEmail
    FROM `user`
    WHERE userID = ?
");

$stmt->bind_param("s", $userID);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    $conn->close();

    header("Location: adminDashboard.php");
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box page-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Edit Student</h2>

    <hr>

    <?php if ($message != ""): ?>
        <?php if ($messageType == "error"): ?>
            <div style="
                display: block;
                width: 100%;
                padding: 8px 10px;
                margin: 8px 0;
                border-radius: 6px;
                background-color: #f8d7da;
                color: #842029;
                font-size: 12px;
            ">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php else: ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <label for="userID">User ID:</label>

        <input
            type="text"
            id="userID"
            value="<?= htmlspecialchars($student["userID"]) ?>"
            disabled
        >

        <label for="userName">Name:</label>

        <input
            type="text"
            id="userName"
            name="userName"
            value="<?= htmlspecialchars($student["userName"]) ?>"
            required
        >

        <label for="userPhoneNo">Phone:</label>

        <input
            type="tel"
            id="userPhoneNo"
            name="userPhoneNo"
            pattern="0[0-9]{9,10}"
            minlength="10"
            maxlength="11"
            inputmode="numeric"
            title="Phone number must start with 0 and contain 10 or 11 digits"
            value="<?= htmlspecialchars($student["userPhoneNo"]) ?>"
            required
        >

        <label for="userEmail">Email:</label>

        <input
            type="email"
            id="userEmail"
            name="userEmail"
            value="<?= htmlspecialchars($student["userEmail"]) ?>"
            required
            oninput="checkEmail(this)"
        >

        <label for="userPassword">New Password:</label>

        <input
            type="password"
            id="userPassword"
            name="userPassword"
            pattern="[0-9]{4}"
            maxlength="4"
            inputmode="numeric"
            title="Password must be exactly 4 digits"
            placeholder="Leave blank to keep current password"
        >

        <button type="submit" name="update">
            Update
        </button>

        <a href="adminDashboard.php" class="gray-button">
            Back
        </a>
    </form>
</div>

<script>
function checkEmail(input) {
    var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (input.value === "") {
        input.setCustomValidity("");
    } else if (!emailPattern.test(input.value)) {
        input.setCustomValidity("Please enter a valid email address.");
    } else {
        input.setCustomValidity("");
    }
}
</script>

</body>
</html>

<?php
$conn->close();
?>