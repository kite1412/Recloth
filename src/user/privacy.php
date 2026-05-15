<?php
session_start();
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi - RECLOTH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            font-family: "Inter", sans-serif;
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
            font-family: "Archivo Black", sans-serif;
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-4xl mx-auto bg-[var(--white)] backdrop-blur-2xl border border-[var(--glass-border)] rounded-3xl shadow-2xl overflow-hidden">
        <div class="p-8 md:p-12">
            <a href="register.php" class="inline-flex items-center gap-2 text-sm font-medium text-[var(--muted)] hover:text-[var(--accent)] transition-colors mb-8">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Kembali
            </a>

            <h1 class="brand-font text-3xl md:text-4xl text-white mb-2">Kebijakan Privasi</h1>
            <p class="text-[var(--muted)] mb-10">Pembaruan terakhir: <?= date('d M Y') ?></p>

            <div class="prose prose-invert max-w-none space-y-8">
                <section>
                    <h2 class="text-xl font-bold text-white mb-3">1. Informasi yang Kami Kumpulkan</h2>
                    <p class="text-gray-300 leading-relaxed">
                        Kami mengumpulkan informasi dari Anda saat Anda mendaftar di situs kami, menempatkan pesanan, berlangganan newsletter, atau mengisi formulir. Informasi yang kami kumpulkan meliputi namun tidak terbatas pada nama Anda, alamat email, alamat pengiriman, dan nomor telepon.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-white mb-3">2. Penggunaan Informasi</h2>
                    <p class="text-gray-300 leading-relaxed mb-3">
                        Segala informasi yang kami kumpulkan dari Anda dapat digunakan untuk salah satu cara berikut:
                    </p>
                    <ul class="list-disc pl-5 text-gray-300 leading-relaxed space-y-2">
                        <li>Untuk mempersonalisasi pengalaman Anda dan memenuhi kebutuhan individu Anda dengan lebih baik.</li>
                        <li>Untuk meningkatkan situs web dan penawaran kami berdasarkan informasi dan umpan balik yang kami terima.</li>
                        <li>Untuk memproses transaksi dan pengiriman pesanan Anda secara efisien.</li>
                        <li>Untuk mengirimkan email berkala mengenai pembaruan status pesanan, penawaran promosi, atau berita perusahaan lainnya.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-white mb-3">3. Perlindungan Data</h2>
                    <p class="text-gray-300 leading-relaxed">
                        Kami menerapkan berbagai langkah keamanan untuk menjaga keamanan informasi pribadi Anda saat Anda memesan atau memasukkan, menyerahkan, atau mengakses informasi pribadi Anda. Kami menggunakan enkripsi canggih untuk melindungi informasi sensitif yang dikirim secara online, serta melindungi informasi Anda secara offline (database hanya dapat diakses oleh personel yang berwenang).
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-white mb-3">4. Penggunaan Cookies</h2>
                    <p class="text-gray-300 leading-relaxed">
                        Situs kami menggunakan cookie (file kecil yang ditransfer ke hard drive komputer Anda melalui browser Web) yang memungkinkan sistem situs mengenali browser Anda serta menangkap dan mengingat informasi tertentu (seperti menyimpan item di keranjang belanja Anda untuk kunjungan berikutnya).
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-white mb-3">5. Pengungkapan Kepada Pihak Ketiga</h2>
                    <p class="text-gray-300 leading-relaxed">
                        Kami tidak menjual, memperdagangkan, atau menyewakan informasi identitas pribadi Anda kepada pihak luar. Hal ini tidak mencakup pihak ketiga tepercaya yang membantu kami mengoperasikan situs web kami (misalnya, penyedia layanan kurir/pengiriman atau gateway pembayaran terenkripsi), selama pihak-pihak tersebut setuju untuk merahasiakan informasi ini.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-white mb-3">6. Persetujuan Anda</h2>
                    <p class="text-gray-300 leading-relaxed">
                        Dengan menggunakan situs web kami, Anda menyetujui kebijakan privasi kami yang tertulis di halaman ini. Jika kami memutuskan untuk mengubah kebijakan privasi kami, kami akan memposting perubahan tersebut di halaman ini.
                    </p>
                </section>
            </div>
            
            <div class="mt-12 pt-8 border-t border-[rgba(255,255,255,0.1)] flex justify-center">
                <a href="register.php" class="bg-gradient-to-r from-[var(--accent)] to-[#fef08a] text-[#111] px-8 py-3 rounded-xl font-bold shadow-[0_4px_15px_rgba(212,175,55,0.3)] hover:shadow-[0_8px_25px_rgba(212,175,55,0.5)] transition-all">
                    Kembali ke Pendaftaran
                </a>
            </div>
        </div>
    </div>
</body>
</html>
