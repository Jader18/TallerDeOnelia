<?php
require_once '../config/database.php';

$stmt = $pdo->prepare("
    SELECT nombre, descripcion, precio_base, duracion_horas
    FROM tipos_evento
    WHERE slug = ? AND activo = 1
");
$stmt->execute(['15-anos']); 
$evento = $stmt->fetch();

if (!$evento) {
    die('Evento no encontrado o inactivo.');
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($evento['nombre']) ?> - El Taller de Onelia</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
</head>

<body>
    <?php include '../components/header.php'; ?>

    <main>
        <section class="evento-detalle">
            <div class="container">
                <h1><?= htmlspecialchars($evento['nombre']) ?></h1>

                <img src="../assets/img/tipos-eventos/4.jpg"
                    alt="<?= htmlspecialchars($evento['nombre']) ?>"
                    class="foto-principal">

                <!-- Galería adicional en cards -->
                <div class="galeria-adicional">
                    <div class="foto-card">
                        <img src="../assets/img/eventos/15/15-1.jpg" alt="Foto adicional 1">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/15/15-2.jpg" alt="Foto adicional 2">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/15/15-3.jpg" alt="Foto adicional 3">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/15/15-4.jpg" alt="Foto adicional 4">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/15/15-5.jpg" alt="Foto adicional 5">
                    </div>
                </div>

                <div class="info">
                    <p><?= nl2br(htmlspecialchars($evento['descripcion'] ?: 'Descripción no disponible.')) ?></p>
                    <p><strong>Precio base:</strong> C$<?= number_format($evento['precio_base'], 2, ',', '.') ?></p>
                    <p><strong>Duración aproximada:</strong> <?= $evento['duracion_horas'] ?> horas</p>

                    <a href="../#calendario" class="btn btn-reservar">Reservar este tipo de evento</a>
                </div>
            </div>
        </section>
    </main>

    <?php include '../components/footer.php'; ?>
</body>

</html>