<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
require '../config/database.php';

// Menentukan user_id sementara untuk simulasi (nanti bias diganti dengan session user login sebenarnya)
$userId = $_SESSION['user_id'] ?? 1;

// Membuat tabel carts dan cart_items jika belum ada
$pdo->exec("
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
");

// Mendapatkan cart untuk user saat ini
$stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->execute([$userId]);
$cart = $stmt->fetch();

if (!$cart) {
    // Buat cart baru jika user belum memilikinya
    $stmt = $pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    $cartId = $pdo->lastInsertId();
} else {
    $cartId = $cart['id'];
}

$action = $_GET['action'] ?? '';
$productId = (int) ($_GET['id'] ?? 0);

// Menangani Aksi Keranjang
if ($action === 'add' && $productId > 0) {
    // Cek apakah produk sudah ada di keranjang
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    $item = $stmt->fetch();

    if ($item) {
        // Update jumlah jika sudah ada
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$item['id']]);
    } else {
        // Insert produk baru ke keranjang
        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$cartId, $productId]);
    }
    header("Location: cart.php");
    exit;
} elseif ($action === 'remove' && $productId > 0) {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    header("Location: cart.php");
    exit;
} elseif ($action === 'increase' && $productId > 0) {
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    header("Location: cart.php");
    exit;
} elseif ($action === 'decrease' && $productId > 0) {
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = GREATEST(quantity - 1, 1) WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cartId, $productId]);
    header("Location: cart.php");
    exit;
} elseif ($action === 'cancel_order' && isset($_GET['order_id'])) {
    $orderIdToCancel = (int) $_GET['order_id'];
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$orderIdToCancel, $userId]);
    if ($stmt->fetch()) {
        $stmtDel = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmtDel->execute([$orderIdToCancel]);
    }
    header("Location: cart.php");
    exit;
}

// Cek ketersediaan kolom
$columnsStmt = $pdo->query("SHOW COLUMNS FROM products");
$columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN));
$hasImage = in_array('image', $columns, true);

// Fetch Isi Keranjang dari DB
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

// Fetch Pesanan Saya (User Orders)
$stmt = $pdo->prepare("
    SELECT id, total_price, status, created_at, payment_method, payment_address, address
    FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$userOrdersRaw = $stmt->fetchAll();

$envPath = __DIR__ . '/../../.env';
$env = file_exists($envPath) ? parse_ini_file($envPath) : [];
$serverKey = $env['MIDTRANS_SERVER_KEY'] ?? '';
$authHeader = 'Authorization: Basic ' . base64_encode($serverKey . ':');

$userOrders = [];
$stmtItems = $pdo->prepare("
    SELECT oi.quantity, oi.price, p.name, " . ($hasImage ? "IF(p.image LIKE 'uploads/%', CONCAT('/Recloth/src/admin/', p.image), p.image)" : "''") . " AS image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmtUpdateStatus = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");

foreach ($userOrdersRaw as $order) {
    if ($order['status'] === 'pending' && !empty($serverKey)) {
        $ch = curl_init("https://api.sandbox.midtrans.com/v2/orders-{$order['id']}/status");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            $authHeader
        ]);
        $response = curl_exec($ch);
        
        if (!curl_errno($ch)) {
            $respData = json_decode($response, true);
            if (isset($respData['status_code']) && $respData['status_code'] == '200') {
                $ts = $respData['transaction_status'] ?? '';
                $newStatus = null;
                if (in_array($ts, ['cancel', 'expire', 'deny'])) {
                    $newStatus = 'cancel';
                } elseif (in_array($ts, ['settlement', 'capture'])) {
                    $newStatus = 'completed';
                }
                
                if ($newStatus) {
                    $stmtUpdateStatus->execute([$newStatus, $order['id']]);
                    $order['status'] = $newStatus;
                }
            }
        }
    }

    $stmtItems->execute([$order['id']]);
    $order['items'] = $stmtItems->fetchAll();

    // Fetch shipment info
    $stmtShip = $pdo->prepare("SELECT service_name, shipment_price FROM shipment_info WHERE order_id = ? LIMIT 1");
    $stmtShip->execute([$order['id']]);
    $order['shipment'] = $stmtShip->fetch() ?: null;

    $userOrders[] = $order;
}

