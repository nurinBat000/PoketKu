<?php
session_start();
include "db.php";

if (!isset($_SESSION["adminID"])) {
    header("Location: index.php");
    exit();
}

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

$searchValue = "%" . $search . "%";

$stmt = $conn->prepare("
    SELECT
        expenses.expense_id,
        expenses.user_id,
        user.userName,
        expenses.category,
        expenses.amount,
        expenses.expense_date
    FROM expenses
    INNER JOIN `user`
        ON expenses.user_id = user.userID
    WHERE expenses.user_id LIKE ?
        OR user.userName LIKE ?
        OR expenses.category LIKE ?
        OR expenses.expense_date LIKE ?
    ORDER BY expenses.expense_id ASC
");

$stmt->bind_param(
    "ssss",
    $searchValue,
    $searchValue,
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
    <title>Expense Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box record-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Expense Records</h2>

    <hr>

    <h3>Search Expenses</h3>

    <form method="GET" class="search-form">
        <input
            type="text"
            name="search"
            placeholder="Search user ID, name, category or date"
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">Search</button>

        <a href="adminExpenses.php" class="green-button">
            Show All
        </a>
    </form>

    <hr>

    <h3>Expense List</h3>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?= str_pad(
                                $row["expense_id"],
                                3,
                                "0",
                                STR_PAD_LEFT
                            ) ?>
                        </td>

                        <td><?= htmlspecialchars($row["user_id"]) ?></td>
                        <td><?= htmlspecialchars($row["userName"]) ?></td>
                        <td><?= htmlspecialchars($row["category"]) ?></td>
                        <td>RM <?= number_format($row["amount"], 2) ?></td>
                        <td><?= htmlspecialchars($row["expense_date"]) ?></td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="6">
                        No expense records found.
                    </td>
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