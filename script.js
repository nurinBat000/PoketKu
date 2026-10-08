function setBudget() {
    var period = document.getElementById("period").value;
    var allowance = document.getElementById("allowance").value;

    if (allowance == "") {
        alert("Please enter amount.");
    } else {
        localStorage.setItem("budgetPeriod", period);
        localStorage.setItem("budgetAmount", Number(allowance));
        alert("Budget has been set to RM " + Number(allowance).toFixed(2));
    }
}

function addExpense() {
    var category = document.getElementById("category").value;
    var amount = document.getElementById("amount").value;
    var date = document.getElementById("date").value;

    if (amount == "") {
        alert("Please enter amount.");
    } else if (date == "") {
        alert("Please select date.");
    } else {
        var expenses = JSON.parse(localStorage.getItem("expenses")) || [];

        var expense = {
            category: category,
            amount: Number(amount),
            date: date
        };

        expenses.push(expense);
        localStorage.setItem("expenses", JSON.stringify(expenses));
        alert("Expense has been added: RM " + Number(amount).toFixed(2));
    }
}

function showBudget() {
    var period = localStorage.getItem("budgetPeriod") || "weekly";
    var budget = Number(localStorage.getItem("budgetAmount")) || 0;
    var expenses = JSON.parse(localStorage.getItem("expenses")) || [];
    var spent = 0;

    for (var i = 0; i < expenses.length; i++) {
        spent = spent + expenses[i].amount;
    }

    var remaining = budget - spent;

    document.getElementById("budgetPeriod").innerHTML =
        "Budget Period: " + period;

    document.getElementById("budgetAmount").innerHTML =
        "Budget Amount: RM " + budget.toFixed(2);

    document.getElementById("remainingBudget").innerHTML =
        "Remaining Budget: RM " + remaining.toFixed(2);

    document.getElementById("totalSpent").innerHTML =
        "RM " + spent.toFixed(2);

    document.getElementById("remaining").innerHTML =
        "RM " + remaining.toFixed(2);

    var saving = Number(localStorage.getItem("saving")) || 0;

    document.getElementById("saving").innerHTML =
        "Virtual Saving: RM " + saving.toFixed(2);
}

function showHistory() {
    var expenses = JSON.parse(localStorage.getItem("expenses")) || [];
    var table = document.getElementById("expenseHistory");

    table.innerHTML = "";

    for (var i = 0; i < expenses.length; i++) {
        var row = table.insertRow();

        var cell1 = row.insertCell(0);
        var cell2 = row.insertCell(1);
        var cell3 = row.insertCell(2);
        var cell4 = row.insertCell(3);
        var cell5 = row.insertCell(4);

        cell1.innerHTML = i + 1;
        cell2.innerHTML = expenses[i].category;
        cell3.innerHTML = expenses[i].amount.toFixed(2);
        cell4.innerHTML = expenses[i].date;

        cell5.innerHTML =
            '<button onclick="editExpense(' + i + ')">Edit</button>' +
            '<button onclick="deleteExpense(' + i + ')">Delete</button>';
    }
}

function editExpense(index) {
    var expenses = JSON.parse(localStorage.getItem("expenses")) || [];

    var newAmount = prompt(
        "Enter new expense Amount:",
        expenses[index].amount
    );

    if (newAmount == null) {
        return;
    }

    if (newAmount == "") {
        alert("Please enter amount");
    } else {
        expenses[index].amount = Number(newAmount);
        localStorage.setItem("expenses", JSON.stringify(expenses));
        alert("Expense has been updated.");
        location.reload();
    }
}

function deleteExpense(index) {
    var expenses = JSON.parse(localStorage.getItem("expenses")) || [];

    expenses.splice(index, 1);
    localStorage.setItem("expenses", JSON.stringify(expenses));

    alert("Expense has been deleted.");
    location.reload();
}

function searchExpense() {
    var search = document.getElementById("search").value.toLowerCase();
    var expenses = JSON.parse(localStorage.getItem("expenses")) || [];
    var table = document.getElementById("expenseHistory");

    table.innerHTML = "";

    for (var i = 0; i < expenses.length; i++) {
        if (expenses[i].category.toLowerCase().includes(search)) {
            var row = table.insertRow();

            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);
            var cell5 = row.insertCell(4);

            cell1.innerHTML = i + 1;
            cell2.innerHTML = expenses[i].category;
            cell3.innerHTML = expenses[i].amount.toFixed(2);
            cell4.innerHTML = expenses[i].date;

            cell5.innerHTML =
                '<button onclick="editExpense(' + i + ')">Edit</button>' +
                '<button onclick="deleteExpense(' + i + ')">Delete</button>';
        }
    }
}

function login() {
    var username = document.getElementById("username").value;
    var password = document.getElementById("password").value;

    if (username == "") {
        alert("Please enter username.");
    } else if (password == "") {
        alert("Please enter password.");
    } else {
        alert("Login successful.");
        location.href = "dashboard.html";
    }
}