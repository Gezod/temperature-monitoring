<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5B63D3;
            --secondary: #7C83DB;
            --accent: #00BCD4;
            --accent-light: #4DD0E1;
            --surface: #FFFFFF;
            --text-primary: #2D3748;
            --text-secondary: #718096;
            --border: #E2E8F0;
            --error: #EF4444;
            --error-bg: #FEE2E2;
            --shadow-sm: 0 2px 8px rgba(91, 99, 211, 0.08);
            --shadow-md: 0 8px 24px rgba(91, 99, 211, 0.12);
            --shadow-lg: 0 16px 48px rgba(91, 99, 211, 0.16);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, #5B63D3 0%, #7C83DB 50%, #6B73D6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 188, 212, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(77, 208, 225, 0.15) 0%, transparent 50%);
            animation: gradientMove 20s ease infinite;
            pointer-events: none;
        }

        @keyframes gradientMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-5%, -5%) rotate(5deg); }
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .brand-section {
            color: var(--surface);
            animation: slideInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .brand-section h1 {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 400;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .brand-section .accent-text {
            color: var(--accent);
            font-style: italic;
        }

        .brand-section p {
            font-size: 1.125rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.7);
            max-width: 480px;
        }

        .login-card {
            background: var(--surface);
            border-radius: 24px;
            padding: 48px;
            box-shadow: var(--shadow-lg);
            position: relative;
            animation: slideInRight 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--accent) 0%, var(--accent-light) 100%);
            border-radius: 24px 24px 0 0;
        }

        .login-header {
            margin-bottom: 36px;
        }

        .login-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }

        .alert {
            background: var(--error-bg);
            color: var(--error);
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.9375rem;
            border-left: 4px solid var(--error);
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: 0.01em;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            font-size: 1rem;
            font-family: 'Manrope', sans-serif;
            color: var(--text-primary);
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .form-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.15);
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }

        .btn-login {
            width: 100%;
            padding: 16px 24px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Manrope', sans-serif;
            color: var(--surface);
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(0, 188, 212, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 188, 212, 0.4);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-footer {
            margin-top: 24px;
            text-align: center;
        }

        .form-footer a {
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .form-footer a:hover {
            color: var(--accent-light);
            text-decoration: underline;
        }

        /* Decorative elements */
        .decorative-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .circle-1 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 188, 212, 0.15) 0%, transparent 70%);
            top: -200px;
            right: -200px;
            animation: float 8s ease-in-out infinite;
        }

        .circle-2 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(77, 208, 225, 0.12) 0%, transparent 70%);
            bottom: -150px;
            left: -150px;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .container {
                grid-template-columns: 1fr;
                gap: 40px;
                max-width: 480px;
            }

            .brand-section {
                text-align: center;
            }

            .brand-section p {
                margin: 0 auto;
            }

            .login-card {
                padding: 36px 28px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .brand-section h1 {
                font-size: 2rem;
            }

            .login-card {
                padding: 28px 20px;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Loading state animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }
    </style>
</head>
<body>
    <div class="decorative-circle circle-1"></div>
    <div class="decorative-circle circle-2"></div>
    
    <div class="container">
        <div class="brand-section">
            <h1>
                Selamat Datang <span class="accent-text">Kembali</span>
            </h1>
            <p>
                Masuk ke akun Anda untuk mengakses dashboard dan fitur-fitur lengkap sistem kami.
            </p>
        </div>

        <div class="login-card">
            <div class="login-header">
                <h2>Masuk</h2>
                <p>Gunakan kredensial Anda untuk melanjutkan</p>
            </div>

            @if ($errors->any())
                <div class="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/login" id="loginForm">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        class="form-input" 
                        placeholder="nama@email.com"
                        required
                        autocomplete="email"
                        value="{{ old('email') }}"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="form-input" 
                        placeholder="Masukkan password Anda"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    Masuk Sekarang
                </button>

                <div class="form-footer">
                    <a href="/forgot-password">Lupa password?</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Enhance form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.classList.add('loading');
            btn.textContent = '';
        });

        // Add input validation feedback
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#10B981';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(16, 185, 129)') {
                    this.style.borderColor = '';
                }
            });
        });
    </script>
</body>
</html>