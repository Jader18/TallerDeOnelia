<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El Taller de Onelia</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

    <!-- 🔴 Color rojito quemado para botones del calendario -->
    <style>
        .fc .fc-button {
            background-color: #8B2E2E !important;
            border-color: #721c24 !important;
            color: #ffffff !important;
        }

        .fc .fc-button:hover {
            background-color: #721c24 !important;
            border-color: #5a1a1f !important;
        }

        .fc .fc-button:active,
        .fc .fc-button:focus {
            background-color: #721c24 !important;
            border-color: #5a1a1f !important;
            box-shadow: none !important;
        }
    </style>

</head>

<body>

    <?php include 'components/header.php'; ?>

    <main id="inicio">
        <section class="hero">
            <h1>Decoramos tus momentos más especiales</h1>
        </section>

        <section class="carrusel" id="galeria">
            <div class="swiper mySwiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide"><img src="assets/img/carrusel/aire-libre1.jpg" alt="Decoración al aire libre"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/baby1.jpeg" alt="Decoración para baby shower"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/cumple1.jpeg" alt="Decoración para cumpleaños"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/cumple2.jpg" alt="Decoración para cumpleaños"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/cumple3.jpg" alt="Decoración para cumpleaños"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/holiday1.jpeg" alt="Decoración navideña"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/holiday2.jpeg" alt="Decoración navideña"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/propuesta1.jpeg" alt="Decoración propuesta matrimonio"></div>
                    <div class="swiper-slide"><img src="assets/img/carrusel/quince1.jpg" alt="Decoración 15 años"></div>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </section>

        <section class="eventos" id="eventos">
            <div class="container">
                <h2>Tipos de Eventos</h2>
                <?php include 'components/tipos-eventos.php'; ?>
            </div>
        </section>

        <section class="calendario" id="calendario">
            <div class="container">
                <h2>Disponibilidad</h2>
                <div id="calendar"></div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            console.log('Inicializando FullCalendar');

            const calendarEl = document.getElementById('calendar');

            if (!calendarEl) {
                console.error('Elemento #calendar no encontrado');
                return;
            }

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                timeZone: 'America/Managua',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                events: 'api/get-disponibilidad.php',
                eventDidMount: function(info) {
                    if (info.event.classNames.includes('disponible')) {
                        info.el.style.backgroundColor = '#2ea74a';
                        info.el.style.borderColor = '#154920';
                        info.el.style.color = '#146e29';
                    } else if (info.event.classNames.includes('ocupado')) {
                        info.el.style.backgroundColor = '#f8d7da';
                        info.el.style.borderColor = '#dc3545';
                        info.el.style.color = '#721c24';
                    }
                }
            });

            calendar.setOption('buttonText', {
                today: 'Hoy',
                month: 'Mes'
            });

            calendar.render();

            setTimeout(function() {
                calendar.updateSize();
            }, 300);

        });


        const swiper = new Swiper(".mySwiper", {
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev"
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true
            },
        });
    </script>

</body>

</html>
