<?php
session_start();
require '../config/database.php';

// Menentukan user_id sementara untuk simulasi
$userId = $_SESSION['user_id'] ?? 1;

// Mendapatkan cart untuk user saat ini
$stmt = $pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->execute([$userId]);
$cart = $stmt->fetch();

$cartItems = [];
$totalItems = 0;
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
            " . ($hasImage ? 'p.image' : "''") . " AS image, 
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
        $totalPrice += $item['quantity'] * $item['price'];
    }
}

// Redirect back to cart if it's empty
if (empty($cartItems)) {
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
    <link rel="icon" type="image/png" href="/public/icons/app-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Symphony';
            src: url('/public/fonts/symphony-pro-regular.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --bg: #f4f4f4;
            --text: #121212;
            --muted: #6f6f6f;
            --line: #e6e6e6;
            --white: #ffffff;
            --black: #111111;
            --radius: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(180deg, #efefef 0%, #fafafa 65%, #f1f1f1 100%);
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
            background: var(--white);
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
            color: #111;
            background: #fff;
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
            background: #111;
            border: 1px solid #111;
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
            background: #fff;
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
            background: #fff;
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
            background: var(--black);
            color: var(--white);
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
        }

        .payment-method:hover {
            border-color: #aaa;
        }

        .payment-method input[type="radio"] {
            margin: 0;
            cursor: pointer;
        }

        .payment-method label {
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            flex: 1;
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
                <li><a href="catalog.php">Kategori</a></li>
            </ul>
            <div class="nav-actions">
                <a class="cart-icon" href="cart.php" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor" />
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor" />
                    </svg>
                </a>
                <div class="auth-links">
                    <a class="masuk" href="login.php">Masuk</a>
                    <a class="daftar" href="register.php">Daftar</a>
                </div>
            </div>
        </nav>

        <p class="breadcrumb">
            <a href="../../index.php">Beranda</a> / 
            <a href="catalog.php">Katalog</a> / 
            <a href="cart.php">Keranjang</a> / 
            Pembayaran
        </p>

        <div class="payment-layout">
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
                    <h3>Metode Pembayaran</h3>
                    <div class="payment-method">
                        <input type="radio" id="method-transfer" name="payment_method" value="transfer" checked>
                        <label for="method-transfer">Transfer Bank (BCA, Mandiri, BNI)</label>
                    </div>
                    <div class="payment-method">
                        <input type="radio" id="method-ewallet" name="payment_method" value="ewallet">
                        <label for="method-ewallet">E-Wallet (GoPay, OVO, Dana)</label>
                    </div>
                </div>
            </section>

            <aside class="payment-summary">
                <h3>Ringkasan Pembayaran</h3>
                <div class="summary-row">
                    <span class="label">Total Harga (<?= e($totalItems) ?> barang)</span>
                    <span><?= rupiah($totalPrice) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Biaya Pengiriman</span>
                    <span>Gratis</span>
                </div>
                <div class="summary-total">
                    <span>Total Tagihan</span>
                    <span><?= rupiah($totalPrice) ?></span>
                </div>
                <form action="#" method="POST">
                    <button type="submit" class="pay-btn" onclick="alert('Pembayaran berhasil disimulasikan!'); return false;">Bayar Sekarang</button>
                </form>
            </aside>
        </div>

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
</body>
</html>
