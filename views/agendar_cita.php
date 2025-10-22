<?php
ini_set('display_errors', 1); // Le dice a PHP que muestre los errores
error_reporting(E_ALL);    // Nos aseguramos de ver todos los tipos de errores

require_once 'includes/header.php';
require_once 'includes/sidebar_secretaria.php';
require_once '../core/db_connection.php';
require_once '../core/funciones.php';
require_once '../core/config.php';



// --- LÓGICA PARA CARGAR PACIENTES Y MÉDICOS PARA EL FORMULARIO ---
$conexion = conectarDB();
// Obtener lista de todos los pacientes
$sql_pacientes = "SELECT numero_documento, nombre, apellido FROM pacientes ORDER BY apellido ASC";
$resultado_pacientes = $conexion->query($sql_pacientes);
$pacientes = [];
if ($resultado_pacientes) {
    while($fila = $resultado_pacientes->fetch_assoc()) { $pacientes[] = $fila; }
}
// Obtener lista de todos los médicos
$sql_medicos = "SELECT id_medico, nombre_medico, apellido_medico FROM usuarios WHERE rol = 'Medico' ORDER BY apellido_medico ASC";
$resultado_medicos = $conexion->query($sql_medicos);
$medicos = [];
if ($resultado_medicos) {
    while($fila = $resultado_medicos->fetch_assoc()) { $medicos[] = $fila; }
}
$conexion->close();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2"><div class="col-sm-6"><h1 class="m-0">Agenda de Citas</h1></div></div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-body p-0">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modal-cita" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modal-title"></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="modal-body-content">
            </div>
            <div class="modal-footer justify-content-between" id="modal-footer-content">
            </div>
        </div>
    </div>
</div>

