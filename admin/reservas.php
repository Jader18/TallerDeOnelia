<?php
session_start();
require_once '../config/database.php';

// Protección + timeout
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

if (!isset($_SESSION['usuario_id']) || 
    !isset($_SESSION['rol_nombre']) || 
    !in_array($_SESSION['rol_nombre'], ['superadmin', 'admin', 'editor'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['rol_nombre'] === 'superadmin');
$is_admin = in_array($_SESSION['rol_nombre'], ['superadmin', 'admin']);

// Obtener estado seleccionado de los botones de filtro rápido
$estado_seleccionado = $_GET['estado'] ?? '';

// Filtros - SIN PAGINACIÓN (mostrar todos)
$estado = $estado_seleccionado;
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$cliente = $_GET['cliente'] ?? '';
$tipo_evento = $_GET['tipo_evento'] ?? '';

// Construir WHERE clause
$where = [];
$params = [];

if ($estado) {
    $where[] = "r.estado = ?";
    $params[] = $estado;
}
if ($fecha_desde) {
    $where[] = "r.fecha_evento >= ?";
    $params[] = $fecha_desde;
}
if ($fecha_hasta) {
    $where[] = "r.fecha_evento <= ?";
    $params[] = $fecha_hasta;
}
if ($cliente) {
    $where[] = "(c.nombre LIKE ? OR c.email LIKE ?)";
    $params[] = "%$cliente%";
    $params[] = "%$cliente%";
}
if ($tipo_evento) {
    $where[] = "r.tipo_evento_id = ?";
    $params[] = $tipo_evento;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Obtener conteos por estado para los botones de filtro rápido
$counts_by_state = [];
$estados_lista = ['pendiente', 'confirmada', 'proceso', 'completada', 'cancelada'];

foreach ($estados_lista as $est) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE estado = ?");
    $stmt->execute([$est]);
    $counts_by_state[$est] = $stmt->fetchColumn();
}
$total_reservas = array_sum($counts_by_state);

// Lista de reservas - SIN PAGINACIÓN (todas)
$sql = "SELECT r.*, c.nombre AS cliente_nombre, c.email AS cliente_email, te.nombre AS tipo_nombre
        FROM reservas r
        LEFT JOIN clientes c ON r.cliente_id = c.id
        LEFT JOIN tipos_evento te ON r.tipo_evento_id = te.id
        $where_clause
        ORDER BY r.fecha_evento DESC, r.hora_inicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de tipos de evento para filtro
$stmt = $pdo->query("SELECT id, nombre FROM tipos_evento WHERE activo = 1 ORDER BY nombre");
$tipos_evento = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de estados para filtro
$estados = ['pendiente', 'confirmada', 'proceso', 'completada', 'cancelada'];

// Procesar acciones POST
$message = '';
$message_type = 'success';

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($action === 'confirmar') {
            $pdo->prepare("UPDATE reservas SET estado = 'confirmada', updated_at = NOW() WHERE id = ?")
                ->execute([$id]);
            $message = 'Reserva confirmada correctamente.';
        } elseif ($action === 'proceso') {
            $pdo->prepare("UPDATE reservas SET estado = 'proceso', updated_at = NOW() WHERE id = ?")
                ->execute([$id]);
            $message = 'Reserva marcada como en proceso.';
        } elseif ($action === 'completada') {
            $pdo->prepare("UPDATE reservas SET estado = 'completada', updated_at = NOW() WHERE id = ?")
                ->execute([$id]);
            $message = 'Reserva completada correctamente.';
        } elseif ($action === 'cancelar' && $is_admin) {
            $motivo = trim($_POST['motivo_cancelacion'] ?? '');
            $pdo->prepare("UPDATE reservas SET estado = 'cancelada', motivo_cancelacion = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$motivo, $id]);
            $message = 'Reserva cancelada.';
        } elseif ($action === 'revertir_cancelacion' && $is_admin) {
            $pdo->prepare("UPDATE reservas SET estado = 'pendiente', motivo_cancelacion = NULL, updated_at = NOW() WHERE id = ?")
                ->execute([$id]);
            $message = 'Cancelación revertida. Reserva vuelta a pendiente.';
        }
        
        // Recargar la página para mostrar los cambios
        header("Location: reservas.php?" . http_build_query($_GET));
        exit;
        
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - El Taller de Onelia</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Estilos mejorados */
        .reservas-container {
            padding: 2rem 1rem;
            max-width: 1400px;
            margin: 0 auto;
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

        /* Mensajes */
        .success-msg, .error-msg {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            animation: slideIn 0.3s ease;
        }

        .success-msg {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .success-msg::before {
            content: '✓';
            font-size: 1.2rem;
            font-weight: bold;
        }

        .error-msg::before {
            content: '✗';
            font-size: 1.2rem;
            font-weight: bold;
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

        /* Botones de filtro por estado */
        .estados-filtro {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .estado-filtro-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 110px;
            padding: 1.2rem 1rem;
            border-radius: var(--radius);
            background: white;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .estado-filtro-btn:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .estado-filtro-btn.activo {
            border-color: var(--primary);
            background: var(--bg-light);
            box-shadow: var(--shadow-md);
        }

        .estado-filtro-btn .estado-nombre {
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .estado-filtro-btn .estado-count {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .estado-filtro-btn .estado-total {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 0.2rem;
        }

        /* Colores específicos para cada estado */
        .estado-filtro-btn.pendiente .estado-count { color: #856404; }
        .estado-filtro-btn.pendiente.activo { background: #fff3cd; }
        
        .estado-filtro-btn.confirmada .estado-count { color: #155724; }
        .estado-filtro-btn.confirmada.activo { background: #d4edda; }
        
        .estado-filtro-btn.proceso .estado-count { color: #004085; }
        .estado-filtro-btn.proceso.activo { background: #cce5ff; }
        
        .estado-filtro-btn.completada .estado-count { color: #0f5132; }
        .estado-filtro-btn.completada.activo { background: #d1e7dd; }
        
        .estado-filtro-btn.cancelada .estado-count { color: #721c24; }
        .estado-filtro-btn.cancelada.activo { background: #f8d7da; }

        /* Filtros */
        .filtros-section {
            background: var(--bg-light);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border: 1px solid rgba(200, 155, 123, 0.1);
            box-shadow: var(--shadow-sm);
        }

        .filtros-title {
            color: var(--primary);
            margin-bottom: 1.2rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filtros-title::before {
            content: '🔍';
            font-size: 1.1rem;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filtros-actions {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-group {
            margin-bottom: 0;
            position: relative;
            min-height: 85px;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            transition: var(--transition);
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(122, 74, 58, 0.1);
        }

        /* Helper text para fechas */
        .fecha-helper {
            display: block;
            color: #666;
            font-size: 0.7rem;
            margin-top: 0.2rem;
            opacity: 0.7;
            transition: opacity 0.2s;
            line-height: 1.2;
            position: absolute;
            bottom: -18px;
            left: 0;
            white-space: nowrap;
        }

        .form-group:focus-within .fecha-helper {
            opacity: 1;
            color: var(--primary);
        }

        /* Tabla */
        .table-responsive {
            overflow-x: auto;
            margin: 2rem 0;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            background: white;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        .admin-table th {
            background: var(--primary);
            color: white;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            color: var(--text-dark);
            vertical-align: middle;
        }

        .admin-table tbody tr {
            transition: var(--transition);
        }

        .admin-table tbody tr:hover {
            background: #fcf8f6;
        }

        /* Badges de estado */
        .estado-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 100px;
        }

        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .estado-confirmada {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .estado-proceso {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .estado-completada {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Motivo de cancelación */
        .motivo-cancelacion {
            max-width: 200px;
            font-size: 0.85rem;
            color: #721c24;
            background: #f8d7da;
            padding: 0.3rem 0.6rem;
            border-radius: var(--radius-sm);
            display: inline-block;
            white-space: normal;
            word-break: break-word;
        }

        /* Número de orden */
        .orden-numero {
            font-weight: 700;
            color: var(--primary);
            font-family: monospace;
            font-size: 1rem;
        }

        /* Cliente info */
        .cliente-info {
            line-height: 1.4;
        }

        .cliente-nombre {
            font-weight: 600;
            color: var(--text-dark);
        }

        .cliente-email {
            font-size: 0.85rem;
            color: #666;
        }

        /* Fecha/hora */
        .fecha-evento {
            font-weight: 600;
            color: var(--text-dark);
        }

        .hora-evento {
            font-size: 0.85rem;
            color: #666;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            min-width: 200px;
        }

        .btn-small {
            padding: 0.5rem 0.8rem;
            font-size: 0.8rem;
            border-radius: var(--radius-sm);
            min-width: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            white-space: nowrap;
        }

        .btn-small i {
            font-size: 0.9rem;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--secondary-dark);
        }

        /* Input de motivo */
        .motivo-input {
            width: 150px;
            padding: 0.4rem 0.6rem;
            border: 1px solid #ccc;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            margin-right: 0.3rem;
        }

        .motivo-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Total de registros */
        .total-registros {
            text-align: right;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #666;
        }

        .total-registros span {
            font-weight: 700;
            color: var(--primary);
        }

        /* Mensaje sin resultados */
        .no-results {
            text-align: center;
            padding: 3rem !important;
            color: #999;
        }

        .no-results i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .reservas-container {
                padding: 1.5rem 0.5rem;
            }

            .estados-filtro {
                gap: 0.5rem;
            }

            .estado-filtro-btn {
                min-width: calc(50% - 0.5rem);
                padding: 0.8rem;
            }

            .filtros-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filtros-actions {
                flex-direction: column;
                width: 100%;
            }

            .filtros-actions .btn {
                width: 100%;
            }

            .form-group {
                min-height: auto;
            }

            .fecha-helper {
                position: static;
                margin-top: 0.2rem;
                white-space: normal;
            }

            .action-buttons {
                flex-direction: column;
                min-width: auto;
            }

            .btn-small {
                width: 100%;
                justify-content: center;
            }

            .motivo-input {
                width: 100%;
                margin-right: 0;
                margin-bottom: 0.3rem;
            }
        }

        @media (max-width: 480px) {
            .estado-badge {
                min-width: 80px;
                font-size: 0.75rem;
                padding: 0.3rem 0.5rem;
            }

            .btn-small {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }
            
            .estado-filtro-btn {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="reservas-container container">
    <h2 class="page-title">Gestión de Reservas</h2>

    <?php if ($message): ?>
        <div class="<?= $message_type === 'success' ? 'success-msg' : 'error-msg' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Botones de filtro por estado -->
    <div class="estados-filtro">
        <a href="reservas.php" class="estado-filtro-btn <?= !$estado_seleccionado ? 'activo' : '' ?>">
            <span class="estado-nombre">Todas</span>
            <span class="estado-count"><?= $total_reservas ?></span>
            <span class="estado-total">reservas</span>
        </a>
        
        <?php foreach ($estados_lista as $est): ?>
            <a href="?estado=<?= $est ?>" 
               class="estado-filtro-btn <?= $est ?> <?= $estado_seleccionado === $est ? 'activo' : '' ?>">
                <span class="estado-nombre"><?= ucfirst($est) ?></span>
                <span class="estado-count"><?= $counts_by_state[$est] ?></span>
                <span class="estado-total">reservas</span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="filtros-section">
        <div class="filtros-title">
            <span>Filtros de búsqueda</span>
        </div>
        
        <form id="filtros-form" method="GET" action="">
            <input type="hidden" name="estado" value="<?= htmlspecialchars($estado_seleccionado) ?>">
            
            <div class="filtros-grid">
                <div class="form-group">
                    <label for="fecha_desde">Fecha desde</label>
                    <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>" class="fecha-input">
                </div>

                <div class="form-group">
                    <label for="fecha_hasta">Fecha hasta</label>
                    <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" class="fecha-input">
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente</label>
                    <input type="text" id="cliente" name="cliente" value="<?= htmlspecialchars($cliente) ?>" placeholder="Nombre o email">
                </div>

                <div class="form-group">
                    <label for="tipo_evento">Tipo de evento</label>
                    <select id="tipo_evento" name="tipo_evento">
                        <option value="">Todos</option>
                        <?php foreach ($tipos_evento as $te): ?>
                            <option value="<?= $te['id'] ?>" <?= $tipo_evento == $te['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($te['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group filtros-actions">
                    <button type="button" id="aplicar-filtros" class="btn btn-reservar">
                        <i class="fas fa-search"></i> Aplicar filtros
                    </button>
                    <a href="reservas.php<?= $estado_seleccionado ? '?estado='.$estado_seleccionado : '' ?>" class="btn btn-secondary">
                        <i class="fas fa-eraser"></i> Limpiar filtros
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Total de registros encontrados -->
    <div class="total-registros">
        Mostrando <span><?= count($reservas) ?></span> reservas
    </div>

    <!-- Tabla de reservas -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th># Orden</th>
                    <th>Cliente</th>
                    <th>Tipo Evento</th>
                    <th>Fecha / Hora</th>
                    <th>Estado</th>
                    <th>Motivo Cancelación</th>
                    <?php if ($is_admin): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservas)): ?>
                    <tr>
                        <td colspan="<?= $is_admin ? 7 : 6 ?>" class="no-results">
                            <i class="fas fa-calendar-times"></i>
                            No hay reservas para mostrar
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservas as $r): ?>
                        <tr>
                            <td>
                                <span class="orden-numero"><?= htmlspecialchars($r['numero_orden']) ?></span>
                            </td>
                            <td>
                                <div class="cliente-info">
                                    <div class="cliente-nombre"><?= htmlspecialchars($r['cliente_nombre']) ?></div>
                                    <div class="cliente-email"><?= htmlspecialchars($r['cliente_email']) ?></div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($r['tipo_nombre']) ?></td>
                            <td>
                                <div class="fecha-evento"><?= date('d/m/Y', strtotime($r['fecha_evento'])) ?></div>
                                <div class="hora-evento"><?= date('h:i A', strtotime($r['hora_inicio'])) ?></div>
                            </td>
                            <td>
                                <span class="estado-badge estado-<?= $r['estado'] ?>">
                                    <?= ucfirst($r['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['estado'] === 'cancelada' && !empty($r['motivo_cancelacion'])): ?>
                                    <span class="motivo-cancelacion">
                                        <i class="fas fa-info-circle"></i> <?= htmlspecialchars($r['motivo_cancelacion']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#999; font-size:0.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($is_admin): ?>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($r['estado'] === 'pendiente'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="confirmar">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-small">
                                                    <i class="fas fa-check"></i> Confirmar
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array($r['estado'], ['confirmada', 'proceso'])): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="proceso">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-warning btn-small">
                                                    <i class="fas fa-sync-alt"></i> Proceso
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="completada">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-small">
                                                    <i class="fas fa-check-circle"></i> Completar
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($r['estado'] !== 'cancelada' && $r['estado'] !== 'completada'): ?>
                                            <form method="POST" style="display:inline-flex; align-items:center; gap:0.3rem; flex-wrap:wrap;">
                                                <input type="hidden" name="action" value="cancelar">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <input type="text" name="motivo_cancelacion" placeholder="Motivo" class="motivo-input" required>
                                                <button type="submit" class="btn btn-danger btn-small">
                                                    <i class="fas fa-ban"></i> Cancelar
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($r['estado'] === 'cancelada'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="revertir_cancelacion">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-small">
                                                    <i class="fas fa-undo"></i> Revertir a Pendiente
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts mejorados -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Agregar helper text a inputs de fecha
    const fechaInputs = document.querySelectorAll('input[type="date"]');
    
    fechaInputs.forEach(input => {
        if (!input.parentNode.querySelector('.fecha-helper')) {
            const helpText = document.createElement('small');
            helpText.className = 'fecha-helper';
            helpText.innerHTML = '↵ Presiona Enter después de seleccionar';
            input.parentNode.appendChild(helpText);
        }
    });

    // Manejo de filtros
    const filtrosForm = document.getElementById('filtros-form');
    const aplicarBtn = document.getElementById('aplicar-filtros');
    
    // Prevenir envío con Enter en fechas
    fechaInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                aplicarBtn.click();
            }
        });
    });
    
    // Botón aplicar filtros
    aplicarBtn.addEventListener('click', function() {
        if (document.activeElement) {
            document.activeElement.blur();
        }
        setTimeout(() => {
            filtrosForm.submit();
        }, 100);
    });
    
    // Enter en campo cliente también aplica filtros
    const clienteInput = document.getElementById('cliente');
    if (clienteInput) {
        clienteInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                aplicarBtn.click();
            }
        });
    }
    
    // En móviles
    if (window.innerWidth <= 768) {
        fechaInputs.forEach(input => {
            input.addEventListener('focus', function() {
                setTimeout(() => {
                    this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            });
        });
    }
});

// Botones de estado
document.querySelectorAll('.estado-filtro-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });
});
</script>

</body>
</html>