$totalItems = 0;
$subtotalPrice = 0;
foreach ($cartItems as $item) {
    $totalItems += $item['quantity'];
    $subtotalPrice += $item['quantity'] * $item['price'];
}
$totalPrice = !empty($cartItems) ? $subtotalPrice : 0;

function rupiah($price): string
{
    return 'Rp' . number_format((float) $price, 0, ',', '.');
}

function e($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Recloth</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" href="/Recloth/public/icons/app-logo.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet">
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

        /* --- Navbar Styles dari catalog.php --- */
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

        /* --- Cart Layout --- */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
            align-items: start;
        }

        .cart-box {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 8px 18px rgba(17, 17, 17, 0.02);
        }

        .cart-box h2,
        .cart-summary h3 {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--line);
        }

        /* Cart Items */
        .cart-items-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .cart-item {
            display: flex;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--line);
        }

        .cart-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .item-img {
            width: 110px;
            height: 130px;
            border-radius: 10px;
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
            font-size: 11px;
            color: #888;
            text-align: center;
            padding: 10px;
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .item-name {
            font-size: 16px;
            font-weight: 700;
        }

        .item-category {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .item-price {
            font-size: 16px;
            font-weight: 800;
            margin-top: 6px;
        }

        .item-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 4px 10px;
        }

        .qty-btn {
            text-decoration: none;
            color: #111;
            font-size: 18px;
            line-height: 1;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f4f4;
            border-radius: 50%;
        }

        .qty-btn:hover {
            background: #e0e0e0;
        }

        .qty-value {
            font-size: 14px;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        .remove-btn {
            text-decoration: none;
            color: #d12;
            font-size: 13px;
            font-weight: 700;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }

        .empty-cart a {
            display: inline-block;
            margin-top: 14px;
            text-decoration: none;
            font-weight: 700;
            color: #fff;
            background: var(--primary);
            padding: 10px 24px;
            border-radius: 999px;
        }

        /* Order Summary */
        .cart-summary {
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

        .checkout-btn {
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
            transition: opacity 0.2s;
        }

        .checkout-btn:hover {
            opacity: 0.9;
        }

        /* --- Footer Styles dari catalog.php --- */
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
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
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

        /* Tabs Styles */
        .tabs-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: rgba(255, 255, 255, 0.5);
            padding: 6px;
            border-radius: 14px;
            border: 1px solid var(--line);
            width: fit-content;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 700;
            color: var(--muted);
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-btn:hover {
            color: var(--black);
            background: rgba(0, 0, 0, 0.03);
        }

        .tab-btn.active {
            color: #fff;
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cart-layout.orders-active {
            grid-template-columns: 1fr;
        }

        .cart-layout.orders-active .cart-summary {
            display: none;
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="cart-icon" href="cart.php?tab=cart" aria-label="Keranjang">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
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
                <?php else: ?>
                    <div class="auth-links">
                        <a class="masuk" href="login.php">Masuk</a>
                        <a class="daftar" href="register.php">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>

        <p class="breadcrumb">
            <a href="../../index.php">Beranda</a> /
            <a href="catalog.php">Katalog</a> /
            Keranjang
        </p>

        <div class="tabs-nav">
            <button class="tab-btn active" onclick="switchTab('cart')">
                Keranjang (<?= count($cartItems) ?>)
            </button>
            <button class="tab-btn" onclick="switchTab('orders')">
                Pesanan Saya (<?= count($userOrders) ?>)
            </button>
        </div>

        <div id="cartLayout" class="cart-layout">
            <div class="main-column">
                <!-- Tab: Orders -->
                <div id="ordersPane" class="tab-pane">
                    <?php if (empty($userOrders)): ?>
                        <div class="cart-box">
                            <div class="empty-cart">
                                <p>Kamu belum memiliki riwayat pesanan.</p>
                                <a href="catalog.php">Mulai Belanja</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="orders-list" style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($userOrders as $order): ?>
                                <div class="order-card" style="background: var(--white); border: 1px solid var(--line); border-radius: 12px; padding: 16px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--line); padding-bottom: 12px;">
                                        <span style="font-size: 14px; font-weight: 700;">Order #<?= $order['id'] ?></span>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <?php
                                            $statusLabel = '';
                                            $statusBg = '#f0f0f0';
                                            $statusColor = '#111';

                                            if ($order['status'] === 'pending') {
                                                $statusLabel = 'Menunggu Pembayaran';
                                                $statusBg = '#fff3e0';
                                                $statusColor = '#e65100';
                                            } elseif ($order['status'] === 'cancel') {
                                                $statusLabel = 'Dibatalkan';
                                                $statusBg = '#ffebee';
                                                $statusColor = '#d32f2f';
                                            } elseif ($order['status'] === 'completed') {
                                                $statusLabel = 'Pembayaran Selesai';
                                                $statusBg = '#e8f5e9';
                                                $statusColor = '#2e7d32';
                                            } else {
                                                $statusLabel = ucfirst($order['status']);
                                            }
                                            ?>
                                            <span style="font-size: 11px; padding: 4px 10px; background: <?= $statusBg ?>; color: <?= $statusColor ?>; border-radius: 999px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                <?= e($statusLabel) ?>
                                            </span>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="cart.php?action=cancel_order&order_id=<?= $order['id'] ?>"
                                                    onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');"
                                                    style="text-decoration: none; font-size: 12px; padding: 4px 10px; background: #ffebee; color: #d32f2f; border-radius: 999px; font-weight: 600;">Batalkan</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="order-items-preview" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                                        <?php foreach ($order['items'] as $oi): ?>
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <div style="width: 50px; height: 60px; border-radius: 6px; background: #f1f1f1; overflow: hidden; flex-shrink: 0;">
                                                    <?php if (!empty($oi['image'])): ?>
                                                        <img src="<?= e($oi['image']) ?>" alt="<?= e($oi['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #888;">Tanpa Gambar</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="flex: 1;">
                                                    <h4 style="font-size: 13px; font-weight: 600; margin-bottom: 4px;"><?= e($oi['name']) ?></h4>
                                                    <p style="font-size: 12px; color: var(--muted);"><?= e($oi['quantity']) ?> x <?= rupiah($oi['price']) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; font-size: 14px; color: var(--muted); border-top: 1px dashed var(--line); padding-top: 12px;">
                                        <span><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
                                        <span style="font-weight: 700; color: var(--black);">Total: <?= rupiah($order['total_price']) ?></span>
                                    </div>
                                    
                                    <?php if (!empty($order['payment_method'])): ?>
                                        <div style="margin-top: 12px; padding: 12px; background: #fafafa; border: 1px solid var(--line); border-radius: 8px; font-size: 13px;">
                                            <?php if (!empty($order['address'])): ?>
                                                <div style="margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid var(--line);">
                                                    <span style="color: var(--muted); display: block; margin-bottom: 4px;">Alamat Pengiriman</span>
                                                    <p style="font-weight: 500; color: var(--black); line-height: 1.4;"><?= nl2br(e($order['address'])) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid var(--line);">
                                                <span style="color: var(--muted);">Opsi Pengiriman</span>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <?php if (!empty($order['shipment'])): ?>
                                                        <span style="font-size: 12px; font-weight: 600; color: var(--black);"><?= e($order['shipment']['service_name']) ?></span>
                                                        <span style="font-weight: 700; color: var(--black);"><?= rupiah($order['shipment']['shipment_price']) ?></span>
                                                    <?php else: ?>
                                                        <span style="font-size: 12px; color: var(--muted);">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <span style="color: var(--muted);">Metode Pembayaran</span>
                                                <span style="font-weight: 600; text-transform: uppercase;"><?= e($order['payment_method']) ?></span>
                                            </div>
                                            <?php if (!empty($order['payment_address'])): ?>
                                                <?php if ($order['payment_method'] === 'gopay' && $order['status'] === 'pending'): ?>
                                                    <div style="display: flex; justify-content: center; margin: 12px 0;">
                                                        <img src="<?= e($order['payment_address']) ?>" alt="QR Code GoPay" style="width: 150px; height: 150px; object-fit: contain;">
                                                    </div>
                                                <?php endif; ?>
                                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                                                    <span style="color: var(--muted);"><?= $order['payment_method'] === 'gopay' ? 'Link QR Code' : 'Virtual Account' ?></span>
                                                    <div style="display: flex; align-items: center; gap: 8px; max-width: 100%;">
                                                        <span style="font-weight: 700; color: var(--black); letter-spacing: 0.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;" title="<?= e($order['payment_address']) ?>">
                                                            <?= e($order['payment_address']) ?>
                                                        </span>
                                                        <button type="button"
                                                            onclick="navigator.clipboard.writeText('<?= e($order['payment_address']) ?>'); const btn = this; const ot = btn.innerText; btn.innerText = 'Tersalin!'; setTimeout(() => btn.innerText = ot, 2000);"
                                                            style="background: var(--primary); color: var(--white); border: none; border-radius: 4px; padding: 4px 8px; font-size: 11px; font-family: inherit; font-weight: 600; cursor: pointer; outline: none; flex-shrink: 0;">Salin</button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($order['status'] === 'completed'): ?>
                                        <div class="shipment-progress" style="margin-top: 16px; padding: 16px; background: #f8faf8; border: 1px solid #d4edda; border-radius: 10px;">
                                            <p style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #2e7d32; margin-bottom: 14px;">Progres Pengiriman</p>
                                            <div style="display: flex; align-items: center; gap: 0; position: relative;">
                                                <?php
                                                $steps = [
                                                    ['label' => 'Pembayaran Selesai', 'done' => true],
                                                    ['label' => 'Sedang Dikemas', 'done' => false, 'active' => true],
                                                    ['label' => 'Dikirim', 'done' => false],
                                                    ['label' => 'Selesai', 'done' => false],
                                                ];
                                                foreach ($steps as $i => $step):
                                                    $isDone = $step['done'];
                                                    $isActive = $step['active'] ?? false;
                                                    $dotBg = $isDone ? '#2e7d32' : ($isActive ? '#ff9800' : '#d0d0d0');
                                                    $dotBorder = $isDone ? '#2e7d32' : ($isActive ? '#ff9800' : '#d0d0d0');
                                                    $labelColor = $isDone ? '#2e7d32' : ($isActive ? '#e65100' : '#aaa');
                                                    $labelWeight = ($isDone || $isActive) ? '700' : '500';
                                                ?>
                                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 1;">
                                                        <div style="width: <?= $isActive ? '14px' : '10px' ?>; height: <?= $isActive ? '14px' : '10px' ?>; border-radius: 50%; background: <?= $dotBg ?>; border: 2px solid <?= $dotBorder ?>; <?= $isActive ? 'box-shadow: 0 0 0 4px rgba(255,152,0,0.2);' : '' ?>"></div>
                                                        <span style="font-size: 10px; margin-top: 8px; color: <?= $labelColor ?>; font-weight: <?= $labelWeight ?>; text-align: center; line-height: 1.3;"><?= $step['label'] ?></span>
                                                    </div>
                                                    <?php if ($i < count($steps) - 1): ?>
                                                        <div style="flex: 1; height: 2px; background: <?= $isDone ? '#2e7d32' : '#e0e0e0' ?>; margin-top: -18px; position: relative; z-index: 0;"></div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Cart -->
                <div id="cartPane" class="tab-pane active">
                    <section class="cart-box">
                        <h2>Keranjang Belanja</h2>

                    <?php if (empty($cartItems)): ?>
                        <div class="empty-cart">
                            <p>Keranjang belanja kamu masih kosong.</p>
                            <a href="catalog.php">Mulai Belanja</a>
                        </div>
                    <?php else: ?>
                        <div class="cart-items-list">
                            <?php foreach ($cartItems as $item): ?>
                                <article class="cart-item">
                                    <div class="item-img">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="item-fallback">Tanpa Gambar</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <h3 class="item-name"><?= e($item['name']) ?></h3>
                                        <p class="item-category"><?= e(ucwords((string) ($item['category_name'] ?? '-'))) ?></p>
                                        <p class="item-price"><?= rupiah($item['price']) ?></p>

                                        <div class="item-actions">
                                            <div class="qty-controls">
                                                <a href="cart.php?action=decrease&id=<?= $item['product_id'] ?>" class="qty-btn"
                                                    aria-label="Kurangi">&minus;</a>
                                                <span class="qty-value"><?= e($item['quantity']) ?></span>
                                                <a href="cart.php?action=increase&id=<?= $item['product_id'] ?>" class="qty-btn"
                                                    aria-label="Tambah">&plus;</a>
                                            </div>
                                            <a href="cart.php?action=remove&id=<?= $item['product_id'] ?>"
                                                class="remove-btn">Hapus</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
            </section>
        </div>
    </div> <!-- main-column closed -->

    <aside class="cart-summary">
        <h3>Ringkasan Pesanan</h3>
        <div class="summary-row">
            <span class="label">Total Item</span>
            <span><?= e($totalItems) ?> barang</span>
        </div>
        <div class="summary-row">
            <span class="label">Total Harga</span>
            <span><?= rupiah($subtotalPrice) ?></span>
        </div>
        <?php if (!empty($cartItems)): ?>
        <div class="summary-row">
            <span class="label">Ongkir</span>
            <span style="font-size:12px;color:var(--muted);font-style:italic;">Dihitung saat checkout</span>
        </div>
        <?php endif; ?>
        <div class="summary-total">
            <span>Total Belanja</span>
            <span><?= rupiah($totalPrice) ?></span>
        </div>
        <?php if (!empty($cartItems)): ?>
            <a href="payment.php" class="checkout-btn">Lanjut ke Pembayaran</a>
        <?php else: ?>
            <a href="catalog.php" class="checkout-btn"
                style="background:#e0e0e0;color:#888;pointer-events:none;">Lanjut ke Pembayaran</a>
        <?php endif; ?>
    </aside>
</div> <!-- cartLayout closed -->

    <footer>
        <section>
            <a class="brand" href="../../index.php">Recloth</a>
            <p style="margin-top: 10px; max-width: 280px;">Recloth menyediakan pakaian thrift pilihan dengan
                kualitas terjamin dan harga terjangkau.</p>
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

<script>
    function switchTab(tab) {
        const cartPane = document.getElementById('cartPane');
        const ordersPane = document.getElementById('ordersPane');
        const cartLayout = document.getElementById('cartLayout');
        const btns = document.querySelectorAll('.tab-btn');

        btns.forEach(btn => btn.classList.remove('active'));

        if (tab === 'cart') {
            cartPane.classList.add('active');
            ordersPane.classList.remove('active');
            cartLayout.classList.remove('orders-active');
            btns[0].classList.add('active');
        } else {
            cartPane.classList.remove('active');
            ordersPane.classList.add('active');
            cartLayout.classList.add('orders-active');
            btns[1].classList.add('active');
        }

        // Update URL parameter without reloading
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    // Auto switch to orders if redirected from payment or cancel
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'orders' || (urlParams.has('action') && urlParams.get('action') === 'cancel_order')) {
        switchTab('orders');
    } else if (urlParams.get('tab') === 'cart') {
        switchTab('cart');
    }
</script>

    <p class="copyright">Recloth © <?= date('Y') ?>. Semua Hak Dilindungi.</p>
</div>

</body>

</html>