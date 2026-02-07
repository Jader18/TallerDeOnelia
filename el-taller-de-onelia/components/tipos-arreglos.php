<?php
// components/tipos-arreglos.php

require_once __DIR__ . '/../config/database.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    echo '<p style="color: red;">Error interno: conexión a base de datos no disponible.</p>';
    return;
}

$stmt = $pdo->query("
    SELECT nombre, precio_base, duracion_horas
    FROM tipos_arreglo
    WHERE activo = 1
    ORDER BY precio_base ASC
");

$arreglos = $stmt->fetchAll();

if (empty($arreglos)) {
    echo '<p>No hay tipos de arreglos disponibles en este momento.</p>';
} else {
    echo '<div class="arreglos-lista">';
    foreach ($arreglos as $a) {
        echo '<div class="arreglo-item">';
        echo '<strong>' . htmlspecialchars($a['nombre']) . '</strong><br>';
        echo 'Desde €' . number_format($a['precio_base'], 2, ',', '.') . ' · Duración aprox. ' . $a['duracion_horas'] . ' horas';
        echo '</div>';
    }
    echo '</div>';
}