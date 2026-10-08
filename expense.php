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

if (isset($_POST["add_expense"])) {
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
            $message = "Expense amount must be greater than RM 0.";
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
                $budgetStmt->close();

            } else {
                $budgetStmt->close();

                $stmt = $conn->prepare("
                    INSERT INTO expenses
                    (
                        user_id,
                        category,
                        amount,
                        expense_date
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "ssds",
                    $userID,
                    $category,
                    $amount,
                    $expenseDate
                );

                if ($stmt->execute()) {
                    $message = "Expense added successfully.";
                    $messageType = "success";
                } else {
                    $message = "Failed to add expense.";
                    $messageType = "error";
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box page-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Add Expense</h2>

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
        <label for="category">Expense Category:</label>

        <select
            id="category"
            name="category"
            onchange="showNewCategory()"
            required
        >
            <option value="">-- Select Category --</option>

            <option
                value="Food"
                <?= isset($_POST["category"]) && $_POST["category"] == "Food" ? "selected" : "" ?>
            >
                Food
            </option>

            <option
                value="Transportation"
                <?= isset($_POST["category"]) && $_POST["category"] == "Transportation" ? "selected" : "" ?>
            >
                Transportation
            </option>

            <option
                value="Entertainment"
                <?= isset($_POST["category"]) && $_POST["category"] == "Entertainment" ? "selected" : "" ?>
            >
                Entertainment
            </option>

            <option
                value="Shopping"
                <?= isset($_POST["category"]) && $_POST["category"] == "Shopping" ? "selected" : "" ?>
            >
                Shopping
            </option>

            <option
                value="Other"
                <?= isset($_POST["category"]) && $_POST["category"] == "Other" ? "selected" : "" ?>
            >
                Other
            </option>

            <option
                value="new"
                <?= isset($_POST["category"]) && $_POST["category"] == "new" ? "selected" : "" ?>
            >
                + Create New Category
            </option>
        </select>

        <div
            id="newCategoryBox"
            style="<?= isset($_POST["category"]) && $_POST["category"] == "new" ? "display: block;" : "display: none;" ?>"
        >
            <label for="new_category">New Category:</label>

            <input
                type="text"
                id="new_category"
                name="new_category"
                placeholder="Enter new category"
                value="<?= isset($_POST["new_category"]) ? htmlspecialchars($_POST["new_category"]) : "" ?>"
            >
        </div>

        <label for="amount">Expense Amount (RM):</label>

        <input
            type="number"
            id="amount"
            name="amount"
            step="0.01"
            min="0.01"
            value="<?= isset($_POST["amount"]) ? htmlspecialchars($_POST["amount"]) : "" ?>"
            required
        >

        <label for="expense_date">Expense Date:</label>

        <input
            type="date"
            id="expense_date"
            name="expense_date"
            value="<?= isset($_POST["expense_date"]) ? htmlspecialchars($_POST["expense_date"]) : "" ?>"
            required
        >

        <button type="submit" name="add_expense">
            Add Expense
        </button>

        <a href="userDashboard.php" class="green-button">
            Back
        </a>
    </form>
</div>

<script>
function showNewCategory() {
    var category = document.getElementById("category").value;
    var newCategoryBox = document.getElementById("newCategoryBox");
    var newCategory = document.getElementById("new_category");

    if (category == "new") {
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