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

if (isset($_GET["delete"])) {
    $userID = trim($_GET["delete"]);

    if ($userID != "") {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                DELETE FROM expenses
                WHERE user_id = ?
            ");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                DELETE FROM savings
                WHERE user_id = ?
            ");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                DELETE FROM budgets
                WHERE user_id = ?
            ");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                DELETE FROM `user`
                WHERE userID = ?
            ");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
        }
    }

    header("Location: adminDashboard.php");
    exit();
}

$searchValue = "%" . $search . "%";

$stmt = $conn->prepare("
    SELECT
        userID,
        userName,
        userPhoneNo,
        userEmail
    FROM `user`
    WHERE userID LIKE ?
        OR userName LIKE ?
        OR userPhoneNo LIKE ?
        OR userEmail LIKE ?
    ORDER BY userID ASC
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box admin-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Admin Dashboard</h2>

    <p>
        Welcome, <?= htmlspecialchars($_SESSION["adminName"]) ?>!
    </p>

    <hr>

    <h3>User Management</h3>

    <form method="GET" class="search-form">
        <input
            type="text"
            name="search"
            placeholder="Search student"
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">Search</button>

        <a href="adminDashboard.php" class="green-button">
            Show All
        </a>
    </form>

    <hr>

    <h3>Student List</h3>

    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["userID"]) ?></td>
                        <td><?= htmlspecialchars($row["userName"]) ?></td>
                        <td><?= htmlspecialchars($row["userPhoneNo"]) ?></td>
                        <td><?= htmlspecialchars($row["userEmail"]) ?></td>

                        <td class="action-cell">
                            <a
                                href="editStudent.php?id=<?= urlencode($row["userID"]) ?>"
                                class="green-button small-button"
                            >
                                Edit
                            </a>

                            <a
                                href="adminDashboard.php?delete=<?= urlencode($row["userID"]) ?>"
                                class="green-button small-button"
                                onclick="return confirm('Delete this student and all related records?');"
                            >
                                Delete
                            </a>

                            <a
                                href="studentData.php?id=<?= urlencode($row["userID"]) ?>"
                                class="green-button small-button"
                            >
                                Data
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="5">
                        No student records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="addStudent.php" class="green-button add-button">
        + Add New Student
    </a>

    <hr>

    <h3>System Records</h3>

    <div class="button-group">
        <a href="adminBudgets.php" class="green-button">
            Budgets
        </a>

        <a href="adminExpenses.php" class="green-button">
            Expenses
        </a>

        <a href="adminSavings.php" class="green-button">
            Savings
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

<?php
$stmt->close();
$conn->close();
?>