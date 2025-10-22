// Contenido para assets/js/listar_pacientes.js

$(document).ready(function() {
    
    // Inicialización de la tabla DataTables
    $('#tabla-listar-pacientes').DataTable({
        "ajax": {
            // Llamamos a la nueva acción que creamos en la API
            "url": "../controllers/paciente_controller.php?action=get_all_listado",
            "dataSrc": "data"
        },
        "columns": [
            { 
                "data": "numero_documento",
                // Hacemos que el documento sea un enlace a "buscar_paciente.php"
                "render": function(data, type, row) {
                    return `<a href="buscar_paciente.php?documento=${data}">${data}</a>`;
                }
            },
            { "data": "nombre" },
            { "data": "apellido" },
            { "data": "sexo" },
            { "data": "fecha_nacimiento" }
        ],
        // Mantenemos la configuración del "nuevo" estilo
        "responsive": true,
        "lengthChange": true, // Lo activamos para que sea igual a la tabla de gestión
        "autoWidth": false,
        "language": {
            "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json"
        }
    });
});