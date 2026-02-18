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
    $_SESSION['rol_nombre'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

// Procesar acciones POST
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol_id = intval($_POST['rol_id'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if (empty($nombre) || empty($email) || empty($password) || $rol_id <= 0) {
                throw new Exception('Todos los campos son obligatorios.');
            }

            // Verificar email único
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está registrado.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios 
                (nombre, email, password_hash, rol_id, activo, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$nombre, $email, $password_hash, $rol_id, $activo]);
            $message = 'Usuario agregado correctamente.';
        } elseif ($action === 'edit') {
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rol_id = intval($_POST['rol_id'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;
            $password = $_POST['password'] ?? '';

            if ($id <= 0 || empty($nombre) || empty($email) || $rol_id <= 0) {
                throw new Exception('Datos incompletos.');
            }

            // Verificar email único (excepto para el mismo usuario)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está en uso por otro usuario.');
            }

            $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol_id = ?, activo = ?, updated_at = NOW()";
            $params = [$nombre, $email, $rol_id, $activo];

            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password_hash = ?";
                $params[] = $password_hash;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = 'Usuario actualizado correctamente.';
        } elseif ($action === 'toggle_active') {
            $id = intval($_POST['id'] ?? 0);
            $nuevo_estado = intval($_POST['nuevo_estado'] ?? 0);
            $stmt = $pdo->prepare("UPDATE usuarios SET activo = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            $message = 'Estado actualizado correctamente.';
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    } catch (PDOException $e) {
        $message = 'Error en base de datos: ' . $e->getMessage();
        $message_type = 'error';
    }
}

$stmt = $pdo->query("
    SELECT u.*, r.nombre AS rol_nombre 
    FROM usuarios u 
    LEFT JOIN roles r ON u.rol_id = r.id 
    ORDER BY u.nombre ASC
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar roles disponibles
$stmt = $pdo->query("SELECT id, nombre FROM roles ORDER BY nombre");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - El Taller de Onelia</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .usuarios-container {
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

        /* Sección de formulario mejorada */
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
            content: '👤';
            font-size: 1.3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
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

        .form-group input,
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

        /* Sección de lista */
        .lista-section {
            margin-top: 3rem;
        }

        .lista-section h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .lista-section h3::before {
            content: '📋';
            font-size: 1.3rem;
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

        /* Tabla mejorada */
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
            min-width: 1000px;
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
        .badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 70px;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Badges de rol */
        .rol-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--bg-light);
            color: var(--primary);
            border: 1px solid var(--secondary);
        }

        .rol-superadmin {
            background: #7a4a3a;
            color: white;
            border: none;
        }

        .rol-admin {
            background: #c89b7b;
            color: white;
            border: none;
        }

        .rol-editor {
            background: #e8dfd9;
            color: #5e3a2d;
            border: 1px solid #c89b7b;
        }

        /* ID */
        .user-id {
            font-weight: 700;
            color: var(--primary);
            font-family: monospace;
            font-size: 1rem;
        }

        /* Email */
        .user-email {
            color: #555;
            font-size: 0.9rem;
        }

        /* Último login */
        .last-login {
            font-size: 0.9rem;
            color: #666;
        }

        .last-login .nunca {
            color: #999;
            font-style: italic;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            min-width: 180px;
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

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
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
            max-height: 90vh;
            overflow-y: auto;
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
            .usuarios-container {
                padding: 1.5rem 0.5rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-section h3 {
                font-size: 1.3rem;
            }

            .action-buttons {
                flex-direction: column;
                min-width: auto;
            }

            .btn-small {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .badge {
                min-width: 60px;
                font-size: 0.75rem;
                padding: 0.3rem 0.5rem;
            }

            .rol-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }

            .user-id {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="usuarios-container container">
    <h2 class="page-title">Gestión de Usuarios</h2>

    <?php if ($message): ?>
        <div class="<?= $message_type === 'success' ? 'success-msg' : 'error-msg' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario agregar nuevo usuario -->
    <div class="form-section">
        <h3>Agregar Nuevo Usuario</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" placeholder="usuario@ejemplo.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>

                <div class="form-group">
                    <label for="rol_id">Rol</label>
                    <select id="rol_id" name="rol_id" required>
                        <option value="">Seleccionar rol</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="activo" name="activo" checked>
                    <label for="activo" style="margin-bottom: 0;">Usuario activo</label>
                </div>
            </div>

            <button type="submit" class="btn btn-reservar">
                <i class="fas fa-save"></i> Agregar Usuario
            </button>
        </form>
    </div>

    <!-- Lista de usuarios -->
    <div class="lista-section">
        <h3>Usuarios Existentes</h3>
        
        <!-- Total de registros -->
        <div class="total-registros">
            Mostrando <span><?= count($usuarios) ?></span> usuarios
        </div>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último login</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" class="no-results">
                                <i class="fas fa-users"></i>
                                No hay usuarios registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><span class="user-id">#<?= $u['id'] ?></span></td>
                                <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                                <td><span class="user-email"><?= htmlspecialchars($u['email']) ?></span></td>
                                <td>
                                    <span class="rol-badge <?php 
                                        if ($u['rol_nombre'] === 'superadmin') echo 'rol-superadmin';
                                        elseif ($u['rol_nombre'] === 'admin') echo 'rol-admin';
                                        elseif ($u['rol_nombre'] === 'editor') echo 'rol-editor';
                                    ?>">
                                        <?= htmlspecialchars($u['rol_nombre'] ?? 'Sin rol') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $u['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="last-login">
                                        <?php if ($u['ultimo_login']): ?>
                                            <i class="fas fa-clock" style="font-size:0.8rem; opacity:0.5;"></i>
                                            <?= date('d/m/Y H:i', strtotime($u['ultimo_login'])) ?>
                                        <?php else: ?>
                                            <span class="nunca">Nunca</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-secondary btn-small" onclick="editUsuario(<?= $u['id'] ?>, '<?= addslashes($u['nombre']) ?>', '<?= addslashes($u['email']) ?>', <?= $u['rol_id'] ?>, <?= $u['activo'] ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>

                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="nuevo_estado" value="<?= $u['activo'] ? 0 : 1 ?>">
                                            <button type="submit" class="btn <?= $u['activo'] ? 'btn-danger' : 'btn-success' ?> btn-small">
                                                <i class="fas <?= $u['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                        
                                        <!-- ELIMINADO: Botón de eliminar -->
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para editar -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Editar Usuario</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group">
                <label for="edit_nombre">Nombre completo</label>
                <input type="text" id="edit_nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
            </div>

            <div class="form-group">
                <label for="edit_email">Correo electrónico</label>
                <input type="email" id="edit_email" name="email" placeholder="usuario@ejemplo.com" required>
            </div>

            <div class="form-group">
                <label for="edit_password">Nueva contraseña</label>
                <input type="password" id="edit_password" name="password" minlength="6" placeholder="Dejar en blanco para no cambiar">
                <small style="color: #666; font-size: 0.8rem;">Solo si deseas cambiar la contraseña</small>
            </div>

            <div class="form-group">
                <label for="edit_rol_id">Rol</label>
                <select id="edit_rol_id" name="rol_id" required>
                    <option value="">Seleccionar rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="edit_activo" name="activo">
                <label for="edit_activo" style="margin-bottom: 0;">Usuario activo</label>
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

<script>
function editUsuario(id, nombre, email, rol_id, activo) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_rol_id').value = rol_id;
    document.getElementById('edit_activo').checked = activo == 1;
    document.getElementById('edit_password').value = '';
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

</body>
</html>