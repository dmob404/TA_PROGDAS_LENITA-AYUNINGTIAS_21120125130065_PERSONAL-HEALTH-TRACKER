<?php
session_start();

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);

    if ($username != "") {
        $_SESSION['user'] = $username;

        if (!file_exists("users/$username")) {
            mkdir("users/$username", 0777, true);
        }
        if (!file_exists("users/$username/data.json")) {
            file_put_contents("users/$username/data.json", "[]");
        }
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="dark">
<div class="container">
<h1>Login User</h1>
<form method="POST">
<input type="text" name="username" placeholder="Masukkan username..." required>
<button type="submit" name="login" class="btn-primary">Masuk</button>
</form>
</div>
</body>
</html>