<div id="form-container-template" style="display: none;">
    <form id="form-cita" action="../controllers/cita_controller.php" method="POST">
        <input type="hidden" name="action" id="form_action" value="guardar">
        <input type="hidden" name="tipo_registro" id="tipo_registro" value="existente">
        <input type="hidden" id="id_cita" name="id_cita">

        <div id="seccion-paciente-existente">
            <div class="form-group">
                <label for="paciente_documento">Seleccione el Paciente</label>
                <select class="form-control" id="paciente_documento" name="paciente_documento" required>
                    <option value="">-- Elija un paciente --</option>
                    <?php foreach($pacientes as $paciente): ?>
                        <option value="<?php echo htmlspecialchars($paciente['numero_documento']); ?>">
                            <?php echo htmlspecialchars($paciente['apellido'] . ', ' . $paciente['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="#" id="btn-mostrar-form-nuevo">¿Paciente nuevo? Regístralo aquí.</a>
        </div>

        <div id="seccion-paciente-nuevo" style="display: none;">
            <h5>Datos del Nuevo Paciente</h5>
            <div class="form-group"><label>Nº de Documento</label><input type="text" class="form-control" name="nuevo_numero_documento"></div>
            <div class="form-group"><label>Nombres</label><input type="text" class="form-control texto-mayusculas" name="nuevo_nombre"></div>
            <div class="form-group"><label>Apellidos</label><input type="text" class="form-control texto-mayusculas" name="nuevo_apellido"></div>
            <div class="form-group"><label>Teléfono (WhatsApp)</label><input type="text" class="form-control" name="nuevo_telefono_whatsapp"></div>
            <a href="#" id="btn-mostrar-form-existente">Cancelar y buscar paciente existente.</a>
        </div>
        
        <hr>

        <div class="form-group">
            <label for="id_medico">Asignar al Médico</label>
            <select class="form-control" id="id_medico" name="id_medico" required>
                <option value="">-- Elija un médico --</option>
                <?php foreach($medicos as $medico): ?>
                    <option value="<?php echo $medico['id_medico']; ?>">
                        <?php echo 'Dr(a). ' . htmlspecialchars($medico['nombre_medico'] . ' ' . $medico['apellido_medico']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label for="fecha_cita">Fecha</label><input type="date" class="form-control" id="fecha_cita" name="fecha_cita" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label for="hora_cita">Hora</label><input type="time" class="form-control" id="hora_cita" name="hora_cita" required></div>
            </div>
        </div>
        <div class="form-group">
            <label for="notas_secretaria">Notas (Motivo de la cita, etc.)</label>
            <textarea class="form-control" id="notas_secretaria" name="notas_secretaria" rows="3"></textarea>
        </div>
    </form>
</div>

<div id="texto-a-copiar" style="display:none;"></div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    /**
     * INICIALIZACIÓN DE ELEMENTOS
     * Se guardan en variables los elementos del DOM que se usarán repetidamente
     * para mejorar el rendimiento y la legibilidad del código.
     */
    var calendarEl = document.getElementById('calendar');
    var modalCita = $('#modal-cita');
    var modalBody = $('#modal-body-content');
    var modalFooter = $('#modal-footer-content');
    var modalTitle = $('#modal-title');
    const urlParams = new URLSearchParams(window.location.search);

    /**
     * GESTIÓN DE NOTIFICACIONES INICIALES
     * Lee parámetros de la URL (status, msg) para mostrar notificaciones
     * (toasts) de éxito o error al cargar la página.
     */
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');
    if (status === 'success') {
        toastr.success(msg || '¡Operación realizada exitosamente!');
    } else if (status === 'error') {
        toastr.error(msg || 'Ocurrió un error inesperado.');
    }

    /**
     * FUNCIÓN: mostrarFormulario (CORREGIDA)
     * Se actualiza la firma para aceptar 3 parámetros y manejar todos los casos.
     * @param {string} fecha - La fecha seleccionada.
     * @param {string|null} hora - La hora seleccionada (opcional).
     * @param {object|null} datosCita - Objeto con datos si se está editando.
     */
    function mostrarFormulario(fecha, hora = null, datosCita = null) {
        var formHtml = $('#form-container-template').html();
        modalBody.html(formHtml);
        modalFooter.html(`
            <div><button type="button" class="btn btn-danger" id="btn-cancelar-cita" style="display: none;">Cancelar Cita</button></div>
            <div><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button><button type="button" class="btn btn-primary" id="btn-guardar-cita">Guardar</button></div>
        `);
        
        const hoy = new Date();
        const anio = hoy.getFullYear();
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const dia = String(hoy.getDate()).padStart(2, '0');
        const fechaMinima = `${anio}-${mes}-${dia}`;
        modalBody.find('#fecha_cita').attr('min', fechaMinima);
        modalBody.find('#hora_cita').attr('min', '08:00');
        modalBody.find('#hora_cita').attr('max', '18:00'); // Corregido a 18:00 si el horario termina a las 19:00

        asignarEventosFormulario();

        if (datosCita) { // Si se pasan datosCita, es para EDITAR.
            modalTitle.text('Detalles de la Cita');
            $('#btn-guardar-cita').text('Guardar Cambios');
            $('#btn-cancelar-cita').show();
            modalBody.find('#id_cita').val(datosCita.id_cita);
            modalBody.find('#paciente_documento').val(datosCita.paciente_documento);
            modalBody.find('#id_medico').val(datosCita.id_medico_asignado);
            modalBody.find('#fecha_cita').val(datosCita.fecha_cita);
            modalBody.find('#hora_cita').val(datosCita.hora_cita);
            modalBody.find('#notas_secretaria').val(datosCita.notas_secretaria);
        } else { // Si no, es para CREAR una nueva cita.
            modalTitle.text('Agendar Nueva Cita');
            modalBody.find('#fecha_cita').val(fecha);
            if (hora) { // Si se pasó una hora (desde la vista de semana)...
                modalBody.find('#hora_cita').val(hora); // ...se asigna al campo.
            }
        }
    }

    /**
     * FUNCIÓN: asignarEventosFormulario
     * Vincula las acciones (guardar, cancelar, etc.) a los botones y campos del formulario del modal.
     */
    function asignarEventosFormulario() {
        var form = modalBody.find('#form-cita');

        modalBody.find('#fecha_cita').on('change', function() {
            const fechaSeleccionada = new Date(this.value);
            const diaSemana = fechaSeleccionada.getUTCDay();
            if (diaSemana === 0) { // El día 0 es Domingo
                toastr.warning('No se pueden agendar citas en Domingo. Por favor, elija otro día.');
                this.value = '';
            }
        });

        $('#btn-guardar-cita').on('click', function(e) {
            e.preventDefault();
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            var formData = new FormData(form[0]);

            fetch('../controllers/cita_controller.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalCita.modal('hide');
                    calendar.refetchEvents();
                    toastr.success(data.message);
                } else {
                    toastr.error(data.message || 'Ocurrió un error.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error de conexión. Intente de nuevo.');
            })
            .finally(() => {
                button.prop('disabled', false).text('Guardar');
            });
        });

        $('#btn-cancelar-cita').on('click', function() {
            if (confirm('¿Estás seguro de que deseas cancelar esta cita?')) {
                var button = $(this);
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cancelando...');
                
                var formData = new FormData();
                formData.append('action', 'cancelar');
                formData.append('id_cita', modalBody.find('#id_cita').val());

                fetch('../controllers/cita_controller.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalCita.modal('hide');
                        calendar.refetchEvents();
                        toastr.success(data.message);
                    } else {
                        toastr.error(data.message || 'Ocurrió un error al cancelar.');
                    }
                })
                .catch(error => { console.error('Error:', error); toastr.error('Error de conexión.'); })
                .finally(() => { button.prop('disabled', false).text('Cancelar Cita'); });
            }
        });

        modalBody.on('click', '#btn-mostrar-form-nuevo', function(e) {
            e.preventDefault();
            modalBody.find('#seccion-paciente-existente').hide();
            modalBody.find('#seccion-paciente-nuevo').show();
            modalBody.find('#tipo_registro').val('nuevo');
            modalBody.find('#seccion-paciente-nuevo input').prop('required', true);
            modalBody.find('#seccion-paciente-existente select').prop('required', false);
        });

        modalBody.on('click', '#btn-mostrar-form-existente', function(e) {
            e.preventDefault();
            modalBody.find('#seccion-paciente-nuevo').hide();
            modalBody.find('#seccion-paciente-existente').show();
            modalBody.find('#tipo_registro').val('existente');
            modalBody.find('#seccion-paciente-nuevo input').prop('required', false);
            modalBody.find('#seccion-paciente-existente select').prop('required', true);
        });
    }
    
    const fechaHoy = new Date().toISOString().split('T')[0];

    /**
     * INICIALIZACIÓN DE FULLCALENDAR
     */
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
        events: '../controllers/citas_api.php',
        
        validRange: { start: fechaHoy },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        businessHours: {
            daysOfWeek: [ 1, 2, 3, 4, 5, 6], // Lunes a Sábado
            startTime: '08:00',
            endTime: '19:00',
        },
        
        /**
         * FUNCIÓN: dateClick (UNIFICADA Y CORREGIDA)
         * Se ejecuta al hacer clic en un día o una hora.
         */
        dateClick: function(info) {
            const diaSemana = info.date.getDay();
            // Corregido: En tu código tenías bloqueado Sábado (6) y Domingo (0).
            // Lo ajusto para que solo bloquee Domingo (0) ya que Sábado está en tus businessHours.
            if (diaSemana === 0) { 
                return; 
            }

            if (info.view.type.startsWith('timeGrid')) { // Si la vista es Semana o Día
                const fecha = info.dateStr.split('T')[0];
                const hora = info.dateStr.split('T')[1].substring(0, 5);
                mostrarFormulario(fecha, hora);
                modalCita.modal('show');
            } else { // Si la vista es Mes
                $.get(`../controllers/citas_api.php?action=get_citas_by_date&fecha=${info.dateStr}`, function(citasDelDia) {
                    let modalBodyHtml = `<div class="mb-3">`;
                    if (citasDelDia.length > 0) {
                        modalBodyHtml += `<h5>Citas Agendadas:</h5><ul class="list-group">`;
                        citasDelDia.forEach(cita => {
                            modalBodyHtml += `<li class="list-group-item">${cita.hora_cita.substring(0,5)} - ${cita.nombre} ${cita.apellido}</li>`;
                        });
                        modalBodyHtml += `</ul>`;
                    } else {
                        modalBodyHtml += `<p>No hay citas agendadas para este día.</p>`;
                    }
                    modalBodyHtml += `</div><hr>`;
                    modalBody.html(modalBodyHtml);
                    modalFooter.html(`<button type="button" class="btn btn-primary" id="btn-abrir-formulario">Agendar Nueva Cita</button><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>`);
                    modalTitle.text('Agenda del ' + info.dateStr);
                    modalCita.modal('show');
                    $('#btn-abrir-formulario').on('click', function() {
                        mostrarFormulario(info.dateStr);
                    });
                });
            }
        },
        
        /**
         * FUNCIÓN: eventClick (CORREGIDA)
         * Se ejecuta al hacer clic sobre una cita ya existente para editarla.
         */
        eventClick: function(info) {
             $.get(`../controllers/citas_api.php?action=get_cita&id=${info.event.id}`, function(data) {
                if(data) {
                    // Llamada correcta a la nueva función: se pasa null en el parámetro de la hora.
                    mostrarFormulario(data.fecha_cita, null, data);
                    modalCita.modal('show');
                } else {
                    toastr.error('No se pudieron cargar los detalles de la cita.');
                }
            });
        }
    });
    
    calendar.render();

    /**
     * LÓGICA PARA PRESELECCIONAR PACIENTE
     */
    const pacientePreseleccionado = urlParams.get('preseleccionar_paciente');
    if (pacientePreseleccionado) {
        const hoyStr = new Date().toISOString().slice(0, 10);
        mostrarFormulario(hoyStr, null, null); // Llamada corregida
        modalBody.find('#paciente_documento').val(pacientePreseleccionado);
        modalCita.modal('show');
    }
});
</script>