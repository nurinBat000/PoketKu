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

if (isset($_POST["setBudget"])) {
    $period = trim($_POST["period"]);
    $allowance = trim($_POST["allowance"]);
    $startDate = trim($_POST["start_date"]);

    if ($period == "" || $allowance == "" || $startDate == "") {
        $message = "Please complete all fields.";
        $messageType = "error";

    } elseif ($period != "Weekly" && $period != "Monthly") {
        $message = "Invalid budget period.";
        $messageType = "error";

    } elseif (!is_numeric($allowance) || $allowance <= 0) {
        $message = "Allowance must be more than RM 0.";
        $messageType = "error";

    } else {
        $start = new DateTime($startDate);
        $end = clone $start;

        if ($period == "Weekly") {
            $end->modify("+7 days");
        } else {
            $end->modify("+1 month");
        }

        $endDate = $end->format("Y-m-d");

        $stmt = $conn->prepare("
            SELECT budget_id
            FROM budgets
            WHERE user_id = ?
                AND start_date < ?
                AND end_date > ?
        ");

        $stmt->bind_param(
            "sss",
            $userID,
            $endDate,
            $startDate
        );

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "This budget period overlaps with an existing budget.";
            $messageType = "error";
            $stmt->close();

        } else {
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO budgets
                (
                    user_id,
                    period,
                    allowance,
                    start_date,
                    end_date,
                    saving_transferred
                )
                VALUES (?, ?, ?, ?, ?, 0)
            ");

            $stmt->bind_param(
                "ssdss",
                $userID,
                $period,
                $allowance,
                $startDate,
                $endDate
            );

            if ($stmt->execute()) {
                $message = "Budget set successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to set budget.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Budget</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box page-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Set Your Budget</h2>

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
        <label for="period">Budget Period:</label>

        <select
            id="period"
            name="period"
            onchange="calculateEndDate()"
            required
        >
            <option value="">Select Period</option>

            <option
                value="Weekly"
                <?= isset($_POST["period"]) && $_POST["period"] == "Weekly" ? "selected" : "" ?>
            >
                Weekly
            </option>

            <option
                value="Monthly"
                <?= isset($_POST["period"]) && $_POST["period"] == "Monthly" ? "selected" : "" ?>
            >
                Monthly
            </option>
        </select>

        <label for="allowance">Allowance Amount (RM):</label>

        <input
            type="number"
            id="allowance"
            name="allowance"
            min="0.01"
            step="0.01"
            value="<?= isset($_POST["allowance"]) ? htmlspecialchars($_POST["allowance"]) : "" ?>"
            required
        >

        <label for="start_date">Start Date:</label>

        <input
            type="date"
            id="start_date"
            name="start_date"
            value="<?= isset($_POST["start_date"]) ? htmlspecialchars($_POST["start_date"]) : "" ?>"
            onchange="calculateEndDate()"
            required
        >

        <label for="end_date">End Date:</label>

        <input
            type="date"
            id="end_date"
            readonly
        >

        <button type="submit" name="setBudget">
            Set Budget
        </button>

        <a href="userDashboard.php" class="gray-button">
            Back
        </a>
    </form>
</div>

<script>
function calculateEndDate() {
    var period = document.getElementById("period").value;
    var startValue = document.getElementById("start_date").value;
    var endInput = document.getElementById("end_date");

    if (period === "" || startValue === "") {
        endInput.value = "";
        return;
    }

    var parts = startValue.split("-");

    var start = new Date(
        parseInt(parts[0]),
        parseInt(parts[1]) - 1,
        parseInt(parts[2])
    );

    var end = new Date(start);

    if (period === "Weekly") {
        end.setDate(end.getDate() + 7);
    } else if (period === "Monthly") {
        end.setMonth(end.getMonth() + 1);
    }

    var year = end.getFullYear();
    var month = String(end.getMonth() + 1).padStart(2, "0");
    var day = String(end.getDate()).padStart(2, "0");

    endInput.value = year + "-" + month + "-" + day;
}

calculateEndDate();
</script>

</body>
</html>

<?php
$conn->close();
?>