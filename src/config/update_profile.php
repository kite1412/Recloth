<?php
session_start();
require_once __DIR__ . '/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    // Validasi input
    if (empty($name)) {
        header("Location: ../user/profile.php?error=" . urlencode("Nama tidak boleh kosong"));
        exit;
    }

    try {
        // Update data user
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $userId]);

        // Update session name if changed
        $_SESSION['user_name'] = $name;

        header("Location: ../user/profile.php?success=1");
        exit;
    } catch (PDOException $e) {
        header("Location: ../user/profile.php?error=" . urlencode("Terjadi kesalahan sistem: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../user/profile.php");
    exit;
}
