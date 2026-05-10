<?php
session_start();
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../user/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add') {
            $label = trim($_POST['label'] ?? 'Rumah');
            $address = trim($_POST['address'] ?? '');
            $zipCode = trim($_POST['zip_code'] ?? '');
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            if (empty($address)) {
                throw new Exception("Alamat tidak boleh kosong.");
            }

            if (empty($zipCode) || !ctype_digit($zipCode)) {
                throw new Exception("Kode pos harus diisi dan berupa angka.");
            }

            $pdo->beginTransaction();

            if ($isDefault) {
                $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }

            $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, label, address, zip_code, is_default) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $label, $address, (int)$zipCode, $isDefault]);

            $pdo->commit();
            header("Location: ../user/profile.php?success=1");
            exit;

        } elseif ($action === 'delete') {
            $addressId = $_POST['address_id'] ?? 0;

            // Pastikan alamat milik user tersebut dan bukan alamat default terakhir
            $stmt = $pdo->prepare("SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $userId]);
            $addr = $stmt->fetch();

            if ($addr) {
                if ($addr['is_default']) {
                    throw new Exception("Alamat utama tidak dapat dihapus. Silakan tetapkan alamat lain sebagai utama terlebih dahulu.");
                }

                $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$addressId, $userId]);
                header("Location: ../user/profile.php?success=1");
                exit;
            }

        } elseif ($action === 'set_default') {
            $addressId = $_POST['address_id'] ?? 0;

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$addressId, $userId]);
            $pdo->commit();

            header("Location: ../user/profile.php?success=1");
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../user/profile.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

header("Location: ../user/profile.php");
exit;
