document.addEventListener('DOMContentLoaded', function() {

    // =================================================================
    // INICIALIZACIÓN DE FUNCIONES GENERALES
    // =================================================================

    // --- CÁLCULO AUTOMÁTICO DE IMC ---
    const pesoInput = document.getElementById('peso_kg');
    const tallaInput = document.getElementById('talla_cm');
    const imcInput = document.getElementById('imc');

    function calcularIMC() {
        const peso = parseFloat(pesoInput.value);
        const tallaCm = parseFloat(tallaInput.value);

        if (peso > 0 && tallaCm > 0) {
            const tallaM = tallaCm / 100;
            const imc = peso / (tallaM * tallaM);
            imcInput.value = imc.toFixed(2);
        } else {
            imcInput.value = '';
        }
    }
    if(pesoInput) pesoInput.addEventListener('input', calcularIMC); // Verificación añadida
    if(tallaInput) tallaInput.addEventListener('input', calcularIMC); // Verificación añadida


    // --- LÓGICA PARA MOSTRAR CAMPOS DE GESTACIÓN ---
    const sexoSelect = document.getElementById('sexo');
    const seccionGestacion = document.getElementById('seccion-gestacion');
    const embarazadaSelect = document.getElementById('embarazada');
    const semanasInput = document.getElementById('semanas_gestacion');

    if (sexoSelect) { // Verificación añadida
        sexoSelect.addEventListener('change', function() {
            if (this.value === 'FEMENINO') {
                seccionGestacion.style.display = 'flex'; // Usar flex para que se alinee bien en fila
            } else {
                seccionGestacion.style.display = 'none';
                embarazadaSelect.value = '0';
                semanasInput.value = '';
                semanasInput.disabled = true;
            }
        });
    }

    if (embarazadaSelect) { // Verificación añadida
        embarazadaSelect.addEventListener('change', function() {
            semanasInput.disabled = this.value !== '1';
            if (this.value !== '1') {
                semanasInput.value = '';
            }
        });
    }

    // --- CÁLCULO AUTOMÁTICO DE EDAD ---
    const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
    const edadInput = document.getElementById('edad');

    if (fechaNacimientoInput) { // Verificación añadida
        fechaNacimientoInput.addEventListener('change', function() {
            const fechaNacimiento = new Date(this.value);
            // Añadimos ajuste de zona horaria para evitar errores de un día
            const offset = fechaNacimiento.getTimezoneOffset();
            const fechaNacimientoAjustada = new Date(fechaNacimiento.getTime() + (offset*60*1000));

            if (!isNaN(fechaNacimientoAjustada.getTime())) {
                const hoy = new Date();
                let edad = hoy.getFullYear() - fechaNacimientoAjustada.getFullYear();
                const mes = hoy.getMonth() - fechaNacimientoAjustada.getMonth();

                if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimientoAjustada.getDate())) {
                    edad--;
                }
                edadInput.value = edad >= 0 ? edad : '';
            } else {
                edadInput.value = '';
            }
        });
    }


    // --- AGREGAR Y QUITAR MEDICAMENTOS DINÁMICAMENTE ---
    const btnAgregarMedicamento = document.getElementById('btn-agregar-medicamento');
    const containerReceta = document.getElementById('receta-medica-container');

    if (btnAgregarMedicamento) { // Verificación añadida
        btnAgregarMedicamento.addEventListener('click', function() {
            const nuevaFila = document.createElement('div');
            nuevaFila.classList.add('row', 'medicamento-row', 'align-items-end'); // align-items-end para alinear el botón
            nuevaFila.innerHTML = `
                <div class="col-md-4"><div class="form-group mb-0"><label>Medicamento</label><input type="text" class="form-control" name="medicamento_nombre[]"></div></div>
                <div class="col-md-4"><div class="form-group mb-0"><label>Horario/Dosis</label><input type="text" class="form-control" name="medicamento_dosis[]"></div></div>
                <div class="col-md-3"><div class="form-group mb-0"><label>Cantidad</label><input type="text" class="form-control" name="medicamento_cantidad[]"></div></div>
                <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm btn-remover-medicamento mb-3">X</button></div> 
            `; // mb-0 en form-group y mb-3 en botón para mejor alineación
            containerReceta.appendChild(nuevaFila);
        });
    }

    if (containerReceta) { // Verificación añadida
        containerReceta.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-remover-medicamento')) {
                e.target.closest('.medicamento-row').remove();
            }
        });
    }

    // =================================================================
    // LÓGICA ESPECÍFICA PARA CARGAR PACIENTES EXISTENTES
    // =================================================================
    const documentoACargarInput = document.getElementById('documento_a_cargar');
    
    if (documentoACargarInput && documentoACargarInput.value) { // Verificación añadida
        const documento = documentoACargarInput.value;
        fetch(`../controllers/paciente_controller.php?action=get_one&documento=${documento}`)
    
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    rellenarFormularioPaciente(data.paciente);
                } else {
                    // Si no se encuentra, solo pre-rellenamos el documento y dejamos el resto editable
                    document.getElementById('numero_documento').value = documento;
                }
            })
            .catch(error => {
                console.error('Error al cargar datos del paciente:', error);
                // Podríamos mostrar un toastr aquí
                // toastr.error('No se pudieron cargar los datos del paciente.');
            });
    }

    /**
     * FUNCIÓN: rellenarFormularioPaciente
     * Rellena los campos del formulario con los datos de un paciente existente y los bloquea.
     * @param {object} paciente - Objeto con los datos del paciente.
     */
    function rellenarFormularioPaciente(paciente) {
        document.getElementById('titulo-formulario').textContent = `Nueva Consulta para: ${paciente.nombre} ${paciente.apellido}`;
        
        // Mapeo de IDs de campo a propiedades del objeto paciente
        const campos = {
            'tipo_documento': paciente.tipo_documento,
            'numero_documento': paciente.numero_documento,
            'nombre': paciente.nombre,
            'apellido': paciente.apellido,
            'fecha_nacimiento': paciente.fecha_nacimiento,
            'sexo': paciente.sexo,
            'estado_civil': paciente.estado_civil,
            'telefono_whatsapp': paciente.telefono_whatsapp,
            'direccion': paciente.direccion,
            'profesion': paciente.profesion
        };

        // Rellena y bloquea cada campo
        for (const [id, valor] of Object.entries(campos)) {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = valor || ''; // Usa valor o string vacío si es null/undefined
                
                // SOLO bloqueamos el número de documento (que es la llave primaria)
                if (id === 'numero_documento') {
                     campo.setAttribute('readonly', true);
                } else {
                    // Nos aseguramos de que todos los demás campos estén habilitados
                    campo.removeAttribute('readonly');
                    campo.removeAttribute('disabled');
                }
            }
        }
        
        // Disparamos 'change' para recalcular edad y mostrar/ocultar gestación
        if(fechaNacimientoInput) fechaNacimientoInput.dispatchEvent(new Event('change'));
        if(sexoSelect) sexoSelect.dispatchEvent(new Event('change')); 
        
       // Rellenar campos de gestación y dejarlos editables
        if (paciente.sexo === 'FEMENINO') {
            embarazadaSelect.value = paciente.embarazada || '0'; 
            embarazadaSelect.removeAttribute('disabled'); // El médico puede editar esto
            if (paciente.embarazada == '1' || paciente.embarazada === 1) {
                semanasInput.value = paciente.semanas_gestacion;
                semanasInput.disabled = false;
                semanasInput.removeAttribute('readonly'); // El médico puede editar esto
            }
        }
    }
}); // <-- La llave extra fue eliminada de aquí.