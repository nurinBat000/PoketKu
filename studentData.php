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

$resultUser = $stmt->get_result();

if ($resultUser->num_rows == 0) {
    $stmt->close();
    $conn->close();

    header("Location: adminDashboard.php");
    exit();
}

$student = $resultUser->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT
        budget_id,
        allowance,
        start_date,
        end_date
    FROM budgets
    WHERE user_id = ?
        AND period = 'Monthly'
    ORDER BY start_date DESC, budget_id DESC
");

$stmt->bind_param("s", $userID);
$stmt->execute();

$budgetResult = $stmt->get_result();
$totalExtra = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Data</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box record-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2><?= htmlspecialchars($student["userName"]) ?> - Data</h2>

    <p>User ID: <?= htmlspecialchars($student["userID"]) ?></p>

    <hr>

    <h3>Monthly Extra Money</h3>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Budget</th>
                    <th>Spent</th>
                    <th>Extra</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($budgetResult->num_rows > 0): ?>
                <?php while ($budget = $budgetResult->fetch_assoc()): ?>
                    <?php
                    $allowance = (float)$budget["allowance"];
                    $startDate = $budget["start_date"];
                    $endDate = $budget["end_date"];

                    $expenseStmt = $conn->prepare("
                        SELECT COALESCE(SUM(amount), 0) AS total
                        FROM expenses
                        WHERE user_id = ?
                            AND expense_date BETWEEN ? AND ?
                    ");

                    $expenseStmt->bind_param(
                        "sss",
                        $userID,
                        $startDate,
                        $endDate
                    );

                    $expenseStmt->execute();

                    $expenseResult = $expenseStmt->get_result();
                    $expenseData = $expenseResult->fetch_assoc();
                    $totalSpent = (float)$expenseData["total"];

                    $expenseStmt->close();

                    $extraMoney = $allowance - $totalSpent;

                    if ($extraMoney < 0) {
                        $extraMoney = 0;
                    }

                    $totalExtra += $extraMoney;
                    $month = date("F Y", strtotime($startDate));
                    ?>

                    <tr>
                        <td><?= htmlspecialchars($month) ?></td>
                        <td>RM <?= number_format($allowance, 2) ?></td>
                        <td>RM <?= number_format($totalSpent, 2) ?></td>
                        <td>RM <?= number_format($extraMoney, 2) ?></td>
                    </tr>
                <?php endwhile; ?>

                <tr>
                    <td><strong>Total Extra Money</strong></td>
                    <td></td>
                    <td></td>
                    <td>
                        <strong>RM <?= number_format($totalExtra, 2) ?></strong>
                    </td>
                </tr>

            <?php else: ?>
                <tr>
                    <td colspan="4">No monthly budget records found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <a href="adminDashboard.php" class="green-button">
        Back
    </a>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>