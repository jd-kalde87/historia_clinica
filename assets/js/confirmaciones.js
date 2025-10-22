// Contenido completo para assets/js/confirmaciones.js

$(document).ready(function() {
    // Inicialización de la tabla DataTables
    var table = $('#tabla-confirmaciones').DataTable({
        "ajax": {
            "url": "../controllers/citas_api.php?action=get_citas_pendientes",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "paciente" },
            { "data": "fecha_cita" },
            { "data": "hora_cita" },
            { "data": "motivo" },
            { 
                "data": "id_cita",
                "render": function(data, type, row) {
                    return `<button class="btn btn-info btn-sm btn-confirmar" data-id="${data}" title="Generar mensaje de confirmación">
                                <i class="fas fa-comments"></i> Confirmar
                            </button>`;
                },
                "orderable": false
            }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
        },
        "responsive": true
    });

    // --- INICIO DE LA NUEVA FUNCIONALIDAD ---
    // Evento de clic para el botón "Confirmar"
    $('#tabla-confirmaciones tbody').on('click', '.btn-confirmar', function() {
        var idCita = $(this).data('id'); // Obtenemos el ID de la cita desde el botón
        
        // Hacemos la llamada a la API para generar el mensaje
        $.get(`../controllers/citas_api.php?action=generar_mensaje&id=${idCita}`, function(response) {
            if (response.success) {
                // Limpiamos el número de teléfono
                let telefonoLimpio = '';
                if (response.telefono) {
                    telefonoLimpio = response.telefono.replace(/[^0-9]/g, '');
                    if (telefonoLimpio.length === 10) { 
                        telefonoLimpio = '57' + telefonoLimpio; // Asumimos prefijo de Colombia
                    }
                }
                const mensajeUrl = encodeURIComponent(response.mensaje);
                const whatsappLink = `https://wa.me/${telefonoLimpio}?text=${mensajeUrl}`;
                

                // Usamos SweetAlert2 para mostrar el mensaje
                Swal.fire({
                    title: '<strong>Confirmación de Cita</strong>',
                    icon: 'info',
                    html: `<pre style="white-space: pre-wrap; text-align: left;">${response.mensaje}</pre>`,
                    showCloseButton: true,
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: '<i class="fab fa-whatsapp"></i> Enviar',
                    confirmButtonAriaLabel: 'Enviar por WhatsApp',
                    cancelButtonText: '<i class="fas fa-copy"></i> Copiar',
                    cancelButtonAriaLabel: 'Copiar texto'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Si se hace clic en "Enviar", se abre WhatsApp en una nueva pestaña
                        window.open(whatsappLink, '_blank');
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Si se hace clic en "Copiar", se copia el texto al portapapeles
                        navigator.clipboard.writeText(response.mensaje).then(() => {
                            toastr.success('¡Mensaje copiado al portapapeles!');
                        });
                    }
                });
            } else {
                toastr.error(response.message || 'No se pudo generar el mensaje.');
            }
        });
    });
    // --- FIN DE LA NUEVA FUNCIONALIDAD ---
});