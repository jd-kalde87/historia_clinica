$(document).ready(function() {
    
    // Inicialización de la tabla DataTables
    var table = $('#tabla-gestionar-pacientes').DataTable({
        // ... (configuración de la tabla sin cambios) ...
        "ajax": {
            "url": "../controllers/paciente_controller.php?action=get_all",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "numero_documento" },
            { "data": "nombre_completo" },
            { "data": "telefono_whatsapp" },
            { 
                "data": "numero_documento",
                "render": function(data, type, row) {
                    return `
                        <button class="btn btn-warning btn-sm btn-editar-paciente" data-documento="${data}" title="Editar Datos del Paciente">
                            <i class="fas fa-user-edit"></i> Editar
                        </button>
                    `;
                },
                "orderable": false 
            }
        ],
        "language": {
            "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json"
        },
        "responsive": true,
        "autoWidth": false
    });

    /**
     * FUNCIÓN: mostrarModalEdicion
     * Muestra el formulario para editar un paciente con sus datos cargados.
     */
    function mostrarModalEdicion(datosPaciente) {
        const formHtml = $('#formulario-paciente-template').html();

        Swal.fire({
            title: 'Editar Paciente',
            html: formHtml,
            confirmButtonText: 'Guardar Cambios',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                // Rellenamos el formulario con los datos del paciente
                const popup = Swal.getPopup();
                $(popup).find('#numero_documento_original').val(datosPaciente.numero_documento);
                $(popup).find('#numero_documento').val(datosPaciente.numero_documento);
                $(popup).find('#tipo_documento').val(datosPaciente.tipo_documento);
                $(popup).find('#nombre').val(datosPaciente.nombre);
                $(popup).find('#apellido').val(datosPaciente.apellido);
                $(popup).find('#fecha_nacimiento').val(datosPaciente.fecha_nacimiento);
                $(popup).find('#telefono_whatsapp').val(datosPaciente.telefono_whatsapp);
                $(popup).find('#sexo').val(datosPaciente.sexo);
                $(popup).find('#estado_civil').val(datosPaciente.estado_civil);
                $(popup).find('#direccion').val(datosPaciente.direccion);
                $(popup).find('#profesion').val(datosPaciente.profesion);
                $(popup).find('#embarazada').val(datosPaciente.embarazada || '0');
                
                // Lógica para mostrar/ocultar campos de gestación
                if (datosPaciente.sexo === 'FEMENINO') {
                    $(popup).find('#seccion-gestacion-editar').show();
                }
                if (datosPaciente.embarazada == '1') {
                    $(popup).find('#semanas_gestacion').prop('disabled', false).val(datosPaciente.semanas_gestacion);
                }

                // Listeners para los campos dinámicos (sexo y gestación)
                $(popup).on('change', '#sexo', function() {
                    if ($(this).val() === 'FEMENINO') {
                        $(popup).find('#seccion-gestacion-editar').slideDown();
                    } else {
                        $(popup).find('#seccion-gestacion-editar').slideUp();
                        $(popup).find('#embarazada').val('0');
                        $(popup).find('#semanas_gestacion').val('').prop('disabled', true);
                    }
                });

                $(popup).on('change', '#embarazada', function() {
                    const semanasInput = $(popup).find('#semanas_gestacion');
                    if ($(this).val() === '1') {
                        semanasInput.prop('disabled', false);
                    } else {
                        semanasInput.val('').prop('disabled', true);
                    }
                });
            },
            preConfirm: () => {
                // Validación del formulario antes de enviar
                const form = Swal.getPopup().querySelector('#form-editar-paciente');
                let esValido = true;
                
                form.querySelectorAll('[required]').forEach(field => {
                    if (field.offsetParent !== null && !field.value) {
                        field.style.borderColor = 'red';
                        esValido = false;
                    } else {
                        field.style.borderColor = '#ced4da';
                    }
                });

                if (!esValido) {
                    Swal.showValidationMessage('Por favor, completa todos los campos obligatorios.');
                    return false;
                }
                
                return new FormData(form);
            }
        }).then((result) => {
            // --- INICIO DE LA CORRECCIÓN: Lógica de guardado ---
            if (result.isConfirmed) {
                const formData = result.value;
                
                // Enviamos los datos del formulario al controlador por POST
                fetch('../controllers/paciente_controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Mostramos la respuesta real del servidor
                    if (data.success) {
                        Swal.fire('¡Actualizado!', data.message, 'success');
                        table.ajax.reload(); // Recargamos la tabla para ver los cambios
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                });
            }
            // --- FIN DE LA CORRECCIÓN ---
        });
    }

    /**
     * EVENTO CLICK: Botón "Editar" (lápiz amarillo)
     * Se activa al hacer clic en el botón de editar de cualquier fila.
     */
    $('#tabla-gestionar-pacientes tbody').on('click', '.btn-editar-paciente', function() {
        var documento = $(this).data('documento'); // Obtenemos el ID (documento)

        // Hacemos la llamada a la API para obtener los datos completos del paciente
        $.get(`../controllers/paciente_controller.php?action=get_paciente_completo&documento=${documento}`, function(response) {
            if (response.status === 'success') {
                mostrarModalEdicion(response.data);
            } else {
                Swal.fire('Error', response.message || 'No se pudieron cargar los datos del paciente.', 'error');
            }
        });
    });

});