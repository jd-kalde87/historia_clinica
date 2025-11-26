$(document).ready(function() {
    
    // --- ACCIÓN: CLICK EN "NUEVO PACIENTE" ---
    $('#btn-nuevo-paciente').on('click', function() {
        // Llamamos al modal pasando 'null' para indicar que es un registro nuevo
        mostrarModalEdicion(null);
    });

    // Inicialización de la tabla DataTables
    var table = $('#tabla-gestionar-pacientes').DataTable({
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
     * Muestra el formulario para editar o crear un paciente.
     */
    function mostrarModalEdicion(datosPaciente) {
        const formHtml = $('#formulario-paciente-template').html();

        Swal.fire({
            title: datosPaciente ? 'Editar Paciente' : 'Registrar Nuevo Paciente',
            html: formHtml,
            width: '800px', // Hacemos el modal un poco más ancho para mejor visualización
            confirmButtonText: 'Guardar Datos',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const popup = Swal.getPopup();
                
                // Listeners para los campos dinámicos (sexo y gestación)
                // Se deben declarar antes de rellenar los datos para que reaccionen
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

                // --- LÓGICA DE RELLENO DE DATOS ---
                if (datosPaciente) {
                    // MODO EDICIÓN: Rellenar campos y bloquear ID
                    $(popup).find('#numero_documento_original').val(datosPaciente.numero_documento);
                    $(popup).find('#numero_documento').val(datosPaciente.numero_documento).prop('readonly', true);
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
                    
                    // Mostrar campos de gestación si aplica
                    if (datosPaciente.sexo === 'FEMENINO') {
                        $(popup).find('#seccion-gestacion-editar').show();
                    }
                    if (datosPaciente.embarazada == '1') {
                        $(popup).find('#semanas_gestacion').prop('disabled', false).val(datosPaciente.semanas_gestacion);
                    }
                } else {
                    // MODO CREACIÓN (NUEVO): Limpiar y desbloquear
                    $(popup).find('#form-editar-paciente')[0].reset(); 
                    $(popup).find('#numero_documento').prop('readonly', false); // Permitir escribir el documento
                    $(popup).find('#numero_documento_original').val(''); // Vacío para que el controlador sepa que es INSERT
                    $(popup).find('#seccion-gestacion-editar').hide();
                }
            },
            preConfirm: () => {
                // Validación del formulario antes de enviar
                const form = Swal.getPopup().querySelector('#form-editar-paciente');
                let esValido = true;
                
                form.querySelectorAll('[required]').forEach(field => {
                    // Validamos solo si el campo es visible
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
            // --- ENVÍO DE DATOS AL SERVIDOR ---
            if (result.isConfirmed) {
                const formData = result.value;
                
                fetch('../controllers/paciente_controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Guardado!', data.message, 'success');
                        table.ajax.reload(); // Recargamos la tabla para ver el nuevo paciente
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                });
            }
        });
    }

    /**
     * EVENTO CLICK: Botón "Editar" (lápiz amarillo)
     */
    $('#tabla-gestionar-pacientes tbody').on('click', '.btn-editar-paciente', function() {
        var documento = $(this).data('documento'); 

        $.get(`../controllers/paciente_controller.php?action=get_paciente_completo&documento=${documento}`, function(response) {
            if (response.status === 'success') {
                mostrarModalEdicion(response.data); // Llamamos al modal en modo EDICIÓN
            } else {
                Swal.fire('Error', response.message || 'No se pudieron cargar los datos del paciente.', 'error');
            }
        });
    });

});