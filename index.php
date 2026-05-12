<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: src/admin/dashboard.php');
    exit;
}
$title = "Recloth | Toko Thrift Pilihan";
require __DIR__ . '/src/config/database.php';
require __DIR__ . '/src/config/product_repository.php';

$newArrivals = recloth_fetch_products($pdo, [
    'sort' => 'terbaru',
    'limit' => 4,
]);

$topSelling = recloth_fetch_products($pdo, [
    'featured' => true,
    'limit' => 4,
]);

function formatRupiah(float $amount): string
{
    return "Rp" . number_format($amount, 0, ',', '.');
}

function oldPrice(float $price, int $discount = 0): float
{
    $discount = max(0, min(90, $discount));
    if ($discount <= 0) {
        return $price;
    }
    return $price / (1 - ($discount / 100));
}

function productImage(string $url): string
{
    $url = trim($url);
    if ($url !== '') {
        return $url;
    }

    return 'https://dummyimage.com/600x700/e9e9e9/7a7a7a&text=Foto+Belum+Tersedia';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" href="public/icons/app-logo.png">
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

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--bg);
            border-bottom: 1px solid var(--line);
            padding: 16px 22px;
            border-radius: 0 0 14px 14px;
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
            color: #2e2e2e;
            font-size: 14px;
        }

        .menu a {
            color: #2e2e2e;
            text-decoration: none;
            font-weight: 600;
        }

        .menu a:hover {
            color: #000;
        }

        .search {
            flex: 1;
            max-width: 430px;
        }

        .search input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 11px 16px;
            background: #f8f8f8;
            font-size: 13px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px;
            align-items: center;
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            padding: 38px 34px;
        }

        .hero h1 {
            font-family: "Archivo Black", sans-serif;
            font-size: clamp(36px, 5vw, 66px);
            line-height: 0.95;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .hero p {
            color: #565656;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .hero-btn {
            display: inline-block;
            background: #8b9d77;
            color: #fff;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            padding: 13px 28px;
            margin-bottom: 26px;
            font-size: 13px;
        }

        .hero-stats {
            display: flex;
            gap: 26px;
            flex-wrap: wrap;
        }

        .hero-stats strong {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: var(--black);
        }

        .hero-stats span {
            font-size: 12px;
            color: #787878;
        }

        .hero-image {
            min-height: 420px;
            border-radius: 16px;
            background: url("https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80") center/cover no-repeat;
            position: relative;
            isolation: isolate;
        }

        .hero-image::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 28%, rgba(0, 0, 0, 0.22));
            z-index: -1;
        }

        .section {
            margin-top: 52px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--line);
        }

        .section h2 {
            text-align: center;
            font-family: "Archivo Black", sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: clamp(28px, 3.5vw, 44px);
            margin-bottom: 24px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-link {
            color: inherit;
            text-decoration: none;
            display: block;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 12px;
            background: #ededed;
        }

        .card h3 {
            margin-top: 10px;
            font-size: 14px;
            min-height: 38px;
        }

        .price {
            margin-top: 7px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }

        .price del {
            color: #959595;
            font-weight: 500;
            font-size: 13px;
        }

        .discount {
            color: var(--danger);
            font-size: 11px;
            background: #ffecec;
            border-radius: 999px;
            padding: 3px 9px;
            font-weight: 700;
        }

        .view-all {
            margin: 26px auto 4px;
            width: max-content;
            border: 1px solid #d4d4d4;
            border-radius: 999px;
            padding: 9px 24px;
            text-decoration: none;
            font-size: 13px;
            color: #383838;
            display: block;
        }

        .reviews {
            margin-top: 52px;
        }

        .reviews h2 {
            text-align: left;
            margin-bottom: 16px;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .review {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }

        .review .stars {
            color: #f6a623;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .review h4 {
            font-size: 14px;
            margin-bottom: 7px;
        }

        .review p {
            color: #666;
            font-size: 12px;
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
            .products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hero {
                grid-template-columns: 1fr;
            }

            .hero-image {
                min-height: 340px;
            }

            footer {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
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

            .products,
            .review-grid {
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
            <a class="brand" href="index.php">Recloth</a>
            <ul class="menu">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="src/user/catalog.php">Katalog</a></li>
                <li><a href="src/user/category.php">Kategori</a></li>
            </ul>
            <div class="search">
                <form action="src/user/category.php" method="GET">
                    <input type="text" name="search" placeholder="Cari produk thrift favoritmu...">
                </form>
            </div>
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="cart-icon" href="src/user/cart.php?tab=cart" aria-label="Keranjang">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="9.5" cy="19" r="1.2" fill="currentColor" />
                            <circle cx="17.5" cy="19" r="1.2" fill="currentColor" />
                        </svg>
                    </a>
                    <a class="cart-icon" href="src/user/profile.php" aria-label="Profil">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a class="cart-icon" href="src/config/logout.php" aria-label="Logout" style="color: #d24e4e; background: var(--bg); border-color: var(--line);">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <div class="auth-links">
                        <a class="masuk" href="src/user/login.php">Masuk</a>
                        <a class="daftar" href="src/user/register.php">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>

        <header class="hero">
            <div>
                <h1>Temukan Baju Thrift Sesuai Gayamu</h1>
                <p>
                    Recloth adalah platform e-commerce berbasis web yang menawarkan pakaian thrift berkualitas pilihan.
                    Setiap produk dikurasi dengan teliti, sehingga kamu bisa tampil stylish dengan harga terjangkau dan
                    pengalaman belanja yang nyaman.
                </p>
                <a class="hero-btn" href="src/user/catalog.php">Belanja Sekarang</a>

                <div class="hero-stats">
                    <div>
                        <strong>200+</strong>
                        <span>Produk Thrift Terkurasi</span>
                    </div>
                    <div>
                        <strong>2,000+</strong>
                        <span>Pembeli Terlayani</span>
                    </div>
                    <div>
                        <strong>30,000+</strong>
                        <span>Pesanan Terselesaikan</span>
                    </div>
                </div>
            </div>

            <div class="hero-image" aria-label="Gambar hero Recloth"></div>
        </header>

        <section class="section">
            <h2>Produk Terbaru</h2>
            <div class="products">
                <?php foreach ($newArrivals as $product): ?>
                    <?php $discount = (int) ($product['discount_percent'] ?? 0); ?>
                    <?php $before = oldPrice((float) $product['price'], $discount); ?>
                    <article class="card">
                        <a class="card-link" href="src/user/detail_product.php?id=<?= (int) $product['id'] ?>">
                            <img src="<?= htmlspecialchars(productImage((string) ($product['image'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <h3><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="price">
                                <span><?= formatRupiah((float) $product['price']) ?></span>
                                <?php if ($discount > 0): ?>
                                    <del><?= formatRupiah((float) $before) ?></del>
                                    <span class="discount">-<?= (int) $discount ?>%</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <a class="view-all" href="src/user/catalog.php">Lihat Semua</a>
        </section>

        <section class="section">
            <h2>Koleksi Pilihan</h2>
            <div class="products">
                <?php foreach ($topSelling as $product): ?>
                    <?php $discount = (int) ($product['discount_percent'] ?? 0); ?>
                    <?php $before = oldPrice((float) $product['price'], $discount); ?>
                    <article class="card">
                        <a class="card-link" href="src/user/detail_product.php?id=<?= (int) $product['id'] ?>">
                            <img src="<?= htmlspecialchars(productImage((string) ($product['image'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <h3><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="price">
                                <span><?= formatRupiah((float) $product['price']) ?></span>
                                <?php if ($discount > 0): ?>
                                    <del><?= formatRupiah((float) $before) ?></del>
                                    <span class="discount">-<?= (int) $discount ?>%</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <a class="view-all" href="src/user/category.php?sort=terbaru">Lihat Semua</a>
        </section>

        <section class="reviews">
            <h2>Testimoni Pelanggan</h2>
            <div class="review-grid">
                <article class="review">
                    <div class="stars">★★★★★</div>
                    <h4>Rina M. ✓</h4>
                    <p>Kualitas baju thrift-nya bagus banget dan kondisi masih layak pakai. Pengiriman juga cepat.</p>
                </article>
                <article class="review">
                    <div class="stars">★★★★★</div>
                    <h4>Dimas K. ✓</h4>
                    <p>Dari cari produk sampai checkout prosesnya mudah. Barang yang datang sesuai foto.</p>
                </article>
                <article class="review">
                    <div class="stars">★★★★★</div>
                    <h4>Salsa L. ✓</h4>
                    <p>Harga bersahabat untuk mahasiswa, pilihannya banyak, dan admin responsif waktu ditanya.</p>
                </article>
            </div>
        </section>

        <footer>
            <section>
                <a class="brand" href="#">Recloth</a>
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
                    <li>Detail Pengiriman</li>
                    <li>Kebijakan Privasi</li>
                </ul>
            </section>
        </footer>

        <p class="copyright">Recloth © <?= date('Y') ?>. Semua Hak Dilindungi.</p>
    </div>
</body>

</html>