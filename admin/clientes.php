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

if (
    !isset($_SESSION['usuario_id']) ||
    !isset($_SESSION['rol_nombre']) ||
    !in_array($_SESSION['rol_nombre'], ['superadmin', 'admin', 'editor'])
) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['rol_nombre'] === 'superadmin');
$is_admin_or_super = in_array($_SESSION['rol_nombre'], ['superadmin', 'admin']);

// Filtros 
$nombre = trim($_GET['nombre'] ?? '');
$email = trim($_GET['email'] ?? '');
$telefono = trim($_GET['telefono'] ?? '');
$ciudad = trim($_GET['ciudad'] ?? '');
$activo = $_GET['activo'] ?? '';

$where = [];
$params = [];

if ($nombre) {
    $where[] = "nombre LIKE ?";
    $params[] = "%$nombre%";
}
if ($email) {
    $where[] = "email LIKE ?";
    $params[] = "%$email%";
}
if ($telefono) {
    $where[] = "telefono LIKE ?";
    $params[] = "%$telefono%";
}
if ($ciudad) {
    $where[] = "ciudad LIKE ?";
    $params[] = "%$ciudad%";
}
if ($activo !== '') {
    $where[] = "activo = ?";
    $params[] = $activo;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Lista de clientes 
$sql = "SELECT * FROM clientes $where_clause ORDER BY nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar acciones POST (solo superadmin)
$message = '';
$message_type = 'success';

if ($is_superadmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add' || $action === 'edit') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $ciudad = trim($_POST['ciudad'] ?? 'Managua');
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO clientes 
                    (nombre, email, telefono, direccion, ciudad, activo, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$nombre, $email, $telefono, $direccion, $ciudad, $activo]);
                $message = 'Cliente agregado correctamente.';
            } else { // edit
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("
                    UPDATE clientes 
                    SET nombre = ?, email = ?, telefono = ?, direccion = ?, ciudad = ?, activo = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $email, $telefono, $direccion, $ciudad, $activo, $id]);
                $message = 'Cliente actualizado correctamente.';
            }
        } elseif ($action === 'toggle_active') {
            $id = intval($_POST['id'] ?? 0);
            $nuevo_estado = intval($_POST['nuevo_estado'] ?? 0);
            $stmt = $pdo->prepare("UPDATE clientes SET activo = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            $message = 'Estado actualizado correctamente.';
        }
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
    <title>Gestión de Clientes - El Taller de Onelia</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .clientes-container {
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
        .success-msg,
        .error-msg {
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

        /* Sección de formulario */
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            margin-bottom: 3rem;
            border: 1px solid rgba(200, 155, 123, 0.1);
        }

        .form-section h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section h3::before {
            content: '➕';
            font-size: 1.3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(122, 74, 58, 0.1);
        }

        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
            transform: scale(1.1);
        }

        /* Filtros */
        .filtros-section {
            background: var(--bg-light);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border: 1px solid rgba(200, 155, 123, 0.1);
        }

        .filtros-title {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filtros-title::before {
            content: '🔍';
            font-size: 1.1rem;
        }

        .filtros-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
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

        /* Tabla */
        .table-responsive {
            overflow-x: auto;
            margin: 2rem 0;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 800px;
        }

        .admin-table th {
            background: var(--primary);
            color: white;
            padding: 1.2rem 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            color: var(--text-dark);
        }

        .admin-table tbody tr {
            transition: var(--transition);
        }

        .admin-table tbody tr:hover {
            background: #fcf8f6;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Botones de acción en tabla */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: var(--radius-sm);
            min-width: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-small i {
            font-size: 0.9rem;
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            position: relative;
            box-shadow: var(--shadow-lg);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-right: 2rem;
        }

        .close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 2rem;
            cursor: pointer;
            color: #999;
            transition: var(--transition);
            line-height: 1;
        }

        .close:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filtros-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .filtros-actions .btn {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 1.5rem;
                max-height: 90vh;
                overflow-y: auto;
            }
        }

        @media (max-width: 480px) {
            .form-section {
                padding: 1.5rem 1rem;
            }

            .form-section h3 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="clientes-container container">
        <h2 class="page-title">Gestión de Clientes</h2>

        <?php if ($message): ?>
            <div class="<?= $message_type === 'success' ? 'success-msg' : 'error-msg' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($is_superadmin): ?>
            <!-- Formulario agregar (solo superadmin) -->
            <div class="form-section">
                <h3>Agregar Nuevo Cliente</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre">Nombre completo</label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Correo electrónico</label>
                            <input type="email" id="email" name="email" placeholder="cliente@ejemplo.com" required>
                        </div>

                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" placeholder="8888-8888" required>
                        </div>

                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion" placeholder="Dirección completa">
                        </div>

                        <div class="form-group">
                            <label for="ciudad">Ciudad</label>
                            <input type="text" id="ciudad" name="ciudad" value="Managua" placeholder="Ciudad">
                        </div>

                        <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="activo" name="activo" checked>
                            <label for="activo" style="margin-bottom: 0;">Cliente activo</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-reservar" style="min-width: 200px;">
                        <i class="fas fa-save"></i> Agregar Cliente
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filtros-section">
            <div class="filtros-title">
                <span>Filtros de búsqueda</span>
            </div>
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Buscar por nombre">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Buscar por email">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono) ?>" placeholder="Buscar por teléfono">
                    </div>

                    <div class="form-group">
                        <label for="ciudad">Ciudad</label>
                        <input type="text" id="ciudad" name="ciudad" value="<?= htmlspecialchars($ciudad) ?>" placeholder="Buscar por ciudad">
                    </div>

                    <div class="form-group">
                        <label for="activo">Estado</label>
                        <select id="activo" name="activo">
                            <option value="">Todos los estados</option>
                            <option value="1" <?= $activo === '1' ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= $activo === '0' ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>

                    <div class="form-group filtros-actions">
                        <button type="submit" class="btn btn-reservar">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="clientes.php" class="btn btn-secondary">
                            <i class="fas fa-eraser"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Total de registros encontrados -->
        <div class="total-registros">
            Mostrando <span><?= count($clientes) ?></span> clientes
        </div>

        <!-- Tabla con scroll responsive -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Ciudad</th>
                        <th>Estado</th>
                        <?php if ($is_superadmin): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="<?= $is_admin_or_super ? 6 : 5 ?>" style="text-align:center; padding: 3rem;">
                                <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                                No hay clientes para mostrar
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= htmlspecialchars($c['telefono']) ?></td>
                                <td><?= htmlspecialchars($c['ciudad']) ?></td>
                                <td>
                                    <span class="badge <?= $c['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $c['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <?php if ($is_superadmin): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($is_superadmin): ?>
                                                <button class="btn btn-secondary btn-small" onclick="editCliente(<?= $c['id'] ?>, '<?= addslashes($c['nombre']) ?>', '<?= addslashes($c['email']) ?>', '<?= addslashes($c['telefono']) ?>', '<?= addslashes($c['direccion']) ?>', '<?= addslashes($c['ciudad']) ?>', <?= $c['activo'] ?>)">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($is_superadmin): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <input type="hidden" name="nuevo_estado" value="<?= $c['activo'] ? 0 : 1 ?>">
                                                    <button type="submit" class="btn <?= $c['activo'] ? 'btn-danger' : 'btn-success' ?> btn-small">
                                                        <i class="fas <?= $c['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                        <?= $c['activo'] ? 'Desactivar' : 'Activar' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($is_superadmin): ?>
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

    <?php if ($is_superadmin): ?>
        <!-- Modal editar mejorado (solo superadmin) -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Editar Cliente</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="form-group">
                        <label for="edit_nombre">Nombre completo</label>
                        <input type="text" id="edit_nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Correo electrónico</label>
                        <input type="email" id="edit_email" name="email" placeholder="cliente@ejemplo.com" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_telefono">Teléfono</label>
                        <input type="text" id="edit_telefono" name="telefono" placeholder="8888-8888" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_direccion">Dirección</label>
                        <input type="text" id="edit_direccion" name="direccion" placeholder="Dirección completa">
                    </div>

                    <div class="form-group">
                        <label for="edit_ciudad">Ciudad</label>
                        <input type="text" id="edit_ciudad" name="ciudad" value="Managua" placeholder="Ciudad">
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="edit_activo" name="activo">
                        <label for="edit_activo" style="margin-bottom: 0;">Cliente activo</label>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-reservar">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <script>
            function editCliente(id, nombre, email, telefono, direccion, ciudad, activo) {
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre').value = nombre;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_telefono').value = telefono;
                document.getElementById('edit_direccion').value = direccion || '';
                document.getElementById('edit_ciudad').value = ciudad || 'Managua';
                document.getElementById('edit_activo').checked = activo == 1;
                document.getElementById('editModal').style.display = 'flex';
            }

            function closeModal() {
                document.getElementById('editModal').style.display = 'none';
            }

            // Cerrar modal al hacer clic fuera
            window.onclick = function(event) {
                const modal = document.getElementById('editModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
    <?php endif; ?>

</body>

</html>