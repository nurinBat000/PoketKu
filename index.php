<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-box login-box">
    <img src="PoketKu.png" alt="PoketKu Logo" class="logo">

    <h2>Student Budget Tracker</h2>

    <hr>

    <h3>Login</h3>

    <form action="login.php" method="POST">
        <label for="userID">User ID / Admin ID:</label>

        <input
            type="text"
            id="userID"
            name="userID"
            required
        >

        <label for="password">Password:</label>

        <input
            type="password"
            id="password"
            name="password"
            pattern="[0-9]{4}"
            maxlength="4"
            inputmode="numeric"
            title="Password must be exactly 4 digits"
            required
        >

        <button type="submit" name="login">
            Login
        </button>
    </form>
</div>

</body>
</html>