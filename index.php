<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Peminjaman Alat</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 380px;
            background: white;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            margin: 0 0 10px;
            font-size: 25px;
            color: #1d4ed8;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 28px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 18px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .eye-button {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }

        .eye-button:hover {
            color: #2563eb;
        }

        .eye-button svg {
            width: 21px;
            height: 21px;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-button:hover {
            background: #1d4ed8;
        }

        .error-message {
            padding: 10px;
            margin-bottom: 18px;
            border-radius: 7px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="login-container">
    <h1>Login</h1>

    <p class="subtitle">Aplikasi Peminjaman Alat</p>

    <?php if (isset($_GET["error"])): ?>
        <div class="error-message">
            Username atau password salah. Silakan coba lagi.
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Masukkan username"
                autocomplete="username"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    id="togglePassword"
                    class="eye-button"
                    aria-label="Lihat password"
                    title="Lihat password"
                >
                    <svg
                        id="eyeIcon"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="login-button">
            Masuk
        </button>

    </form>
</div>

<script>
    const password = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const eyeIcon = document.getElementById("eyeIcon");

    togglePassword.addEventListener("click", function () {
        const sedangTersembunyi = password.type === "password";

        password.type = sedangTersembunyi ? "text" : "password";

        togglePassword.setAttribute(
            "aria-label",
            sedangTersembunyi ? "Sembunyikan password" : "Lihat password"
        );

        togglePassword.setAttribute(
            "title",
            sedangTersembunyi ? "Sembunyikan password" : "Lihat password"
        );

        if (sedangTersembunyi) {
            eyeIcon.innerHTML = `
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                <path d="M9.9 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a14 14 0 0 1-3 3.8"/>
                <path d="M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10 10 0 0 0 3.1-.5"/>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            `;
        }
    });
</script>

</body>
</html>