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
        // Swiper
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

        // FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto'
            });
            calendar.render();
        });
    </script>

</body>

</html>