<?php
header('Content-Type: application/json');

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['accion']) || !isset($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$order_id = $input['order_id'];

if ($input['accion'] === 'estado') {
    $stmt = $pdo->prepare("
        SELECT estado 
        FROM reservas 
        WHERE numero_orden = ?
    ");
    $stmt->execute([$order_id]);
    $estado = $stmt->fetchColumn();

    if ($estado) {
        echo json_encode(['success' => true, 'message' => 'Estado de la reserva: ' . ucfirst($estado)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
    }
} elseif ($input['accion'] === 'cancelar') {
    $motivo = trim($input['motivo'] ?? '');

    if (!$motivo) {
        echo json_encode(['success' => false, 'message' => 'Debes ingresar un motivo para cancelar']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE reservas 
        SET estado = 'cancelada', motivo_cancelacion = ?
        WHERE numero_orden = ? AND estado = 'pendiente'
    ");
    $stmt->execute([$motivo, $order_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Reserva cancelada con éxito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo cancelar (ya no pendiente o no encontrada)']);
    }
}