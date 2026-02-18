<?php
require_once 'config/database.php';

$fechaPreseleccionada = null;
if (isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha'])) {
    $fechaPreseleccionada = $_GET['fecha'];
}

$tipoSeleccionado = null;
if (isset($_GET['tipo']) && is_numeric($_GET['tipo'])) {
    $tipoSeleccionado = (int)$_GET['tipo'];
}

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
                <!-- Formulario de reserva mejorado -->
                <div class="reserva-form-container">
                    <div class="form-header">
                        <h2>Reserva tu fecha</h2>
                        <p>Selecciona el tipo de evento, fecha y hora disponible. Te contactaremos para confirmar detalles.</p>
                    </div>
                    
                    <div class="form-body">
                        <form id="form-reserva" class="form-reserva">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="tipo_evento_id">Tipo de evento *</label>
                                    <select name="tipo_evento_id" id="tipo_evento_id" required>
                                        <option value="" disabled <?= !$tipoSeleccionado ? 'selected' : '' ?> hidden>Selecciona un tipo de evento</option>
                                        <?php foreach ($tipos as $t): ?>
                                            <option value="<?= $t['id'] ?>" 
                                                data-precio="<?= $t['precio_base'] ?>"
                                                <?= ($tipoSeleccionado == $t['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="precio-evento" style="margin-top:0.5rem;font-weight:600;color:var(--secondary);"></div>
                                </div>

                                <div class="form-group full-width">
                                    <label for="fecha_evento">Fecha *</label>
                                    <div class="calendar-section">
                                        <h3>Calendario de disponibilidad</h3>
                                        <div id="reserva-calendar"></div>
                                    </div>
                                    <input type="date"
                                        name="fecha_evento"
                                        id="fecha_evento" required
                                        min="<?= date('Y-m-d') ?>"
                                        value="<?= $fechaPreseleccionada ?? '' ?>"
                                        style="margin-top:1rem;">

                                    <span id="fecha-mensaje" class="error-message" style="display:none;">La fecha no está disponible</span>
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
                                    <input type="text" name="cliente_nombre" id="cliente_nombre" required placeholder="Ej: Juan Pérez">
                                </div>

                                <div class="form-group">
                                    <label for="cliente_email">Email *</label>
                                    <input type="email" name="cliente_email" id="cliente_email" required placeholder="correo@ejemplo.com">
                                </div>

                                <div class="form-group">
                                    <label for="cliente_telefono">Teléfono *</label>
                                    <input type="tel" name="cliente_telefono" id="cliente_telefono" required placeholder="Ej: 8888-8888">
                                </div>

                                <div class="form-group">
                                    <label for="direccion_evento">Dirección del evento *</label>
                                    <input type="text" name="direccion_evento" id="direccion_evento" required placeholder="Dirección completa">
                                </div>

                                <div class="form-group full-width">
                                    <label for="notas">Notas adicionales (incluye detalles para personalizar tu decoración) *</label>
                                    <textarea name="notas" id="notas" rows="4" required placeholder="Describe los detalles de tu evento..."></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-reservar" id="btn-enviar">Enviar reserva</button>
                            </div>
                        </form>

                        <div id="respuesta-form" class="respuesta-mensaje" style="margin-top:2rem;padding:1rem;border-radius:8px;display:none;"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="gestion-reserva">
            <div class="container">
                <div class="reserva-form-container" style="margin-top:2rem;">
                    <div class="form-header">
                        <h2>Ver estado o cancelar reserva</h2>
                        <p>Ingresa tu número de orden para consultar o cancelar</p>
                    </div>
                    
                    <div class="form-body">
                        <form id="form-gestion">
                            <div class="form-group">
                                <label for="order_id">Número de orden (TO-XXXXX) *</label>
                                <input type="text" name="order_id" id="order_id" required placeholder="Ej: TO-12345" pattern="TO-[0-9]{5}">
                            </div>
                            <div class="form-actions" style="justify-content:center;">
                                <button type="submit" class="btn btn-secondary">Ver estado</button>
                                <button type="button" id="cancelar-reserva" class="btn btn-danger">Cancelar reserva</button>
                            </div>
                        </form>

                        <div id="respuesta-gestion" class="respuesta-mensaje" style="margin-top:2rem;padding:1rem;border-radius:8px;display:none;"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'components/footer.php'; ?>

    <!-- Modal personalizado -->
    <div id="custom-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:2rem;border-radius:12px;width:90%;max-width:400px;text-align:center;">
            <p id="modal-message" style="margin-bottom:1.5rem; color:var(--text-dark);"></p>
            <div id="modal-buttons" style="display:flex; gap:1rem; justify-content:center;"></div>
        </div>
    </div>

    <script>
        // Función mostrar mensaje mejorada
        function mostrarMensaje(div, bg, color, mensaje) {
            div.style.background = bg;
            div.style.color = color;
            div.innerHTML = mensaje;
            div.style.display = 'block';
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
                    buttons.innerHTML = `
                        <button id="cancelar-modal" class="btn btn-secondary" style="padding:0.6rem 1.4rem;">Cancelar</button>
                        <button id="confirmar-modal" class="btn btn-reservar" style="padding:0.6rem 1.4rem;">Confirmar</button>
                    `;
                    document.getElementById('cancelar-modal').onclick = () => {
                        modal.style.display = 'none';
                        resolve(false);
                    };
                    document.getElementById('confirmar-modal').onclick = () => {
                        modal.style.display = 'none';
                        resolve(true);
                    };
                } else if (type === 'prompt') {
                    buttons.innerHTML = `
                        <div style="width:100%; margin-bottom:1rem;">
                            <input type="text" id="modal-input" style="width:100%; padding:0.8rem; border-radius:var(--radius-sm); border:2px solid #e0e0e0; font-family:inherit; font-size:inherit;">
                        </div>
                        <div style="display:flex; justify-content:center; gap:1rem;">
                            <button id="cancelar-modal" class="btn btn-secondary">Cancelar</button>
                            <button id="confirmar-modal" class="btn btn-reservar">Aceptar</button>
                        </div>
                    `;
                    document.getElementById('cancelar-modal').onclick = () => {
                        modal.style.display = 'none';
                        resolve('');
                    };
                    document.getElementById('confirmar-modal').onclick = () => {
                        const value = document.getElementById('modal-input').value.trim();
                        modal.style.display = 'none';
                        resolve(value);
                    };
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectTipo = document.getElementById('tipo_evento_id');
            if (selectTipo) {
                <?php if ($tipoSeleccionado): ?>
                    setTimeout(function() {
                        const selected = selectTipo.options[selectTipo.selectedIndex];
                        const precio = selected.dataset.precio;
                        const div = document.getElementById('precio-evento');
                        if (precio && precio > 0) {
                            div.innerHTML = `Precio base: C$${parseFloat(precio).toLocaleString('es-NI',{minimumFractionDigits:2})}`;
                        }
                    }, 100);
                <?php endif; ?>

                selectTipo.addEventListener('change', function() {
                    const selected = this.options[this.selectedIndex];
                    const precio = selected.dataset.precio;
                    const div = document.getElementById('precio-evento');
                    if (precio && precio > 0) {
                        div.innerHTML = `Precio base: C$${parseFloat(precio).toLocaleString('es-NI',{minimumFractionDigits:2})}`;
                    } else {
                        div.innerHTML = '';
                    }
                });
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
                    height: 'auto',
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

                <?php if ($fechaPreseleccionada): ?>
                    calendar.gotoDate('<?= $fechaPreseleccionada ?>');
                    document.getElementById('fecha_evento').dispatchEvent(new Event('change'));
                <?php endif; ?>
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

            const motivoSeleccionado = await showModalMotivo();
            if (!motivoSeleccionado) {
                mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Operación cancelada');
                return;
            }

            const confirmar = await showModal('¿Seguro que quieres cancelar esta reserva?', 'confirm');
            if (!confirmar) {
                mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Operación cancelada');
                return;
            }

            fetch('api/gestion-reserva.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        accion: 'cancelar',
                        motivo: motivoSeleccionado.trim()
                    })
                })
                .then(r => r.json())
                .then(data => {
                    mostrarMensaje(respuesta,
                        data.success ? '#d4edda' : '#f8d7da',
                        data.success ? '#155724' : '#721c24',
                        data.message);
                })
                .catch(() => {
                    mostrarMensaje(respuesta, '#f8d7da', '#721c24', 'Error de conexión');
                });
        });

        // Función para el modal de motivos - CORREGIDA
        async function showModalMotivo() {
            return new Promise((resolve) => {
                const modal = document.createElement('div');
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.width = '100%';
                modal.style.height = '100%';
                modal.style.background = 'rgba(0,0,0,0.6)';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '9999';

                const content = document.createElement('div');
                content.style.background = 'white';
                content.style.padding = '2rem';
                content.style.borderRadius = '12px';
                content.style.maxWidth = '400px';
                content.style.width = '90%';
                content.style.boxShadow = '0 8px 30px rgba(0,0,0,0.3)';
                content.style.textAlign = 'center';
                content.style.fontFamily = "'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif";

                content.innerHTML = `
                    <h3 style="margin-top:0; color:#7a4a3a; font-family:inherit;">Motivo de cancelación</h3>
                    <p style="margin-bottom:1.5rem; font-family:inherit;">Selecciona el motivo principal:</p>
                    <select id="motivo-select" style="width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:6px; border:1px solid #ccc; font-family:inherit; font-size:inherit;">
                        <option value="">Selecciona un motivo...</option>
                        <option value="Cambio de planes">Cambio de planes</option>
                        <option value="Fecha no disponible para mí">Fecha no disponible para mí</option>
                        <option value="Problemas personales">Problemas personales</option>
                        <option value="Encontré otra opción">Encontré otra opción</option>
                        <option value="Otro">Otro (especificar abajo)</option>
                    </select>
                    <textarea id="motivo-otro" placeholder="Especifica aquí si elegiste 'Otro'" style="width:100%; height:80px; padding:0.8rem; border-radius:6px; border:1px solid #ccc; display:none; font-family:inherit; font-size:inherit; resize:vertical;"></textarea>
                    <div style="margin-top:1.5rem; text-align:right; display:flex; justify-content:flex-end; gap:1rem;">
                        <button id="cancelar-modal" class="btn btn-secondary" style="padding:0.6rem 1.4rem; font-family:inherit;">Cancelar</button>
                        <button id="confirmar-modal" class="btn btn-reservar" style="padding:0.6rem 1.4rem; font-family:inherit;">Confirmar</button>
                    </div>
                `;

                modal.appendChild(content);
                document.body.appendChild(modal);

                const select = content.querySelector('#motivo-select');
                const textarea = content.querySelector('#motivo-otro');
                const btnCancelar = content.querySelector('#cancelar-modal');
                const btnConfirmar = content.querySelector('#confirmar-modal');

                select.addEventListener('change', function() {
                    textarea.style.display = this.value === 'Otro' ? 'block' : 'none';
                });

                btnCancelar.addEventListener('click', () => {
                    document.body.removeChild(modal);
                    resolve(null);
                });

                btnConfirmar.addEventListener('click', () => {
                    let motivo = select.value;
                    if (motivo === 'Otro') {
                        motivo = textarea.value.trim();
                    }
                    document.body.removeChild(modal);
                    resolve(motivo || null);
                });
            });
        }
    </script>
</body>

</html>