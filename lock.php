<?php
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already unlocked
if (!isAppLocked()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (verifyLogin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Neplatné jméno nebo heslo';
    }
}

$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="cs" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevBase - Přihlášení</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-blur: blur(15px);
        }

        [data-bs-theme="light"] {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(0, 0, 0, 0.1);
        }

        body {
            background: <?php echo $theme === 'dark' ? '#0f172a' : '#f8fafc'; ?>;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(147, 51, 234, 0.15) 0px, transparent 50%);
        }

        .lock-container {
            max-width: 400px;
            width: 90%;
            perspective: 1000px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.9) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .lock-icon {
            font-size: 4rem;
            background: linear-gradient(45deg, #3b82f6, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            display: inline-block;
            animation: pulse-glow 3s infinite;
        }

        @keyframes pulse-glow {
            0% { transform: scale(1); filter: drop-shadow(0 0 0px rgba(59, 130, 246, 0)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.5)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 0px rgba(59, 130, 246, 0)); }
        }

        .form-control {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            text-align: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        [data-bs-theme="light"] .form-control {
            background: rgba(255, 255, 255, 0.5);
            color: #1e293b;
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
            transform: translateY(-2px);
        }

        .btn-unlock {
            background: linear-gradient(45deg, #3b82f6, #9333ea);
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.05rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-unlock:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.5);
            filter: brightness(1.1);
        }

        .error-shake {
            animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
            border-color: #ef4444 !important;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>
    <div class="lock-container">
        <div class="glass-card text-center">
            <div class="lock-icon">
                <i class="bi bi-person-lock"></i>
            </div>
            <h2 class="mb-2 fw-bold">DevBase</h2>
            <p class="text-secondary mb-4 small text-uppercase ls-wide">Vítejte zpět</p>
            
            <form method="POST" action="lock.php" id="lockForm">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control <?php echo $error ? 'error-shake' : ''; ?>" 
                           placeholder="login" autofocus required autocomplete="username">
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control <?php echo $error ? 'error-shake' : ''; ?>" 
                           placeholder="password" required autocomplete="current-password">
                    <?php if ($error): ?>
                        <div class="text-danger mt-2 small"><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-unlock">
                    PŘIHLÁSIT SE <i class="bi bi-box-arrow-in-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userInp = document.querySelector('input[name="username"]');
            const passInp = document.querySelector('input[name="password"]');

            document.getElementById('lockForm').addEventListener('submit', (e) => {
                if (!userInp.value || !passInp.value) {
                    e.preventDefault();
                    [userInp, passInp].forEach(inp => {
                        if(!inp.value) {
                            inp.classList.remove('error-shake');
                            void inp.offsetWidth;
                            inp.classList.add('error-shake');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
