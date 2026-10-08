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
        budgets.budget_id,
        budgets.user_id,
        user.userName,
        budgets.period,
        budgets.allowance,
        budgets.start_date,
        budgets.end_date,
        budgets.saving_transferred
    FROM budgets
    INNER JOIN `user`
        ON budgets.user_id = user.userID
    WHERE budgets.user_id LIKE ?
        OR user.userName LIKE ?
        OR budgets.period LIKE ?
    ORDER BY budgets.budget_id ASC
");

$stmt->bind_param(
    "sss",
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
    <title>Budget Records</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="main-box record-box">

    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Budget Records</h2>

    <hr>

    <h3>Search Budgets</h3>

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search user ID, name or period"
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">
            Search
        </button>

        <a href="adminBudgets.php" class="green-button">
            Show All
        </a>

    </form>

    <hr>

    <h3>Budget List</h3>

    <div class="table-box">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Period</th>
                    <th>Allowance</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($result->num_rows > 0): ?>

                <?php while ($row = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?= str_pad(
                                $row["budget_id"],
                                3,
                                "0",
                                STR_PAD_LEFT
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["user_id"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["userName"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["period"]) ?>
                        </td>

                        <td>
                            RM <?= number_format($row["allowance"], 2) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["start_date"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["end_date"]) ?>
                        </td>

                        <td>
                            <?= $row["saving_transferred"] == 1 ? "🔵" : "🟢" ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8">
                        No budget records found.
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