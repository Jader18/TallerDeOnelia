<?php
// api/get-disponibilidad.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

try {
    $hoy = date('Y-m-d');
    
    $habilitados = $pdo->query("
        SELECT fecha 
        FROM disponibilidad_admin 
        WHERE estado = 'habilitado'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $ocupadas = $pdo->query("
        SELECT DISTINCT fecha_evento AS fecha
        FROM reservas 
        WHERE estado IN ('pendiente', 'confirmada', 'proceso', 'completada')
    ")->fetchAll(PDO::FETCH_COLUMN);

    $eventos = [];

    foreach ($habilitados as $fecha) {
        if ($fecha >= $hoy && !in_array($fecha, $ocupadas)) {
            $eventos[] = [
                'title' => 'Disponible',
                'start' => $fecha,
                'allDay' => true,
                'className' => 'disponible',
                'editable' => false
            ];
        }
    }

    foreach ($ocupadas as $fecha) {
        if ($fecha >= $hoy) {
            $eventos[] = [
                'title' => 'Ocupado',
                'start' => $fecha,
                'allDay' => true,
                'className' => 'ocupado',
                'editable' => false
            ];
        }
    }

    echo json_encode($eventos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>