<?php
require '../config/database.php';
require '../config/product_repository.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
	die('Koneksi database tidak valid. Pastikan ../config/database.php menyediakan variabel $pdo (PDO).');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = filter_input(
	INPUT_GET,
	'id',
	FILTER_VALIDATE_INT,
	['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
	header('Location: catalog.php?message=pilih_produk_dari_katalog', true, 302);
	exit;
}

$product = recloth_fetch_product_by_id($pdo, (int) $id);

if (!$product) {
	header('Location: catalog.php?message=produk_tidak_ditemukan', true, 302);
	exit;
}

$galleryImages = recloth_fetch_product_images(
	$pdo,
	(int) $product['id'],
	(string) ($product['image'] ?? '')
);

function rupiah($price): string
{
	return 'Rp' . number_format((float) $price, 0, ',', '.');
}

function e($text): string
{
	return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function displayValue($value, string $fallback = '-'): string
{
	$value = trim((string) $value);
	return $value === '' ? $fallback : $value;
}

function oldPriceValue(float $price, int $discount = 0): float
{
	$discount = max(0, min(90, $discount));
	if ($discount <= 0) {
		return $price;
	}
	return $price / (1 - ($discount / 100));
}

$conditionText = displayValue($product['condition_status'] ?? '', 'Belum diatur');
$sizeText = displayValue($product['size_label'] ?? '', 'Belum diatur');
$materialText = displayValue($product['material'] ?? '', 'Belum diatur');
$yearText = !empty($product['production_year']) ? (string) $product['production_year'] : 'Belum diatur';
$genderText = !empty($product['gender']) ? ucfirst((string) $product['gender']) : 'Unisex';
$stockText = (int) ($product['stock'] ?? 0) > 0 ? 'Tersedia' : 'Habis';
$categoryText = ucwords((string) ($product['category_name'] ?? '-'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Detail Produk - Recloth</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
	<style>
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
			font-family: "Archivo Black", sans-serif;
			font-size: 26px;
			text-decoration: none;
			color: var(--black);
			letter-spacing: 0.8px;
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

		.detail-layout {
			display: grid;
			grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
			gap: 18px;
		}

		.gallery,
		.summary {
			background: #fff;
			border: 1px solid var(--line);
			border-radius: var(--radius);
			padding: 14px;
		}

		.hero-image {
			width: 100%;
			aspect-ratio: 4 / 5;
			background: #ececec;
			border-radius: 12px;
			overflow: hidden;
			border: 1px solid var(--line);
			box-shadow: 0 12px 24px rgba(17, 17, 17, 0.06);
		}

		.hero-image img {
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
			font-size: 14px;
			text-align: center;
			padding: 10px;
		}

		.thumb-grid {
			margin-top: 12px;
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 8px;
		}

		.thumb {
			border-radius: 10px;
			overflow: hidden;
			background: #f2f2f2;
			border: 1px solid var(--line);
			aspect-ratio: 1 / 1;
		}

		.thumb img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}

		.summary-top {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-bottom: 8px;
		}

		.chip {
			border-radius: 999px;
			border: 1px solid var(--line);
			background: #fafafa;
			padding: 6px 10px;
			font-size: 12px;
			color: #3f3f3f;
			font-weight: 600;
		}

		.product-name {
			font-size: clamp(24px, 3.4vw, 38px);
			line-height: 1.1;
			margin-bottom: 8px;
		}

		.price {
			font-size: clamp(24px, 2.8vw, 34px);
			font-weight: 800;
			margin-bottom: 12px;
		}

		.price-row {
			margin-bottom: 12px;
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
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

		.description {
			color: #3b3b3b;
			font-size: 14px;
			margin-bottom: 14px;
			white-space: pre-line;
		}

		.spec-grid {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 8px;
			margin-bottom: 14px;
		}

		.spec-item {
			border: 1px solid var(--line);
			border-radius: 10px;
			background: #fafafa;
			padding: 10px;
		}

		.spec-item h4 {
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.6px;
			color: #777;
			margin-bottom: 4px;
		}

		.spec-item p {
			font-size: 14px;
			font-weight: 700;
			color: #1d1d1d;
		}

		.actions {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
		}

		.btn {
			flex: 1;
			min-width: 180px;
			text-align: center;
			text-decoration: none;
			border-radius: 999px;
			padding: 11px 14px;
			font-size: 13px;
			font-weight: 700;
			border: 1px solid #111;
		}

		.btn.primary {
			background: #111;
			color: #fff;
		}

		.btn.secondary {
			background: #fff;
			color: #111;
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
			.detail-layout {
				grid-template-columns: 1fr;
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

			.thumb-grid {
				grid-template-columns: repeat(3, minmax(0, 1fr));
			}

			.spec-grid {
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
		<a class="brand" href="../../index.php">RECLOTH</a>
		<ul class="menu">
			<li><a href="../../index.php">Beranda</a></li>
			<li><a href="catalog.php">Katalog</a></li>
			<li><a href="category.php">Kategori</a></li>
		</ul>
		<div class="search">
			<input type="text" placeholder="Cari produk thrift favoritmu..." aria-label="Cari produk" disabled>
		</div>
		<div class="nav-actions">
			<a class="cart-icon" href="cart.php" aria-label="Keranjang">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
					<circle cx="9.5" cy="19" r="1.2" fill="currentColor"/>
					<circle cx="17.5" cy="19" r="1.2" fill="currentColor"/>
				</svg>
			</a>
			<div class="auth-links">
				<a class="masuk" href="login.php">Masuk</a>
				<a class="daftar" href="register.php">Daftar</a>
			</div>
		</div>
	</nav>

	<p class="breadcrumb">Beranda &gt; Katalog &gt; <?= e($product['name']) ?></p>

	<section class="detail-layout">
		<article class="gallery">
			<div class="hero-image">
				<?php if (!empty($galleryImages)): ?>
					<img src="<?= e($galleryImages[0]) ?>" alt="<?= e($product['name']) ?>">
				<?php else: ?>
					<div class="img-fallback">Foto produk belum tersedia</div>
				<?php endif; ?>
			</div>

			<?php if (count($galleryImages) > 1): ?>
				<div class="thumb-grid">
					<?php foreach ($galleryImages as $index => $img): ?>
						<div class="thumb">
							<img src="<?= e($img) ?>" alt="<?= e($product['name']) ?> - foto <?= (int) ($index + 1) ?>">
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>

		<article class="summary">
			<div class="summary-top">
				<span class="chip"><?= e($categoryText) ?></span>
				<span class="chip"><?= e($genderText) ?></span>
				<span class="chip"><?= e($stockText) ?></span>
			</div>

			<h1 class="product-name"><?= e($product['name']) ?></h1>
			<?php $discount = (int) ($product['discount_percent'] ?? 0); ?>
			<?php $oldPrice = oldPriceValue((float) $product['price'], $discount); ?>
			<div class="price-row">
				<p class="price"><?= rupiah($product['price']) ?></p>
				<?php if ($discount > 0): ?>
					<p class="old-price"><?= rupiah($oldPrice) ?></p>
					<span class="discount">-<?= (int) $discount ?>%</span>
				<?php endif; ?>
			</div>
			<p class="description"><?= e(displayValue($product['description'] ?? '', 'Deskripsi produk belum tersedia.')) ?></p>

			<div class="spec-grid">
				<div class="spec-item">
					<h4>Kondisi</h4>
					<p><?= e($conditionText) ?></p>
				</div>
				<div class="spec-item">
					<h4>Ukuran</h4>
					<p><?= e($sizeText) ?></p>
				</div>
				<div class="spec-item">
					<h4>Tahun</h4>
					<p><?= e($yearText) ?></p>
				</div>
				<div class="spec-item">
					<h4>Bahan</h4>
					<p><?= e($materialText) ?></p>
				</div>
			</div>

			<div class="actions">
				<a class="btn primary" href="cart.php?action=add&id=<?= (int) $product['id'] ?>">Tambah ke Keranjang</a>
				<a class="btn secondary" href="catalog.php">Kembali ke Katalog</a>
			</div>
		</article>
	</section>

	<footer>
		<section>
			<a class="brand" href="../../index.php">RECLOTH</a>
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
