<?php
session_start();
date_default_timezone_set('America/Managua');
require_once '../config/database.php';

// Protección + timeout de 20 min
$session_timeout = 1200;

if (isset($_SESSION['ultimo_acceso'])) {
    $inactividad = time() - $_SESSION['ultimo_acceso'];
    if ($inactividad > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?expired=1");
        exit;
    }
}

$_SESSION['ultimo_acceso'] = time();

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['rol_nombre']) ||
    !in_array($_SESSION['rol_nombre'], ['superadmin', 'admin', 'editor'])
) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['rol_nombre'] === 'superadmin');

// Estadísticas 
try {
    // 1. Reservas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'");
    $stmt->execute();
    $reservas_pendientes = $stmt->fetchColumn() ?: 0;

    // 2. Reservas de hoy
    $hoy = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE fecha_evento = ? AND estado != 'cancelada'");
    $stmt->execute([$hoy]);
    $reservas_hoy = $stmt->fetchColumn() ?: 0;

    // 3. Días del calendario admin
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN estado = 'habilitado' THEN 1 ELSE 0 END) AS habilitados,
            SUM(CASE WHEN estado = 'deshabilitado' THEN 1 ELSE 0 END) AS deshabilitados
        FROM disponibilidad_admin
    ");
    $stmt->execute();
    $dias = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['habilitados' => 0, 'deshabilitados' => 0];

    // 4. Total tipos de evento activos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tipos_evento WHERE activo = 1");
    $stmt->execute();
    $tipos_activos = $stmt->fetchColumn() ?: 0;

    // 5. Total clientes activos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE activo = 1");
    $stmt->execute();
    $clientes_activos = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    error_log("Error en estadísticas dashboard: " . $e->getMessage());
    $reservas_pendientes = $reservas_hoy = $tipos_activos = $clientes_activos = 0;
    $dias = ['habilitados' => 0, 'deshabilitados' => 0];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - El Taller de Onelia</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Estilos para dashboard */
        .dashboard-container {
            padding: 2rem 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header con información del usuario */
        .welcome-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: var(--shadow-lg);
        }

        .welcome-text h2 {
            font-size: 1.8rem;
            margin-bottom: 0.3rem;
        }

        .welcome-text p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .user-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-badge i {
            font-size: 1.1rem;
        }

        .user-badge.superadmin {
            background: rgba(255, 215, 0, 0.3);
            color: #ffd700;
        }

        .page-title {
            text-align: center;
            color: var(--primary);
            margin-bottom: 2.5rem;
            font-size: clamp(2rem, 5vw, 2.8rem);
            position: relative;
            padding-bottom: 1rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        /* Grid de estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.8rem 1.5rem;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: var(--transition);
            border: 1px solid rgba(200, 155, 123, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            font-size: 2.8rem;
            color: var(--primary);
            opacity: 0.9;
            min-width: 60px;
            text-align: center;
        }

        .stat-content {
            flex: 1;
        }

        .stat-content h3 {
            font-size: 1rem;
            margin-bottom: 0.3rem;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.2;
        }

        /* Sección de acceso rápido */
        .quick-access {
            margin-top: 3rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title {
            color: var(--primary);
            font-size: clamp(1.5rem, 4vw, 2rem);
            position: relative;
            padding-bottom: 0.5rem;
            margin: 0;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .role-indicator {
            background: var(--bg-light);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--secondary);
        }

        /* Grid de acciones rápidas */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.8rem;
        }

        .action-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            border: 1px solid rgba(200, 155, 123, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .action-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: var(--transition);
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .action-card:hover::after {
            transform: scaleX(1);
        }

        .action-card i {
            font-size: 3.2rem;
            color: var(--primary);
            margin-bottom: 1.2rem;
            transition: var(--transition);
        }

        .action-card:hover i {
            transform: scale(1.1);
            color: var(--secondary);
        }

        .action-card h4 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
            font-weight: 600;
        }

        .action-card p {
            font-size: 0.9rem;
            opacity: 0.7;
            margin: 0;
            line-height: 1.4;
        }

        /* Badge de restricción para admins */
        .restricted-badge {
            margin-top: 0.8rem;
            font-size: 0.75rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            justify-content: center;
        }

        .restricted-badge i {
            font-size: 0.8rem;
            margin: 0;
            color: #999;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1.5rem 1rem;
            }

            .welcome-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .welcome-text h2 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem 1.2rem;
            }

            .stat-icon {
                font-size: 2.2rem;
                min-width: 50px;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
                gap: 1.2rem;
            }

            .action-card {
                padding: 1.8rem 1.2rem;
                flex-direction: row;
                text-align: left;
                gap: 1.2rem;
            }

            .action-card i {
                font-size: 2.5rem;
                margin-bottom: 0;
                min-width: 50px;
            }

            .action-card h4 {
                margin-bottom: 0.3rem;
                font-size: 1.2rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 1.2rem;
            }

            .stat-icon {
                margin-bottom: 0.5rem;
            }

            .action-card {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem 1rem;
            }

            .action-card i {
                margin-bottom: 0.8rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="dashboard-container container">

        <!-- Header de bienvenida personalizado -->
        <div class="welcome-header">
            <div class="welcome-text">
                <h2>¡Bienvenido(a), <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>!</h2>
                <p><?php
                    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');
                    echo strftime('%A, %d de %B de %Y');
                    ?></p>
            </div>
            <div class="user-badge <?= $is_superadmin ? 'superadmin' : '' ?>">
                <i class="fas <?= $is_superadmin ? 'fa-crown' : 'fa-user' ?>"></i>
                <span><?= $is_superadmin ? 'Super Administrador' : 'Administrador' ?></span>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-content">
                    <h3>Reservas Pendientes</h3>
                    <p class="stat-number"><?= number_format($reservas_pendientes) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-calendar-day stat-icon"></i>
                <div class="stat-content">
                    <h3>Reservas Hoy</h3>
                    <p class="stat-number"><?= number_format($reservas_hoy) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-content">
                    <h3>Días Habilitados</h3>
                    <p class="stat-number"><?= number_format($dias['habilitados']) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-ban stat-icon"></i>
                <div class="stat-content">
                    <h3>Días Deshabilitados</h3>
                    <p class="stat-number"><?= number_format($dias['deshabilitados']) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-gift stat-icon"></i>
                <div class="stat-content">
                    <h3>Tipos de Evento</h3>
                    <p class="stat-number"><?= number_format($tipos_activos) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-content">
                    <h3>Clientes Activos</h3>
                    <p class="stat-number"><?= number_format($clientes_activos) ?></p>
                </div>
            </div>
        </div>

        <!-- Acceso Rápido -->
        <div class="quick-access">
            <div class="section-header">
                <h3 class="section-title">Acceso Rápido</h3>
                <?php if (!$is_superadmin): ?>
                    <span class="role-indicator">
                        <i class="fas fa-eye"></i> Modo consulta
                    </span>
                <?php endif; ?>
            </div>

            <div class="quick-actions-grid">
                <?php if ($is_superadmin || $_SESSION['rol_nombre'] === 'admin'): ?>
                    <a href="disponibilidad.php" class="action-card">
                        <i class="fas fa-calendar-alt"></i>
                        <div>
                            <h4>Disponibilidad</h4>
                            <p>Gestionar calendario</p>
                            <?php if (!$is_superadmin): ?>
                                <span class="restricted-badge"><i class="fas fa-lock-open"></i> Solo ver/editar</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endif; ?>

                <a href="tipos_evento.php" class="action-card">
                    <i class="fas fa-gift"></i>
                    <div>
                        <h4>Tipos de Evento</h4>
                        <p>Catálogo de servicios</p>
                        <?php if (!$is_superadmin): ?>
                            <span class="restricted-badge"><i class="fas fa-lock"></i> Solo lectura</span>
                        <?php endif; ?>
                    </div>
                </a>

                <a href="reservas.php" class="action-card">
                    <i class="fas fa-list"></i>
                    <div>
                        <h4>Reservas</h4>
                        <p>Gestionar pedidos</p>
                    </div>
                </a>

                <a href="clientes.php" class="action-card">
                    <i class="fas fa-users"></i>
                    <div>
                        <h4>Clientes</h4>
                        <p>Directorio de contactos</p>
                        <?php if (!$is_superadmin): ?>
                            <span class="restricted-badge"><i class="fas fa-lock"></i> Solo lectura</span>
                        <?php endif; ?>
                    </div>
                </a>

                <?php if ($is_superadmin): ?>
                    <a href="usuarios.php" class="action-card">
                        <i class="fas fa-user-shield"></i>
                        <div>
                            <h4>Usuarios</h4>
                            <p>Administrar cuentas</p>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>