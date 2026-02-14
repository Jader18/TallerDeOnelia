<?php
header('Content-Type: application/json');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$datos = [
    'tipo_evento_id'   => filter_input(INPUT_POST, 'tipo_evento_id', FILTER_VALIDATE_INT),
    'fecha_evento'     => $_POST['fecha_evento'] ?? '',
    'hora_inicio'      => $_POST['hora_inicio'] ?? '',
    'hora_fin'         => $_POST['hora_fin'] ?? null,
    'cliente_nombre'   => trim($_POST['cliente_nombre'] ?? ''),
    'cliente_email'    => trim($_POST['cliente_email'] ?? ''),
    'cliente_telefono' => trim($_POST['cliente_telefono'] ?? ''),
    'direccion_evento' => trim($_POST['direccion_evento'] ?? ''),
    'notas'            => trim($_POST['notas'] ?? '')
];

if (!$datos['tipo_evento_id'] || !$datos['fecha_evento'] || !$datos['hora_inicio'] ||
    !$datos['cliente_nombre'] || !$datos['cliente_email'] || !$datos['cliente_telefono']) {
    echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
    exit;
}

// 1. Validar que la fecha esté habilitada por el admin
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM disponibilidad_admin 
    WHERE fecha = ? AND estado = 'habilitado'
");
$stmt->execute([$datos['fecha_evento']]);
$habilitada = $stmt->fetchColumn() > 0;

if (!$habilitada) {
    echo json_encode(['success' => false, 'message' => 'La fecha no está disponible para reservas online']);
    exit;
}

// 2. Validar que la fecha no tenga ninguna reserva activa 
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reservas 
    WHERE fecha_evento = ?
    AND estado IN ('pendiente', 'confirmada', 'proceso')
");
$stmt->execute([$datos['fecha_evento']]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'La fecha ya está ocupada']);
    exit;
}

if ($datos['hora_fin']) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reservas 
        WHERE fecha_evento = ? 
        AND (
            (hora_inicio <= ? AND hora_fin >= ?) OR 
            (hora_inicio <= ? AND hora_fin IS NULL) OR 
            (? >= hora_inicio AND ? <= hora_fin)
        )
        AND estado IN ('pendiente', 'confirmada', 'proceso')
    ");
    $stmt->execute([
        $datos['fecha_evento'],
        $datos['hora_inicio'],
        $datos['hora_inicio'],
        $datos['hora_fin'],
        $datos['hora_fin'],
        $datos['hora_fin']
    ]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'La hora seleccionada se solapa con otra reserva']);
        exit;
    }
}

// Generar número de orden único TO-XXXXX
$numero_orden = 'TO-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);

// Verificar unicidad (colisión extremadamente rara)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE numero_orden = ?");
$stmt->execute([$numero_orden]);
if ($stmt->fetchColumn() > 0) {
    $numero_orden = 'TO-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
}

// Buscar o crear cliente
$stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
$stmt->execute([$datos['cliente_email']]);
$cliente_id = $stmt->fetchColumn();

if (!$cliente_id) {
    $stmt = $pdo->prepare("
        INSERT INTO clientes (nombre, email, telefono, direccion, ciudad)
        VALUES (?, ?, ?, ?, 'Managua')
    ");
    $stmt->execute([
        $datos['cliente_nombre'],
        $datos['cliente_email'],
        $datos['cliente_telefono'],
        $datos['direccion_evento']
    ]);
    $cliente_id = $pdo->lastInsertId();
}

// Insertar reserva
try {
    $stmt = $pdo->prepare("
        INSERT INTO reservas (
            numero_orden, tipo_evento_id, cliente_id, fecha_evento, hora_inicio, hora_fin,
            direccion_evento, notas, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");
    $stmt->execute([
        $numero_orden,
        $datos['tipo_evento_id'],
        $cliente_id,
        $datos['fecha_evento'],
        $datos['hora_inicio'],
        $datos['hora_fin'],
        $datos['direccion_evento'],
        $datos['notas']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Reserva enviada con éxito.<br><strong>Número de orden:</strong> ' . $numero_orden . '<br>Guarda este número para consultar o cancelar. Te contactaremos pronto.'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la reserva']);
}