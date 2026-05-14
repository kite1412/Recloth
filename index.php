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
            --primary: #2d5a40; /* Deep elegant green */
            --primary-glow: rgba(45, 90, 64, 0.5);
            --accent: #D4AF37; /* Luxury Gold */
            --accent-glow: rgba(212, 175, 55, 0.4);
            --bg: #070707; /* Deep space black */
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text: #FFFFFF;
            --muted: #A1A1AA;
            --line: rgba(255, 255, 255, 0.1);
            --sidebar-bg: #111111;
            --radius: 20px;
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
            line-height: 1.5;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* MASSIVE AMBIENT GLOW ORBS IN BACKGROUND */
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
        @keyframes floatCard { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }

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
            padding: 16px 28px;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-family: "Symphony", sans-serif;
            font-size: 42px;
            text-decoration: none;
            color: var(--accent);
            letter-spacing: 1px;
            padding: 5px 15px 10px 5px; /* Prevent cutting off cursive tails */
            line-height: 1;
            transition: transform 0.3s;
            text-shadow: 0 2px 10px rgba(212,175,55,0.2);
        }
        .brand:hover { transform: scale(1.02); }

        .menu {
            list-style: none;
            display: flex;
            gap: 24px;
            font-size: 14px;
        }

        .menu a {
            color: #FFFFFF;
            text-decoration: none;
            font-weight: 700;
            position: relative;
            padding: 5px 0;
            transition: color 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 13px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .menu a::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px;
            background: linear-gradient(90deg, var(--accent), #fef08a); transition: width 0.3s ease-in-out;
        }

        .menu a:hover {
            color: var(--accent);
        }
        .menu a:hover::after { width: 100%; }

        .search {
            flex: 1;
            max-width: 430px;
        }

        .search input {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
            font-size: 13px;
            font-family: "Montserrat", sans-serif;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);
        }
        .search input::placeholder { color: rgba(255,255,255,0.7); }
        .search input:focus { background: rgba(255,255,255,0.15); box-shadow: 0 0 0 4px rgba(212,175,55,0.2); outline: none; border-color: var(--accent); }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .cart-icon {
            width: 44px;
            height: 44px;
            border: 1px solid var(--line);
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text);
            background: #FFFFFF;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .cart-icon:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--primary); color: var(--primary); }

        .cart-icon svg {
            width: 19px;
            height: 19px;
        }

        .auth-links {
            display: flex;
            gap: 10px;
        }

        .auth-links a {
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border-radius: 999px;
            padding: 11px 18px;
            transition: all 0.3s;
        }

        .auth-links .masuk {
            color: var(--accent);
            border: 1px solid var(--accent);
            background: transparent;
        }
        .auth-links .masuk:hover { background: var(--accent); color: #111; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(212,175,55,0.3); }

        .auth-links .daftar {
            color: #111111;
            background: linear-gradient(135deg, var(--accent) 0%, #fef08a 50%, var(--accent) 100%);
            background-size: 200% auto; animation: gradientText 3s linear infinite, pulseGlow 3s infinite alternate;
            border: none;
        }
        .auth-links .daftar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212, 175, 55, 0.6); }

        /* --- GLASSMORPHISM HERO --- */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            background: rgba(20, 20, 20, 0.4);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-top: 1px solid rgba(212, 175, 55, 0.3); /* Gold rim light */
            color: #FFFFFF;
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5), inset 0 0 20px rgba(255,255,255,0.02);
            padding: 56px 48px;
            margin-top: 32px;
            position: relative;
            overflow: hidden;
            animation: heroReveal 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.15), transparent 50%);
            animation: floatGlow1 8s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .hero::after {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(45, 90, 64, 0.3), transparent 50%);
            animation: floatGlow2 10s ease-in-out infinite alternate-reverse;
            pointer-events: none;
        }
        @keyframes floatGlow1 { 0% { transform: translate(0, 0) scale(1); opacity: 0.5; } 100% { transform: translate(30px, -30px) scale(1.2); opacity: 1; } }
        @keyframes floatGlow2 { 0% { transform: translate(0, 0) scale(1); opacity: 0.5; } 100% { transform: translate(-30px, 30px) scale(1.3); opacity: 1; } }
        @keyframes heroReveal { to { opacity: 1; transform: translateY(0); } }
        @keyframes pulseGlow { 0% { box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4); } 100% { box-shadow: 0 10px 30px rgba(212, 175, 55, 0.8); } }

        .hero h1 {
            font-family: "Archivo Black", sans-serif;
            font-size: clamp(38px, 4.5vw, 64px);
            line-height: 1.1;
            text-transform: uppercase;
            margin-bottom: 20px;
            color: #FFFFFF;
            position: relative;
            z-index: 2;
            letter-spacing: 2px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }
        .hero h1 span {
            background: linear-gradient(135deg, var(--accent), #fef08a, var(--accent));
            background-size: 200% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: gradientText 3s linear infinite;
        }

        .hero p {
            color: #F8F8F8; /* Maximum contrast white instead of gray */
            margin-bottom: 36px;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.7;
            position: relative;
            z-index: 2;
        }

        .hero-btn {
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--accent), #fef08a, var(--accent));
            background-size: 200% auto; animation: gradientShift 3s ease infinite, pulseGlow 3s infinite alternate;
            color: #111111;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 800;
            padding: 16px 36px;
            margin-bottom: 36px;
            font-size: 14px;
            letter-spacing: 1px;
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.4);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 2;
        }
        .hero-btn:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 12px 25px rgba(212, 175, 55, 0.5); }

        .hero-stats {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-stats strong {
            display: block;
            font-size: 26px;
            font-weight: 700;
            color: var(--accent);
        }

        .hero-stats span {
            font-size: 12px;
            color: #E2E8F0; /* Brighter color to avoid camouflage */
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .hero-image {
            min-height: 440px;
            border-radius: 16px;
            background: url("https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80") center/cover no-repeat;
            position: relative;
            isolation: isolate;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .hero:hover .hero-image { transform: scale(1.02) translateY(-4px); }

        .section {
            margin-top: 52px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--line);
        }

        .section h2 {
            text-align: center;
            font-family: "Archivo Black", sans-serif;
            font-weight: 800;
            font-size: clamp(36px, 4vw, 48px);
            margin-bottom: 60px;
            background: linear-gradient(to right, #FFFFFF, var(--accent), #FFFFFF);
            background-size: 200% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: gradientText 6s linear infinite;
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        .section h2::after {
            content: ''; display: block; width: 100px; height: 6px;
            background: linear-gradient(90deg, var(--accent), #fef08a, var(--accent)); 
            background-size: 200% auto; animation: gradientShift 3s ease infinite, pulseGlow 2s infinite alternate;
            margin: 20px auto 0; border-radius: 999px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
        }

        /* --- GLASS PRODUCT CARDS --- */
        .card {
            background: rgba(20, 20, 20, 0.4);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            padding: 16px;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            animation: floatCard 8s ease-in-out infinite, pulseGlowCard 4s infinite alternate;
            animation-delay: calc(var(--i, 0) * 0.4s);
            overflow: hidden;
        }
        @keyframes pulseGlowCard {
            0% { box-shadow: 0 5px 15px rgba(212, 175, 55, 0.05); }
            100% { box-shadow: 0 15px 35px rgba(212, 175, 55, 0.25); }
        }
        
        .card::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(212, 175, 55, 0.15), transparent 70%);
            opacity: 0; transition: opacity 0.5s; pointer-events: none;
            border-radius: 20px;
        }
        .card:hover::before { opacity: 1; }

        /* Card Hover Sweep */
        .card::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: skewX(-20deg); transition: 0.7s; z-index: 1; pointer-events: none;
        }
        .card:hover::after { left: 150%; }

        .card-link { color: inherit; text-decoration: none; display: block; position: relative; z-index: 2; }

        .card:hover {
            transform: translateY(-15px) scale(1.03);
            background: rgba(30, 30, 30, 0.6);
            border-top: 1px solid var(--accent);
            box-shadow: 0 30px 60px rgba(0,0,0,0.8), 0 0 30px rgba(212,175,55,0.2);
        }

        .card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            border-radius: 12px;
            background: #222;
            transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card:hover img { transform: scale(1.08); }
        .card .img-wrapper { overflow: hidden; border-radius: 12px; margin-bottom: 16px; position: relative; }

        .card h3 {
            margin-top: 8px;
            font-size: 15px;
            font-weight: 800;
            min-height: 44px;
            color: #FFFFFF;
            line-height: 1.4;
            transition: all 0.3s;
        }
        .card:hover h3 { 
            background: linear-gradient(90deg, var(--accent), #fef08a, var(--accent));
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientText 2s linear infinite;
        }

        .price {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 17px;
            color: var(--accent);
        }

        .price del {
            color: rgba(255,255,255,0.4);
            font-size: 12px;
            font-weight: 500;
            -webkit-text-fill-color: initial;
        }

        .discount {
            color: #111;
            font-size: 11px;
            background: linear-gradient(135deg, var(--accent), #fef08a);
            border-radius: 4px;
            padding: 4px 8px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(212,175,55,0.3);
        }

        .view-all {
            margin: 50px auto 16px;
            width: max-content;
            background: linear-gradient(135deg, var(--accent) 0%, #fef08a 50%, var(--accent) 100%);
            background-size: 200% auto; animation: gradientShift 4s ease infinite, pulseGlow 3s infinite alternate;
            border: 2px solid var(--accent);
            border-radius: 999px;
            padding: 16px 40px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 800;
            color: #111111;
            display: block;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 10px 30px rgba(212,175,55,0.3);
        }
        .view-all:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 15px 40px rgba(212,175,55,0.4); }

        .reviews {
            margin-top: 64px;
        }

        .reviews h2 {
            text-align: left;
            margin-bottom: 20px;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        /* --- GLASS REVIEWS --- */
        .review {
            background: rgba(20, 20, 20, 0.4); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: var(--radius); padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1); position: relative; overflow: hidden;
            animation: floatCard 8s infinite alternate-reverse;
        }
        .review::before {
            content: "“"; position: absolute; top: -20px; right: 20px;
            font-size: 140px; font-family: "Symphony", sans-serif;
            color: rgba(212, 175, 55, 0.1); line-height: 1; transition: color 0.5s;
        }
        .review:hover { transform: translateY(-15px) scale(1.03); background: rgba(30,30,30,0.6); border-top-color: var(--accent); box-shadow: 0 30px 60px rgba(0,0,0,0.8), 0 0 30px rgba(212,175,55,0.15); }
        .review:hover::before { color: rgba(212, 175, 55, 0.3); }

        .review .stars {
            color: var(--accent);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .review h4 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #FFFFFF;
            font-weight: 800;
        }

        .review p {
            color: #A1A1AA;
            font-size: 14px;
            line-height: 1.6;
        }

        /* --- GLASS FOOTER --- */
        footer {
            margin-top: 100px; margin-bottom: 40px;
            display: grid; grid-template-columns: 1.4fr repeat(2, 1fr); gap: 40px;
            padding: 60px;
            background: rgba(20, 20, 20, 0.4); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-top: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 30px; box-shadow: 0 30px 60px rgba(0,0,0,0.6), inset 0 0 30px rgba(255,255,255,0.02);
            position: relative; overflow: hidden;
        }
        footer::after {
            content: ''; position: absolute; bottom: 0; right: 0; width: 50%; height: 100%;
            background: radial-gradient(circle at bottom right, rgba(45,90,64,0.15), transparent 60%); pointer-events: none;
        }

        footer h5 { margin-bottom: 20px; font-size: 14px; color: var(--accent); letter-spacing: 2px; text-transform: uppercase; font-weight: 800; }
        footer p { color: #A1A1AA; line-height: 1.8; font-size: 14px; margin-top: 16px; max-width: 90%; }
        footer ul { list-style: none; display: grid; gap: 16px; }
        footer ul li { color: #E2E8F0; font-size: 14px; font-weight: 500; transition: color 0.3s; cursor: pointer; }
        footer ul li:hover { color: var(--accent); }

        .copyright {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #777;
            font-size: 12px;
            grid-column: 1 / -1;
            text-align: center;
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
                <h1>Temukan Baju <span>Thrift</span> Sesuai Gayamu</h1>
                <p>
                    Recloth adalah platform e-commerce berbasis web yang menawarkan pakaian thrift berkualitas pilihan.
                    Setiap produk dikurasi dengan teliti, sehingga kamu bisa tampil stylish dengan harga terjangkau dan
                    pengalaman belanja yang nyaman.
                </p>
                <a class="hero-btn" href="src/user/catalog.php">Belanja Sekarang</a>

                <div class="hero-stats">
                    <div>
                        <strong>200+</strong>
                        <span>Produk Terkurasi</span>
                    </div>
                    <div>
                        <strong>2K+</strong>
                        <span>Pelanggan</span>
                    </div>
                    <div>
                        <strong>30K+</strong>
                        <span>Pesanan Selesai</span>
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
                            <div class="img-wrapper">
                                <img src="<?= htmlspecialchars(productImage((string) ($product['image'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
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
                            <div class="img-wrapper">
                                <img src="<?= htmlspecialchars(productImage((string) ($product['image'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
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