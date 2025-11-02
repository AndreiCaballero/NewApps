<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, password_hash, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);

    if ($stmt->fetch() && password_verify($password, $hash)) {
        // store user id and role in session
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;

        $stmt->close();

        // update last login timestamp
        $update = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id=?");
        $update->bind_param("i", $id);
        $update->execute();

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
<div class="bg-white p-6 rounded-2xl shadow-lg w-96">
  <h2 class="text-xl font-bold mb-4">Login</h2>
  <?php if (isset($error)) echo "<p class='text-red-500 mb-3'>$error</p>"; ?>
  <form method="post">
    <input type="email" name="email" placeholder="Email" required class="w-full p-2 mb-3 border rounded">
    <input type="password" name="password" placeholder="Password" required class="w-full p-2 mb-3 border rounded">
    <button class="w-full bg-blue-500 text-white p-2 rounded">Login</button>
  </form>
  <p class="mt-3 text-sm">Don’t have an account? <a href="register.php" class="text-blue-600">Register</a></p>
</div>
</body>
</html>