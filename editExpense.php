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

if (isset($_GET["id"])) {
    $expenseID = (int)$_GET["id"];
} elseif (isset($_POST["expense_id"])) {
    $expenseID = (int)$_POST["expense_id"];
} else {
    header("Location: history.php");
    exit();
}

if (isset($_POST["update_expense"])) {
    $category = trim($_POST["category"]);
    $newCategory = trim($_POST["new_category"]);
    $amount = (float)$_POST["amount"];
    $expenseDate = trim($_POST["expense_date"]);

    if ($category == "new") {
        if ($newCategory == "") {
            $message = "Please enter a new category.";
            $messageType = "error";
        } else {
            $category = $newCategory;
        }
    }

    if ($message == "") {
        if ($category == "" || $expenseDate == "") {
            $message = "Please complete all fields.";
            $messageType = "error";
        } elseif ($amount <= 0) {
            $message = "Amount must be greater than RM 0.";
            $messageType = "error";
        } else {
            $budgetStmt = $conn->prepare("
                SELECT budget_id
                FROM budgets
                WHERE user_id = ?
                    AND ? BETWEEN start_date AND end_date
                LIMIT 1
            ");

            $budgetStmt->bind_param(
                "ss",
                $userID,
                $expenseDate
            );

            $budgetStmt->execute();
            $budgetResult = $budgetStmt->get_result();

            if ($budgetResult->num_rows == 0) {
                $message = "Expense date is outside the budget period.";
                $messageType = "error";
            } else {
                $stmt = $conn->prepare("
                    UPDATE expenses
                    SET
                        category = ?,
                        amount = ?,
                        expense_date = ?
                    WHERE expense_id = ?
                        AND user_id = ?
                ");

                $stmt->bind_param(
                    "sdsis",
                    $category,
                    $amount,
                    $expenseDate,
                    $expenseID,
                    $userID
                );

                if ($stmt->execute()) {
                    $message = "Expense updated successfully.";
                    $messageType = "success";
                } else {
                    $message = "Failed to update expense.";
                    $messageType = "error";
                }

                $stmt->close();
            }

            $budgetStmt->close();
        }
    }
}

$stmt = $conn->prepare("
    SELECT
        expense_id,
        category,
        amount,
        expense_date
    FROM expenses
    WHERE expense_id = ?
        AND user_id = ?
");

$stmt->bind_param(
    "is",
    $expenseID,
    $userID
);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    $conn->close();

    header("Location: history.php");
    exit();
}

$expense = $result->fetch_assoc();
$stmt->close();

$standardCategories = [
    "Food",
    "Transportation",
    "Entertainment",
    "Shopping",
    "Other"
];

$isCustomCategory = !in_array(
    $expense["category"],
    $standardCategories
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box page-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Edit Expense</h2>

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
        <input
            type="hidden"
            name="expense_id"
            value="<?= htmlspecialchars($expense["expense_id"]) ?>"
        >

        <label for="category">Expense Category:</label>

        <select
            id="category"
            name="category"
            onchange="showNewCategory()"
            required
        >
            <option
                value="Food"
                <?= $expense["category"] == "Food" ? "selected" : "" ?>
            >
                Food
            </option>

            <option
                value="Transportation"
                <?= $expense["category"] == "Transportation" ? "selected" : "" ?>
            >
                Transportation
            </option>

            <option
                value="Entertainment"
                <?= $expense["category"] == "Entertainment" ? "selected" : "" ?>
            >
                Entertainment
            </option>

            <option
                value="Shopping"
                <?= $expense["category"] == "Shopping" ? "selected" : "" ?>
            >
                Shopping
            </option>

            <option
                value="Other"
                <?= $expense["category"] == "Other" ? "selected" : "" ?>
            >
                Other
            </option>

            <option
                value="new"
                <?= $isCustomCategory ? "selected" : "" ?>
            >
                + Create New Category
            </option>
        </select>

        <div
            id="newCategoryBox"
            style="<?= $isCustomCategory ? "display: block;" : "display: none;" ?>"
        >
            <label for="new_category">New Category:</label>

            <input
                type="text"
                id="new_category"
                name="new_category"
                value="<?= $isCustomCategory ? htmlspecialchars($expense["category"]) : "" ?>"
                <?= $isCustomCategory ? "required" : "" ?>
            >
        </div>

        <label for="amount">Expense Amount (RM):</label>

        <input
            type="number"
            id="amount"
            name="amount"
            step="0.01"
            min="0.01"
            value="<?= htmlspecialchars($expense["amount"]) ?>"
            required
        >

        <label for="expense_date">Expense Date:</label>

        <input
            type="date"
            id="expense_date"
            name="expense_date"
            value="<?= htmlspecialchars($expense["expense_date"]) ?>"
            required
        >

        <button type="submit" name="update_expense">
            Update
        </button>

        <a href="history.php" class="gray-button">
            Back
        </a>
    </form>
</div>

<script>
function showNewCategory() {
    var category = document.getElementById("category");
    var newCategoryBox = document.getElementById("newCategoryBox");
    var newCategory = document.getElementById("new_category");

    if (category.value == "new") {
        newCategoryBox.style.display = "block";
        newCategory.required = true;
    } else {
        newCategoryBox.style.display = "none";
        newCategory.required = false;
        newCategory.value = "";
    }
}

showNewCategory();
</script>

</body>
</html>

<?php
$conn->close();
?>