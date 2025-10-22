$(document).ready(function() {
    /**
     * INICIALIZACIÓN DE LA TABLA
     * Configura y carga los datos de los usuarios en la tabla usando la librería DataTables.
     * Se conecta a la API para obtener los datos y define la estructura de las columnas.
     */
    var table = $('#tabla-usuarios').DataTable({
        "ajax": {
            "url": "../controllers/usuario_api.php?action=get_all",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "nombre_completo" },
            { "data": "rol" },
            { "data": "usuario" },
            { 
                "data": "id_medico",
                "render": function(data, type, row) {
                    return `
                        <button class="btn btn-warning btn-sm btn-editar" data-id="${data}" title="Editar Usuario">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-eliminar" data-id="${data}" title="Eliminar Usuario">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
                "orderable": false
            }
        ],
        "language": {
            "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json"
        },
        "responsive": true
    });

    /**
     * FUNCIÓN: mostrarModalUsuario
     * Muestra una ventana emergente (SweetAlert2) con el formulario para crear o editar un usuario.
     * @param {object|null} datosUsuario - Si se proveen datos, el modal entra en modo "edición",
     * de lo contrario, entra en modo "creación".
     */
    function mostrarModalUsuario(datosUsuario = null) {
        const formHtml = $('#formulario-usuario-template').html();
        const esNuevoUsuario = datosUsuario === null;

        Swal.fire({
            title: esNuevoUsuario ? 'Añadir Nuevo Usuario' : 'Editar Usuario',
            html: formHtml,
            confirmButtonText: 'Guardar Cambios',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const popup = Swal.getPopup();

                if (esNuevoUsuario) {
                    $(popup).find('#password').prop('required', true);
                    $(popup).find('#passwordHelp').text('La contraseña es obligatoria para usuarios nuevos.');
                } else {
                    // Lógica para rellenar el formulario en modo edición
                    $(popup).find('#password').prop('required', false);
                    $(popup).find('#passwordHelp').text('Dejar en blanco para no cambiar la contraseña.');
                    
                    $(popup).find('#id_usuario').val(datosUsuario.id_medico);
                    $(popup).find('#rol').val(datosUsuario.rol);
                    $(popup).find('#nombre_medico').val(datosUsuario.nombre_medico);
                    $(popup).find('#apellido_medico').val(datosUsuario.apellido_medico);
                    $(popup).find('#usuario').val(datosUsuario.usuario);
                    $(popup).find('#especialidad').val(datosUsuario.especialidad);
                    $(popup).find('#registro_medico').val(datosUsuario.registro_medico);
                }

                // Listener para mostrar/ocultar los campos específicos del rol "Médico"
                $(popup).on('change', '#rol', function() {
                    if ($(this).val() === 'Medico') {
                        $(popup).find('#campos-medico').slideDown();
                    } else {
                        $(popup).find('#campos-medico').slideUp();
                    }
                });
                $(popup).find('#rol').trigger('change'); // Se activa al abrir el modal para ajustar la vista
            },
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#form-usuario');
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
            if (result.isConfirmed) {
                const formData = result.value;

                fetch('../controllers/usuario_controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Éxito!', data.message, 'success');
                        table.ajax.reload(); // Recarga la tabla para mostrar los cambios
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
     * EVENTO CLICK: Botón "Añadir Nuevo Usuario"
     * Se activa al hacer clic en el botón principal de la página para crear un usuario.
     * Llama a la función 'mostrarModalUsuario' sin datos para iniciar el modo de creación.
     */
    $('#btn-nuevo-usuario').on('click', function() {
        mostrarModalUsuario();
    });

    /**
     * EVENTO CLICK: Botón "Editar" (lápiz amarillo) en la tabla.
     * Se activa al hacer clic en el botón de editar de una fila específica de la tabla.
     */
    $('#tabla-usuarios tbody').on('click', '.btn-editar', function() {
        var idUsuario = $(this).data('id'); // Obtenemos el ID del usuario desde el botón

        // Pide a la API los datos del usuario seleccionado
        $.get(`../controllers/usuario_api.php?action=get_usuario&id=${idUsuario}`, function(response) {
            if (response.success) {
                // Si la API devuelve los datos, llama al modal en modo de edición
                mostrarModalUsuario(response.data);
            } else {
                Swal.fire('Error', response.message || 'No se pudieron cargar los datos del usuario.', 'error');
            }
        });
    });

    /**
     * EVENTO CLICK: Botón "Eliminar" (papelera roja) en la tabla.
     * Se activa al hacer clic en el botón de eliminar de una fila.
     */
    $('#tabla-usuarios tbody').on('click', '.btn-eliminar', function() {
        var idUsuario = $(this).data('id'); // Obtenemos el ID del usuario.

        // Muestra una ventana de confirmación antes de proceder con la eliminación.
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            // Si el administrador confirma la eliminación...
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('id_usuario', idUsuario);

                // ...se envía la solicitud de eliminación al controlador.
                fetch('../controllers/usuario_controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡Eliminado!', data.message, 'success');
                        table.ajax.reload(); // Recargamos la tabla para que el usuario desaparezca.
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                });
            }
        });
    });
});