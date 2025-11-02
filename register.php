<?php
require 'config.php';

$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']);
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $email     = trim($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("INSERT INTO users (username, firstname, lastname, email, password_hash, role) 
                          VALUES (?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param("sssss", $username, $firstname, $lastname, $email, $password);


    if ($stmt->execute()) {
        $success = "Registration Successful! Redirecting to login...";
        // auto redirect after 3 seconds
        header("refresh:3;url=login.php");
    } else {
        $error = "Username or email already exists.";
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
  <h2 class="text-xl font-bold mb-4">Register</h2>

  <?php if (isset($success)): ?>
    <p class="text-green-600 font-semibold mb-3"><?= $success ?></p>
  <?php elseif (isset($error)): ?>
    <p class="text-red-500 mb-3"><?= $error ?></p>
  <?php endif; ?>

  <form method="post">
    <input type="text" name="username" placeholder="Username" required class="w-full p-2 mb-3 border rounded">
    <input type="text" name="firstname" placeholder="First Name" required class="w-full p-2 mb-3 border rounded">
    <input type="text" name="lastname" placeholder="Last Name" required class="w-full p-2 mb-3 border rounded">
    <input type="email" name="email" placeholder="Email" required class="w-full p-2 mb-3 border rounded">
    <input type="password" name="password" placeholder="Password" required class="w-full p-2 mb-3 border rounded">
    <button class="w-full bg-blue-500 text-white p-2 rounded">Register</button>
  </form>

  <p class="mt-3 text-sm">Already have an account? <a href="login.php" class="text-blue-600">Login</a></p>
</div>
</body>
</html>