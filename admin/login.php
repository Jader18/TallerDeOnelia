<?php
session_start();

// Forzar HTTPS (comentar en desarrollo local si no tienes SSL)
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Headers de seguridad
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;");

// Configuración de la base de datos
require_once '../config/database.php';

// Configuración de intentos fallidos (por IP)
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 minutos

// Generar o recuperar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Verificar si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header("Location: disponibilidad.php");
    exit;
}

// Procesar formulario
$error = '';
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
                // Consulta ajustada 
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

                    // Redirigir al panel
                    header("Location: disponibilidad.php");
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

    // Regenerar token CSRF después de POST
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - El Taller de Onelia</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .login-container {
            max-width: 420px;
            margin: 80px auto;
            padding: 2.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
        }

        .login-title {
            text-align: center;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
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
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #5e3a2d;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h1 class="login-title">Panel de Administración</h1>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required autocomplete="email" autofocus>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>

            <p style="text-align: center; margin-top: 1.5rem;">
                <a href="../index.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">← Volver a la página principal</a>
            </p>
        </form>
    </div>

</body>

</html>