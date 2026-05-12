<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
require '../config/database.php';

// Menentukan user_id sementara untuk simulasi
$userId = $_SESSION['user_id'] ?? 1;

// Mendapatkan cart untuk user saat ini
$stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->execute([$userId]);
$cart = $stmt->fetch();

$cartItems = [];
$totalItems = 0;
$subtotalPrice = 0;
$ongkir = 0;
$totalPrice = 0;

if ($cart) {
    $cartId = $cart['id'];

    // Cek ketersediaan kolom
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN));
    $hasImage = in_array('image', $columns, true);

    $sql = "
        SELECT 
            ci.product_id, 
            ci.quantity, 
            p.name, 
            p.price, 
            " . ($hasImage ? "IF(p.image LIKE 'uploads/%', CONCAT('/Recloth/src/admin/', p.image), p.image)" : "''") . " AS image,
            c.name AS category_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE ci.cart_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cartId]);
    $cartItems = $stmt->fetchAll();

    foreach ($cartItems as $item) {
        $totalItems += $item['quantity'];
        $subtotalPrice += $item['quantity'] * $item['price'];
    }
    $totalPrice = $subtotalPrice;
}

// Ambil daftar alamat user
$stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();

$paymentAddress = null;
$selectedPaymentMethod = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cartItems)) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $addressId = $_POST['address_id'] ?? '';
    $shippingCost = (int) ($_POST['shipping_cost'] ?? 0);
    $shippingService = trim($_POST['shipping_service'] ?? '');

    // Validasi alamat
    $selectedAddressText = '';
    if (empty($addressId)) {
        $errorMsg = "Silakan pilih alamat pengiriman.";
    } else {
        foreach ($addresses as $addr) {
            if ($addr['id'] == $addressId) {
                $selectedAddressText = $addr['label'] . ": " . $addr['address'];
                break;
            }
        }
        if (empty($selectedAddressText)) {
            $errorMsg = "Alamat tidak valid.";
        }
    }
    
    if (!$errorMsg && empty($shippingService)) {
        $errorMsg = "Silakan pilih opsi pengiriman.";
    }
    if (!$errorMsg && $shippingCost <= 0) {
        $errorMsg = "Biaya pengiriman tidak valid.";
    }

    // Recalculate total with shipping
    $ongkir = $shippingCost;
    $totalPrice = $subtotalPrice + $ongkir;

    if (!$errorMsg && in_array($paymentMethod, ['bni', 'bca', 'bri', 'gopay'])) {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, payment_method, address) VALUES (?, ?, 'pending', ?, ?)");
            $stmt->execute([$userId, $totalPrice, $paymentMethod, $selectedAddressText]);
            $orderId = $pdo->lastInsertId();

            // Create shipment info
            $stmt = $pdo->prepare("INSERT INTO shipment_info (order_id, shipment_price, service_name) VALUES (?, ?, ?)");
            $stmt->execute([$orderId, $ongkir, $shippingService]);
            
            // Create order items
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            // Hit Midtrans API
            $envPath = __DIR__ . '/../../.env';
            $env = file_exists($envPath) ? parse_ini_file($envPath) : [];
            $serverKey = $env['MIDTRANS_SERVER_KEY'] ?? '';
            
            $payload = [
                "transaction_details" => [
                    "order_id" => "orders-" . $orderId,
                    "gross_amount" => $totalPrice
                ]
            ];
            
            if ($paymentMethod === 'gopay') {
                $payload["payment_type"] = "gopay";
            } else {
                $payload["payment_type"] = "bank_transfer";
                $payload["bank_transfer"] = [
                    "bank" => $paymentMethod
                ];
            }
            
            $ch = curl_init('https://api.sandbox.midtrans.com/v2/charge');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ]);
            
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            $responseData = json_decode($response, true);
            
            if (isset($responseData['status_code']) && $responseData['status_code'] == '201') {
                if ($paymentMethod === 'gopay') {
                    $paymentAddress = $responseData['actions'][0]['url'] ?? null;
                } else {
                    $paymentAddress = $responseData['va_numbers'][0]['va_number'] ?? null;
                }
                
                if ($paymentAddress) {
                    $stmt = $pdo->prepare("UPDATE orders SET payment_address = ? WHERE id = ?");
                    $stmt->execute([$paymentAddress, $orderId]);
                }
                $selectedPaymentMethod = $paymentMethod;
                
                // Insert into payments table
                $stmt = $pdo->prepare("INSERT INTO payments (order_id, method, amount, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$orderId, $paymentMethod, $totalPrice]);
                
                // Clear cart items
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
                $stmt->execute([$cartId]);
                
                $pdo->commit();
            } else {
                throw new Exception($responseData['status_message'] ?? 'Payment API Error');
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = $e->getMessage();
        }
    } elseif (!$errorMsg) {
        $errorMsg = "Metode pembayaran belum didukung atau pilih metode terlebih dahulu.";
    }
}

