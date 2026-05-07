<?php
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi
    if (empty($name) || empty($email) || empty($address) || empty($password) || empty($password_confirm)) {
        header("Location: ../user/register.php?error=" . urlencode("Semua field harus diisi"));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../user/register.php?error=" . urlencode("Format email tidak valid"));
        exit;
    }

    if (strlen($password) < 8) {
        header("Location: ../user/register.php?error=" . urlencode("Password minimal 8 karakter"));
        exit;
    }

    if ($password !== $password_confirm) {
        header("Location: ../user/register.php?error=" . urlencode("Password dan konfirmasi tidak cocok"));
        exit;
    }

    // Cek apakah email sudah terdaftar
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: ../user/register.php?error=" . urlencode("Email sudah terdaftar"));
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, $hashedPassword]);
        $userId = $pdo->lastInsertId();

        // Insert address
        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, address, is_default) VALUES (?, ?, 1)");
        $stmt->execute([$userId, $address]);

        $pdo->commit();
        header("Location: ../user/register.php?success=" . urlencode("Pendaftaran berhasil! Silakan login."));
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../user/register.php?error=" . urlencode("Terjadi kesalahan sistem: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../user/register.php");
    exit;
}
