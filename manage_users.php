<?php
require 'config.php';

// allow only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle promote/demote/delete actions
if (isset($_GET['action'], $_GET['id'])) {
    $userId = (int) $_GET['id'];

    if ($_GET['action'] === 'promote') {
        $stmt = $mysqli->prepare("UPDATE users SET role='admin' WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    if ($_GET['action'] === 'demote') {
        $stmt = $mysqli->prepare("UPDATE users SET role='user' WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    if ($_GET['action'] === 'delete') {
        // delete files first (to avoid orphan files)
        $stmt = $mysqli->prepare("DELETE FROM files WHERE user_id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $stmt = $mysqli->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
    header("Location: manage_users.php");
    exit;
}

// Fetch all users
$result = $mysqli->query("SELECT id, username, firstname, lastname, email, role, last_login, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-5xl mx-auto bg-white p-6 rounded-2xl shadow">
  <h2 class="text-2xl font-bold mb-4">👑 Admin - Manage Users</h2>

  <table class="w-full border-collapse">
    <thead>
      <tr class="bg-gray-200 text-left">
        <th class="p-2 border">ID</th>
        <th class="p-2 border">Username</th>
        <th class="p-2 border">Name</th>
        <th class="p-2 border">Email</th>
        <th class="p-2 border">Role</th>
        <th class="p-2 border">Last Login</th>
        <th class="p-2 border">Registered</th>
        <th class="p-2 border">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="border-b">
          <td class="p-2"><?= $row['id'] ?></td>
          <td class="p-2"><?= htmlspecialchars($row['username']) ?></td>
          <td class="p-2"><?= htmlspecialchars($row['firstname'] . " " . $row['lastname']) ?></td>
          <td class="p-2"><?= htmlspecialchars($row['email']) ?></td>
          <td class="p-2 font-semibold"><?= htmlspecialchars($row['role']) ?></td>
          <td class="p-2"><?= $row['last_login'] ?: "Never" ?></td>
          <td class="p-2"><?= $row['created_at'] ?></td>
          <td class="p-2 space-x-2">
            <?php if ($row['role'] === 'user'): ?>
              <a href="?action=promote&id=<?= $row['id'] ?>" class="bg-green-500 text-white px-2 py-1 rounded text-sm">Promote</a>
            <?php elseif ($row['role'] === 'admin' && $row['id'] != $_SESSION['user_id']): ?>
              <a href="?action=demote&id=<?= $row['id'] ?>" class="bg-yellow-500 text-white px-2 py-1 rounded text-sm">Demote</a>
            <?php endif; ?>
            <?php if ($row['id'] != $_SESSION['user_id']): ?>
              <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')" class="bg-red-500 text-white px-2 py-1 rounded text-sm">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <a href="dashboard.php" class="inline-block mt-6 bg-blue-500 text-white px-4 py-2 rounded">Back to Dashboard</a>
</div>
</body>