// Redirect back to cart if it's empty
if (empty($cartItems) && !$paymentAddress) {
    header("Location: cart.php");
    exit;
}

function rupiah($price): string {
    return 'Rp' . number_format((float) $price, 0, ',', '.');
}

function e($text): string {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Recloth</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" href="/Recloth/public/icons/app-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Symphony';
            src: url('/Recloth/public/fonts/symphony-pro-regular.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
        }
        :root {
            --primary: #6a7f52;
            --primary-hover: #526340;
            --bg: #f3eddf;
            --text: #2e3522;
            --muted: #6b735c;
            --line: #cbd5bb;
            --white: #bac6a9;
            --black: #36442c;
            --success: #1ea672;
            --danger: #d24e4e;
            --radius: 18px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: "Montserrat", sans-serif;
            line-height: 1.4;
        }

        .site-wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 20px 28px;
        }

        /* Navbar Styles */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--bg);
            border-bottom: 1px solid var(--line);
            padding: 16px 22px;
            border-radius: 0 0 14px 14px;
            margin-bottom: 18px;
        }

        .brand {
            font-family: "Symphony", sans-serif;
            font-size: 30px;
            text-decoration: none;
            color: var(--black);
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .menu {
            list-style: none;
            display: flex;
            gap: 20px;
            font-size: 14px;
        }

        .menu a {
            color: #2d2d2d;
            text-decoration: none;
            font-weight: 600;
        }

        .nav-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .cart-icon {
            width: 40px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #fff;
            background: var(--primary);
        }

        .cart-icon svg {
            width: 19px;
            height: 19px;
        }

        .auth-links {
            display: flex;
            gap: 8px;
        }

        .auth-links a {
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border-radius: 999px;
            padding: 10px 14px;
        }

        .auth-links .masuk {
            color: #1d1d1d;
            border: 1px solid #d9d9d9;
            background: #fff;
        }

        .auth-links .daftar {
            color: #fff;
            background: var(--primary);
            border: 1px solid var(--primary);
        }

        .breadcrumb {
            margin: 6px 0 14px;
            font-size: 12px;
            color: var(--muted);
        }
        
        .breadcrumb a {
            color: var(--muted);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            color: var(--black);
        }

        /* Payment Layout */
        .payment-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
            align-items: start;
        }

        .payment-box {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 8px 18px rgba(17, 17, 17, 0.02);
        }

        .payment-box h2, .payment-summary h3 {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }

        /* Order Items */
        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .order-item {
            display: flex;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--line);
            align-items: center;
        }

        .order-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .item-img {
            width: 70px;
            height: 90px;
            border-radius: 8px;
            background: #f1f1f1;
            overflow: hidden;
            flex-shrink: 0;
        }

        .item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #888;
            text-align: center;
            padding: 4px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 15px;
            font-weight: 700;
        }

        .item-meta {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .item-price-total {
            font-size: 16px;
            font-weight: 800;
            text-align: right;
        }

        /* Payment Summary */
        .payment-summary {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 24px;
            position: sticky;
            top: 20px;
            box-shadow: 0 8px 18px rgba(17, 17, 17, 0.02);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .summary-row span.label {
            color: var(--muted);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--line);
            padding-top: 16px;
            margin-top: 16px;
            font-size: 18px;
            font-weight: 800;
        }

        .pay-btn {
            display: block;
            width: 100%;
            background: #8b9d77;
            color: #fff;
            text-align: center;
            text-decoration: none;
            padding: 14px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            margin-top: 24px;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .pay-btn:hover {
            opacity: 0.9;
        }

        .payment-methods {
            margin-top: 24px;
        }

        .payment-group-title {
            font-size: 14px;
            font-weight: 700;
            margin: 16px 0 10px;
            color: var(--black);
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: border-color 0.2s;
            font-weight: 600;
            font-size: 14px;
        }

        .payment-method:hover {
            border-color: #aaa;
        }

        .payment-method input[type="radio"] {
            margin: 0;
            cursor: pointer;
        }

        .payment-icon {
            height: 20px;
            width: auto;
            object-fit: contain;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px;
            width: fit-content;
            min-width: min(420px, 90vw);
            max-width: 90vw;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .modal-header p {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .va-box {
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            position: relative;
        }

        .va-bank {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 8px;
        }

        .va-number-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .va-number {
            font-size: 24px;
            font-family: monospace;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--black);
            word-break: break-all;
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s, background 0.2s;
        }

        .copy-btn:hover {
            color: var(--black);
            background: #e0e0e0;
        }

        .copy-btn svg {
            width: 20px;
            height: 20px;
        }

        .copy-feedback {
            font-size: 12px;
            color: #27ae60;
            font-weight: 600;
            margin-top: 8px;
            opacity: 0;
            transition: opacity 0.2s;
            position: absolute;
            bottom: 6px;
            left: 0;
            right: 0;
        }

        .copy-feedback.show {
            opacity: 1;
        }

        .expiry-text {
            font-size: 13px;
            color: #e74c3c;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .modal-actions .btn-primary {
            display: block;
            width: 100%;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            padding: 14px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            transition: opacity 0.2s;
        }

        .modal-actions .btn-primary:hover {
            opacity: 0.9;
        }

        /* Footer Styles */
        footer {
            margin-top: 64px;
            display: grid;
            grid-template-columns: 1.4fr repeat(2, 1fr);
            gap: 20px;
            font-size: 13px;
            color: #4f4f4f;
        }

        footer h5 {
            margin-bottom: 12px;
            font-size: 12px;
            color: #0f0f0f;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        footer ul {
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .copyright {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            color: #777;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .payment-layout {
                grid-template-columns: 1fr;
            }
            .payment-summary {
                position: static;
            }
        }

        @media (max-width: 760px) {
            .site-wrap {
                padding: 0 12px 20px;
            }

            .navbar {
                flex-wrap: wrap;
                padding: 14px;
            }

            .menu {
                width: 100%;
                justify-content: space-between;
                font-size: 12px;
                gap: 8px;
            }

            .nav-actions {
                width: 100%;
                justify-content: flex-end;
            }

            footer {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="site-wrap">
        <nav class="navbar">
            <a class="brand" href="../../index.php">Recloth</a>
            <ul class="menu">
                <li><a href="../../index.php">Beranda</a></li>
                <li><a href="catalog.php">Katalog</a></li>
                <li><a href="category.php">Kategori</a></li>
            </ul>
            <div class="nav-actions">
                <a class="cart-icon" href="cart.php?tab=cart" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor" />
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor" />
                    </svg>
                </a>
                <a class="cart-icon" href="profile.php" aria-label="Profil">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a class="cart-icon" href="../config/logout.php" aria-label="Logout" style="color: #d24e4e; background: var(--bg); border-color: var(--line);">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </nav>

        <p class="breadcrumb">
            <a href="../../index.php">Beranda</a> / 
            <a href="catalog.php">Katalog</a> / 
            <a href="cart.php">Keranjang</a> / 
            Pembayaran
        </p>

        <?php if ($errorMsg): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
            <?= e($errorMsg) ?>
        </div>
        <?php endif; ?>

        <form class="payment-layout" method="POST">
            <section class="payment-box">
                <h2>Detail Pesanan</h2>
                
                <div class="order-items-list">
                    <?php foreach ($cartItems as $item): ?>
                        <article class="order-item">
                            <div class="item-img">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">
                                <?php else: ?>
                                    <div class="item-fallback">Tanpa Gambar</div>
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <h3 class="item-name"><?= e($item['name']) ?></h3>
                                <p class="item-meta"><?= e(ucwords((string) ($item['category_name'] ?? '-'))) ?> &bull; Qty: <?= e($item['quantity']) ?></p>
                            </div>
                            <div class="item-price-total">
                                <?= rupiah($item['quantity'] * $item['price']) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="payment-methods">
                    <h3>Alamat Pengiriman</h3>
                    <div class="address-selection" style="margin-bottom: 32px;">
                        <?php if (empty($addresses)): ?>
                            <div style="padding: 16px; background: #fff1f2; border: 1px solid #fda4af; border-radius: 12px; font-size: 14px;">
                                <p style="color: #be123c; font-weight: 600; margin-bottom: 8px;">Kamu belum memiliki alamat pengiriman.</p>
                                <a href="profile.php" style="color: #111; font-weight: 700; text-decoration: underline;">Tambah Alamat di Profil</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $addr): ?>
                                <label class="payment-method" style="padding: 16px; align-items: flex-start; cursor: pointer;">
                                    <input type="radio" name="address_id" value="<?= $addr['id'] ?>" data-zipcode="<?= e($addr['zip_code'] ?? '') ?>" <?= $addr['is_default'] ? 'checked' : '' ?> style="margin-top: 4px;" onchange="onAddressChange(this)">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                            <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?= e($addr['label']) ?></span>
                                            <?php if ($addr['is_default']): ?>
                                                <span style="background: var(--primary); color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 999px;">UTAMA</span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="font-size: 13px; font-weight: 500; color: var(--muted); line-height: 1.5;"><?= nl2br(e($addr['address'])) ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <h3>Opsi Pengiriman</h3>
                    <input type="hidden" name="shipping_cost" id="shippingCostInput" value="0">
                    <input type="hidden" name="shipping_service" id="shippingServiceInput" value="">
                    <div id="shippingOptions" style="margin-bottom: 32px;">
                        <div id="shippingPlaceholder" style="padding: 20px; text-align: center; color: var(--muted); font-size: 13px; background: #fafafa; border: 1px dashed var(--line); border-radius: 12px;">Pilih alamat pengiriman untuk melihat opsi kurir.</div>
                    </div>

                    <h3>Metode Pembayaran</h3>
                    
                    <h4 class="payment-group-title">Transfer Bank</h4>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="bni" checked>
                        <img src="../../public/icons/bni.png" alt="BNI" class="payment-icon">
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="bca">
                        <img src="../../public/icons/bca.png" alt="BCA" class="payment-icon">
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="bri">
                        <img src="../../public/icons/bri.png" alt="BRI" class="payment-icon">
                    </label>

                    <h4 class="payment-group-title">E-Wallet</h4>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="gopay">
                        <img src="../../public/icons/gopay.png" alt="GoPay" class="payment-icon">
                        GoPay
                    </label>
                </div>
            </section>

            <aside class="payment-summary">
                <h3>Ringkasan Pembayaran</h3>
                <div class="summary-row">
                    <span class="label">Total Harga (<?= e($totalItems) ?> barang)</span>
                    <span><?= rupiah($subtotalPrice) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Ongkir</span>
                    <span id="summaryOngkir">-</span>
                </div>
                <div class="summary-total">
                    <span>Total Tagihan</span>
                    <span id="summaryTotal"><?= rupiah($subtotalPrice) ?></span>
                </div>
                <button type="submit" class="pay-btn" id="payBtn" disabled style="opacity:0.5;cursor:not-allowed;">Bayar Sekarang</button>
            </aside>
        </form>

        <footer>
            <section>
                <a class="brand" href="../../index.php">Recloth</a>
                <p style="margin-top: 10px; max-width: 280px;">Recloth menyediakan pakaian thrift pilihan dengan kualitas terjamin dan harga terjangkau.</p>
            </section>
            <section>
                <h5>Navigasi Belanja</h5>
                <ul>
                    <li>Katalog Produk</li>
                    <li>Cari &amp; Filter Kategori</li>
                    <li>Keranjang Belanja</li>
                    <li>Checkout Pembayaran</li>
                </ul>
            </section>
            <section>
                <h5>Akun &amp; Bantuan</h5>
                <ul>
                    <li>Registrasi &amp; Login</li>
                    <li>Konfirmasi Pesanan</li>
                    <li>Layanan Pelanggan</li>
                    <li>Kebijakan Privasi</li>
                </ul>
            </section>
        </footer>

        <p class="copyright">Recloth © <?= date('Y') ?>. Semua Hak Dilindungi.</p>
    </div>

    <?php if ($paymentAddress): ?>
    <div class="modal-overlay" id="vaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pembayaran Berhasil Dibuat</h3>
                <?php if ($selectedPaymentMethod === 'gopay'): ?>
                    <p>Silakan scan QR Code di bawah ini menggunakan aplikasi Gojek atau aplikasi e-wallet lainnya.</p>
                <?php else: ?>
                    <p>Silakan selesaikan pembayaran Anda melalui transfer bank ke nomor Virtual Account di bawah ini.</p>
                <?php endif; ?>
            </div>
            
            <div class="va-box">
                <span class="va-bank"><?= strtoupper(e($selectedPaymentMethod ?? 'Bank')) ?></span>
                <?php if ($selectedPaymentMethod === 'gopay'): ?>
                    <div style="display: flex; flex-direction: column; align-items: center; margin: 16px 0;">
                        <img src="<?= e($paymentAddress) ?>" alt="QR Code GoPay" style="width: 200px; height: 200px; object-fit: contain; margin-bottom: 12px;">
                        <div class="va-number-container" style="gap: 8px; width: 100%;">
                            <span class="va-number" id="vaNumberText" style="font-size: 11px; font-weight: 500; font-family: inherit; color: var(--muted); letter-spacing: 0; max-width: 100%; word-break: break-all;"><?= e($paymentAddress) ?></span>
                            <button type="button" class="copy-btn" onclick="copyVA()" aria-label="Salin">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 8V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 8H6C4.89543 8 4 8.89543 4 10V18C4 19.1046 4.89543 20 6 20H14C15.1046 20 16 19.1046 16 18V10C16 8.89543 15.1046 8 14 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="va-number-container">
                        <span class="va-number" id="vaNumberText"><?= e($paymentAddress) ?></span>
                        <button type="button" class="copy-btn" onclick="copyVA()" aria-label="Salin">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 8V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 8H6C4.89543 8 4 8.89543 4 10V18C4 19.1046 4.89543 20 6 20H14C15.1046 20 16 19.1046 16 18V10C16 8.89543 15.1046 8 14 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                <div id="copyFeedback" class="copy-feedback">Tersalin!</div>
            </div>
            
            <p class="expiry-text">Selesaikan sebelum: <?= e(date('d M Y, H:i', strtotime('+1 day'))) ?></p>
            
            <div class="modal-actions">
                <a href="cart.php?tab=orders" class="btn-primary">Kembali ke Keranjang</a>
            </div>
        </div>
    </div>
    
    <script>
        function copyVA() {
            const vaText = document.getElementById('vaNumberText').innerText;
            navigator.clipboard.writeText(vaText).then(() => {
                const feedback = document.getElementById('copyFeedback');
                feedback.classList.add('show');
                setTimeout(() => {
                    feedback.classList.remove('show');
                }, 2000);
            });
        }
        
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    </script>
    <?php endif; ?>

<script>
const subtotalPrice = <?= (int) $subtotalPrice ?>;

function rupiah(val) {
    return 'Rp' + val.toLocaleString('id-ID');
}

function onAddressChange(radio) {
    const zip = radio.dataset.zipcode;
    if (!zip) {
        document.getElementById('shippingOptions').innerHTML =
            '<div id="shippingPlaceholder" style="padding:20px;text-align:center;color:var(--muted);font-size:13px;background:#fff1f2;border:1px solid #fda4af;border-radius:12px;">Alamat ini belum memiliki kode pos. Perbarui di halaman profil.</div>';
        resetShipping();
        return;
    }
    fetchShippingCosts(zip);
}

function resetShipping() {
    document.getElementById('shippingCostInput').value = '0';
    document.getElementById('shippingServiceInput').value = '';
    document.getElementById('summaryOngkir').textContent = '-';
    document.getElementById('summaryTotal').textContent = rupiah(subtotalPrice);
    const btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.style.opacity = '0.5';
    btn.style.cursor = 'not-allowed';
}

function fetchShippingCosts(zipCode) {
    const container = document.getElementById('shippingOptions');
    container.innerHTML = '<div style="padding:24px;text-align:center;"><div style="display:inline-block;width:24px;height:24px;border:3px solid #e0e0e0;border-top-color:#111;border-radius:50%;animation:spin .6s linear infinite;"></div><p style="margin-top:10px;font-size:13px;color:var(--muted);">Memuat opsi pengiriman...</p></div>';
    resetShipping();

    fetch('../config/shipping_cost.php?destination=' + encodeURIComponent(zipCode))
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                container.innerHTML = '<div style="padding:16px;text-align:center;color:#be123c;font-size:13px;background:#fff1f2;border:1px solid #fda4af;border-radius:12px;">' + (res.message || 'Tidak ada layanan pengiriman tersedia.') + '</div>';
                return;
            }

            // Filter out extremely expensive / cargo services (above Rp100.000)
            const services = res.data.filter(s => s.cost <= 100000);
            if (services.length === 0) {
                container.innerHTML = '<div style="padding:16px;text-align:center;color:#be123c;font-size:13px;background:#fff1f2;border:1px solid #fda4af;border-radius:12px;">Tidak ada layanan pengiriman reguler tersedia untuk tujuan ini.</div>';
                return;
            }

            let html = '';
            services.forEach((s, i) => {
                const etdText = s.etd ? (' · ' + s.etd) : '';
                html += `
                <label class="payment-method" style="padding:14px;cursor:pointer;${i === 0 ? 'border-color:#111;' : ''}">
                    <input type="radio" name="shipping_option" value="${i}" data-cost="${s.cost}" data-service="${s.name} - ${s.service}" onchange="onShippingSelect(this)" ${i === 0 ? 'checked' : ''} style="margin-top:2px;">
                    <div style="flex:1;display:flex;flex-direction:column;gap:2px;">
                        <span style="font-size:14px;font-weight:700;">${s.name}</span>
                        <span style="font-size:12px;color:var(--muted);">${s.service}${s.description && s.description !== s.service ? ' — ' + s.description : ''}${etdText}</span>
                    </div>
                    <span style="margin-left:auto;font-weight:700;white-space:nowrap;">${rupiah(s.cost)}</span>
                </label>`;
            });

            container.innerHTML = html;

            // Auto-select first option
            const firstRadio = container.querySelector('input[name="shipping_option"]');
            if (firstRadio) onShippingSelect(firstRadio);
        })
        .catch(() => {
            container.innerHTML = '<div style="padding:16px;text-align:center;color:#be123c;font-size:13px;background:#fff1f2;border:1px solid #fda4af;border-radius:12px;">Gagal memuat opsi pengiriman. Coba lagi.</div>';
        });
}

function onShippingSelect(radio) {
    const cost = parseInt(radio.dataset.cost, 10);
    const service = radio.dataset.service;

    document.getElementById('shippingCostInput').value = cost;
    document.getElementById('shippingServiceInput').value = service;
    document.getElementById('summaryOngkir').textContent = rupiah(cost);
    document.getElementById('summaryTotal').textContent = rupiah(subtotalPrice + cost);

    // Highlight selected
    document.querySelectorAll('#shippingOptions .payment-method').forEach(el => el.style.borderColor = '');
    radio.closest('.payment-method').style.borderColor = '#111';

    const btn = document.getElementById('payBtn');
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.style.cursor = 'pointer';
}

// On page load, auto-fetch for the default-checked address
document.addEventListener('DOMContentLoaded', function() {
    const checked = document.querySelector('input[name="address_id"]:checked');
    if (checked) {
        const zip = checked.dataset.zipcode;
        if (zip) fetchShippingCosts(zip);
    }
});
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</body>
</html>

