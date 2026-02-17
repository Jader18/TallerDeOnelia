<?php
session_start();
require_once '../config/database.php';

// Tiempo máximo de inactividad: 20 minutos (1200 segundos)
$session_timeout = 1200;

if (isset($_SESSION['ultimo_acceso'])) {
    $inactividad = time() - $_SESSION['ultimo_acceso'];
    if ($inactividad > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?expired=1");
        exit;
    }
}

$_SESSION['ultimo_acceso'] = time();

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Protección real
if (!isset($_SESSION['usuario_id']) || 
    !isset($_SESSION['rol_nombre']) || 
    !in_array($_SESSION['rol_nombre'], ['superadmin', 'admin'])) {
    header("Location: login.php");
    exit;
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

        .admin-header {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1000;
            padding: 12px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .admin-title {
            margin: 0;
            font-size: clamp(1.4rem, 5vw, 1.8rem);
            font-weight: 600;
            color: var(--primary);
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .user-name {
            font-weight: 500;
            color: #555;
            font-size: 1rem;
        }

        .btn-action {
            padding: 0.6rem 1.4rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-back {
            background: #7a4a3a;
            color: white;
        }

        .btn-back:hover {
            background: #5e3a2d;
            transform: translateY(-1px);
        }

        .btn-logout {
            background: #dc3545;
            color: white;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        #calendar {
            max-width: 100%;
            margin: 24px auto 16px;
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .fc .fc-toolbar {
            padding: 8px 0;
            flex-wrap: wrap;
        }

        .fc .fc-toolbar-title {
            font-size: clamp(1.1rem, 4vw, 1.3rem);
        }

        .fc-daygrid-day.fc-day-today {
            background: #fff3cd !important;
        }

        .disponible {
            background-color: #d4edda !important;
            border: 2px solid #28a745 !important;
            color: #155724 !important;
            cursor: pointer;
        }

        .deshabilitado {
            background-color: #f8f9fa !important;
            border: 1px solid #ccc !important;
            color: #6c757d !important;
            cursor: pointer;
        }

        .ocupado {
            background-color: #f8d7da !important;
            border: 2px solid #dc3545 !important;
            color: #721c24 !important;
            pointer-events: none;
        }

        .fc-toolbar .fc-button,
        .fc .fc-button,
        .fc .fc-button-primary,
        .fc-button-enabled,
        .fc-today-button,
        .fc-prev-button,
        .fc-next-button {
            background: #7a4a3a !important;
            border: none !important;
            color: white !important;
            box-shadow: none !important;
            font-weight: 500 !important;
        }

        .fc-toolbar .fc-button:hover,
        .fc .fc-button:hover,
        .fc .fc-button-primary:hover,
        .fc-button-enabled:hover,
        .fc-today-button:hover,
        .fc-prev-button:hover,
        .fc-next-button:hover {
            background: #5e3a2d !important;
        }

        .fc-toolbar .fc-button:active,
        .fc-toolbar .fc-button:focus,
        .fc .fc-button:active,
        .fc .fc-button:focus,
        .fc .fc-button-primary:active,
        .fc .fc-button-primary:focus,
        .fc-button-enabled:active,
        .fc-button-enabled:focus,
        .fc-today-button:active,
        .fc-today-button:focus,
        .fc-prev-button:active,
        .fc-prev-button:focus,
        .fc-next-button:active,
        .fc-next-button:focus {
            background: #5e3a2d !important;
            border-color: #5e3a2d !important;
            box-shadow: none !important;
            outline: none !important;
        }

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
            .admin-header { flex-direction: column; gap: 1rem; text-align: center; padding: 12px 16px; }
            .user-controls { justify-content: center; flex-wrap: wrap; gap: 1rem; }
            #calendar { padding: 8px; }
        }
    </style>
</head>
<body>

<div class="admin-header">
    <h1 class="admin-title">Disponibilidad del Calendario</h1>
    <div class="user-controls">
        <span class="user-name">Bienvenido(a), <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></span>
        <a href="/" class="btn-action btn-back">Volver al sitio</a>
        <a href="logout.php" class="btn-action btn-logout">Cerrar sesión</a>
    </div>
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

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },

        buttonText: {
            today: 'Hoy'
        },

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
                    usuario_id: <?php echo json_encode($_SESSION['usuario_id']); ?>
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) mostrarMensaje('Error: ' + data.error, true);
                else mostrarMensaje(mensaje);
            })
            .catch(() => {
                mostrarMensaje('Error de conexión', true);
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