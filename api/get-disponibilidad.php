<?php
// api/get-disponibilidad.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

try {
    // 1. Días habilitados por admin
    $habilitados = $pdo->query("
        SELECT fecha 
        FROM disponibilidad_admin 
        WHERE estado = 'habilitado'
    ")->fetchAll(PDO::FETCH_COLUMN);

    // 2. Fechas con CUALQUIER reserva (pendiente, confirmada, proceso, completada)
    $ocupadas = $pdo->query("
        SELECT DISTINCT fecha_evento AS fecha
        FROM reservas 
        WHERE estado IN ('pendiente', 'confirmada', 'proceso', 'completada')
    ")->fetchAll(PDO::FETCH_COLUMN);

    $eventos = [];

    // 3. Solo mostrar como disponible si está habilitado Y no tiene ninguna reserva
    foreach ($habilitados as $fecha) {
        if (!in_array($fecha, $ocupadas)) {
            $eventos[] = [
                'title' => 'Disponible',
                'start' => $fecha,
                'allDay' => true,
                'className' => 'disponible',
                'editable' => false
            ];
        }
    }

    // 4. Mostrar como ocupado cualquier fecha con reserva (cualquier estado)
    foreach ($ocupadas as $fecha) {
        $eventos[] = [
            'title' => 'Ocupado',
            'start' => $fecha,
            'allDay' => true,
            'className' => 'ocupado',
            'editable' => false
        ];
    }

    echo json_encode($eventos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}