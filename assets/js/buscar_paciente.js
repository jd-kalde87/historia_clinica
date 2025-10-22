document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-buscar-paciente');
    const inputBusqueda = document.getElementById('numero_documento_busqueda');
    const resultadoContainer = document.getElementById('resultado-busqueda');

    // Función única y centralizada para realizar la búsqueda
    function realizarBusqueda(documento) {
        if (!documento) return;

        resultadoContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Buscando...</p></div>';

        // --- INICIO DE LA CORRECCIÓN ---
        // Ajustamos la URL para que incluya la 'action' que el controlador ahora espera
        const url = `${BASE_URL}controllers/paciente_controller.php?action=get_one&documento=${documento}`;
        // --- FIN DE LA CORRECCIÓN ---

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Respuesta del servidor no fue OK: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    mostrarResultados(data.paciente, data.consultas);
                } else {
                    mostrarNoEncontrado(documento);
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                resultadoContainer.innerHTML = `<div class="alert alert-danger">Ocurrió un error en la búsqueda. Revisa la consola (F12) para más detalles.</div>`;
            });
    }

    // Al cargar la página, revisamos si viene un documento en la URL (desde el listado de pacientes)
    const urlParams = new URLSearchParams(window.location.search);
    const docURL = urlParams.get('documento');
    if (docURL) {
        inputBusqueda.value = docURL;
        realizarBusqueda(docURL);
    }

    // Listener para el envío del formulario de búsqueda manual
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const documento = inputBusqueda.value.trim();
        if (documento !== '') {
            realizarBusqueda(documento);
        }
    });

    function mostrarResultados(paciente, consultas) {
        let consultasHtml = '<p>Este paciente aún no tiene consultas registradas.</p>';
        if (consultas.length > 0) {
            consultasHtml = `
                <ul class="list-group list-group-flush">
                    ${consultas.map(c => `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${new Date(c.fecha_consulta).toLocaleDateString('es-CO')}</strong> - ${c.motivo_consulta}
                            </div>
                            <a href="ver_historia.php?id=${c.id_historia}" class="btn btn-sm btn-info">Ver Detalle</a>
                        </li>
                    `).join('')}
                </ul>
            `;
        }

        const pacienteHtml = `
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Paciente Encontrado</h3>
                </div>
                <div class="card-body">
                    <h4>${paciente.nombre} ${paciente.apellido}</h4>
                    <p class="card-text">
                        <strong>Documento:</strong> ${paciente.tipo_documento} ${paciente.numero_documento}<br>
                        <strong>Fecha de Nacimiento:</strong> ${paciente.fecha_nacimiento}<br>
                        <strong>Teléfono:</strong> ${paciente.telefono_whatsapp || 'No registrado'}
                    </p>
                    <a href="nueva_historia.php?documento=${paciente.numero_documento}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i> Iniciar Nueva Consulta
                    </a>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Historial de Consultas</h3>
                </div>
                <div class="card-body p-0">
                    ${consultasHtml}
                </div>
            </div>
        `;
        resultadoContainer.innerHTML = pacienteHtml;
    }

    function mostrarNoEncontrado(documento) {
        const html = `
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Sin Resultados</h3>
                </div>
                <div class="card-body text-center">
                    <p>No se encontró ningún paciente con el documento ingresado.</p>
                    <a href="nueva_historia.php?documento=${documento}" class="btn btn-success btn-lg">
                         <i class="fas fa-user-plus"></i> Registrar Nuevo Paciente y Crear Primera Consulta
                    </a>
                </div>
            </div>
        `;
        resultadoContainer.innerHTML = html;
    }
});