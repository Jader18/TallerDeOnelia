<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="El Taller de Onelia: Decoraciones personalizadas para baby showers, bodas, cumpleaños y eventos en Managua.">
    <title>El Taller de Onelia - Decoraciones Personalizadas</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>
<body>

    <?php include 'components/header.php'; ?>

    <main>
        <!-- Hero mejorado -->
        <section class="hero">
            <div class="container">
                <h1>Decoraciones Únicas para Momentos Inolvidables</h1>
                <p>El Taller de Onelia crea arreglos personalizados para baby showers, bodas, cumpleaños, 15 años y eventos especiales en Managua.</p>
                <a href="#calendario" class="btn-primary">Ver disponibilidad y reservar</a>
            </div>
        </section>

        <!-- Calendario de disponibilidad -->
        <section class="calendario" id="calendario">
            <div class="container">
                <h2>Disponibilidad Actual</h2>
                <div id="calendar"></div>
            </div>
        </section>

        <!-- Galería de tipos de arreglos (dinámica desde BD) -->
        <section class="arreglos">
            <div class="container">
                <h2>Nuestros Tipos de Arreglos</h2>
                <?php include 'components/tipos-arreglos.php'; ?>
            </div>
        </section>

        <!-- Placeholder para formulario de reserva (lo desarrollamos completo después) -->
        <section class="formulario-reserva">
            <div class="container">
                <h2>Reserva tu Fecha</h2>
                <p>Selecciona una fecha disponible en el calendario y completa el formulario para reservar. Próximamente disponible el formulario completo con selección de tipo de arreglo, hora y detalles del evento.</p>
                <a href="#calendario" class="btn-primary">Ver calendario</a>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                timeZone: 'Europe/Amsterdam',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                height: 'auto',
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch('api/get-disponibilidad.php')
                        .then(response => {
                            if (!response.ok) throw new Error('Error en la respuesta del servidor');
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);
                            successCallback(data);
                        })
                        .catch(error => {
                            console.error('Error cargando eventos:', error);
                            failureCallback(error);
                        });
                },
                eventDidMount: function(info) {
                    if (info.event.extendedProps.className === 'ocupado') {
                        info.el.style.backgroundColor = '#ff4d4d';
                        info.el.style.borderColor = '#cc0000';
                        info.el.style.color = 'white';
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>