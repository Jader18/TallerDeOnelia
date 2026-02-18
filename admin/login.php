<?php
session_start();

if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Headers de seguridad estrictos
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;");

require_once '../config/database.php';

$max_attempts = 5;
$lockout_time = 15 * 60; // 15 minutos

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Verificar si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Mensajes desde GET
$error = '';
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = 'Sesión expirada por inactividad. Inicia sesión nuevamente.';
}
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $error = 'Sesión cerrada correctamente.';
}

// Procesar formulario
$intentos = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $error = 'Error de validación. Intenta de nuevo.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Manejo de intentos fallidos por IP
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempt_key = 'login_attempts_' . md5($ip);
        $intentos = $_SESSION[$attempt_key] ?? 0;

        if ($intentos >= $max_attempts) {
            $lockout_key = 'lockout_' . md5($ip);
            if (isset($_SESSION[$lockout_key]) && time() - $_SESSION[$lockout_key] < $lockout_time) {
                $error = 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.';
            } else {
                unset($_SESSION[$attempt_key]);
                unset($_SESSION[$lockout_key]);
                $intentos = 0;
            }
        }

        if (!$error && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT u.id, u.nombre, u.email, u.password_hash, u.rol_id, r.nombre AS rol_nombre 
                    FROM usuarios u
                    LEFT JOIN roles r ON u.rol_id = r.id
                    WHERE u.email = ? AND u.activo = 1 
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Login exitoso
                    session_regenerate_id(true);

                    $_SESSION['usuario_id']     = $user['id'];
                    $_SESSION['usuario_nombre'] = $user['nombre'];
                    $_SESSION['rol_id']         = $user['rol_id'];
                    $_SESSION['rol_nombre']     = $user['rol_nombre'] ?? 'sin_rol';
                    $_SESSION['ultimo_acceso']  = time();

                    // Resetear intentos
                    unset($_SESSION[$attempt_key]);
                    unset($_SESSION[$lockout_key]);

                    // Regenerar token CSRF SOLO después de login exitoso
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Redirigir al dashboard
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $intentos++;
                    $_SESSION[$attempt_key] = $intentos;
                    if ($intentos >= $max_attempts) {
                        $_SESSION['lockout_' . md5($ip)] = time();
                    }
                    $error = 'Credenciales incorrectas. Intentos restantes: ' . ($max_attempts - $intentos);
                }
            } catch (PDOException $e) {
                error_log("Error en login: " . $e->getMessage());
                $error = 'Error interno del servidor. Intenta más tarde.';
            }
        } else {
            $error = 'Ingresa un correo electrónico válido.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - El Taller de Onelia</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #faf7f5 0%, #e8dfd9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.5s ease;
            border: 1px solid rgba(200, 155, 123, 0.2);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .login-header .brand-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .error-msg, .success-msg {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            animation: slideIn 0.3s ease;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .success-msg {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error-msg i, .success-msg i {
            font-size: 1.1rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(122, 74, 58, 0.1);
        }

        /* Contenedor de contraseña con icono de mostrar */
        .password-container {
            position: relative;
        }

        .password-container input {
            padding-right: 3rem;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: var(--transition);
            background: transparent;
            border: none;
            padding: 0.5rem;
            font-size: 1.2rem;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .toggle-password:focus {
            outline: 2px solid var(--primary);
            border-radius: 4px;
        }

        /* Intentos restantes */
        .attempts-info {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
            text-align: right;
        }

        .attempts-info i {
            color: var(--primary);
            margin-right: 0.3rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(122, 74, 58, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            font-size: 1.1rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .back-link a:hover {
            color: var(--secondary);
            transform: translateX(-3px);
        }

        .back-link a i {
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .back-link a:hover i {
            transform: translateX(-3px);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 1.8rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .login-header .brand-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="login-header">
            <div class="brand-icon">
                <i class="fas fa-palette"></i>
            </div>
            <h1>Panel de Administración</h1>
            <p>El Taller de Onelia</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i>
                Sesión cerrada correctamente.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope" style="margin-right: 0.3rem;"></i>
                    Correo electrónico
                </label>
                <input type="email" id="email" name="email" required autocomplete="email" autofocus 
                       placeholder="usuario@ejemplo.com">
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock" style="margin-right: 0.3rem;"></i>
                    Contraseña
                </label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••">
                    <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
                <?php if ($intentos > 0): ?>
                    <div class="attempts-info">
                        <i class="fas fa-info-circle"></i>
                        Intentos restantes: <?= $max_attempts - $intentos ?>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>

            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    Volver a la página principal
                </a>
            </div>
        </form>
    </div>

    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // También permitir mostrar/ocultar con Enter en el botón (accesibilidad)
        document.querySelector('.toggle-password').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePassword();
            }
        });
    </script>

</body>
</html>