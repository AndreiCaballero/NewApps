<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "secure_files");
if ($mysqli->connect_errno) {
    die("Failed to connect: " . $mysqli->connect_error);
}

// Master key (in real-world: store in env vars / Vault, not code)
define("MASTER_KEY", hash("sha256", "SuperSecretKey123!", true));
?>
