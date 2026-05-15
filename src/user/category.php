<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
require '../config/database.php';
require '../config/product_repository.php';

$search = trim($_GET['search'] ?? '');
$gender = strtolower(trim($_GET['gender'] ?? ''));
$category = strtolower(trim($_GET['category'] ?? ($_GET['kategori'] ?? '')));
$minRaw = trim($_GET['min'] ?? '');
$maxRaw = trim($_GET['max'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');
$minPrice = is_numeric($minRaw) ? (float) $minRaw : null;
$maxPrice = is_numeric($maxRaw) ? (float) $maxRaw : null;

$kategoriMap = [
    'Atasan' => ['kaos', 'kemeja', 'hoodie', 'sweater'],
    'Bawahan' => ['celana', 'rok'],
    'Outer' => ['jaket', 'cardigan', 'blazer'],
    'Lainnya' => ['topi', 'sepatu', 'ikat pinggang', 'aksesoris'],
];

$allowedKategori = [];
foreach ($kategoriMap as $groupItems) {
    foreach ($groupItems as $item) {
        $allowedKategori[] = $item;
    }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Koneksi database tidak valid. Pastikan ../config/database.php menyediakan variabel $pdo (PDO).');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($category !== '' && !in_array($category, $allowedKategori, true)) {
    $category = '';
}

$products = recloth_fetch_products($pdo, [
    'search' => $search,
    'gender' => $gender,
    'category' => $category,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort' => $sort,
]);

$produkText = $category !== '' ? ucfirst($category) : 'Semua Produk';

function rupiah($price): string
{
    return 'Rp' . number_format((float) $price, 0, ',', '.');
}

function priceBeforeDiscount($price, int $discountPercent = 0): float
{
    $discountPercent = max(0, min(90, $discountPercent));
    if ($discountPercent <= 0) {
        return $price;
    }
    return (float) $price / (1 - ($discountPercent / 100));
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
    <title>Kategori Produk - Recloth</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
		@font-face {
			font-family: 'Symphony';
			src: url('/Recloth/public/fonts/symphony-pro-regular.otf') format('opentype');
			font-weight: normal;
			font-style: normal;
		}
        :root {
            --primary: #2d5a40;
            --primary-hover: #1b4332;
            --primary-glow: rgba(45, 90, 64, 0.5);
            --accent: #D4AF37;
            --accent-glow: rgba(212, 175, 55, 0.4);
            --bg: #070707;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text: #FFFFFF;
            --muted: #A1A1AA;
            --line: rgba(255, 255, 255, 0.1);
            --white: rgba(20, 20, 20, 0.4);
            --black: #FFFFFF;
            --success: #10b981;
            --danger: #ef4444;
            --radius: 18px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg); color: var(--text);
            font-family: "Montserrat", sans-serif; line-height: 1.4;
            overflow-x: hidden; -webkit-font-smoothing: antialiased;
        }
        body::before {
            content: ''; position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 60%);
            border-radius: 50%; z-index: -1; pointer-events: none; filter: blur(80px);
            animation: floatGlow1 20s ease-in-out infinite alternate;
        }
        body::after {
            content: ''; position: fixed; bottom: -10%; right: -10%; width: 60vw; height: 60vw;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 60%);
            border-radius: 50%; z-index: -1; pointer-events: none; filter: blur(100px);
            animation: floatGlow2 25s ease-in-out infinite alternate-reverse;
        }
        @keyframes floatGlow1 { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(100px, 100px) scale(1.2); } }
        @keyframes floatGlow2 { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(-100px, -100px) scale(1.3); } }
        @keyframes gradientText { 0% { background-position: 0% 50%; } 100% { background-position: 100% 50%; } }

        .site-wrap { max-width: 1240px; margin: 0 auto; padding: 0 20px 28px; position: relative; z-index: 1; }

        .navbar {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            background: rgba(10, 10, 10, 0.6); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border); border-top: none;
            padding: 16px 22px; border-radius: 0 0 24px 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5); position: sticky; top: 0; z-index: 100; margin-bottom: 18px;
        }
        .brand {
            font-family: "Symphony", sans-serif; font-size: 30px; text-decoration: none;
            color: var(--accent); letter-spacing: 1px; margin-top: 5px;
            text-shadow: 0 2px 10px rgba(212,175,55,0.2);
        }
        .menu { list-style: none; display: flex; gap: 20px; font-size: 14px; }
        .menu a {
            color: #FFFFFF; text-decoration: none; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px; font-size: 13px;
            transition: color 0.3s; position: relative; padding: 5px 0;
        }
        .menu a::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px;
            background: linear-gradient(90deg, var(--accent), #fef08a); transition: width 0.3s ease-in-out;
        }
        .menu a:hover { color: var(--accent); }
        .menu a:hover::after { width: 100%; }

        .search { flex: 1; max-width: 400px; }
        .search input {
            width: 100%; border: 1px solid rgba(255,255,255,0.2); border-radius: 999px;
            padding: 10px 16px; background: rgba(255,255,255,0.1); color: #FFFFFF; font-size: 13px;
            font-family: "Montserrat", sans-serif; transition: all 0.3s ease;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);
        }
        .search input::placeholder { color: rgba(255,255,255,0.7); }
        .search input:focus { background: rgba(255,255,255,0.15); box-shadow: 0 0 0 4px rgba(212,175,55,0.2); outline: none; border-color: var(--accent); }

        .nav-actions { display: flex; gap: 8px; align-items: center; }
        .cart-icon {
            width: 40px; height: 40px; border: 1px solid var(--line); border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; color: var(--text); background: rgba(255,255,255,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .cart-icon:hover { transform: translateY(-2px); border-color: var(--accent); color: var(--accent); }
        .cart-icon svg { width: 19px; height: 19px; }

        .auth-links { display: flex; gap: 8px; }
        .auth-links a { text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 999px; padding: 10px 14px; transition: all 0.3s; }
        .auth-links .masuk { color: var(--accent); border: 1px solid var(--accent); background: transparent; }
        .auth-links .masuk:hover { background: var(--accent); color: #111; transform: translateY(-2px); }
        .auth-links .daftar { color: #111; background: linear-gradient(135deg, var(--accent) 0%, #fef08a 50%, var(--accent) 100%); background-size: 200% auto; border: none; }
        .auth-links .daftar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212,175,55,0.6); }

        .breadcrumb { margin: 6px 0 14px; font-size: 12px; color: var(--muted); }

        .catalog-layout { display: grid; grid-template-columns: 280px 1fr; gap: 18px; }

        .sidebar {
            background: rgba(20, 20, 20, 0.4); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(212,175,55,0.2);
            border-radius: var(--radius); padding: 16px; height: max-content; position: sticky; top: 80px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        .sidebar h3 { font-size: 18px; margin-bottom: 4px; color: #FFFFFF; }
        .sidebar p { color: var(--muted); font-size: 12px; margin-bottom: 6px; }
        .sidebar form { display: grid; gap: 8px; }

        .filter-group { border: 0; border-top: 1px solid var(--line); padding-top: 12px; margin-top: 4px; min-width: 0; }
        .filter-group:first-of-type { margin-top: 0; }
        .filter-group legend { font-size: 14px; font-weight: 700; margin-bottom: 9px; padding: 0; display: block; color: #FFFFFF; }

        .filter-list { list-style: none; display: grid; gap: 6px; font-size: 13px; color: #E2E8F0; }
        .filter-list label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .filter-list input[type="radio"] { accent-color: var(--accent); }

        .group-label { margin-top: 8px; margin-bottom: 4px; color: var(--accent); font-size: 12px; font-weight: 700; }

        .price-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

        .sort-select {
            border: 1px solid rgba(255,255,255,0.2); border-radius: 999px; padding: 8px 12px;
            font-size: 13px; background: rgba(255,255,255,0.1); color: #FFFFFF; width: 100%;
        }
        .sort-select option { background: #111; color: #fff; }

        .sidebar input[type="text"],
        .sidebar input[type="number"] {
            width: 100%; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px;
            padding: 9px 10px; font-size: 13px; background: rgba(255,255,255,0.1); color: #FFFFFF;
        }

        .apply-btn, .reset-btn {
            display: inline-block; margin-top: 2px; width: 100%; border-radius: 999px;
            border: 1px solid var(--accent); padding: 10px 12px; font-size: 13px;
            font-weight: 700; text-align: center; text-decoration: none; cursor: pointer; transition: all 0.3s;
        }
        .apply-btn { background: var(--accent); color: #111; }
        .apply-btn:hover { box-shadow: 0 4px 15px rgba(212,175,55,0.4); transform: translateY(-1px); }
        .reset-btn { background: transparent; color: var(--muted); border-color: rgba(255,255,255,0.2); }
        .reset-btn:hover { color: #fff; border-color: rgba(255,255,255,0.4); }

        .content { min-width: 0; }
        .content-head {
            display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px;
            background: rgba(20,20,20,0.4); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(212,175,55,0.2);
            border-radius: 16px; padding: 16px 18px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        .content-head h1 { font-size: 30px; line-height: 1; color: #FFFFFF; }
        .info-sort { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 13px; }

        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }

        .card {
            background: rgba(20,20,20,0.4); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(212,175,55,0.2);
            border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); position: relative;
        }
        .card::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.1), transparent);
            transform: skewX(-20deg); transition: 0.7s; z-index: 1; pointer-events: none;
        }
        .card:hover::after { left: 150%; }
        .card:hover {
            transform: translateY(-10px) scale(1.02); background: rgba(30,30,30,0.6);
            border-top: 1px solid var(--accent);
            box-shadow: 0 30px 60px rgba(0,0,0,0.8), 0 0 30px rgba(212,175,55,0.15);
        }
        .card-link { display: block; text-decoration: none; color: inherit; padding: 10px; position: relative; z-index: 2; }
        .card-link:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 12px; }

        .img-wrap { width: 100%; height: 270px; background: #222; border-radius: 12px; overflow: hidden; }
        .img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
        .card:hover .img-wrap img { transform: scale(1.08); }
        .img-fallback { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #A1A1AA; font-size: 13px; padding: 8px; text-align: center; }

        .card-body { padding: 14px 2px 10px; }
        .product-name { font-size: 18px; font-weight: 700; min-height: 48px; color: #FFFFFF; transition: all 0.3s; }
        .card:hover .product-name {
            background: linear-gradient(90deg, var(--accent), #fef08a, var(--accent));
            background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: gradientText 2s linear infinite;
        }
        .meta { margin-top: 5px; color: var(--muted); font-size: 12px; }

        .price-row { margin-top: 10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .price { font-size: 42px; font-weight: 800; line-height: 1; color: var(--accent); }
        .old-price { font-size: 17px; color: rgba(255,255,255,0.4); text-decoration: line-through; }
        .discount { padding: 8px 14px; border-radius: 999px; background: linear-gradient(135deg, var(--accent), #fef08a); color: #111; font-size: 18px; font-weight: 800; box-shadow: 0 4px 10px rgba(212,175,55,0.3); }

        .empty { border: 1px dashed rgba(255,255,255,0.2); border-radius: 14px; padding: 28px 10px; text-align: center; color: var(--muted); background: rgba(20,20,20,0.4); }

        footer {
            margin-top: 64px; display: grid; grid-template-columns: 1.4fr repeat(2, 1fr); gap: 20px;
            font-size: 13px; color: #A1A1AA; padding: 40px;
            background: rgba(20,20,20,0.4); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(212,175,55,0.3);
            border-radius: 30px; box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        }
        footer h5 { margin-bottom: 12px; font-size: 12px; color: var(--accent); letter-spacing: 2px; text-transform: uppercase; font-weight: 800; }
        footer ul { list-style: none; display: grid; gap: 8px; }
        footer ul li { color: #E2E8F0; font-weight: 500; transition: color 0.3s; cursor: pointer; }
        footer ul li:hover { color: var(--accent); }
        .copyright { margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1); color: #777; font-size: 12px; }

        @media (max-width: 1080px) {
            .catalog-layout { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            footer { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 760px) {
            .site-wrap { padding: 0 12px 20px; }
            .navbar { flex-wrap: wrap; padding: 14px; }
            .menu { width: 100%; justify-content: space-between; font-size: 12px; gap: 8px; }
            .nav-actions { width: 100%; justify-content: flex-end; }
            .auth-links a { padding: 8px 12px; font-size: 12px; }
            .search { order: 3; max-width: 100%; width: 100%; }
            .content-head { flex-direction: column; align-items: flex-start; }
            .grid { grid-template-columns: 1fr; }
            footer { grid-template-columns: 1fr; gap: 14px; }
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
        <div class="search">
            <input type="text" value="<?= e($search) ?>" form="filter-form" name="search" placeholder="Cari produk thrift favoritmu...">
        </div>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="cart-icon" href="cart.php?tab=cart" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor"/>
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor"/>
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

    <p class="breadcrumb">Beranda &gt; Kategori</p>

    <div class="catalog-layout">
        <aside class="sidebar">
            <h3>Filter Produk</h3>
            <p>Pilih filter untuk mempersempit hasil.</p>
            <form id="filter-form" method="GET" action="category.php">
                <fieldset class="filter-group">
                    <legend>Gender</legend>
                    <ul class="filter-list">
                        <li><label><input type="radio" name="gender" value="" <?= $gender === '' ? 'checked' : '' ?>> Semua</label></li>
                        <li><label><input type="radio" name="gender" value="pria" <?= $gender === 'pria' ? 'checked' : '' ?>> Pria</label></li>
                        <li><label><input type="radio" name="gender" value="wanita" <?= $gender === 'wanita' ? 'checked' : '' ?>> Wanita</label></li>
                    </ul>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Harga</legend>
                    <div class="price-row">
                        <input type="number" name="min" min="0" step="1000" placeholder="Min" value="<?= e($minRaw) ?>">
                        <input type="number" name="max" min="0" step="1000" placeholder="Max" value="<?= e($maxRaw) ?>">
                    </div>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Kategori Produk</legend>
                    <ul class="filter-list">
                        <li><label><input type="radio" name="category" value="" <?= $category === '' ? 'checked' : '' ?>> Semua Kategori</label></li>
                    </ul>
                    <?php foreach ($kategoriMap as $group => $items): ?>
                        <p class="group-label"><?= e($group) ?></p>
                        <ul class="filter-list">
                            <?php foreach ($items as $item): ?>
                                <li>
                                    <label>
                                        <input type="radio" name="category" value="<?= e($item) ?>" <?= $category === $item ? 'checked' : '' ?>>
                                        <?= e(ucwords($item)) ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Urutkan</legend>
                    <select class="sort-select" name="sort">
                        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="harga_terendah" <?= $sort === 'harga_terendah' ? 'selected' : '' ?>>Harga Terendah</option>
                        <option value="harga_tertinggi" <?= $sort === 'harga_tertinggi' ? 'selected' : '' ?>>Harga Tertinggi</option>
                    </select>
                </fieldset>

                <button class="apply-btn" type="submit">Terapkan Filter</button>
                <a class="reset-btn" href="category.php">Atur Ulang Filter</a>
            </form>
        </aside>

        <section class="content">
            <div class="content-head">
                <h1><?= e($produkText) ?></h1>
                <div class="info-sort">
                    <span>Menampilkan <?= count($products) ?> produk</span>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty">Produk tidak ditemukan. Coba ubah pencarian atau filter.</div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($products as $product): ?>
                        <?php $discount = (int) ($product['discount_percent'] ?? 0); ?>
                        <?php $oldPrice = priceBeforeDiscount((float) $product['price'], $discount); ?>
                        <article class="card">
                            <a class="card-link" href="detail_product.php?id=<?= (int) $product['id'] ?>" aria-label="Lihat detail <?= e($product['name']) ?>">
                                <div class="img-wrap">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="img-fallback">Gambar belum tersedia</div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h2 class="product-name"><?= e($product['name']) ?></h2>
                                    <p class="meta">
                                        <?= e(ucwords((string) ($product['category_name'] ?? '-'))) ?>
                                        <?php if (!empty($product['gender'])): ?>
                                            | <?= e(ucfirst((string) $product['gender'])) ?>
                                        <?php endif; ?>
                                    </p>
                                    <div class="price-row">
                                        <p class="price"><?= rupiah($product['price']) ?></p>
                                        <?php if ($discount > 0): ?>
                                            <p class="old-price"><?= rupiah($oldPrice) ?></p>
                                            <span class="discount">-<?= (int) $discount ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
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
