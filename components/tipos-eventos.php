<?php
// components/tipos-eventos.php

require_once __DIR__ . '/../config/database.php';

// Consulta todos los tipos activos (incluye slug)
$stmt = $pdo->query("
    SELECT id, nombre, slug, descripcion, precio_base, duracion_horas
    FROM tipos_evento
    WHERE activo = 1
    ORDER BY precio_base ASC
");

$arreglos = $stmt->fetchAll();

if (empty($arreglos)) {
    echo '<p style="text-align: center; color: #666;">No hay tipos de eventos disponibles en este momento.</p>';
} else {
    echo '<div class="arreglos-grid">';

    foreach ($arreglos as $a) {
        $basePath = "assets/img/tipos-eventos/{$a['id']}";
        $extensiones = ['.jpg', '.jpeg', '.png'];  // orden de prioridad
        $imagen = null;

        foreach ($extensiones as $ext) {
            $rutaPrueba = $basePath . $ext;
            if (file_exists($rutaPrueba)) {
                $imagen = $rutaPrueba;
                break;
            }
        }

        // Fallback obligatorio
        if (!$imagen) {
            $imagen = "assets/img/placeholder.png";
        }

        // Enlace a página de detalle usando slug de BD
        $urlDetalle = "eventos/{$a['slug']}.php";

        echo '<a href="' . htmlspecialchars($urlDetalle) . '" class="arreglo-link">';
        echo '<div class="arreglo-card">';
        echo '<img src="' . htmlspecialchars($imagen) . '" alt="' . htmlspecialchars($a['nombre']) . '">';
        echo '<div class="arreglo-info">';
        echo '<h3>' . htmlspecialchars($a['nombre']) . '</h3>';
        echo '<p>' . htmlspecialchars($a['descripcion'] ?: 'Arreglo personalizado con duración aproximada de ' . $a['duracion_horas'] . ' horas.') . '</p>';
        echo '<div class="precio">Desde C$' . number_format($a['precio_base'], 2, ',', '.') . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</a>';
    }

    echo '</div>';
}