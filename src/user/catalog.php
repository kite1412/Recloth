<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
require '../config/database.php';
require '../config/product_repository.php';

$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');

if (!isset($pdo) || !($pdo instanceof PDO)) {
	die('Koneksi database tidak valid. Pastikan ../config/database.php menyediakan variabel $pdo (PDO).');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$products = recloth_fetch_products($pdo, [
	'search' => $search,
	'sort' => $sort,
]);

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
	<title>Katalog Produk - Recloth</title>
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

		.toolbar {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 14px;
			background: var(--white);
			border: 1px solid var(--line);
			border-radius: 12px;
			padding: 12px 14px;
		}

		.toolbar-left h1 {
			font-size: 30px;
			line-height: 1;
			margin-bottom: 4px;
		}

		.toolbar-left p {
			color: var(--muted);
			font-size: 13px;
		}

		.toolbar-form {
			display: flex;
			gap: 8px;
			align-items: center;
			flex-wrap: wrap;
		}

		.toolbar-form input,
		.toolbar-form select {
			border: 1px solid var(--line);
			border-radius: 999px;
			padding: 9px 12px;
			font-size: 13px;
			background: #fff;
		}

		.toolbar-form button,
		.toolbar-form a {
			border-radius: 999px;
			border: 1px solid var(--black);
			padding: 9px 12px;
			font-size: 13px;
			font-weight: 700;
			text-decoration: none;
			cursor: pointer;
		}

		.toolbar-form button {
			background: var(--primary);
			color: #fff;
		}

		.toolbar-form a {
			background: #fff;
			color: #111;
		}

		.grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 14px;
		}

		.card {
			background: var(--white);
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
			color: #2e3522;
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
			color: #2e3522;
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
			color: #2e3522;
			background: var(--white);
		}

		footer {
			margin-top: 64px;
			display: grid;
			grid-template-columns: 1.4fr repeat(2, 1fr);
			gap: 20px;
			font-size: 13px;
			color: #2e3522;
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
			color: #2e3522;
			font-size: 12px;
		}

		@media (max-width: 1080px) {
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

			.toolbar {
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
			<input type="text" value="<?= e($search) ?>" form="catalog-form" name="search" placeholder="Cari produk thrift favoritmu...">
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

	<p class="breadcrumb">Beranda &gt; Katalog</p>

	<section class="toolbar">
		<div class="toolbar-left">
			<h1>Semua Produk</h1>
			<p>Menampilkan <?= count($products) ?> produk thrift pilihan Recloth.</p>
		</div>
		<form id="catalog-form" class="toolbar-form" method="GET" action="catalog.php">
			<select name="sort">
				<option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
				<option value="harga_terendah" <?= $sort === 'harga_terendah' ? 'selected' : '' ?>>Harga Terendah</option>
				<option value="harga_tertinggi" <?= $sort === 'harga_tertinggi' ? 'selected' : '' ?>>Harga Tertinggi</option>
			</select>
			<button type="submit">Terapkan</button>
			<a href="catalog.php">Reset</a>
		</form>
	</section>

	<?php if (empty($products)): ?>
		<div class="empty">Produk tidak ditemukan. Coba ubah kata pencarian.</div>
	<?php else: ?>
		<section class="grid">
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
		</section>
	<?php endif; ?>

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