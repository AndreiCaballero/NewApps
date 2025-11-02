<?php
declare(strict_types=1);
require 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// --- CSRF token generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int)$_SESSION['user_id'];

// --- Fetch uploaded files for this user ---
$sql = "SELECT id, filename, filepath, created_at FROM files WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($sql);

if (! $stmt) {
    // prepare failed — log real DB error (for dev) and show friendly message
    error_log('DB prepare error: ' . $mysqli->error);
    http_response_code(500);
    exit('Server error (DB prepare failed).');
}

$stmt->bind_param('i', $userId);

if (! $stmt->execute()) {
    error_log('DB execute error: ' . $stmt->error);
    http_response_code(500);
    exit('Server error (DB execute failed).');
}

// Fetch results — compatible with installations without mysqlnd
$files = [];
if (method_exists($stmt, 'get_result')) {
    $result = $stmt->get_result();
    $files = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} else {
    // fallback: bind_result + fetch
  $stmt->bind_result($id, $filename, $filepath, $created_at);
  while ($stmt->fetch()) {
    $files[] = [
      'id' => $id,
      'filename' => $filename,
      'filepath' => $filepath,
      'created_at' => $created_at,
    ];
  }
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Secure File Upload</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center py-8">

<div class="bg-white shadow-lg rounded-2xl p-8 w-96 mb-6">
  <h1 class="text-2xl font-semibold mb-4 text-center">Upload a File</h1>

  <form action="upload.php" method="POST" enctype="multipart/form-data" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

    <input type="file" name="file" required
           class="block w-full text-sm text-gray-700 border rounded-lg cursor-pointer bg-gray-50">

    <button type="submit"
            class="w-full bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">
      Upload
    </button>
  </form>

  <a href="logout.php" class="block mt-4 text-center text-sm text-gray-500 hover:text-gray-700">Logout</a>
</div>

<!-- File list -->
<div class="bg-white shadow-lg rounded-2xl p-8 w-96">
  <h2 class="text-xl font-semibold mb-4 text-center">Your Uploaded Files</h2>

  <?php if (count($files) === 0): ?>
    <p class="text-gray-500 text-center">No files uploaded yet.</p>
  <?php else: ?>
    <ul class="divide-y divide-gray-200">
      <?php foreach ($files as $file): ?>
        <li class="py-2 flex justify-between items-center">
          <div class="flex items-center space-x-2">
            <!-- Filename -->
            <span class="text-gray-700 break-all flex-grow">
               <?= htmlspecialchars($file['filename'], ENT_QUOTES) ?>
            </span>

            <div class="flex items-center space-x-2">
              <!-- View button (opens in new tab) -->
              <?php
                $stored = $file['filepath'] ?? '';
                $url = 'uploads/' . $stored;
              ?>
              <a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"
                 class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600"
                 target="_blank" title="View in browser">
                 View
              </a>

              <!-- Download button -->
              <a href="download.php?id=<?= $file['id'] ?>"
                 class="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600"
                 title="Download file">
                 Download
              </a>
              
              <!-- Delete form -->
              <form action="delete_file.php" method="POST" class="inline" 
                    onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($file['filename']), ENT_QUOTES) ?>?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600"
                        title="Delete file">
                  Delete
                </button>
              </form>
            </div>
          </div>
          <span class="text-gray-400 text-xs ml-2">
            <?= htmlspecialchars($file['created_at'], ENT_QUOTES) ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

</body>
</html>
