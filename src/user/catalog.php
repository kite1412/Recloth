<?php
require '../config/database.php';

$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');

if (!isset($pdo) || !($pdo instanceof PDO)) {
	die('Koneksi database tidak valid. Pastikan ../config/database.php menyediakan variabel $pdo (PDO).');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columnsStmt = $pdo->query("SHOW COLUMNS FROM products");
$columns = array_map('strtolower', $columnsStmt->fetchAll(PDO::FETCH_COLUMN));

$hasGender = in_array('gender', $columns, true);
$hasImage = in_array('image', $columns, true);

$sql = "
	SELECT
		p.id,
		p.name,
		p.price,
		p.stock,
		" . ($hasImage ? 'p.image' : "''") . " AS image,
		" . ($hasGender ? 'p.gender' : "''") . " AS gender,
		c.name AS category_name
	FROM products p
	LEFT JOIN categories c ON c.id = p.category_id
";

$conditions = [];
$params = [];

if ($search !== '') {
	$conditions[] = 'p.name LIKE :search';
	$params[':search'] = '%' . $search . '%';
}

if (!empty($conditions)) {
	$sql .= ' WHERE ' . implode(' AND ', $conditions);
}

if ($sort === 'harga_terendah') {
	$sql .= ' ORDER BY p.price ASC';
} elseif ($sort === 'harga_tertinggi') {
	$sql .= ' ORDER BY p.price DESC';
} else {
	$sql .= ' ORDER BY p.created_at DESC, p.id DESC';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
	<title>Katalog Produk - Recloth</title>
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

		.toolbar {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 14px;
			background: #fff;
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
			background: #111;
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
			background: #fff;
			border: 1px solid var(--line);
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 8px 18px rgba(17, 17, 17, 0.04);
		}

		.img-wrap {
			width: 100%;
			height: 245px;
			background: #ececec;
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
			padding: 12px;
		}

		.product-name {
			font-size: 15px;
			font-weight: 700;
			min-height: 40px;
		}

		.meta {
			margin-top: 5px;
			color: var(--muted);
			font-size: 12px;
		}

		.price {
			margin-top: 7px;
			font-size: 20px;
			font-weight: 800;
		}

		.actions {
			margin-top: 10px;
			display: flex;
			gap: 8px;
		}

		.actions a {
			flex: 1;
			text-align: center;
			text-decoration: none;
			font-size: 12px;
			font-weight: 700;
			border-radius: 999px;
			padding: 9px;
			border: 1px solid var(--line);
			color: #111;
			background: #fff;
		}

		.actions a.primary {
			background: #111;
			color: #fff;
			border-color: #111;
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
		<a class="brand" href="../../index.php">RECLOTH</a>
		<ul class="menu">
			<li><a href="../../index.php">Beranda</a></li>
			<li><a href="catalog.php">Katalog</a></li>
			<li><a href="category.php">Kategori</a></li>
		</ul>
		<div class="search">
			<input type="text" value="<?= e($search) ?>" form="catalog-form" name="search" placeholder="Cari produk thrift favoritmu...">
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
				<article class="card">
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
						<p class="price"><?= rupiah($product['price']) ?></p>
						<div class="actions">
							<a href="product-detail.php?id=<?= (int) $product['id'] ?>">Detail</a>
							<a class="primary" href="cart.php?action=add&id=<?= (int) $product['id'] ?>">Tambah ke Keranjang</a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

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
