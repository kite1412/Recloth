<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - RECLOTH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #F4F4F5;
            --black: #09090B;
            --white: #FFFFFF;
            --primary: #18181B;
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

        .btn-submit {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(24, 24, 27, 0.25);
            background: var(--black);
        }
        
        .btn-submit:hover::after {
            left: 100%;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 md:p-6 lg:p-8">
    
    <div class="w-full max-w-md bg-white rounded-3xl overflow-hidden shadow-2xl p-8 sm:p-12 relative">
        <a href="login.php" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-black transition-colors mb-8">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke Login
        </a>

        <div class="text-center sm:text-left mb-8">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 mb-3">Reset Password</h2>
            <p class="text-gray-500 font-medium text-sm">Masukkan email Anda beserta password baru yang diinginkan.</p>
        </div>

        <div id="errorMessage" class="hidden mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm font-medium text-red-600 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span></span>
        </div>
        
        <div id="successMessage" class="hidden mb-6 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-medium text-green-600 flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span></span>
        </div>

        <form id="resetForm" method="POST" action="../config/reset_password.php" class="space-y-5">
            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-semibold text-gray-700">Email Terdaftar</label>
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

            <div class="space-y-1.5">
                <label for="new_password" class="block text-sm font-semibold text-gray-700">Password Baru</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Minimal 8 karakter"
                        class="form-input w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm text-gray-900 outline-none"
                        required
                    >
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="confirm_password" class="block text-sm font-semibold text-gray-700">Ulangi Password Baru</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Konfirmasi password"
                        class="form-input w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm text-gray-900 outline-none"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn-submit w-full rounded-xl py-3.5 text-sm font-bold text-white shadow-lg mt-6 flex justify-center items-center gap-2 group">
                <span>Ganti Password</span>
                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </form>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const newPassword = document.getElementById('new_password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();
            const errorMessage = document.getElementById('errorMessage');
            const errorText = errorMessage.querySelector('span');

            errorMessage.classList.add('hidden');
            errorText.textContent = '';

            if (!email || !newPassword || !confirmPassword) {
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

            if (newPassword.length < 8) {
                e.preventDefault();
                errorText.textContent = 'Password minimal 8 karakter';
                errorMessage.classList.remove('hidden');
                return false;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                errorText.textContent = 'Password dan Konfirmasi Password tidak cocok';
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
