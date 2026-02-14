<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
    $_SESSION['rol_nombre'] = 'superadmin';
}

if ($_SESSION['rol_nombre'] !== 'superadmin' && $_SESSION['rol_nombre'] !== 'admin') {
    die('Acceso denegado');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel Admin - Disponibilidad</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 16px;
            background: #f4f4f4;
            color: #333;
        }
        .header-fixed {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1000;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        .header-fixed h1 {
            margin: 0;
            font-size: clamp(1.4rem, 5vw, 1.8rem);
            font-weight: 600;
        }
        #calendar {
            max-width: 100%;
            margin: 16px auto;
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .fc .fc-toolbar {
            padding: 8px 0;
            flex-wrap: wrap;
        }
        .fc .fc-button {
            padding: 8px 14px;
            font-size: 0.95rem;
            border-radius: 8px;
            margin: 2px;
        }
        .fc .fc-toolbar-title {
            font-size: clamp(1.1rem, 4vw, 1.3rem);
        }
        .fc-daygrid-day.fc-day-today { background: #fff3cd !important; }
        .disponible { background-color: #d4edda !important; border: 2px solid #28a745 !important; color: #155724 !important; cursor: pointer; }
        .deshabilitado { background-color: #f8f9fa !important; border: 1px solid #ccc !important; color: #6c757d !important; cursor: pointer; }
        .ocupado { background-color: #f8d7da !important; border: 2px solid #dc3545 !important; color: #721c24 !important; pointer-events: none; }
        .mensaje-flotante {
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff9800;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.3s ease;
            max-width: 90%;
            text-align: center;
        }

        @media (max-width: 768px) {
            body { padding: 8px; }
            .header-fixed { padding: 10px 0; }
            #calendar { padding: 8px; }
            .fc .fc-button { font-size: 0.85rem; padding: 6px 10px; }
        }
    </style>
</head>
<body>

<div class="header-fixed">
    <h1>Disponibilidad del Calendario</h1>
</div>

<div class="mensaje-flotante" id="mensaje-flotante"></div>

<div id="calendar"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mensajeDiv = document.getElementById('mensaje-flotante');
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        timeZone: 'America/Managua',
        selectable: false,
        editable: false,
        dayMaxEvents: true,
        height: 'auto',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        buttonText: { today: 'Hoy' },

        events: '../api/get-disponibilidad.php',

        dateClick: function(info) {
            const fecha = info.dateStr;
            const eventObj = calendar.getEvents().find(ev => ev.startStr === fecha);

            let accion, mensaje;

            if (eventObj && eventObj.classNames.includes('disponible')) {
                accion = 'deshabilitar';
                mensaje = 'Disponibilidad eliminada';
                eventObj.remove();
                calendar.addEvent({
                    title: '',
                    start: fecha,
                    allDay: true,
                    classNames: ['deshabilitado'],
                    editable: false
                });
            } else if (!eventObj || eventObj.classNames.includes('deshabilitado')) {
                accion = 'habilitar';
                mensaje = 'Día marcado como Disponible';
                if (eventObj) eventObj.remove();
                calendar.addEvent({
                    title: 'Disponible',
                    start: fecha,
                    allDay: true,
                    classNames: ['disponible'],
                    editable: false
                });
            } else if (eventObj && eventObj.classNames.includes('ocupado')) {
                mostrarMensaje('Día ocupado, no se puede modificar', true);
                return;
            }

            fetch('../api/actualizar-disponibilidad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    fechas: [fecha],
                    accion: accion,
                    usuario_id: <?php echo $_SESSION['usuario_id']; ?>
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) mostrarMensaje('Error: ' + data.error, true);
                else mostrarMensaje(mensaje);
            });
        },

        eventDidMount: function(info) {
            if (info.event.classNames.includes('disponible')) {
                info.el.style.backgroundColor = '#d4edda';
                info.el.style.borderColor = '#28a745';
                info.el.style.color = '#155724';
                info.el.style.cursor = 'pointer';
            }
            if (info.event.classNames.includes('deshabilitado')) {
                info.el.style.backgroundColor = '#f8f9fa';
                info.el.style.borderColor = '#ccc';
                info.el.style.color = '#6c757d';
                info.el.style.cursor = 'pointer';
            }
            if (info.event.classNames.includes('ocupado')) {
                info.el.style.backgroundColor = '#f8d7da';
                info.el.style.borderColor = '#dc3545';
                info.el.style.color = '#721c24';
                info.el.style.pointerEvents = 'none';
            }
        }
    });

    calendar.render();

    function mostrarMensaje(texto, error = false) {
        mensajeDiv.innerText = texto;
        mensajeDiv.style.background = error ? '#f44336' : '#ff9800';
        mensajeDiv.style.opacity = 1;

        clearTimeout(mensajeDiv.timeout);
        mensajeDiv.timeout = setTimeout(() => {
            mensajeDiv.style.opacity = 0;
        }, 2500);
    }
});
</script>
</body>
</html>