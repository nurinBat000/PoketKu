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
        s.saving_id,
        s.user_id,
        u.userName,
        s.amount,
        s.saving_date
    FROM savings s
    INNER JOIN `user` u
        ON s.user_id = u.userID
    WHERE s.user_id LIKE ?
        OR u.userName LIKE ?
        OR s.saving_date LIKE ?
    ORDER BY s.saving_id DESC
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
    <title>Saving Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box record-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Saving Records</h2>

    <hr>

    <h3>Search Savings</h3>

    <form method="GET" class="search-form">
        <input
            type="text"
            name="search"
            placeholder="Search User ID, Name or Date"
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">Search</button>

        <a href="adminSavings.php" class="green-button">
            Show All
        </a>
    </form>

    <hr>

    <h3>Saving List</h3>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Name</th>
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
                                $row["saving_id"],
                                3,
                                "0",
                                STR_PAD_LEFT
                            ) ?>
                        </td>

                        <td><?= htmlspecialchars($row["user_id"]) ?></td>
                        <td><?= htmlspecialchars($row["userName"]) ?></td>
                        <td>RM <?= number_format($row["amount"], 2) ?></td>
                        <td><?= htmlspecialchars($row["saving_date"]) ?></td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="5">
                        No saving records found.
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