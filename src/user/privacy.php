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
        body {
            background-color: #F4F4F5;
            color: #09090B;
            font-family: "Inter", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .brand-font {
            font-family: "Archivo Black", sans-serif;
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="p-8 md:p-12">
            <a href="register.php" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-black transition-colors mb-8">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Kembali
            </a>

            <h1 class="brand-font text-3xl md:text-4xl mb-2">Kebijakan Privasi</h1>
            <p class="text-gray-500 mb-10">Pembaruan terakhir: <?= date('d M Y') ?></p>

            <div class="prose prose-zinc max-w-none space-y-8">
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">1. Informasi yang Kami Kumpulkan</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Kami mengumpulkan informasi dari Anda saat Anda mendaftar di situs kami, menempatkan pesanan, berlangganan newsletter, atau mengisi formulir. Informasi yang kami kumpulkan meliputi namun tidak terbatas pada nama Anda, alamat email, alamat pengiriman, dan nomor telepon.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">2. Penggunaan Informasi</h2>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Segala informasi yang kami kumpulkan dari Anda dapat digunakan untuk salah satu cara berikut:
                    </p>
                    <ul class="list-disc pl-5 text-gray-700 leading-relaxed space-y-2">
                        <li>Untuk mempersonalisasi pengalaman Anda dan memenuhi kebutuhan individu Anda dengan lebih baik.</li>
                        <li>Untuk meningkatkan situs web dan penawaran kami berdasarkan informasi dan umpan balik yang kami terima.</li>
                        <li>Untuk memproses transaksi dan pengiriman pesanan Anda secara efisien.</li>
                        <li>Untuk mengirimkan email berkala mengenai pembaruan status pesanan, penawaran promosi, atau berita perusahaan lainnya.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">3. Perlindungan Data</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Kami menerapkan berbagai langkah keamanan untuk menjaga keamanan informasi pribadi Anda saat Anda memesan atau memasukkan, menyerahkan, atau mengakses informasi pribadi Anda. Kami menggunakan enkripsi canggih untuk melindungi informasi sensitif yang dikirim secara online, serta melindungi informasi Anda secara offline (database hanya dapat diakses oleh personel yang berwenang).
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">4. Penggunaan Cookies</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Situs kami menggunakan cookie (file kecil yang ditransfer ke hard drive komputer Anda melalui browser Web) yang memungkinkan sistem situs mengenali browser Anda serta menangkap dan mengingat informasi tertentu (seperti menyimpan item di keranjang belanja Anda untuk kunjungan berikutnya).
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">5. Pengungkapan Kepada Pihak Ketiga</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Kami tidak menjual, memperdagangkan, atau menyewakan informasi identitas pribadi Anda kepada pihak luar. Hal ini tidak mencakup pihak ketiga tepercaya yang membantu kami mengoperasikan situs web kami (misalnya, penyedia layanan kurir/pengiriman atau gateway pembayaran terenkripsi), selama pihak-pihak tersebut setuju untuk merahasiakan informasi ini.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">6. Persetujuan Anda</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Dengan menggunakan situs web kami, Anda menyetujui kebijakan privasi kami yang tertulis di halaman ini. Jika kami memutuskan untuk mengubah kebijakan privasi kami, kami akan memposting perubahan tersebut di halaman ini.
                    </p>
                </section>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-100 flex justify-center">
                <a href="register.php" class="bg-black text-white px-8 py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors">
                    Kembali ke Pendaftaran
                </a>
            </div>
        </div>
    </div>
</body>
</html>
