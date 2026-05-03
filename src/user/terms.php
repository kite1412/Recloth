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
    <title>Syarat & Ketentuan - RECLOTH</title>
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

            <h1 class="brand-font text-3xl md:text-4xl mb-2">Syarat & Ketentuan</h1>
            <p class="text-gray-500 mb-10">Pembaruan terakhir: <?= date('d M Y') ?></p>

            <div class="prose prose-zinc max-w-none space-y-8">
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">1. Penerimaan Syarat</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Dengan mengakses dan menggunakan situs web Recloth, Anda setuju untuk terikat oleh Syarat dan Ketentuan ini, serta semua hukum dan peraturan yang berlaku. Jika Anda tidak setuju dengan ketentuan ini, Anda dilarang menggunakan situs ini.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">2. Akun Pengguna</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Untuk melakukan pembelian, Anda mungkin diharuskan untuk membuat akun. Anda bertanggung jawab untuk menjaga kerahasiaan kata sandi akun Anda dan membatasi akses ke komputer atau perangkat Anda. Anda setuju untuk menerima tanggung jawab atas semua aktivitas yang terjadi di bawah akun Anda.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">3. Produk dan Harga</h2>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Sebagai platform thrift, semua produk kami adalah barang pre-loved kecuali dinyatakan lain. Kami berusaha seakurat mungkin dalam mendeskripsikan kondisi produk. Namun, kami tidak menjamin bahwa deskripsi produk sepenuhnya bebas dari kesalahan.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Harga produk dapat berubah sewaktu-waktu tanpa pemberitahuan. Harga yang berlaku adalah harga yang tertera saat Anda melakukan checkout.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">4. Pembayaran dan Pengiriman</h2>
                    <p class="text-gray-700 leading-relaxed mb-3">
                        Kami menerima berbagai metode pembayaran yang sah. Pesanan hanya akan diproses dan dikirim setelah pembayaran dikonfirmasi lunas oleh sistem kami.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Waktu pengiriman bergantung pada layanan kurir yang dipilih dan lokasi tujuan Anda. Recloth tidak bertanggung jawab atas keterlambatan pengiriman yang disebabkan oleh pihak kurir atau kejadian di luar kendali kami.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">5. Pengembalian dan Penukaran</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Mengingat sifat produk pakaian thrift, semua penjualan bersifat final. Kami hanya menerima pengembalian dana atau penukaran barang jika terjadi kesalahan pengiriman produk dari pihak Recloth (misalnya salah warna, ukuran, atau cacat fatal yang tidak disebutkan di deskripsi). Klaim harus dilakukan maksimal 2x24 jam sejak pesanan diterima.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">6. Perubahan Ketentuan</h2>
                    <p class="text-gray-700 leading-relaxed">
                        Recloth berhak memperbarui, mengubah, atau mengganti Syarat dan Ketentuan ini kapan saja dengan memublikasikan pembaruan di situs web kami. Merupakan tanggung jawab Anda untuk memeriksa halaman ini secara berkala.
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
