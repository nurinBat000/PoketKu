<?php
session_start();
include "db.php";

if (!isset($_SESSION["userID"])) {
    header("Location: index.php");
    exit();
}

$userID = $_SESSION["userID"];
$userName = $_SESSION["userName"];
$today = date("Y-m-d");

$stmt = $conn->prepare("
    SELECT
        budget_id,
        allowance,
        start_date,
        end_date
    FROM budgets
    WHERE user_id = ?
        AND end_date < ?
        AND saving_transferred = 0
");

$stmt->bind_param("ss", $userID, $today);
$stmt->execute();

$completedBudgets = $stmt->get_result();

while ($budget = $completedBudgets->fetch_assoc()) {
    $completedBudgetID = (int)$budget["budget_id"];
    $completedAllowance = (float)$budget["allowance"];
    $completedStartDate = $budget["start_date"];
    $completedEndDate = $budget["end_date"];

    $expenseStmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM expenses
        WHERE user_id = ?
            AND expense_date BETWEEN ? AND ?
    ");

    $expenseStmt->bind_param(
        "sss",
        $userID,
        $completedStartDate,
        $completedEndDate
    );

    $expenseStmt->execute();

    $expenseResult = $expenseStmt->get_result();
    $expenseData = $expenseResult->fetch_assoc();

    $completedSpent = (float)$expenseData["total"];
    $extraMoney = $completedAllowance - $completedSpent;

    $expenseStmt->close();

    if ($extraMoney > 0) {
        $savingStmt = $conn->prepare("
            INSERT INTO savings
            (
                user_id,
                amount,
                saving_date
            )
            VALUES (?, ?, ?)
        ");

        $savingStmt->bind_param(
            "sds",
            $userID,
            $extraMoney,
            $completedEndDate
        );

        $savingStmt->execute();
        $savingStmt->close();
    }

    $updateStmt = $conn->prepare("
        UPDATE budgets
        SET saving_transferred = 1
        WHERE budget_id = ?
            AND user_id = ?
    ");

    $updateStmt->bind_param(
        "is",
        $completedBudgetID,
        $userID
    );

    $updateStmt->execute();
    $updateStmt->close();
}

$stmt->close();

$stmt = $conn->prepare("
    SELECT
        budget_id,
        period,
        allowance,
        start_date,
        end_date
    FROM budgets
    WHERE user_id = ?
        AND ? BETWEEN start_date AND end_date
    ORDER BY budget_id DESC
    LIMIT 1
");

$stmt->bind_param("ss", $userID, $today);
$stmt->execute();

$result = $stmt->get_result();

$budgetID = 0;
$period = "";
$allowance = 0;
$startDate = "";
$endDate = "";
$hasActiveBudget = false;

if ($result->num_rows > 0) {
    $budget = $result->fetch_assoc();

    $budgetID = (int)$budget["budget_id"];
    $period = $budget["period"];
    $allowance = (float)$budget["allowance"];
    $startDate = $budget["start_date"];
    $endDate = $budget["end_date"];
    $hasActiveBudget = true;
}

$stmt->close();

$totalSpent = 0;

if ($hasActiveBudget) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM expenses
        WHERE user_id = ?
            AND expense_date BETWEEN ? AND ?
    ");

    $stmt->bind_param(
        "sss",
        $userID,
        $startDate,
        $endDate
    );

    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $totalSpent = (float)$data["total"];

    $stmt->close();
}

$remainingBudget = $allowance - $totalSpent;

if ($remainingBudget < 0) {
    $remainingBudget = 0;
}

$budgetWarning = "";

if ($hasActiveBudget && $allowance > 0) {
    $remainingPercentage = ($remainingBudget / $allowance) * 100;

    if ($remainingBudget <= 0) {
        $budgetWarning = "Your budget has been fully used.";
    } elseif ($remainingPercentage <= 20) {
        $budgetWarning = "Your budget is running low.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box user-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Student Dashboard</h2>

    <div class="welcome-row">
        <p>
            Welcome, <?= htmlspecialchars($userName) ?>!
        </p>

        <a href="monthlyExtra.php" class="green-button">
            Extra Money
        </a>
    </div>

    <hr>

    <?php if ($hasActiveBudget): ?>
        <div class="budget-title-row">
            <h3>
                Budget Summary (<?= $period == "Monthly" ? "M" : "W" ?>)
            </h3>

            <span class="budget-period">
                Period:
                <?= date("d/m/Y", strtotime($startDate)) ?>
                -
                <?= date("d/m/Y", strtotime($endDate)) ?>
            </span>
        </div>

        <p>
            Budget Amount:
            RM <?= number_format($allowance, 2) ?>
        </p>

        <p>
            Remaining Budget:
            RM <?= number_format($remainingBudget, 2) ?>
        </p>

        <?php if ($budgetWarning != ""): ?>
            <p class="budget-warning">
                <?= htmlspecialchars($budgetWarning) ?>
            </p>
        <?php endif; ?>

    <?php else: ?>
        <h3>Budget Summary</h3>

        <p class="budget-warning">
            No active budget. Please set a new budget.
        </p>
    <?php endif; ?>

    <hr>

    <h3>Spending Summary</h3>

    <?php if ($hasActiveBudget): ?>
        <p>
            Current Period Spent:
            RM <?= number_format($totalSpent, 2) ?>
        </p>

        <p>
            Remaining Balance:
            RM <?= number_format($remainingBudget, 2) ?>
        </p>

    <?php else: ?>
        <p>Current Period Spent: RM 0.00</p>
        <p>Remaining Balance: RM 0.00</p>
    <?php endif; ?>

    <hr>

    <div class="dashboard-buttons">
        <a href="budget.php" class="green-button">
            Set Budget
        </a>

        <a href="expense.php" class="green-button">
            Add Expense
        </a>

        <a href="history.php" class="green-button">
            History
        </a>
    </div>

    <hr>

    <div class="bottom-area">
        <a href="logout.php" class="logout-button">
            Logout
        </a>
    </div>
</div>

</body>
</html>