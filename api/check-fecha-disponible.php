<?php
header('Content-Type: application/json');

require_once '../config/database.php';

$fecha = $_GET['fecha'] ?? '';

if (!$fecha) {
    echo json_encode(['disponible' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reservas 
    WHERE fecha_evento = ?
    AND estado IN ('pendiente', 'confirmada', 'proceso')
");
$stmt->execute([$fecha]);
$ocupada = $stmt->fetchColumn() > 0;

echo json_encode(['disponible' => !$ocupada]);