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
                            <option value="" disabled selected hidden>Selecciona un tipo de evento</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>" data-precio="<?= $t['precio_base'] ?>">
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="precio-evento" style="margin-top:0.5rem;font-weight:600;color:var(--secondary);"></div>
                    </div>

                    <div class="form-group">
                        <label for="fecha_evento">Fecha *</label>
                        <div id="reserva-calendar"></div>
                        <input type="date" name="fecha_evento" id="fecha_evento" required min="<?= date('Y-m-d') ?>">
                        <span id="fecha-mensaje" style="color:#dc3545;font-size:0.9rem;display:none;">La fecha no está disponible</span>
                    </div>

                    <div class="form-group">
                        <label for="hora_inicio">Hora de inicio *</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" required>
                    </div>

                    <div class="form-group">
                        <label for="hora_fin">Hora de fin *</label>
                        <input type="time" name="hora_fin" id="hora_fin" required>
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

                <div id="respuesta-form" style="margin-top:2rem;padding:1rem;border-radius:8px;"></div>
            </div>
        </section>

        <section class="gestion-reserva">
            <div class="container">
                <h2>Ver estado o cancelar reserva</h2>

                <form id="form-gestion">
                    <div class="form-group">
                        <label for="order_id">Número de orden (TO-XXXXX) *</label>
                        <input type="text" name="order_id" id="order_id" required placeholder="Ej: TO-12345" pattern="TO-[0-9]{5}">
                    </div>
                    <button type="submit" class="btn">Ver estado</button>
                    <button type="button" id="cancelar-reserva" class="btn btn-danger">Cancelar reserva</button>
                </form>

                <div id="respuesta-gestion" style="margin-top:2rem;padding:1rem;border-radius:8px;"></div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <!-- Modal personalizado -->
    <div id="custom-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:2rem;border-radius:12px;width:90%;max-width:400px;text-align:center;">
            <p id="modal-message" style="margin-bottom:1.5rem;"></p>
            <div id="modal-buttons"></div>
        </div>
    </div>

    <script>
        function mostrarMensaje(div, bg, color, mensaje) {
            div.style.background = bg;
            div.style.color = color;
            div.innerHTML = mensaje;
            div.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function showModal(message, type = 'alert') {
            return new Promise(resolve => {
                const modal = document.getElementById('custom-modal');
                const msg = document.getElementById('modal-message');
                const buttons = document.getElementById('modal-buttons');
                msg.innerText = message;
                buttons.innerHTML = '';
                modal.style.display = 'flex';

                if (type === 'confirm') {
                    buttons.innerHTML = `<button id="ok" class="btn" style="margin-right:10px;">Confirmar</button>
                    <button id="cancel" class="btn btn-danger">Cancelar</button>`;
                    document.getElementById('ok').onclick = () => {
                        modal.style.display = 'none';
                        resolve(true);
                    }
                    document.getElementById('cancel').onclick = () => {
                        modal.style.display = 'none';
                        resolve(false);
                    }
                } else if (type === 'prompt') {
                    buttons.innerHTML = `<input type="text" id="modal-input" class="form-control" style="margin-bottom:1rem;width:100%;padding:0.5rem;">
                    <button id="ok" class="btn">Aceptar</button>`;
                    document.getElementById('ok').onclick = () => {
                        const value = document.getElementById('modal-input').value;
                        modal.style.display = 'none';
                        resolve(value);
                    }
                }
            });
        }

        // Precio dinámico
        document.getElementById('tipo_evento_id').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const precio = selected.dataset.precio;
            const div = document.getElementById('precio-evento');
            if (precio && precio > 0) {
                div.innerHTML = `Precio base: C$${parseFloat(precio).toLocaleString('es-NI',{minimumFractionDigits:2})}`;
            } else {
                div.innerHTML = '';
            }
        });

        // Validación fecha
        document.getElementById('fecha_evento').addEventListener('change', function() {
            const fecha = this.value;
            const mensaje = document.getElementById('fecha-mensaje');
            const btn = document.getElementById('btn-enviar');

            if (fecha) {
                fetch('api/check-fecha-disponible.php?fecha=' + fecha)
                    .then(r => r.json())
                    .then(data => {
                        if (data.disponible) {
                            mensaje.style.display = 'none';
                            btn.disabled = false;
                        } else {
                            mensaje.style.display = 'block';
                            mensaje.innerHTML = 'La fecha no está disponible';
                            btn.disabled = true;
                        }
                    })
                    .catch(() => {
                        mensaje.style.display = 'block';
                        mensaje.innerHTML = 'Error al verificar disponibilidad';
                        btn.disabled = true;
                    });
            } else {
                mensaje.style.display = 'none';
                btn.disabled = false;
            }
        });

        // Calendario 
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('reserva-calendar');
            if (el) {
                const calendar = new FullCalendar.Calendar(el, {
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
                        const events = calendar.getEvents().filter(ev => ev.startStr === info.dateStr);
                        const isDisponible = events.some(ev => ev.classNames.includes('disponible'));
                        const btn = document.getElementById('btn-enviar');
                        const mensaje = document.getElementById('fecha-mensaje');

                        if (isDisponible) {
                            document.getElementById('fecha_evento').value = info.dateStr;
                            document.getElementById('fecha_evento').dispatchEvent(new Event('change'));
                            mensaje.style.display = 'none';
                            btn.disabled = false;
                        } else {
                            mensaje.style.display = 'block';
                            mensaje.innerHTML = 'La fecha no está disponible';
                            btn.disabled = true;
                        }
                    }
                });
                calendar.render();
            }
        });

        // Submit reserva
        document.getElementById('form-reserva').addEventListener('submit', async function(e) {
            e.preventDefault();

            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFin = document.getElementById('hora_fin').value;
            const respuesta = document.getElementById('respuesta-form');

            if (horaFin && horaFin <= horaInicio) {
                mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Hora de fin debe ser posterior a hora de inicio.');
                return;
            }

            const confirmar = await showModal("Verifica que todos los datos sean correctos antes de enviar la reserva.\n\n¿Deseas confirmar y enviar la reserva ahora?", 'confirm');

            if (!confirmar) {
                mostrarMensaje(respuesta, '#fff3cd', '#856404', 'Envío cancelado. Puedes modificar los datos.');
                return;
            }

            const formData = new FormData(this);

            fetch('api/crear-reserva.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    mostrarMensaje(respuesta,
                        data.success ? '#d4edda' : '#f8d7da',
                        data.success ? '#155724' : '#721c24',
                        data.message);
                    if (data.success) {
                        this.reset();
                        document.getElementById('precio-evento').innerHTML = '';

                    }
                })
                .catch(() => {
                    mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Error de conexión. Intenta de nuevo.');
                });
        });

        // Gestión y cancelación 

        // Ver estado de reserva
        document.getElementById('form-gestion').addEventListener('submit', function(e) {
            e.preventDefault();

            const orderId = document.getElementById('order_id').value.trim();
            const respuesta = document.getElementById('respuesta-gestion');

            if (!/^TO-[0-9]{5}$/.test(orderId)) {
                mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Formato inválido: debe ser TO seguido de 5 números');
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
                    mostrarMensaje(
                        respuesta,
                        data.success ? '#d4edda' : '#f8d7da',
                        data.success ? '#155724' : '#721c24',
                        data.message
                    );
                })
                .catch(() => {
                    mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Error de conexión. Intenta nuevamente.');
                });
        });

        document.getElementById('cancelar-reserva').addEventListener('click', async function() {
            const orderId = document.getElementById('order_id').value.trim();
            const respuesta = document.getElementById('respuesta-gestion');

            if (!/^TO-[0-9]{5}$/.test(orderId)) {
                mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Formato inválido: debe ser TO seguido de 5 números');
                return;
            }

            const motivo = await showModal('Ingresa el motivo de cancelación:', 'prompt');

            if (!motivo || motivo.trim() === '') {
                mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Debes ingresar un motivo para cancelar');
                return;
            }

            const confirmar = await showModal('¿Seguro que quieres cancelar esta reserva?', 'confirm');
            if (!confirmar) return;

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
                    mostrarMensaje(respuesta,
                        data.success ? '#d4edda' : '#f8d7da',
                        data.success ? '#155724' : '#721c24',
                        data.message);
                });
        });
    </script>
</body>

</html>