<?php
session_start();
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

        .search {
            flex: 1;
            max-width: 400px;
        }

        .search input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 10px 16px;
            background: #f8f8f8;
            font-size: 13px;
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

        .catalog-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 18px;
        }

        .sidebar {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 16px;
            height: max-content;
            position: sticky;
            top: 14px;
        }

        .sidebar h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .sidebar p {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 6px;
        }

        .sidebar form {
            display: grid;
            gap: 8px;
        }

        .filter-group {
            border: 0;
            border-top: 1px solid var(--line);
            padding-top: 12px;
            margin-top: 4px;
            min-width: 0;
        }

        .filter-group:first-of-type {
            margin-top: 0;
        }

        .filter-group legend {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 9px;
            padding: 0;
            display: block;
        }

        .filter-list {
            list-style: none;
            display: grid;
            gap: 6px;
            font-size: 13px;
            color: #333;
        }

        .filter-list label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .filter-list input[type="radio"] {
            accent-color: #111;
        }

        .group-label {
            margin-top: 8px;
            margin-bottom: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .price-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .sort-select {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            background: #fff;
            width: 100%;
        }

        .sidebar input[type="text"],
        .sidebar input[type="number"] {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
        }

        .apply-btn,
        .reset-btn {
            display: inline-block;
            margin-top: 2px;
            width: 100%;
            border-radius: 999px;
            border: 1px solid var(--black);
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .apply-btn {
            background: #111;
            color: #fff;
        }

        .reset-btn {
            background: #fff;
            color: #111;
        }

        .content {
            min-width: 0;
        }

        .content-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .content-head h1 {
            font-size: 30px;
            line-height: 1;
        }

        .info-sort {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 13px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 18px rgba(17, 17, 17, 0.04);
        }

        .card-link {
            display: block;
            text-decoration: none;
            color: inherit;
            padding: 10px;
        }

        .card-link:focus-visible {
            outline: 2px solid #111;
            outline-offset: 2px;
            border-radius: 12px;
        }

        .img-wrap {
            width: 100%;
            height: 270px;
            background: #ececec;
            border-radius: 12px;
            overflow: hidden;
        }

        .img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .img-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #767676;
            font-size: 13px;
            padding: 8px;
            text-align: center;
        }

        .card-body {
            padding: 14px 2px 10px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 700;
            min-height: 48px;
        }

        .meta {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
        }

        .price-row {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .price {
            font-size: 42px;
            font-weight: 800;
            line-height: 1;
        }

        .old-price {
            font-size: 17px;
            color: #8d8d8d;
            text-decoration: line-through;
        }

        .discount {
            padding: 8px 14px;
            border-radius: 999px;
            background: #f3e8e8;
            color: #bc4b41;
            font-size: 18px;
            font-weight: 800;
        }

        .empty {
            border: 1px dashed #cfcfcf;
            border-radius: 14px;
            padding: 28px 10px;
            text-align: center;
            color: #676767;
            background: #fff;
        }

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

        @media (max-width: 1080px) {
            .catalog-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            footer {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

            .auth-links a {
                padding: 8px 12px;
                font-size: 12px;
            }

            .search {
                order: 3;
                max-width: 100%;
                width: 100%;
            }

            .content-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .grid {
                grid-template-columns: 1fr;
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
        <div class="search">
            <input type="text" value="<?= e($search) ?>" form="filter-form" name="search" placeholder="Cari produk thrift favoritmu...">
        </div>
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="cart-icon" href="cart.php" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor"/>
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor"/>
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
