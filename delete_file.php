<?php
declare(strict_types=1);
require 'config.php';

// --- Auth check ---
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

// --- CSRF protection ---
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(400);
    exit('Invalid CSRF token.');
}

if (!isset($_POST['file_id'])) {
    http_response_code(400);
    exit('Missing file ID.');
}

$fileId = (int)$_POST['file_id'];
$userId = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');

// Fetch file info first (to get filepath for deletion)
if ($isAdmin) {
    // Admins can delete any file
    $stmt = $mysqli->prepare('SELECT filepath FROM files WHERE id = ?');
    $stmt->bind_param('i', $fileId);
} else {
    // Users can only delete their own files
    $stmt = $mysqli->prepare('SELECT filepath FROM files WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $fileId, $userId);
}

if (!$stmt->execute()) {
    error_log('Failed to fetch file info: ' . $stmt->error);
    http_response_code(500);
    exit('Server error.');
}

$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    exit('File not found or access denied.');
}

$stmt->bind_result($filepath);
$stmt->fetch();
$stmt->close();

// Delete the physical file first
$filePath = __DIR__ . '/uploads/' . $filepath;
if (file_exists($filePath)) {
    if (!unlink($filePath)) {
        error_log('Failed to delete file: ' . $filePath);
        http_response_code(500);
        exit('Failed to delete file.');
    }
}

// Delete the database record
if ($isAdmin) {
    $stmt = $mysqli->prepare('DELETE FROM files WHERE id = ?');
    $stmt->bind_param('i', $fileId);
} else {
    $stmt = $mysqli->prepare('DELETE FROM files WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $fileId, $userId);
}

if (!$stmt->execute()) {
    error_log('Failed to delete file record: ' . $stmt->error);
    http_response_code(500);
    exit('Failed to delete file record.');
}

$stmt->close();

// Return to dashboard
header('Location: dashboard.php');
exit;