<?php
session_start();
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: ../user/login.php?error=" . urlencode("Email dan password harus diisi"));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $validPassword = false;
            // Support hash and plain text password check
            if (password_verify($password, $user['password'])) {
                $validPassword = true;
            } elseif ($password === $user['password']) {
                $validPassword = true;
            }

            if ($validPassword) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header("Location: ../../index.php");
                exit;
            }
        }

        header("Location: ../user/login.php?error=" . urlencode("Email atau password salah"));
        exit;
    } catch (PDOException $e) {
        header("Location: ../user/login.php?error=" . urlencode("Terjadi kesalahan sistem: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: ../user/login.php");
    exit;
}
