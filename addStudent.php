<?php
session_start();
include "db.php";

if (!isset($_SESSION["adminID"])) {
    header("Location: index.php");
    exit();
}

$message = "";

if (isset($_POST["addUser"])) {

    $userName = trim($_POST["userName"]);
    $userPhoneNo = trim($_POST["userPhoneNo"]);
    $userEmail = trim($_POST["userEmail"]);
    $userPassword = trim($_POST["userPassword"]);

    if (
        $userName == "" ||
        $userPhoneNo == "" ||
        $userEmail == "" ||
        $userPassword == ""
    ) {
        $message = "Please complete all fields.";

    } elseif (!preg_match("/^0\d{9,10}$/", $userPhoneNo)) {
        $message = "Phone number must start with 0 and contain 10 or 11 digits.";

    } elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";

    } elseif (!preg_match("/^\d{4}$/", $userPassword)) {
        $message = "Password must be exactly 4 digits.";

    } else {

        $check = $conn->prepare("
            SELECT userID
            FROM `user`
            WHERE userEmail = ?
        ");

        $check->bind_param("s", $userEmail);
        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $message = "Email already exists.";

        } else {

            $idResult = $conn->query("
                SELECT userID
                FROM `user`
                ORDER BY userID DESC
                LIMIT 1
            ");

            $nextNumber = 1;

            if ($idResult->num_rows > 0) {
                $lastUser = $idResult->fetch_assoc();
                $lastNumber = intval(substr($lastUser["userID"], 2));
                $nextNumber = $lastNumber + 1;
            }

            $newUserID = "US" . str_pad(
                $nextNumber,
                3,
                "0",
                STR_PAD_LEFT
            );

            $stmt = $conn->prepare("
                INSERT INTO `user`
                (
                    userID,
                    userPassword,
                    userName,
                    userPhoneNo,
                    userEmail
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssss",
                $newUserID,
                $userPassword,
                $userName,
                $userPhoneNo,
                $userEmail
            );

            if ($stmt->execute()) {

                $stmt->close();
                $check->close();
                $conn->close();

                header("Location: adminDashboard.php");
                exit();

            } else {
                $message = "Failed to add student.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Student</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="main-box admin-box">

    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Add New Student</h2>

    <hr>

    <?php if ($message != ""): ?>

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

    <?php endif; ?>

    <form method="POST">

        <label>Name</label>

        <input
            type="text"
            name="userName"
            value="<?= isset($_POST["userName"]) ? htmlspecialchars($_POST["userName"]) : "" ?>"
            required
        >

        <label>Phone</label>

        <input
            type="tel"
            name="userPhoneNo"
            pattern="0[0-9]{9,10}"
            minlength="10"
            maxlength="11"
            inputmode="numeric"
            title="Phone number must start with 0 and contain 10 or 11 digits"
            value="<?= isset($_POST["userPhoneNo"]) ? htmlspecialchars($_POST["userPhoneNo"]) : "" ?>"
            required
        >

        <label>Email</label>

        <input
            type="email"
            name="userEmail"
            value="<?= isset($_POST["userEmail"]) ? htmlspecialchars($_POST["userEmail"]) : "" ?>"
            required
            oninput="checkEmail(this)"
        >

        <label>Password</label>

        <input
            type="password"
            name="userPassword"
            pattern="[0-9]{4}"
            maxlength="4"
            inputmode="numeric"
            title="Password must be exactly 4 digits"
            required
        >

        <button type="submit" name="addUser">
            Add Student
        </button>

        <a href="adminDashboard.php" class="gray-button">
            Back
        </a>

    </form>

</div>

<script>
function checkEmail(input) {

    var emailPattern =
        /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if (input.value === "") {
        input.setCustomValidity("");

    } else if (!emailPattern.test(input.value)) {
        input.setCustomValidity(
            "Please enter a valid email address."
        );

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