<?php
session_start();
include "db.php";

if (!isset($_SESSION["userID"])) {
    header("Location: index.php");
    exit();
}

$userID = $_SESSION["userID"];
$message = "";
$messageType = "";

if (isset($_GET["delete"])) {
    $expenseID = (int)$_GET["delete"];

    $stmt = $conn->prepare("
        DELETE FROM expenses
        WHERE expense_id = ?
            AND user_id = ?
    ");

    $stmt->bind_param("is", $expenseID, $userID);

    if ($stmt->execute()) {
        $message = "Expense deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete expense.";
        $messageType = "error";
    }

    $stmt->close();
}

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$searchValue = "%" . $search . "%";

$stmt = $conn->prepare("
    SELECT
        expense_id,
        category,
        amount,
        expense_date
    FROM expenses
    WHERE user_id = ?
        AND (
            category LIKE ?
            OR expense_date LIKE ?
        )
    ORDER BY expense_date DESC, expense_id DESC
");

$stmt->bind_param(
    "sss",
    $userID,
    $searchValue,
    $searchValue
);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense History</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box history-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Expense History</h2>

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

    <h3>Search Expenses</h3>

    <form method="GET" class="search-form">
        <input
            type="text"
            name="search"
            placeholder="Search category or date"
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">Search</button>

        <a href="history.php" class="green-button">
            Show All
        </a>
    </form>

    <hr>

    <h3>Expense List</h3>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php $number = 1; ?>

            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $number ?></td>
                        <td><?= htmlspecialchars($row["category"]) ?></td>
                        <td>RM <?= number_format($row["amount"], 2) ?></td>
                        <td><?= htmlspecialchars($row["expense_date"]) ?></td>

                        <td class="action-cell">
                            <a
                                href="editExpense.php?id=<?= urlencode($row["expense_id"]) ?>"
                                class="green-button small-button"
                            >
                                Edit
                            </a>

                            <a
                                href="history.php?delete=<?= urlencode($row["expense_id"]) ?>"
                                class="green-button small-button"
                                onclick="return confirm('Delete this expense?');"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>

                    <?php $number++; ?>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="5">
                        No expense records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <a href="userDashboard.php" class="green-button">
        Back
    </a>

    <div class="bottom-area">
        <a href="logout.php" class="logout-button">
            Logout
        </a>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>