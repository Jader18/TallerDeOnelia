<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['accion']) || !isset($input['usuario_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$accion = $input['accion'];
$usuario_id = (int)$input['usuario_id'];
$fechas = $input['fechas'] ?? [];

if (empty($fechas) || !is_array($fechas)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fechas inválidas']);
    exit;
}

try {
    foreach ($fechas as $fecha) {
        $stmt = $pdo->prepare("
            INSERT INTO disponibilidad_admin (fecha, estado, creado_por)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = ?, creado_por = ?
        ");
        $stmt->execute([
            $fecha,
            $accion === 'habilitar' ? 'habilitado' : 'deshabilitado',
            $usuario_id,
            $accion === 'habilitar' ? 'habilitado' : 'deshabilitado',
            $usuario_id
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}