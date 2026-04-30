<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - RECLOTH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #F4F4F5; /* zinc-100 */
            --black: #09090B; /* zinc-950 */
            --white: #FFFFFF;
            --muted: #71717A; /* zinc-500 */
            --primary: #18181B; /* zinc-900 */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg);
            color: var(--black);
            font-family: "Inter", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .brand-font {
            font-family: "Archivo Black", sans-serif;
        }

        /* Hero Image Section */
        .hero-section {
            background-image: url('https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hero-overlay {
            background: linear-gradient(
                135deg, 
                rgba(9, 9, 11, 0.9) 0%, 
                rgba(9, 9, 11, 0.4) 100%
            );
            backdrop-filter: blur(2px);
        }

        /* Form Inputs */
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

        /* Button */
        .btn-register {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(24, 24, 27, 0.25);
            background: var(--black);
        }
        
        .btn-register:hover::after {
            left: 100%;
        }

        /* Link animation */
        .hover-underline {
            position: relative;
            text-decoration: none;
        }
        
        .hover-underline::after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 1px;
            bottom: 0;
            left: 0;
            background-color: currentColor;
            transform-origin: bottom right;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-underline:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        /* Float animation for the quote */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 md:p-6 lg:p-8">
    
    <div class="w-full max-w-6xl bg-white rounded-3xl overflow-hidden shadow-2xl flex flex-col lg:flex-row min-h-[650px] lg:min-h-[750px]">
        
        <!-- Left Side: Hero Image -->
        <div class="hero-section lg:w-5/12 hidden lg:flex flex-col justify-between relative text-white p-12">
            <div class="hero-overlay absolute inset-0"></div>
            
            <div class="relative z-10">
                <a href="../../index.php" class="inline-flex items-center gap-2 text-sm font-medium text-white/80 hover:text-white transition-colors group">
                    <svg class="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Kembali ke Beranda
                </a>
            </div>

            <div class="relative z-10 mt-auto mb-auto">
                <h1 class="brand-font text-5xl xl:text-6xl leading-tight mb-6 tracking-tight">RECLOTH</h1>
                <p class="text-lg text-white/80 max-w-md font-light leading-relaxed">
                    Recloth adalah platform e-commerce yang menyediakan pakaian thrift berkualitas, dikurasi untuk gaya stylish dengan harga terjangkau.
                </p>
            </div>

            <div class="relative z-10 floating-element">
                <div class="bg-white/10 backdrop-blur-lg border border-white/20 p-6 rounded-2xl max-w-sm">
                    <p class="text-sm font-medium italic text-white/90 mb-4">"Bergabunglah dengan ribuan pencinta thrifting lainnya. Gaya keren nggak harus mahal."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Komunitas Tumbuh</p>
                            <p class="text-xs text-white/70">Lebih dari 10.000 anggota</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Form -->
        <div class="lg:w-7/12 flex flex-col justify-center items-center p-8 sm:p-12 md:p-16 relative bg-white">
            <!-- Mobile back button -->
            <a href="../../index.php" class="lg:hidden absolute top-6 left-6 inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-black transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Kembali
            </a>

            <div class="w-full max-w-md">
                <div class="text-center lg:text-left mb-8 mt-8 lg:mt-0">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 mb-3">Buat Akun Baru</h2>
                    <p class="text-gray-500 font-medium">Lengkapi data di bawah ini untuk bergabung dengan Recloth.</p>
                </div>

                <div id="errorMessage" class="hidden mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm font-medium text-red-600 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span></span>
                </div>
                
                <div id="successMessage" class="hidden mb-6 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-medium text-green-600 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span></span>
                </div>

                <form id="registerForm" method="POST" action="../config/register_process.php" class="space-y-5">
                    <div class="space-y-1.5">
                        <label for="name" class="block text-sm font-semibold text-gray-700">Nama Lengkap</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Nama sesuai identitas"
                                class="form-input w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm text-gray-900 outline-none"
                                required
                            >
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="name@example.com"
                                class="form-input w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm text-gray-900 outline-none"
                                required
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Minimal 8 karakter"
                                    class="form-input w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm text-gray-900 outline-none"
                                    required
                                >
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label for="password_confirm" class="block text-sm font-semibold text-gray-700">Ulangi Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    placeholder="Konfirmasi"
                                    class="form-input w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm text-gray-900 outline-none"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-start gap-3">
                            <div class="pt-0.5">
                                <input type="checkbox" id="terms" class="w-4 h-4 rounded border-gray-300 text-black focus:ring-black accent-black">
                            </div>
                            <span class="text-sm text-gray-500 leading-relaxed">
                                Dengan mendaftar, saya menyetujui <a href="terms.php" target="_blank" class="font-semibold text-black hover:underline">Syarat & Ketentuan</a> serta <a href="privacy.php" target="_blank" class="font-semibold text-black hover:underline">Kebijakan Privasi</a> yang berlaku.
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-register w-full rounded-xl py-3.5 text-sm font-bold text-white shadow-lg mt-6 flex justify-center items-center gap-2 group">
                        <span>Daftar Sekarang</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-center text-sm font-medium text-gray-500">
                        Sudah punya akun?
                        <a href="login.php" class="font-bold text-black hover-underline ml-1">Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const passwordConfirm = document.getElementById('password_confirm').value.trim();
            const terms = document.getElementById('terms').checked;
            const errorMessage = document.getElementById('errorMessage');
            const errorText = errorMessage.querySelector('span');

            errorMessage.classList.add('hidden');
            errorText.textContent = '';

            if (!name || !email || !password || !passwordConfirm) {
                e.preventDefault();
                errorText.textContent = 'Semua field harus diisi';
                errorMessage.classList.remove('hidden');
                return false;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                errorText.textContent = 'Format email tidak valid';
                errorMessage.classList.remove('hidden');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                errorText.textContent = 'Password minimal 8 karakter';
                errorMessage.classList.remove('hidden');
                return false;
            }

            if (password !== passwordConfirm) {
                e.preventDefault();
                errorText.textContent = 'Password dan Konfirmasi Password tidak cocok';
                errorMessage.classList.remove('hidden');
                return false;
            }

            if (!terms) {
                e.preventDefault();
                errorText.textContent = 'Anda harus menyetujui Syarat & Ketentuan';
                errorMessage.classList.remove('hidden');
                return false;
            }

            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...';
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            
            return true;
        });

        // Hide error message on input change
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                document.getElementById('errorMessage').classList.add('hidden');
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            const errorMsg = document.getElementById('errorMessage');
            errorMsg.querySelector('span').textContent = urlParams.get('error');
            errorMsg.classList.remove('hidden');
        }

        if (urlParams.has('success')) {
            const successMsg = document.getElementById('successMessage');
            successMsg.querySelector('span').textContent = urlParams.get('success');
            successMsg.classList.remove('hidden');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        }
    </script>
</body>
</html>
