<?php
// components/tipos-arreglos.php
// Lista dinámica de tipos de arreglos desde la BD

require_once realpath(__DIR__ . '/../config/database.php');

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("PDO no está definido o no es válido");
    }

    $stmt = $pdo->query("
        SELECT id, nombre, descripcion, precio_base, duracion_horas 
        FROM tipos_arreglo 
        WHERE activo = 1 
        ORDER BY precio_base ASC
    ");
    $arreglos = $stmt->fetchAll();

    if (empty($arreglos)) {
        echo '<p style="text-align: center; color: #64748b;">No hay tipos de arreglos disponibles en este momento.</p>';
    } else {
        echo '<div class="arreglos-grid">';
        
        foreach ($arreglos as $arreglo) {
            $imagen = 'assets/img/arreglos/' . strtolower(str_replace(' ', '-', $arreglo['nombre'])) . '.jpg';
            if (!file_exists($imagen)) {
                $imagen = 'assets/img/placeholder-arreglo.jpg';
            }

            echo '
            <div class="arreglo-card">
                <img src="' . htmlspecialchars($imagen) . '" alt="' . htmlspecialchars($arreglo['nombre']) . '">
                <div class="arreglo-info">
                    <h3>' . htmlspecialchars($arreglo['nombre']) . '</h3>
                    <p>' . htmlspecialchars($arreglo['descripcion'] ?: 'Arreglo personalizado con duración aproximada de ' . $arreglo['duracion_horas'] . ' horas.') . '</p>
                    <div class="precio">Desde €' . number_format($arreglo['precio_base'], 2, ',', '.') . '</div>
                </div>
            </div>';
        }

        echo '</div>';
    }
} catch (Exception $e) {
    echo '<p style="text-align: center; color: #ef4444;">Error al cargar los arreglos: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

?>