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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Panel Admin - Disponibilidad</title>

    <link rel="stylesheet" href="../assets/css/main.css">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bg-light);
            color: var(--text-dark);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: var(--primary);
            font-size: clamp(2rem, 5vw, 2.8rem);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
            padding-bottom: 1rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Calendario */
        #calendar {
            max-width: 100%;
            margin: 2rem auto;
            background: #fff;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(200, 155, 123, 0.1);
        }

        /* Toolbar */
        .fc .fc-toolbar {
            padding: 0.5rem 0;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem !important;
        }

        .fc .fc-toolbar-title {
            font-size: clamp(1.2rem, 4vw, 1.5rem);
            color: var(--primary);
            font-weight: 600;
        }

        /* Botones del calendario */
        .fc-toolbar .fc-button,
        .fc .fc-button,
        .fc .fc-button-primary,
        .fc-button-enabled,
        .fc-today-button,
        .fc-prev-button,
        .fc-next-button {
            background: var(--primary) !important;
            border: none !important;
            color: white !important;
            box-shadow: var(--shadow-sm) !important;
            font-weight: 500 !important;
            padding: 0.6rem 1.2rem !important;
            border-radius: var(--radius-sm) !important;
            transition: var(--transition) !important;
            text-transform: uppercase !important;
            font-size: 0.9rem !important;
            letter-spacing: 0.5px !important;
        }

        .fc-toolbar .fc-button:hover,
        .fc .fc-button:hover,
        .fc .fc-button-primary:hover,
        .fc-button-enabled:hover,
        .fc-today-button:hover,
        .fc-prev-button:hover,
        .fc-next-button:hover {
            background: var(--primary-dark) !important;
            transform: translateY(-2px) !important;
            box-shadow: var(--shadow-md) !important;
        }

        .fc-toolbar .fc-button:active,
        .fc-toolbar .fc-button:focus,
        .fc .fc-button:active,
        .fc .fc-button:focus {
            transform: translateY(0) !important;
            box-shadow: none !important;
        }

        /* Días del calendario */
        .fc-daygrid-day {
            transition: var(--transition);
            cursor: pointer;
        }

        .fc-daygrid-day:hover {
            transform: scale(0.98);
            z-index: 5;
        }

        .fc-daygrid-day.fc-day-today {
            background: #fff3cd !important;
            box-shadow: inset 0 0 0 2px #ffc107;
        }

        /* Estados */
        .disponible {
            background-color: #d4edda !important;
            border: 2px solid #28a745 !important;
            color: #155724 !important;
            cursor: pointer;
            font-weight: 600;
            position: relative;
        }

        .disponible::after {
            content: '✓';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.8rem;
            color: #28a745;
            font-weight: bold;
        }

        .deshabilitado {
            background-color: #f8f9fa !important;
            border: 1px dashed #ccc !important;
            color: #6c757d !important;
            cursor: pointer;
            position: relative;
        }

        .deshabilitado::after {
            content: '✗';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.8rem;
            color: #999;
        }

        .ocupado {
            background-color: #f8d7da !important;
            border: 2px solid #dc3545 !important;
            color: #721c24 !important;
            pointer-events: none;
            opacity: 0.9;
            position: relative;
        }

        .ocupado::after {
            content: '🔒';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Leyenda */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin: 2rem auto 0;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            max-width: 800px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .legend-color {
            width: 30px;
            height: 30px;
            border-radius: 6px;
        }

        .legend-color.disponible {
            background: #d4edda !important;
            border: 2px solid #28a745;
        }

        .legend-color.deshabilitado {
            background: #f8f9fa !important;
            border: 2px dashed #ccc;
        }

        .legend-color.ocupado {
            background: #f8d7da !important;
            border: 2px solid #dc3545;
        }

        .legend-text {
            font-weight: 500;
            color: var(--text-dark);
        }

        .legend-text small {
            display: block;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Mensaje flotante */
        .mensaje-flotante {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff9800;
            color: white;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            max-width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 4px solid rgba(255,255,255,0.5);
        }

        .mensaje-flotante.error {
            background: #f44336;
        }

        .mensaje-flotante.success {
            background: #4caf50;
        }

        .mensaje-flotante.info {
            background: #ff9800;
        }

        .mensaje-flotante.mostrar {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .mensaje-flotante i {
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem 0.5rem;
            }

            #calendar {
                padding: 0.8rem;
                margin: 1rem auto;
            }

            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.8rem;
            }

            .fc .fc-toolbar .fc-left,
            .fc .fc-toolbar .fc-center,
            .fc .fc-toolbar .fc-right {
                width: 100%;
                text-align: center;
            }

            .fc .fc-button-group,
            .fc .fc-toolbar .fc-button {
                width: auto;
                min-width: 70px;
            }

            .legend {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
                padding: 1rem;
                margin: 1.5rem 1rem;
            }

            .legend-item {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .fc-daygrid-day-number {
                font-size: 0.8rem;
            }

            .disponible::after,
            .deshabilitado::after,
            .ocupado::after {
                font-size: 0.7rem;
                top: 1px;
                right: 1px;
            }

            .mensaje-flotante {
                padding: 0.6rem 1.2rem;
                font-size: 0.85rem;
                top: 10px;
            }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="admin-container">
    <div class="page-header">
        <h1 class="page-title">Gestión de Disponibilidad</h1>
        <p class="page-subtitle">Haz clic en cualquier día para habilitarlo o deshabilitarlo</p>
    </div>

    <div class="mensaje-flotante" id="mensaje-flotante">
        <i class="fas fa-info-circle"></i>
        <span id="mensaje-texto"></span>
    </div>

    <div id="calendar"></div>

    <!-- Leyenda de colores -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color disponible"></div>
            <div class="legend-text">
                Disponible <small>(haz clic para deshabilitar)</small>
            </div>
        </div>
        <div class="legend-item">
            <div class="legend-color deshabilitado"></div>
            <div class="legend-text">
                Deshabilitado <small>(haz clic para habilitar)</small>
            </div>
        </div>
        <div class="legend-item">
            <div class="legend-color ocupado"></div>
            <div class="legend-text">
                Ocupado <small>(no se puede modificar)</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mensajeDiv = document.getElementById('mensaje-flotante');
    const mensajeTexto = document.getElementById('mensaje-texto');
    const calendarEl = document.getElementById('calendar');

    let ultimaAccion = null; 
    let ultimaFecha = null;  

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

            // Validar que la fecha no sea pasada
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fechaClick = new Date(fecha);
            fechaClick.setHours(0, 0, 0, 0);
            
            if (fechaClick < hoy) {
                mostrarMensaje('No se pueden modificar días pasados', 'error');
                return;
            }

            let accion, mensaje, tipoMensaje = 'info';

            if (ultimaFecha === fecha && ultimaAccion) {
                return;
            }

            if (eventObj && eventObj.classNames.includes('disponible')) {
                accion = 'deshabilitar';
                mensaje = 'Día deshabilitado correctamente';
                tipoMensaje = 'success';
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
                mensaje = 'Día habilitado correctamente';
                tipoMensaje = 'success';
                if (eventObj) eventObj.remove();
                calendar.addEvent({
                    title: 'Disponible',
                    start: fecha,
                    allDay: true,
                    classNames: ['disponible'],
                    editable: false
                });
            } else if (eventObj && eventObj.classNames.includes('ocupado')) {
                mostrarMensaje('Este día está ocupado, no se puede modificar', 'error');
                return;
            }

            ultimaFecha = fecha;
            ultimaAccion = accion;

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
                if (!data.success) {
                    mostrarMensaje('Error: ' + data.error, 'error');
                } else {
                    mostrarMensaje(mensaje, tipoMensaje);
                }
                
                setTimeout(() => {
                    ultimaFecha = null;
                    ultimaAccion = null;
                }, 1000);
            })
            .catch(() => {
                mostrarMensaje('Error de conexión', 'error');
                setTimeout(() => {
                    ultimaFecha = null;
                    ultimaAccion = null;
                }, 1000);
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

    function mostrarMensaje(texto, tipo = 'info') {
        if (mensajeDiv.timeout) {
            clearTimeout(mensajeDiv.timeout);
        }

        mensajeTexto.innerText = texto;
        mensajeDiv.className = 'mensaje-flotante ' + tipo;
        
        const icono = mensajeDiv.querySelector('i');
        if (icono) {
            if (tipo === 'success') icono.className = 'fas fa-check-circle';
            else if (tipo === 'error') icono.className = 'fas fa-exclamation-circle';
            else icono.className = 'fas fa-info-circle';
        }

        mensajeDiv.classList.add('mostrar');

        mensajeDiv.timeout = setTimeout(() => {
            mensajeDiv.classList.remove('mostrar');
        }, 2500);
    }
});
</script>

</body>
</html>