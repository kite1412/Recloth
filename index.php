<?php
$title = "Recloth | Toko Thrift Pilihan";

$newArrivals = [
    ["name" => "Kaos Vintage Band", "price" => 120000, "old" => 160000, "rating" => 4.5, "image" => "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=520&q=80"],
    ["name" => "Kemeja Flanel Oversize", "price" => 145000, "old" => 185000, "rating" => 4.6, "image" => "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=520&q=80"],
    ["name" => "Hoodie Streetwear", "price" => 210000, "old" => 250000, "rating" => 4.7, "image" => "https://images.unsplash.com/photo-1527719327859-c6ce80353573?auto=format&fit=crop&w=520&q=80"],
    ["name" => "Sweater Knit Classic", "price" => 175000, "old" => 0, "rating" => 4.4, "image" => "https://images.unsplash.com/photo-1503341504253-dff4815485f1?auto=format&fit=crop&w=520&q=80"],
];

$topSelling = [
    ["name" => "Celana Jeans Relaxed Fit", "price" => 190000, "old" => 230000, "rating" => 4.8, "image" => "https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=520&q=80"],
    ["name" => "Rok Denim A-Line", "price" => 135000, "old" => 0, "rating" => 4.2, "image" => "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=520&q=80"],
    ["name" => "Jaket Varsity Thrift", "price" => 260000, "old" => 300000, "rating" => 4.9, "image" => "https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=520&q=80"],
    ["name" => "Cardigan Rajut Soft", "price" => 170000, "old" => 0, "rating" => 4.5, "image" => "https://images.unsplash.com/photo-1612874742237-6526221588e3?auto=format&fit=crop&w=520&q=80"],
];

function renderStars(float $rating): string
{
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat("★", $full) . ($half ? "☆" : "") . str_repeat("✩", $empty);
}

function formatRupiah(float $amount): string
{
    return "Rp" . number_format($amount, 0, ',', '.');
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
            src: url('public/fonts/symphony-pro-regular.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --bg: #f4f4f4;
            --text: #121212;
            --muted: #808080;
            --line: #e7e7e7;
            --white: #ffffff;
            --black: #000000;
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

        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px;
            align-items: center;
            background: #f0f0f0;
            border-radius: 0 0 20px 20px;
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
            background: var(--black);
            color: var(--white);
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
                <li><a href="src/user/catalog.php">Kategori</a></li>
            </ul>
            <div class="search">
                <input type="text" placeholder="Cari produk thrift favoritmu...">
            </div>
            <div class="nav-actions">
                <a class="cart-icon" href="#" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor" />
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor" />
                    </svg>
                </a>
                <div class="auth-links">
                    <a class="masuk" href="#">Masuk</a>
                    <a class="daftar" href="#">Daftar</a>
                </div>
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
                    <?php $discount = $product['old'] > 0 ? round((($product['old'] - $product['price']) / $product['old']) * 100) : 0; ?>
                    <article class="card">
                        <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="price">
                            <span><?= formatRupiah((float) $product['price']) ?></span>
                            <?php if ($product['old'] > 0): ?>
                                <del><?= formatRupiah((float) $product['old']) ?></del>
                                <span class="discount">-<?= (int) $discount ?>%</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <a class="view-all" href="src/user/catalog.php">Lihat Semua</a>
        </section>

        <section class="section">
            <h2>Koleksi Pilihan</h2>
            <div class="products">
                <?php foreach ($topSelling as $product): ?>
                    <?php $discount = $product['old'] > 0 ? round((($product['old'] - $product['price']) / $product['old']) * 100) : 0; ?>
                    <article class="card">
                        <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="price">
                            <span><?= formatRupiah((float) $product['price']) ?></span>
                            <?php if ($product['old'] > 0): ?>
                                <del><?= formatRupiah((float) $product['old']) ?></del>
                                <span class="discount">-<?= (int) $discount ?>%</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <a class="view-all" href="src/user/catalog.php">Lihat Semua</a>
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