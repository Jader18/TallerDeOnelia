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

    <style>
        #calendar .fc-button {
            background: var(--primary) !important;
            border: none !important;
            color: white !important;
        }

        #calendar .fc-button:hover {
            background: #5e3a2d !important;
        }

        @media (max-width: 768px) {
            .btn-reservar-cal {
                font-size: 1.1rem;
                padding: 0.9rem 1.8rem;
            }
        }

        /* Modal personalizado usando clases del CSS */
        #custom-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #custom-modal .modal-content {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        
        /* Animación para hero */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

</head>

<body>

    <?php include 'components/header.php'; ?>

    <main id="inicio">
        <!-- Hero mejorado -->
        <section class="hero" style="padding: 4rem 0 3rem; background: linear-gradient(135deg, #fff 0%, var(--bg-light) 100%);">
            <h1 style="font-size: clamp(2.2rem, 6vw, 3.5rem); color: var(--primary); margin-bottom: 1rem; animation: fadeInUp 0.8s ease;">Decoramos tus momentos más especiales</h1>
            <p style="font-size: 1.2rem; color: var(--text-dark); opacity: 0.8; max-width: 700px; margin: 0 auto; animation: fadeInUp 1s ease;">Creando experiencias únicas con detalles que enamoran</p>
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
                <h2 style="text-align: center; font-size: 2.5rem; color: var(--primary); margin-bottom: 2rem; position: relative; display: inline-block; width: 100%;">
                    Tipos de Eventos
                    <span style="display: block; width: 80px; height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); margin: 0.5rem auto 0; border-radius: 3px;"></span>
                </h2>
                <?php include 'components/tipos-eventos.php'; ?>
            </div>
        </section>

        <section class="calendario" id="calendario">
            <div class="container">
                <h2 style="text-align: center; font-size: 2.5rem; color: var(--primary); margin-bottom: 2rem; position: relative; display: inline-block; width: 100%;">
                    Disponibilidad
                    <span style="display: block; width: 80px; height: 3px; background: linear-gradient(90deg, var(--primary), var(--secondary)); margin: 0.5rem auto 0; border-radius: 3px;"></span>
                </h2>
                <a href="reservar.php" class="btn btn-reservar" style="display: block; width: 100%; max-width: 300px; margin: 1rem auto 2rem;">Reservar</a>
                <div id="calendar"></div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <!-- Modal HTML con botón usando clases del CSS -->
    <div id="custom-modal">
        <div class="modal-content">
            <p id="modal-message"></p>
            <button id="modal-close" class="btn btn-reservar" style="padding: 0.6rem 1.4rem;">Entendido</button>
        </div>
    </div>

    <script>
        function showModal(message) {
            const modal = document.getElementById('custom-modal');
            const msg = document.getElementById('modal-message');
            const btn = document.getElementById('modal-close');

            msg.innerText = message;
            modal.style.display = 'flex';

            btn.onclick = function() {
                modal.style.display = 'none';
            };
        }

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
                },
                dateClick: function(info) {
                    const eventsOnDate = calendar.getEvents().filter(ev => ev.startStr === info.dateStr);
                    const isDisponible = eventsOnDate.some(ev => ev.classNames.includes('disponible'));

                    if (isDisponible) {
                        window.location.href = `reservar.php?fecha=${info.dateStr}`;
                    } else {
                        showModal('La fecha no está disponible. Por favor selecciona otra.');
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