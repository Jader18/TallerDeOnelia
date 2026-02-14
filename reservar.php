<?php
require_once 'config/database.php';

// Cargar tipos de evento con precio
$stmt = $pdo->query("SELECT id, nombre, precio_base FROM tipos_evento WHERE activo = 1 ORDER BY nombre");
$tipos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar - El Taller de Onelia</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
</head>

<body>

    <?php include 'components/header.php'; ?>

    <main>
        <section class="reserva">
            <div class="container">
                <h1>Reservar tu fecha</h1>
                <p>Selecciona el tipo de evento, fecha y hora disponible. Te contactaremos para confirmar detalles.</p>

                <form id="form-reserva" class="form-reserva">
                    <div class="form-group">
                        <label for="tipo_evento_id">Tipo de evento *</label>
                        <select name="tipo_evento_id" id="tipo_evento_id" required>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>" data-precio="<?= $t['precio_base'] ?>">
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="precio-evento" style="margin-top: 0.5rem; font-weight: 600; color: var(--secondary);"></div>
                    </div>

                    <div class="form-group">
                        <label for="fecha_evento">Fecha *</label>
                        <div id="reserva-calendar"></div>
                        <input type="date" name="fecha_evento" id="fecha_evento" required min="<?= date('Y-m-d') ?>">
                        <span id="fecha-mensaje" style="color: #dc3545; font-size: 0.9rem; display: none;">La fecha no está disponible</span>
                    </div>

                    <div class="form-group">
                        <label for="hora_inicio">Hora de inicio *</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" required>
                    </div>

                    <div class="form-group">
                        <label for="hora_fin">Hora de fin</label>
                        <input type="time" name="hora_fin" id="hora_fin">
                    </div>

                    <div class="form-group">
                        <label for="cliente_nombre">Nombre completo *</label>
                        <input type="text" name="cliente_nombre" id="cliente_nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="cliente_email">Email *</label>
                        <input type="email" name="cliente_email" id="cliente_email" required>
                    </div>

                    <div class="form-group">
                        <label for="cliente_telefono">Teléfono *</label>
                        <input type="tel" name="cliente_telefono" id="cliente_telefono" required>
                    </div>

                    <div class="form-group">
                        <label for="direccion_evento">Dirección del evento *</label>
                        <input type="text" name="direccion_evento" id="direccion_evento" required>
                    </div>

                    <div class="form-group">
                        <label for="notas">Notas adicionales (incluye detalles para personalizar tu decoración) *</label>
                        <textarea name="notas" id="notas" rows="4" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-reservar" id="btn-enviar">Enviar reserva</button>
                </form>

                <div id="respuesta-form" style="margin-top: 2rem; padding: 1rem; border-radius: 8px;"></div>
            </div>
        </section>

        <section class="gestion-reserva">
            <div class="container">
                <h2>Ver estado o cancelar reserva</h2>
                <form id="form-gestion">
                    <div class="form-group">
                        <label for="order_id">Número de orden (TO-XXXXX) *</label>
                        <input type="text" name="order_id" id="order_id" required placeholder="Ej: TO-12345" pattern="TO-[0-9]{5}" title="Formato: TO seguido de 5 números">
                    </div>
                    <button type="submit" class="btn">Ver estado</button>
                    <button type="button" id="cancelar-reserva" class="btn btn-danger">Cancelar reserva</button>
                </form>
                <div id="respuesta-gestion" style="margin-top: 2rem; padding: 1rem; border-radius: 8px;"></div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        // Mostrar precio del tipo de evento seleccionado
        document.getElementById('tipo_evento_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const precio = selectedOption.dataset.precio;
            const precioDiv = document.getElementById('precio-evento');
            if (precio && precio > 0) {
                precioDiv.innerHTML = `Precio base: C$${parseFloat(precio).toLocaleString('es-NI', {minimumFractionDigits: 2})}`;
            } else {
                precioDiv.innerHTML = '';
            }
        });

        // Validación de fecha en tiempo real
        document.getElementById('fecha_evento').addEventListener('change', function() {
            const fecha = this.value;
            const mensaje = document.getElementById('fecha-mensaje');
            const btnEnviar = document.getElementById('btn-enviar');

            if (fecha) {
                fetch('api/check-fecha-disponible.php?fecha=' + fecha)
                    .then(r => r.json())
                    .then(data => {
                        if (data.disponible) {
                            mensaje.style.display = 'none';
                            btnEnviar.disabled = false;
                        } else {
                            mensaje.style.display = 'block';
                            mensaje.innerHTML = 'La fecha no está disponible';
                            btnEnviar.disabled = true;
                        }
                    })
                    .catch(() => {
                        mensaje.style.display = 'block';
                        mensaje.innerHTML = 'Error al verificar disponibilidad';
                        btnEnviar.disabled = true;
                    });
            } else {
                mensaje.style.display = 'none';
                btnEnviar.disabled = false;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const reservaCalendarEl = document.getElementById('reserva-calendar');
            if (reservaCalendarEl) {
                const reservaCalendar = new FullCalendar.Calendar(reservaCalendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    timeZone: 'America/Managua',
                    height: 460,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: ''
                    },
                    buttonText: {
                        today: 'Hoy'
                    },
                    events: 'api/get-disponibilidad.php',

                    // 🔹 COLORES CORREGIDOS (igual que el primer calendario)
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
                        const eventsOnDate = reservaCalendar.getEvents().filter(ev => ev.startStr === info.dateStr);
                        const isDisponible = eventsOnDate.some(ev => ev.classNames.includes('disponible'));
                        const btnEnviar = document.getElementById('btn-enviar');
                        const mensaje = document.getElementById('fecha-mensaje');

                        if (isDisponible) {
                            document.getElementById('fecha_evento').value = info.dateStr;
                            document.getElementById('fecha_evento').dispatchEvent(new Event('change'));
                            mensaje.style.display = 'none';
                            btnEnviar.disabled = false;
                        } else {
                            mensaje.style.display = 'block';
                            mensaje.innerHTML = 'La fecha no está disponible';
                            btnEnviar.disabled = true;
                        }
                    }
                });

                reservaCalendar.render();
            }
        });

        // Envío del formulario de reserva
        document.getElementById('form-reserva').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const respuesta = document.getElementById('respuesta-form');

            fetch('api/crear-reserva.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    respuesta.style.background = data.success ? '#d4edda' : '#f8d7da';
                    respuesta.style.color = data.success ? '#155724' : '#721c24';
                    respuesta.innerHTML = data.message;
                    if (data.success) {
                        this.reset();
                    }
                })
                .catch(() => {
                    respuesta.style.background = '#f8d7da';
                    respuesta.style.color = '#721c24';
                    respuesta.innerHTML = 'Error de conexión. Intenta de nuevo.';
                });
        });

        // Gestión de reserva
        document.getElementById('form-gestion').addEventListener('submit', function(e) {
            e.preventDefault();
            const orderId = document.getElementById('order_id').value.trim();
            const respuesta = document.getElementById('respuesta-gestion');

            if (!/^TO-[0-9]{5}$/.test(orderId)) {
                respuesta.style.background = '#f8d7da';
                respuesta.style.color = '#721c24';
                respuesta.innerHTML = 'Formato inválido: debe ser TO seguido de 5 números';
                return;
            }

            fetch('api/gestion-reserva.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        accion: 'estado'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    respuesta.style.background = data.success ? '#d4edda' : '#f8d7da';
                    respuesta.style.color = data.success ? '#155724' : '#721c24';
                    respuesta.innerHTML = data.message;
                });
        });

        document.getElementById('cancelar-reserva').addEventListener('click', function() {
            const orderId = document.getElementById('order_id').value.trim();
            const respuesta = document.getElementById('respuesta-gestion');

            if (!/^TO-[0-9]{5}$/.test(orderId)) {
                respuesta.style.background = '#f8d7da';
                respuesta.style.color = '#721c24';
                respuesta.innerHTML = 'Formato inválido: debe ser TO seguido de 5 números';
                return;
            }

            const motivo = prompt('Ingresa el motivo de cancelación:');
            if (!motivo || motivo.trim() === '') {
                respuesta.style.background = '#f8d7da';
                respuesta.style.color = '#721c24';
                respuesta.innerHTML = 'Debes ingresar un motivo para cancelar';
                return;
            }

            if (confirm('¿Seguro que quieres cancelar esta reserva?')) {
                fetch('api/gestion-reserva.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            accion: 'cancelar',
                            motivo: motivo.trim()
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        respuesta.style.background = data.success ? '#d4edda' : '#f8d7da';
                        respuesta.style.color = data.success ? '#155724' : '#721c24';
                        respuesta.innerHTML = data.message;
                    });
            }
        });
    </script>


</body>

</html>