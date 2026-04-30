<?php
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        header("Location: ../user/forgot_password.php?error=" . urlencode("Semua field harus diisi"));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../user/forgot_password.php?error=" . urlencode("Format email tidak valid"));
        exit;
    }

    if (strlen($new_password) < 8) {
        header("Location: ../user/forgot_password.php?error=" . urlencode("Password minimal 8 karakter"));
        exit;
    }

    if ($new_password !== $confirm_password) {
        header("Location: ../user/forgot_password.php?error=" . urlencode("Password dan konfirmasi tidak cocok"));
        exit;
    }

    try {
        // Cek apakah email terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Hash password baru
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $user['id']]);

            header("Location: ../user/forgot_password.php?success=" . urlencode("Password berhasil diubah! Mengalihkan ke login..."));
            exit;
        } else {
            header("Location: ../user/forgot_password.php?error=" . urlencode("Email tidak ditemukan di sistem kami"));
            exit;
        }
    } catch (PDOException $e) {
        header("Location: ../user/forgot_password.php?error=" . urlencode("Terjadi kesalahan sistem: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../user/forgot_password.php");
    exit;
}
