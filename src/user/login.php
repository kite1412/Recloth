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
    <title>Log in - Recloth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Symphony';
            src: url('/public/fonts/symphony-pro-regular.otf') format('opentype');
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
            font-family: "Symphony";
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

        /* Button */
        .btn-login {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(24, 24, 27, 0.25);
            background: var(--primary-hover);
        }
        
        .btn-login:hover::after {
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
    
    <div class="w-full max-w-6xl bg-[var(--white)] backdrop-blur-2xl border border-[var(--glass-border)] rounded-3xl overflow-hidden shadow-2xl flex flex-col lg:flex-row min-h-[600px] lg:min-h-[700px]">
        
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
                <h1 class="brand-font text-7xl xl:text-8xl leading-tight mb-6 tracking-tight pl-2">Recloth</h1>
                <p class="text-lg text-white/80 max-w-md font-light leading-relaxed">
                    Recloth adalah platform e-commerce yang menyediakan pakaian thrift berkualitas, dikurasi untuk gaya stylish dengan harga terjangkau.
                </p>
            </div>

            <div class="relative z-10 floating-element">
                <div class="bg-white/10 backdrop-blur-lg border border-white/20 p-6 rounded-2xl max-w-sm">
                    <p class="text-sm font-medium italic text-white/90 mb-4">"Fashion pudar, gaya abadi. Temukan harta karun tersembunyimu di Recloth."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Dipercaya oleh</p>
                            <p class="text-xs text-white/70">5,000+ Pelanggan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Form -->
        <div class="lg:w-7/12 flex flex-col justify-center items-center p-8 sm:p-12 md:p-16 relative">
            <!-- Mobile back button -->
            <a href="../../index.php" class="lg:hidden absolute top-6 left-6 inline-flex items-center gap-2 text-sm font-medium text-gray-400 hover:text-[var(--accent)] transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Kembali
            </a>

            <div class="w-full max-w-md">
                <div class="text-center lg:text-left mb-10 mt-8 lg:mt-0">
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white mb-3">Selamat Datang</h2>
                    <p class="text-[var(--muted)] font-medium">Masuk untuk melanjutkan ke akun Recloth-mu.</p>
                </div>

                <div id="errorMessage" class="hidden mb-6 rounded-xl bg-[rgba(239,68,68,0.1)] border border-[rgba(239,68,68,0.2)] px-4 py-3 text-sm font-medium text-red-400 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span></span>
                </div>
                
                <div id="successMessage" class="hidden mb-6 rounded-xl bg-[rgba(16,185,129,0.1)] border border-[rgba(16,185,129,0.2)] px-4 py-3 text-sm font-medium text-emerald-400 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span></span>
                </div>

                <form id="loginForm" method="POST" action="../config/auth.php" class="space-y-6">
                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-semibold text-gray-300">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="name@example.com"
                                class="form-input w-full rounded-xl border border-[rgba(255,255,255,0.1)] bg-[rgba(255,255,255,0.05)] pl-11 pr-4 py-3.5 text-sm text-white placeholder-gray-500 outline-none"
                                required
                            >
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-gray-300">Password</label>
                            <a href="forgot_password.php" class="text-sm font-semibold text-[var(--muted)] hover:text-[var(--accent)] hover-underline">Lupa password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Minimal 8 karakter"
                                class="form-input w-full rounded-xl border border-[rgba(255,255,255,0.1)] bg-[rgba(255,255,255,0.05)] pl-11 pr-4 py-3.5 text-sm text-white placeholder-gray-500 outline-none"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-login w-full rounded-xl py-3.5 text-sm font-bold text-[#111] shadow-[0_4px_15px_rgba(212,175,55,0.3)] mt-8 flex justify-center items-center gap-2 group hover:shadow-[0_8px_25px_rgba(212,175,55,0.5)] bg-gradient-to-r from-[var(--accent)] to-[#fef08a] border-none">
                        <span>Log in</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <div class="mt-10 pt-6 border-t border-[rgba(255,255,255,0.1)] flex flex-col gap-4">
                    <p class="text-center text-sm font-medium text-[var(--muted)]">
                        Belum punya akun?
                        <a href="register.php" class="font-bold text-[var(--accent)] hover-underline ml-1">Daftar</a>
                    </p>
                    <a href="../../index.php" class="w-full text-center text-sm font-semibold text-gray-300 hover:text-[var(--accent)] py-3 rounded-xl border border-[rgba(255,255,255,0.1)] hover:bg-[rgba(255,255,255,0.05)] transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorMessage = document.getElementById('errorMessage');
            const errorText = errorMessage.querySelector('span');

            errorMessage.classList.add('hidden');
            errorText.textContent = '';

            if (!email || !password) {
                e.preventDefault();
                errorText.textContent = 'Email dan password harus diisi';
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

            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...';
            btn.classList.add('opacity-80', 'cursor-not-allowed');
            
            return true;
        });

        document.getElementById('email').addEventListener('input', function() {
            document.getElementById('errorMessage').classList.add('hidden');
        });

        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('errorMessage').classList.add('hidden');
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
                window.location.href = '../../index.php';
            }, 2000);
        }
    </script>
</body>
</html>
