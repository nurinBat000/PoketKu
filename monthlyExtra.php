<?php
session_start();
include "db.php";

if (!isset($_SESSION["userID"])) {
    header("Location: index.php");
    exit();
}

$userID = $_SESSION["userID"];
$userName = $_SESSION["userName"];

$stmt = $conn->prepare("
    SELECT
        b.budget_id,
        b.allowance,
        b.start_date,
        b.end_date,
        COALESCE(SUM(e.amount), 0) AS total_expenses
    FROM budgets b
    LEFT JOIN expenses e
        ON b.user_id = e.user_id
        AND e.expense_date BETWEEN b.start_date AND b.end_date
    WHERE b.user_id = ?
        AND b.period = 'Monthly'
    GROUP BY
        b.budget_id,
        b.allowance,
        b.start_date,
        b.end_date
    ORDER BY b.start_date DESC
");

$stmt->bind_param("s", $userID);
$stmt->execute();

$result = $stmt->get_result();
$totalExtraMoney = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extra Money</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box record-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Extra Money</h2>

    <p>
        Student:
        <?= htmlspecialchars($userName) ?>
        (<?= htmlspecialchars($userID) ?>)
    </p>

    <hr>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Allowance</th>
                    <th>Total Expenses</th>
                    <th>Extra Money</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $allowance = (float)$row["allowance"];
                    $totalExpenses = (float)$row["total_expenses"];
                    $extraMoney = $allowance - $totalExpenses;

                    if ($extraMoney < 0) {
                        $extraMoney = 0;
                    }

                    $totalExtraMoney += $extraMoney;
                    ?>

                    <tr>
                        <td><?= date("F Y", strtotime($row["start_date"])) ?></td>
                        <td>RM <?= number_format($allowance, 2) ?></td>
                        <td>RM <?= number_format($totalExpenses, 2) ?></td>
                        <td>RM <?= number_format($extraMoney, 2) ?></td>
                    </tr>
                <?php endwhile; ?>

                <tr>
                    <td colspan="3">
                        <strong>Total Extra Money</strong>
                    </td>
                    <td>
                        <strong>RM <?= number_format($totalExtraMoney, 2) ?></strong>
                    </td>
                </tr>

            <?php else: ?>
                <tr>
                    <td colspan="4">
                        No monthly budget records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <a href="userDashboard.php" class="gray-button">
        Back
    </a>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>