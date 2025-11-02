<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if (!isset($_GET['id'])) { die("Missing file id."); }
$fileId = (int) $_GET['id'];

// Admins can download any file; users only their own files
if ($_SESSION['role'] === 'admin') {
    $stmt = $mysqli->prepare("SELECT filename, filepath, iv, tag FROM files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
} else {
    $stmt = $mysqli->prepare("SELECT filename, filepath, iv, tag FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $fileId, $_SESSION['user_id']);
}

$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    die("File not found or access denied.");
}
$stmt->bind_result($filename, $filepath, $iv_b64, $tag_b64);
$stmt->fetch();
$stmt->close();

// make sure uploads folder exists
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads";
if (!is_dir($uploadDir)) {
    die("Uploads folder missing. Path: $uploadDir");
}

$filePath = $uploadDir . DIRECTORY_SEPARATOR . $filepath;
if (!file_exists($filePath)) {
    die("File not found on server.");
}

// If iv/tag are missing (older plaintext files), serve file directly
if (empty($iv_b64) || empty($tag_b64)) {
    // Decide inline vs attachment
    $asAttachment = isset($_GET['download']) && ($_GET['download'] === '1' || $_GET['download'] === 'true');

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
    if ($asAttachment) {
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    } else {
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
    }
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// read ciphertext
$ciphertext = file_get_contents($filePath);
if ($ciphertext === false) die("Failed to read encrypted file.");

// decode iv and tag
$iv = base64_decode($iv_b64);
$tag = base64_decode($tag_b64);

// decrypt
$plaintext = openssl_decrypt(
    $ciphertext,
    'aes-256-gcm',
    MASTER_KEY,
    OPENSSL_RAW_DATA,
    $iv,
    $tag
);

if ($plaintext === false) {
    die("Decryption failed: authentication failed or data corrupted.");
}

// Get MIME type from decrypted data (best-effort)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$tmpMime = $finfo->buffer($plaintext);
$mimeType = $tmpMime ?: 'application/octet-stream';

// Decide inline vs attachment
$asAttachment = isset($_GET['download']) && ($_GET['download'] === '1' || $_GET['download'] === 'true');

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
if ($asAttachment) {
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
}
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($plaintext));
echo $plaintext;
exit;
