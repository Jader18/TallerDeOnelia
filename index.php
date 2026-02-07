<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El Taller de Onelia</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>
<body>

    <header>
        <div class="container">
            <h1>El Taller de Onelia</h1>
            <nav>
                <a href="#">Inicio</a>
                <a href="#calendario">Reservar</a>
                <a href="#">Contacto</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Decoraciones Personalizadas</h1>
                <p>Baby showers, bodas, cumpleaños y más.</p>
                <a href="#calendario" class="btn">Ver disponibilidad</a>
            </div>
        </section>

        <section class="calendario" id="calendario">
            <div class="container">
                <h2>Disponibilidad</h2>
                <div id="calendar"></div>
            </div>
        </section>

        <section class="arreglos">
            <div class="container">
                <h2>Nuestros Tipos de Arreglos</h2>
                <?php include 'components/tipos-arreglos.php'; ?>
            </div>
        </section>

        <section class="formulario">
            <div class="container">
                <h2>Reservar fecha</h2>
                <p>Formulario en desarrollo.</p>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> El Taller de Onelia</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                height: 'auto'
            });
            calendar.render();
        });
    </script>
</body>
</html>