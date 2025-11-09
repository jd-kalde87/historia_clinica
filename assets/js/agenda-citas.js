document.addEventListener('DOMContentLoaded', function() {
    // --- INICIALIZACIÓN DE ELEMENTOS ---
    var calendarEl = document.getElementById('calendar');
    var modalCita = $('#modal-cita');
    var modalBody = $('#modal-body-content');
    var modalFooter = $('#modal-footer-content');
    var modalTitle = $('#modal-title');
    const urlParams = new URLSearchParams(window.location.search);

    // --- SCRIPT PARA MOSTRAR ALERTAS DE ÉXITO O ERROR ---
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');
    if (status === 'success') {
        toastr.success(msg || '¡Operación realizada exitosamente!');
    } else if (status === 'error') {
        toastr.error(msg || 'Ocurrió un error inesperado.');
    }

    // --- FUNCIÓN PARA MOSTRAR EL FORMULARIO ---
    function mostrarFormulario(fecha, datosCita = null) {
        var formHtml = $('#form-container-template').html();
        modalBody.html(formHtml);

        modalFooter.html(`
            <div>
                <button type="button" class="btn btn-danger" id="btn-cancelar-cita" style="display: none;">Cancelar Cita</button>
            </div>
            <div>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-cita">Guardar</button>
            </div>
        `);

        asignarEventosFormulario();

        if (datosCita) { // Editando cita existente
            modalTitle.text('Detalles de la Cita');
            $('#btn-guardar-cita').text('Guardar Cambios');
            $('#btn-cancelar-cita').show();
            modalBody.find('#id_cita').val(datosCita.id_cita);
            modalBody.find('#paciente_documento').val(datosCita.paciente_documento);
            modalBody.find('#id_medico').val(datosCita.id_medico_asignado);
            modalBody.find('#fecha_cita').val(datosCita.fecha_cita);
            modalBody.find('#hora_cita').val(datosCita.hora_cita);
            modalBody.find('#notas_secretaria').val(datosCita.notas_secretaria);
        } else { // Creando cita nueva
            modalTitle.text('Agendar Nueva Cita');
            modalBody.find('#fecha_cita').val(fecha);
        }
    }

    // --- FUNCIÓN PARA ASIGNAR EVENTOS A LOS BOTONES DEL FORMULARIO (CORREGIDA) ---
    function asignarEventosFormulario() {
        var form = modalBody.find('#form-cita');

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
            .catch(error => { console.error('Error:', error); toastr.error('Error de conexión.'); })
            .finally(() => {
                button.prop('disabled', false).text('Guardar'); // Devuelve el texto original
            });
        });

        // --- INICIO DE LA CORRECCIÓN ---
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
                        calendar.refetchEvents(); // Recarga los eventos para que la cita desaparezca
                        toastr.success(data.message);
                    } else {
                        toastr.error(data.message || 'Ocurrió un error.');
                    }
                })
                .catch(error => { console.error('Error:', error); toastr.error('Error de conexión.'); })
                .finally(() => {
                    button.prop('disabled', false).text('Cancelar Cita');
                });
            }
        });
        // --- FIN DE LA CORRECCIÓN ---

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

    // --- CONFIGURACIÓN DEL CALENDARIO ---
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
        events: '../controllers/citas_api.php',
        
        dateClick: function(info) {
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

                modalFooter.html(`
                    <button type="button" class="btn btn-primary" id="btn-abrir-formulario">Agendar Nueva Cita</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                `);
                
                modalTitle.text('Agenda del ' + info.dateStr);
                modalCita.modal('show');
                
                $('#btn-abrir-formulario').on('click', function() {
                    mostrarFormulario(info.dateStr);
                });
            });
        },
        
        eventClick: function(info) {
            $.get(`../controllers/citas_api.php?action=get_cita&id=${info.event.id}`, function(data) {
                if(data) {
                    mostrarFormulario(data.fecha_cita, data);
                    modalCita.modal('show');
                } else {
                    toastr.error('No se pudieron cargar los detalles de la cita.');
                }
            });
        }
    });
    
    calendar.render();
const idCitaVer = urlParams.get('id_cita_ver');

if (idCitaVer) {
    // Si el parámetro existe, usamos la misma lógica que 'eventClick'
    // para obtener los datos de la cita desde la API.

    // Usamos la variable 'modalCita' que ya definiste al inicio del script
    modalCita.modal('show'); // Mostramos el modal primero para que el usuario vea la carga
    modalTitle.text('Cargando Detalles...');
    modalBody.html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>'); // Feedback de carga

    $.get(`../controllers/citas_api.php?action=get_cita&id=${idCitaVer}`, function(data) {
        if(data) {
            // La función 'mostrarFormulario' ya sabe cómo llenar los campos
            // y asignar los eventos a los botones.
            mostrarFormulario(data.fecha_cita, data);
        } else {
            modalCita.modal('hide'); // Ocultar si hay error
            toastr.error('No se pudieron cargar los detalles de la cita solicitada.');
        }
    }, 'json')
    .fail(function() {
        modalCita.modal('hide');
        toastr.error('Error de red al consultar la cita.');
    });
}
    const pacientePreseleccionado = urlParams.get('preseleccionar_paciente');
    if (pacientePreseleccionado) {
        const hoyStr = new Date().toISOString().slice(0, 10);
        mostrarFormulario(hoyStr);
        modalBody.find('#paciente_documento').val(pacientePreseleccionado);
        modalCita.modal('show');
    }
});