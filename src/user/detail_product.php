<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
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
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
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

		.site-wrap {
			max-width: 1240px;
			margin: 0 auto;
			padding: 0 20px 28px;
			position: relative;
			z-index: 1;
		}

		.navbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			background: rgba(10, 10, 10, 0.6);
			backdrop-filter: blur(24px);
			-webkit-backdrop-filter: blur(24px);
			border: 1px solid var(--glass-border);
			border-top: none;
			padding: 16px 22px;
			border-radius: 0 0 24px 24px;
			box-shadow: 0 20px 40px rgba(0,0,0,0.5);
			position: sticky;
			top: 0;
			z-index: 100;
			margin-bottom: 18px;
		}

		.brand {
            font-family: "Symphony", sans-serif;
            font-size: 30px;
            text-decoration: none;
            color: var(--accent);
            letter-spacing: 1px;
            margin-top: 5px;
            text-shadow: 0 2px 10px rgba(212,175,55,0.2);
        }

		.menu {
			list-style: none;
			display: flex;
			gap: 20px;
			font-size: 14px;
		}

		.menu a {
			color: #FFFFFF;
			text-decoration: none;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 1px;
			font-size: 13px;
			transition: color 0.3s;
			position: relative;
			padding: 5px 0;
		}
		.menu a::after {
			content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px;
			background: linear-gradient(90deg, var(--accent), #fef08a); transition: width 0.3s ease-in-out;
		}
		.menu a:hover { color: var(--accent); }
		.menu a:hover::after { width: 100%; }

		.search {
			flex: 1;
			max-width: 400px;
		}

		.search input {
			width: 100%;
			border: 1px solid rgba(255,255,255,0.2);
			border-radius: 999px;
			padding: 10px 16px;
			background: rgba(255,255,255,0.1);
			color: #FFFFFF;
			font-size: 13px;
			font-family: "Montserrat", sans-serif;
			transition: all 0.3s ease;
		}
		.search input::placeholder { color: rgba(255,255,255,0.7); }
		.search input:focus { background: rgba(255,255,255,0.15); box-shadow: 0 0 0 4px rgba(212,175,55,0.2); outline: none; border-color: var(--accent); }

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
			color: var(--text);
			background: rgba(255,255,255,0.05);
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		}
		.cart-icon:hover { transform: translateY(-2px); border-color: var(--accent); color: var(--accent); }

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
			color: var(--accent);
			border: 1px solid var(--accent);
			background: transparent;
		}
		.auth-links .masuk:hover { background: var(--accent); color: #111; transform: translateY(-2px); }

		.auth-links .daftar {
			color: #111;
			background: linear-gradient(135deg, var(--accent) 0%, #fef08a 50%, var(--accent) 100%);
			background-size: 200% auto;
			border: none;
		}
		.auth-links .daftar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212,175,55,0.6); }

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
			background: rgba(20,20,20,0.4);
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
			border: 1px solid var(--glass-border);
			border-top: 1px solid rgba(212,175,55,0.2);
			border-radius: var(--radius);
			padding: 14px;
			box-shadow: 0 15px 35px rgba(0,0,0,0.5);
		}

		.hero-wrapper {
			position: relative;
			width: 100%;
			aspect-ratio: 4 / 5;
			border-radius: 12px;
			overflow: hidden;
			border: 1px solid var(--line);
			box-shadow: 0 12px 24px rgba(17,17,17,.06);
			background: #222;
			cursor: pointer;
		}

		.hero-track {
			display: flex;
			width: 100%; height: 100%;
			transition: transform .35s cubic-bezier(.4,0,.2,1);
			will-change: transform;
		}

		.hero-slide {
			min-width: 100%; height: 100%;
		}

		.hero-slide img {
			width: 100%; height: 100%;
			object-fit: cover; display: block;
		}

		.hero-arrow {
			position: absolute; top: 50%; transform: translateY(-50%);
			width: 38px; height: 38px;
			border-radius: 50%; border: none;
			background: rgba(255,255,255,.85);
			box-shadow: 0 2px 8px rgba(0,0,0,.12);
			cursor: pointer; z-index: 2;
			display: flex; align-items: center; justify-content: center;
			transition: opacity .2s, background .2s;
			opacity: 0;
		}

		.hero-wrapper:hover .hero-arrow { opacity: 1; }
		.hero-arrow:hover { background: #fff; }
		.hero-arrow.left { left: 10px; }
		.hero-arrow.right { right: 10px; }
		.hero-arrow svg { width: 18px; height: 18px; stroke: #222; stroke-width: 2.2; fill: none; }

		.hero-dots {
			position: absolute; bottom: 12px; left: 50%;
			transform: translateX(-50%);
			display: flex; gap: 6px; z-index: 2;
		}

		.hero-dot {
			width: 8px; height: 8px; border-radius: 50%;
			background: rgba(255,255,255,.5); border: none;
			cursor: pointer; transition: background .2s, transform .2s;
			padding: 0;
		}

		.hero-dot.active {
			background: #fff; transform: scale(1.3);
			box-shadow: 0 0 4px rgba(0,0,0,.25);
		}

		.img-fallback {
			width: 100%; height: 100%;
			display: flex; align-items: center; justify-content: center;
			color: #767676; font-size: 14px; text-align: center; padding: 10px;
		}

		.thumb-grid {
			margin-top: 12px;
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 8px;
		}

		.thumb {
			border-radius: 10px; overflow: hidden;
			background: #f2f2f2;
			border: 2px solid transparent;
			aspect-ratio: 1/1;
			cursor: pointer;
			transition: border-color .2s, opacity .2s;
			opacity: .6;
		}

		.thumb.active { border-color: #111; opacity: 1; }
		.thumb:hover { opacity: 1; }

		.thumb img {
			width: 100%; height: 100%;
			object-fit: cover; display: block;
		}

		/* Lightbox */
		.lightbox-overlay {
			position: fixed; inset: 0;
			background: rgba(0,0,0,.88);
			z-index: 9999;
			display: none; align-items: center; justify-content: center;
		}

		.lightbox-overlay.show { display: flex; }

		.lightbox-close {
			position: absolute; top: 18px; right: 22px;
			background: rgba(255,255,255,.15); border: none;
			color: #fff; font-size: 28px; width: 44px; height: 44px;
			border-radius: 50%; cursor: pointer;
			display: flex; align-items: center; justify-content: center;
			transition: background .2s;
		}

		.lightbox-close:hover { background: rgba(255,255,255,.3); }

		.lightbox-img {
			max-width: 90vw; max-height: 88vh;
			object-fit: contain; border-radius: 8px;
			user-select: none;
		}

		.lb-arrow {
			position: absolute; top: 50%; transform: translateY(-50%);
			width: 48px; height: 48px; border-radius: 50%; border: none;
			background: rgba(255,255,255,.15); cursor: pointer;
			display: flex; align-items: center; justify-content: center;
			transition: background .2s;
		}

		.lb-arrow:hover { background: rgba(255,255,255,.3); }
		.lb-arrow.left { left: 16px; }
		.lb-arrow.right { right: 16px; }
		.lb-arrow svg { width: 22px; height: 22px; stroke: #fff; stroke-width: 2.4; fill: none; }

		.lb-counter {
			position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
			color: rgba(255,255,255,.7); font-size: 13px; font-weight: 600;
		}

		.summary-top {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-bottom: 8px;
		}

		.chip {
			border-radius: 999px;
			border: 1px solid rgba(255,255,255,0.2);
			background: rgba(255,255,255,0.1);
			padding: 6px 10px;
			font-size: 12px;
			color: #E2E8F0;
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
			color: var(--accent);
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
			color: rgba(255,255,255,0.4);
			text-decoration: line-through;
		}

		.discount {
			padding: 8px 14px;
			border-radius: 999px;
			background: linear-gradient(135deg, var(--accent), #fef08a);
			color: #111;
			font-size: 18px;
			font-weight: 800;
			box-shadow: 0 4px 10px rgba(212,175,55,0.3);
		}

		.description {
			color: #E2E8F0;
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
			border: 1px solid rgba(255,255,255,0.15);
			border-radius: 10px;
			background: rgba(255,255,255,0.05);
			padding: 10px;
		}

		.spec-item h4 {
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.6px;
			color: var(--muted);
			margin-bottom: 4px;
		}

		.spec-item p {
			font-size: 14px;
			font-weight: 700;
			color: #FFFFFF;
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
			border: 1px solid var(--primary);
		}

		.btn.primary {
			background: var(--accent);
			color: #111;
			border-color: var(--accent);
			box-shadow: 0 4px 15px rgba(212,175,55,0.3);
			transition: all 0.3s;
		}
		.btn.primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(212,175,55,0.5); }

		.btn.secondary {
			background: transparent;
			color: var(--muted);
			border-color: rgba(255,255,255,0.2);
			transition: all 0.3s;
		}
		.btn.secondary:hover { color: #fff; border-color: rgba(255,255,255,0.4); }

		footer {
			margin-top: 64px;
			display: grid;
			grid-template-columns: 1.4fr repeat(2, 1fr);
			gap: 20px;
			font-size: 13px;
			color: #A1A1AA;
			padding: 40px;
			background: rgba(20,20,20,0.4);
			backdrop-filter: blur(30px);
			-webkit-backdrop-filter: blur(30px);
			border: 1px solid var(--glass-border);
			border-top: 1px solid rgba(212,175,55,0.3);
			border-radius: 30px;
			box-shadow: 0 30px 60px rgba(0,0,0,0.6);
		}

		footer h5 {
			margin-bottom: 12px;
			font-size: 12px;
			color: var(--accent);
			letter-spacing: 2px;
			text-transform: uppercase;
			font-weight: 800;
		}

		footer ul {
			list-style: none;
			display: grid;
			gap: 8px;
		}
		footer ul li { color: #E2E8F0; font-weight: 500; transition: color 0.3s; cursor: pointer; }
		footer ul li:hover { color: var(--accent); }

		.copyright {
			margin-top: 20px;
			padding-top: 16px;
			border-top: 1px solid rgba(255,255,255,0.1);
			color: #777;
			font-size: 12px;
		}

		@media (max-width: 1080px) {
			.detail-layout {
				grid-template-columns: 1fr;
			}

			.hero-arrow { opacity: .7; }

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
		<a class="brand" href="../../index.php">Recloth</a>
		<ul class="menu">
			<li><a href="../../index.php">Beranda</a></li>
			<li><a href="catalog.php">Katalog</a></li>
			<li><a href="category.php">Kategori</a></li>
		</ul>
		<div class="search">
			<input type="text" placeholder="Cari produk thrift favoritmu..." aria-label="Cari produk" disabled>
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

	<p class="breadcrumb">Beranda &gt; Katalog &gt; <?= e($product['name']) ?></p>

	<section class="detail-layout">
		<article class="gallery">
			<?php if (!empty($galleryImages)): ?>
			<div class="hero-wrapper" id="heroWrapper">
				<div class="hero-track" id="heroTrack">
					<?php foreach ($galleryImages as $i => $img): ?>
						<div class="hero-slide"><img src="<?= e($img) ?>" alt="<?= e($product['name']) ?> - foto <?= $i+1 ?>" draggable="false"></div>
					<?php endforeach; ?>
				</div>

				<?php if (count($galleryImages) > 1): ?>
				<button class="hero-arrow left" id="arrowLeft" aria-label="Sebelumnya"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button>
				<button class="hero-arrow right" id="arrowRight" aria-label="Selanjutnya"><svg viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg></button>
				<div class="hero-dots" id="heroDots">
					<?php foreach ($galleryImages as $i => $img): ?>
						<button class="hero-dot<?= $i===0?' active':'' ?>" data-idx="<?= $i ?>" aria-label="Gambar <?= $i+1 ?>"></button>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<?php if (count($galleryImages) > 1): ?>
				<div class="thumb-grid" id="thumbGrid">
					<?php foreach ($galleryImages as $index => $img): ?>
						<div class="thumb<?= $index===0?' active':'' ?>" data-idx="<?= $index ?>">
							<img src="<?= e($img) ?>" alt="<?= e($product['name']) ?> - foto <?= (int) ($index + 1) ?>">
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php else: ?>
			<div class="hero-wrapper"><div class="img-fallback">Foto produk belum tersedia</div></div>
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
				<?php if (isset($_SESSION['user_id'])): ?>
					<a class="btn primary" href="cart.php?action=add&id=<?= (int) $product['id'] ?>">Tambah ke Keranjang</a>
				<?php else: ?>
					<a class="btn primary" href="login.php">Tambah ke Keranjang</a>
				<?php endif; ?>
				<a class="btn secondary" href="catalog.php">Kembali ke Katalog</a>
			</div>
		</article>
	</section>

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

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox">
	<button class="lightbox-close" id="lbClose" aria-label="Tutup">&times;</button>
	<button class="lb-arrow left" id="lbLeft" aria-label="Sebelumnya"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button>
	<img class="lightbox-img" id="lbImg" src="" alt="" draggable="false">
	<button class="lb-arrow right" id="lbRight" aria-label="Selanjutnya"><svg viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg></button>
	<span class="lb-counter" id="lbCounter"></span>
</div>

<script>
(function() {
	const track = document.getElementById('heroTrack');
	const wrapper = document.getElementById('heroWrapper');
	if (!track || !wrapper) return;

	const slides = track.querySelectorAll('.hero-slide');
	const total = slides.length;
	if (total === 0) return;

	let current = 0;
	const dots = document.querySelectorAll('#heroDots .hero-dot');
	const thumbs = document.querySelectorAll('#thumbGrid .thumb');

	function goTo(idx, smooth) {
		idx = ((idx % total) + total) % total;
		current = idx;
		track.style.transition = smooth !== false ? 'transform .35s cubic-bezier(.4,0,.2,1)' : 'none';
		track.style.transform = 'translateX(-' + (idx * 100) + '%)';
		dots.forEach(function(d, i) { d.classList.toggle('active', i === idx); });
		thumbs.forEach(function(t, i) { t.classList.toggle('active', i === idx); });
	}

	// Arrow buttons
	var al = document.getElementById('arrowLeft');
	var ar = document.getElementById('arrowRight');
	if (al) al.addEventListener('click', function(e) { e.stopPropagation(); goTo(current - 1); });
	if (ar) ar.addEventListener('click', function(e) { e.stopPropagation(); goTo(current + 1); });

	// Dots
	dots.forEach(function(d) {
		d.addEventListener('click', function(e) {
			e.stopPropagation();
			goTo(parseInt(d.dataset.idx));
		});
	});

	// Thumbnails
	thumbs.forEach(function(t) {
		t.addEventListener('click', function() { goTo(parseInt(t.dataset.idx)); });
	});

	// Touch / swipe
	var sx = 0, sy = 0, dx = 0, swiping = false;
	wrapper.addEventListener('touchstart', function(e) {
		sx = e.touches[0].clientX;
		sy = e.touches[0].clientY;
		dx = 0; swiping = true;
	}, { passive: true });

	wrapper.addEventListener('touchmove', function(e) {
		if (!swiping) return;
		dx = e.touches[0].clientX - sx;
		var dy = Math.abs(e.touches[0].clientY - sy);
		if (dy > Math.abs(dx)) { swiping = false; return; }
		if (Math.abs(dx) > 10) e.preventDefault();
		var pct = -(current * 100) + (dx / wrapper.offsetWidth * 100);
		track.style.transition = 'none';
		track.style.transform = 'translateX(' + pct + '%)';
	}, { passive: false });

	wrapper.addEventListener('touchend', function() {
		if (!swiping) { goTo(current); return; }
		swiping = false;
		if (dx < -40) goTo(current + 1);
		else if (dx > 40) goTo(current - 1);
		else goTo(current);
	});

	// Keyboard
	document.addEventListener('keydown', function(e) {
		if (e.key === 'ArrowLeft') goTo(current - 1);
		else if (e.key === 'ArrowRight') goTo(current + 1);
	});

	// Lightbox
	var lb = document.getElementById('lightbox');
	var lbImg = document.getElementById('lbImg');
	var lbCounter = document.getElementById('lbCounter');
	var images = [];
	slides.forEach(function(s) { images.push(s.querySelector('img').src); });

	function openLightbox(idx) {
		idx = ((idx % total) + total) % total;
		current = idx;
		lbImg.src = images[idx];
		lbCounter.textContent = (idx + 1) + ' / ' + total;
		lb.classList.add('show');
		document.body.style.overflow = 'hidden';
		goTo(idx, false);
	}

	function closeLightbox() {
		lb.classList.remove('show');
		document.body.style.overflow = '';
	}

	wrapper.addEventListener('click', function() { openLightbox(current); });
	document.getElementById('lbClose').addEventListener('click', closeLightbox);
	lb.addEventListener('click', function(e) { if (e.target === lb) closeLightbox(); });

	document.getElementById('lbLeft').addEventListener('click', function(e) {
		e.stopPropagation(); openLightbox(current - 1);
	});
	document.getElementById('lbRight').addEventListener('click', function(e) {
		e.stopPropagation(); openLightbox(current + 1);
	});

	document.addEventListener('keydown', function(e) {
		if (!lb.classList.contains('show')) return;
		if (e.key === 'Escape') closeLightbox();
		if (e.key === 'ArrowLeft') openLightbox(current - 1);
		if (e.key === 'ArrowRight') openLightbox(current + 1);
	});

	// Lightbox swipe
	var lsx = 0, ldx = 0;
	lbImg.addEventListener('touchstart', function(e) { lsx = e.touches[0].clientX; ldx = 0; }, { passive: true });
	lbImg.addEventListener('touchmove', function(e) { ldx = e.touches[0].clientX - lsx; }, { passive: true });
	lbImg.addEventListener('touchend', function() {
		if (ldx < -50) openLightbox(current + 1);
		else if (ldx > 50) openLightbox(current - 1);
	});
})();
</script>
</body>
</html>
