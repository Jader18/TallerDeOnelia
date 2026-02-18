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

// Determinar si el usuario es superadmin (solo él puede editar)
$is_superadmin = ($_SESSION['rol_nombre'] === 'superadmin');

// Procesar acciones POST (solo si es superadmin)
$message = '';
$message_type = 'success';

if ($is_superadmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add' || $action === 'edit') {
            $nombre = trim($_POST['nombre'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio_base = floatval($_POST['precio_base'] ?? 0);
            $duracion_horas = intval($_POST['duracion_horas'] ?? 4);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO tipos_evento 
                    (nombre, slug, descripcion, precio_base, duracion_horas, activo, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$nombre, $slug, $descripcion, $precio_base, $duracion_horas, $activo]);
                $message = 'Tipo de evento agregado correctamente.';
            } else { // edit
                $id = intval($_POST['id'] ?? 0);
                $stmt = $pdo->prepare("
                    UPDATE tipos_evento 
                    SET nombre = ?, slug = ?, descripcion = ?, precio_base = ?, duracion_horas = ?, activo = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $slug, $descripcion, $precio_base, $duracion_horas, $activo, $id]);
                $message = 'Tipo de evento actualizado correctamente.';
            }
        } elseif ($action === 'toggle_active') {
            $id = intval($_POST['id'] ?? 0);
            $nuevo_estado = intval($_POST['nuevo_estado'] ?? 0);
            $stmt = $pdo->prepare("UPDATE tipos_evento SET activo = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            $message = 'Estado actualizado correctamente.';
        }
        // ELIMINADO: acción 'delete' - No se permite eliminar registros
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Cargar lista de tipos de evento - SIN PAGINACIÓN (todos)
$stmt = $pdo->query("SELECT * FROM tipos_evento ORDER BY nombre ASC");
$tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Evento - El Taller de Onelia</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Estilos mejorados sin afectar lógica */
        .tipos-container {
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

        /* Mensajes mejorados */
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
            content: '➕';
            font-size: 1.3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(122, 74, 58, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
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
            min-width: 800px;
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

        /* Precio destacado */
        .precio-tabla {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* Botones de acción mejorados */
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

        /* Modal mejorado */
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
            .tipos-container {
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

            .precio-tabla {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="tipos-container container">
    <h2 class="page-title">Gestión de Tipos de Evento</h2>

    <?php if ($message): ?>
        <div class="<?= $message_type === 'success' ? 'success-msg' : 'error-msg' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($is_superadmin): ?>
        <!-- Formulario para agregar nuevo (solo superadmin) -->
        <div class="form-section">
            <h3>Agregar Nuevo Tipo de Evento</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre del evento</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Ej: 15 Años" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">Slug (URL amigable)</label>
                        <input type="text" id="slug" name="slug" placeholder="Ej: 15-anos" required>
                        <small style="color: #666; font-size: 0.8rem;">Se usará en la URL: /evento/15-anos</small>
                    </div>

                    <div class="form-group full-width">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4" placeholder="Describe los detalles del evento..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="precio_base">Precio Base (C$)</label>
                        <input type="number" id="precio_base" name="precio_base" step="0.01" min="0" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="duracion_horas">Duración (horas)</label>
                        <input type="number" id="duracion_horas" name="duracion_horas" min="1" value="4" required>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="activo" name="activo" checked>
                        <label for="activo" style="margin-bottom: 0;">Evento activo (visible en el sitio)</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-reservar">
                    <i class="fas fa-save"></i> Agregar Tipo de Evento
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Lista de tipos existentes -->
    <div class="lista-section">
        <h3>Tipos de Evento Existentes</h3>
        
        <!-- Total de registros -->
        <div class="total-registros">
            Mostrando <span><?= count($tipos) ?></span> tipos de evento
        </div>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Precio Base</th>
                        <th>Duración</th>
                        <th>Estado</th>
                        <?php if ($is_superadmin): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos)): ?>
                        <tr>
                            <td colspan="<?= $is_superadmin ? 6 : 5 ?>" class="no-results">
                                <i class="fas fa-gift"></i>
                                No hay tipos de evento registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tipos as $tipo): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($tipo['nombre']) ?></strong></td>
                                <td><code><?= htmlspecialchars($tipo['slug']) ?></code></td>
                                <td><span class="precio-tabla">C$ <?= number_format($tipo['precio_base'], 2) ?></span></td>
                                <td><?= $tipo['duracion_horas'] ?> horas</td>
                                <td>
                                    <span class="badge <?= $tipo['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $tipo['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <?php if ($is_superadmin): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-secondary btn-small" onclick="editTipo(<?= $tipo['id'] ?>, '<?= addslashes($tipo['nombre']) ?>', '<?= addslashes($tipo['slug']) ?>', '<?= addslashes($tipo['descripcion'] ?? '') ?>', <?= $tipo['precio_base'] ?>, <?= $tipo['duracion_horas'] ?>, <?= $tipo['activo'] ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="id" value="<?= $tipo['id'] ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?= $tipo['activo'] ? 0 : 1 ?>">
                                                <button type="submit" class="btn <?= $tipo['activo'] ? 'btn-danger' : 'btn-success' ?> btn-small">
                                                    <i class="fas <?= $tipo['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                    <?= $tipo['activo'] ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                            <!-- ELIMINADO: Botón de eliminar -->
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
</div>

<!-- Modal para editar (solo visible para superadmin) -->
<?php if ($is_superadmin): ?>
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Editar Tipo de Evento</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group">
                <label for="edit_nombre">Nombre del evento</label>
                <input type="text" id="edit_nombre" name="nombre" placeholder="Ej: 15 Años" required>
            </div>

            <div class="form-group">
                <label for="edit_slug">Slug (URL amigable)</label>
                <input type="text" id="edit_slug" name="slug" placeholder="Ej: 15-anos" required>
                <small style="color: #666; font-size: 0.8rem;">Se usará en la URL: /evento/15-anos</small>
            </div>

            <div class="form-group">
                <label for="edit_descripcion">Descripción</label>
                <textarea id="edit_descripcion" name="descripcion" rows="4" placeholder="Describe los detalles del evento..."></textarea>
            </div>

            <div class="form-group">
                <label for="edit_precio_base">Precio Base (C$)</label>
                <input type="number" id="edit_precio_base" name="precio_base" step="0.01" min="0" placeholder="0.00" required>
            </div>

            <div class="form-group">
                <label for="edit_duracion_horas">Duración (horas)</label>
                <input type="number" id="edit_duracion_horas" name="duracion_horas" min="1" required>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="edit_activo" name="activo">
                <label for="edit_activo" style="margin-bottom: 0;">Evento activo (visible en el sitio)</label>
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
function editTipo(id, nombre, slug, descripcion, precio_base, duracion_horas, activo) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_slug').value = slug;
    document.getElementById('edit_descripcion').value = descripcion || '';
    document.getElementById('edit_precio_base').value = precio_base;
    document.getElementById('edit_duracion_horas').value = duracion_horas;
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