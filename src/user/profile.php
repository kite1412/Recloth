<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi halaman: hanya untuk user yang sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Ambil data user terbaru dari database
try {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // Ambil daftar alamat
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

$title = "Profil Saya - Recloth";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: "Montserrat", sans-serif;
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

        .brand-font {
            font-family: "Symphony";
        }

        .navbar {
            background: rgba(7, 7, 7, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .cart-icon {
            width: 40px;
            height: 40px;
            border: 1px solid var(--glass-border);
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text);
            background: rgba(255,255,255,0.05);
            transition: all 0.2s;
        }

        .cart-icon:hover {
            background: rgba(212,175,55,0.1);
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .cart-icon svg {
            width: 19px;
            height: 19px;
        }

        .form-input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: rgba(255,255,255,0.05);
            color: #FFFFFF;
            border-color: rgba(255,255,255,0.1);
        }

        .form-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(212,175,55,0.15);
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }

        .btn-update {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(to right, var(--accent), #fef08a);
            color: #111;
            border: none;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212,175,55,0.5);
        }

        .profile-card {
            background: var(--white);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="min-h-screen">
    
    <!-- Navbar -->
    <nav class="navbar sticky top-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="../../index.php" class="brand-font text-3xl text-white">Recloth</a>
            
            <div class="flex items-center gap-4">
                <a href="cart.php?tab=cart" class="cart-icon" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor"/>
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor"/>
                    </svg>
                </a>
                <a href="../config/logout.php" class="cart-icon" aria-label="Logout" style="color: #ef4444;">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 py-12 relative z-10">
        <div class="mb-10">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-sm font-medium text-[var(--muted)] hover:text-[var(--accent)] transition-colors mb-6 group">
                <svg class="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Kembali
            </a>
            <h1 class="text-4xl font-bold tracking-tight text-white mb-2">Profil Saya</h1>
            <p class="text-[var(--muted)] font-medium">Kelola informasi akun dan alamat pengiriman Anda.</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-8 rounded-2xl bg-[rgba(16,185,129,0.1)] border border-[rgba(16,185,129,0.2)] px-6 py-4 text-sm font-medium text-emerald-400 flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Profil berhasil diperbarui!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-8 rounded-2xl bg-[rgba(239,68,68,0.1)] border border-[rgba(239,68,68,0.2)] px-6 py-4 text-sm font-medium text-red-400 flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar Info -->
            <div class="md:col-span-1">
                <div class="profile-card p-8 flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-full bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-12 h-12 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white"><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="text-sm text-[var(--muted)] mb-6"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <div class="w-full pt-6 border-t border-[rgba(255,255,255,0.1)] space-y-4">
                        <a href="cart.php?tab=orders" class="flex items-center justify-between text-sm font-semibold text-gray-300 hover:text-[var(--accent)] transition-colors group">
                            <span>Pesanan Saya</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Edit Form & Addresses -->
            <div class="md:col-span-2 space-y-8">
                <!-- Info Personal -->
                <div class="profile-card p-8 sm:p-10">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[var(--muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Informasi Pribadi
                    </h2>
                    <form action="../config/update_profile.php" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-1.5">
                                <label for="name" class="block text-sm font-semibold text-gray-300">Nama Lengkap</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="<?= htmlspecialchars($user['name']) ?>"
                                    class="form-input w-full rounded-xl border border-[rgba(255,255,255,0.1)] px-4 py-3.5 text-sm text-white outline-none"
                                    required
                                >
                            </div>

                            <div class="space-y-1.5 opacity-60">
                                <label for="email" class="block text-sm font-semibold text-gray-300">Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    value="<?= htmlspecialchars($user['email']) ?>"
                                    class="w-full rounded-xl border border-[rgba(255,255,255,0.1)] px-4 py-3.5 text-sm text-gray-400 bg-[rgba(255,255,255,0.02)] cursor-not-allowed"
                                    disabled
                                >
                                <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah.</p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="btn-update w-full rounded-xl py-4 text-sm font-bold shadow-[0_4px_15px_rgba(212,175,55,0.3)] flex justify-center items-center gap-2 group">
                                <span>Simpan Nama</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Alamat -->
                <div class="profile-card p-8 sm:p-10">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-[var(--muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Alamat Pengiriman
                        </h2>
                        <button onclick="document.getElementById('addressModal').classList.remove('hidden')" class="text-sm font-bold text-[var(--accent)] hover:underline flex items-center gap-1">
                            <span>+ Tambah Alamat</span>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <?php if (empty($addresses)): ?>
                            <div class="text-center py-8 bg-[rgba(255,255,255,0.02)] rounded-2xl border border-dashed border-[rgba(255,255,255,0.1)]">
                                <p class="text-[var(--muted)] text-sm">Belum ada alamat yang tersimpan.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $addr): ?>
                                <div class="p-5 rounded-2xl border <?= $addr['is_default'] ? 'border-[var(--accent)] bg-[rgba(212,175,55,0.05)]' : 'border-[rgba(255,255,255,0.1)]' ?> flex justify-between items-start gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wider text-white"><?= htmlspecialchars($addr['label']) ?></span>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="bg-[var(--accent)] text-[#111] text-[10px] px-2 py-0.5 rounded-full font-bold">UTAMA</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-[var(--muted)] leading-relaxed"><?= nl2br(htmlspecialchars($addr['address'])) ?></p>
                                        <?php if (!empty($addr['zip_code'])): ?>
                                            <p class="text-xs text-gray-500 mt-1 font-medium">Kode Pos: <?= htmlspecialchars($addr['zip_code']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex gap-2">
                                        <?php if (!$addr['is_default']): ?>
                                            <form action="../config/address_actions.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="set_default">
                                                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                                <button type="submit" class="text-xs font-bold text-gray-500 hover:text-[var(--accent)] transition-colors">Utamakan</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="../config/address_actions.php" method="POST" class="inline" onsubmit="return confirm('Hapus alamat ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                            <button type="submit" class="text-xs font-bold text-red-400 hover:text-red-500 transition-colors">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Alamat -->
    <div id="addressModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm">
        <div class="bg-[rgba(20,20,20,0.95)] border border-[rgba(255,255,255,0.1)] rounded-[32px] w-full max-w-lg p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white">Tambah Alamat Baru</h3>
                <button onclick="document.getElementById('addressModal').classList.add('hidden')" class="p-2 text-gray-400 hover:text-white hover:bg-[rgba(255,255,255,0.1)] rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path>
                    </svg>
                </button>
            </div>
            <form action="../config/address_actions.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-300">Label Alamat</label>
                        <input type="text" name="label" placeholder="Rumah, Kantor, dll" class="form-input w-full rounded-xl px-4 py-3.5 text-sm outline-none" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-300">Alamat Lengkap</label>
                        <textarea name="address" rows="4" placeholder="Jl. Contoh No. 123..." class="form-input w-full rounded-xl px-4 py-3.5 text-sm resize-none outline-none" required></textarea>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-300">Kode Pos</label>
                        <input type="text" name="zip_code" placeholder="Contoh: 12345" pattern="[0-9]*" inputmode="numeric" maxlength="10" class="form-input w-full rounded-xl px-4 py-3.5 text-sm outline-none" required>
                    </div>
                    <div class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_default" id="is_default" class="w-4 h-4 rounded border-[rgba(255,255,255,0.2)] bg-[rgba(255,255,255,0.05)] text-[var(--accent)] focus:ring-[var(--accent)] accent-[var(--accent)]">
                        <label for="is_default" class="text-sm font-medium text-[var(--muted)] cursor-pointer">Jadikan alamat utama</label>
                    </div>
                </div>
                <button type="submit" class="btn-update w-full py-4 rounded-xl font-bold text-sm shadow-[0_4px_15px_rgba(212,175,55,0.3)] transition-all">
                    Simpan Alamat
                </button>
            </form>
        </div>
    </div>


    <footer class="max-w-4xl mx-auto px-6 py-12 text-center text-gray-500 text-sm relative z-10">
        <p>&copy; <?= date('Y') ?> Recloth. Semua hak dilindungi.</p>
    </footer>

</body>
</html>
