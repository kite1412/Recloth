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
            src: url('/public/fonts/symphony-pro-regular.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --bg: #F4F4F5;
            --black: #09090B;
            --white: #FFFFFF;
            --muted: #71717A;
            --primary: #18181B;
            --line: #E4E4E7;
        }

        body {
            background-color: var(--bg);
            color: var(--black);
            font-family: "Montserrat", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .brand-font {
            font-family: "Symphony";
        }

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--line);
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
            transition: all 0.2s;
        }

        .cart-icon:hover {
            background: #f4f4f5;
            transform: translateY(-1px);
        }

        .cart-icon svg {
            width: 19px;
            height: 19px;
        }

        .form-input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #FAFAFA;
        }

        .form-input:focus {
            border-color: var(--black);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            background-color: var(--white);
            transform: translateY(-1px);
        }

        .btn-update {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--primary);
            position: relative;
            overflow: hidden;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(24, 24, 27, 0.2);
            background: var(--black);
        }

        .profile-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }
    </style>
</head>
<body class="min-h-screen bg-[#F8F8F8]">
    
    <!-- Navbar -->
    <nav class="navbar sticky top-0 z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="../../index.php" class="brand-font text-3xl text-black">Recloth</a>
            
            <div class="flex items-center gap-4">
                <a href="cart.php?tab=cart" class="cart-icon" aria-label="Keranjang">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M3 4H5L7.3 14.2C7.5 15.1 8.3 15.8 9.2 15.8H17.8C18.7 15.8 19.5 15.1 19.7 14.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9.5" cy="19" r="1.2" fill="currentColor"/>
                        <circle cx="17.5" cy="19" r="1.2" fill="currentColor"/>
                    </svg>
                </a>
                <a href="../config/logout.php" class="cart-icon" aria-label="Logout" style="color: #d24e4e;">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 py-12">
        <div class="mb-10">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-black transition-colors mb-6 group">
                <svg class="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Kembali
            </a>
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 mb-2">Profil Saya</h1>
            <p class="text-gray-500 font-medium">Kelola informasi akun dan alamat pengiriman Anda.</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-8 rounded-2xl bg-green-50 border border-green-200 px-6 py-4 text-sm font-medium text-green-700 flex items-center gap-3">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Profil berhasil diperbarui!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-8 rounded-2xl bg-red-50 border border-red-200 px-6 py-4 text-sm font-medium text-red-700 flex items-center gap-3">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Sidebar Info -->
            <div class="md:col-span-1">
                <div class="profile-card p-8 flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center mb-6 border-4 border-white shadow-sm">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="text-sm text-gray-500 mb-6"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <div class="w-full pt-6 border-t border-gray-100 space-y-4">
                        <a href="cart.php?tab=orders" class="flex items-center justify-between text-sm font-semibold text-gray-700 hover:text-black transition-colors group">
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
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Informasi Pribadi
                    </h2>
                    <form action="../config/update_profile.php" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-1.5">
                                <label for="name" class="block text-sm font-semibold text-gray-700">Nama Lengkap</label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="<?= htmlspecialchars($user['name']) ?>"
                                    class="form-input w-full rounded-xl border border-gray-200 px-4 py-3.5 text-sm text-gray-900 outline-none"
                                    required
                                >
                            </div>

                            <div class="space-y-1.5 opacity-60">
                                <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    value="<?= htmlspecialchars($user['email']) ?>"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-3.5 text-sm text-gray-900 bg-gray-100 cursor-not-allowed"
                                    disabled
                                >
                                <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah.</p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="btn-update w-full rounded-xl py-4 text-sm font-bold text-white shadow-lg flex justify-center items-center gap-2 group">
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
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Alamat Pengiriman
                        </h2>
                        <button onclick="document.getElementById('addressModal').classList.remove('hidden')" class="text-sm font-bold text-black hover:underline flex items-center gap-1">
                            <span>+ Tambah Alamat</span>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <?php if (empty($addresses)): ?>
                            <div class="text-center py-8 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                <p class="text-gray-500 text-sm">Belum ada alamat yang tersimpan.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($addresses as $addr): ?>
                                <div class="p-5 rounded-2xl border <?= $addr['is_default'] ? 'border-black bg-gray-50' : 'border-gray-100' ?> flex justify-between items-start gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wider text-gray-900"><?= htmlspecialchars($addr['label']) ?></span>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="bg-black text-white text-[10px] px-2 py-0.5 rounded-full font-bold">UTAMA</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($addr['address'])) ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <?php if (!$addr['is_default']): ?>
                                            <form action="../config/address_actions.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="set_default">
                                                <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                                <button type="submit" class="text-xs font-bold text-gray-400 hover:text-black transition-colors">Utamakan</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="../config/address_actions.php" method="POST" class="inline" onsubmit="return confirm('Hapus alamat ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                            <button type="submit" class="text-xs font-bold text-red-400 hover:text-red-600 transition-colors">Hapus</button>
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
    <div id="addressModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-6 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-[32px] w-full max-w-lg p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold">Tambah Alamat Baru</h3>
                <button onclick="document.getElementById('addressModal').classList.add('hidden')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path>
                    </svg>
                </button>
            </div>
            <form action="../config/address_actions.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-700">Label Alamat</label>
                        <input type="text" name="label" placeholder="Rumah, Kantor, dll" class="form-input w-full rounded-xl border border-gray-200 px-4 py-3.5 text-sm" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-700">Alamat Lengkap</label>
                        <textarea name="address" rows="4" placeholder="Jl. Contoh No. 123..." class="form-input w-full rounded-xl border border-gray-200 px-4 py-3.5 text-sm resize-none" required></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_default" id="is_default" class="w-4 h-4 rounded border-gray-300">
                        <label for="is_default" class="text-sm font-medium text-gray-600">Jadikan alamat utama</label>
                    </div>
                </div>
                <button type="submit" class="w-full bg-black text-white py-4 rounded-xl font-bold text-sm shadow-xl hover:translate-y-[-2px] transition-all">
                    Simpan Alamat
                </button>
            </form>
        </div>
    </div>


    <footer class="max-w-4xl mx-auto px-6 py-12 text-center text-gray-400 text-sm">
        <p>&copy; <?= date('Y') ?> Recloth. Semua hak dilindungi.</p>
    </footer>

</body>
</html>
