/* =============================================================
 * HISTORIA_CLINICA.JS - VERSIÓN 3 (Con Badges Profesionales)
 * =============================================================*/
document.addEventListener('DOMContentLoaded', function() {

    // =================================================================
    // OBTENER ELEMENTOS DEL DOM
    // =================================================================
    
    // --- Sección Paciente ---
    const sexoSelect = document.getElementById('sexo');
    const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
    const edadInput = document.getElementById('edad');
    const seccionGestacion = document.getElementById('seccion-gestacion');
    const embarazadaSelect = document.getElementById('embarazada');
    const semanasInput = document.getElementById('semanas_gestacion');

    // --- Sección Signos Vitales y Cálculos ---
    const pesoInput = document.getElementById('peso_kg');
    const tallaInput = document.getElementById('talla_cm');
    const imcInput = document.getElementById('imc');
    const imcAlerta = document.getElementById('imc_clasificacion_alerta');
    
    const sistolicaInput = document.getElementById('tension_sistolica');
    const diastolicaInput = document.getElementById('tension_diastolica');
    const htaAlerta = document.getElementById('hta_clasificacion_alerta');

    const creatininaInput = document.getElementById('creatinina_serica');
    const egfrInput = document.getElementById('filtrado_glomerular_ckd_epi');
    const egfrAlerta = document.getElementById('egfr_clasificacion_alerta');

    // --- Sección Receta ---
    const btnAgregarMedicamento = document.getElementById('btn-agregar-medicamento');
    const containerReceta = document.getElementById('receta-medica-container');

    // --- Carga de Datos ---
    const documentoACargarInput = document.getElementById('documento_a_cargar');

    // =================================================================
    // FUNCIONES DE CÁLCULO CLÍNICO
    // =================================================================

    /**
     * Función principal que se llama cuando cualquier dato clínico cambia.
     * Orquesta todos los cálculos y actualizaciones de la UI.
     */
    function actualizarCalculosClinicos() {
        // 1. Calcular Edad
        const edad = calcularEdad();
        
        // 2. Calcular IMC y su clasificación
        const imc = calcularIMC();
        clasificarIMC(imc);

        // 3. Clasificar Hipertensión
        clasificarHTA();

        // 4. Calcular Filtrado Glomerular (CKD-EPI)
        const sexo = sexoSelect ? sexoSelect.value : null;
        const creatinina = parseFloat(creatininaInput.value);
        
        if (edad >= 18 && creatinina > 0 && sexo) {
            calcularCKD_EPI(creatinina, edad, sexo);
        } else {
            // Limpia los campos de TFG si no hay datos suficientes
            if(egfrInput) egfrInput.value = '';
            if(egfrAlerta) {
                egfrAlerta.textContent = '';
                // CAMBIO: Se usa badge-light para ocultar la etiqueta
                egfrAlerta.className = 'badge badge-light';
            }
        }
    }

    /**
     * Calcula la edad y la muestra en el input 'edad'.
     * @returns {number} La edad calculada.
     */
    function calcularEdad() {
        if (!fechaNacimientoInput || !edadInput) return null;

        const fechaNacimiento = new Date(fechaNacimientoInput.value);
        // Ajuste de zona horaria (importante para 'change' de <input type="date">)
        const offset = fechaNacimiento.getTimezoneOffset();
        const fechaNacimientoAjustada = new Date(fechaNacimiento.getTime() + (offset * 60 * 1000));

        if (isNaN(fechaNacimientoAjustada.getTime())) {
            edadInput.value = '';
            return null;
        }

        const hoy = new Date();
        let edad = hoy.getFullYear() - fechaNacimientoAjustada.getFullYear();
        const mes = hoy.getMonth() - fechaNacimientoAjustada.getMonth();

        if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimientoAjustada.getDate())) {
            edad--;
        }
        
        edadInput.value = edad >= 0 ? edad : '';
        return edad >= 0 ? edad : null;
    }

    /**
     * Calcula el IMC y lo muestra en el input 'imc'.
     * @returns {number} El IMC calculado.
     */
    function calcularIMC() {
        if (!pesoInput || !tallaInput || !imcInput) return null;

        const peso = parseFloat(pesoInput.value);
        const tallaCm = parseFloat(tallaInput.value);

        if (peso > 0 && tallaCm > 0) {
            const tallaM = tallaCm / 100;
            const imc = peso / (tallaM * tallaM);
            imcInput.value = imc.toFixed(2);
            return imc;
        } else {
            imcInput.value = '';
            return null;
        }
    }

    /**
     * Muestra la clasificación del IMC en la UI usando Badges.
     * @param {number} imc - El valor del IMC.
     */
    function clasificarIMC(imc) {
        if (!imcAlerta) return;
        
        let clasificacion = '';
        // CAMBIO: Se usan clases de 'badge' de Bootstrap. 'badge-light' se usa para ocultarla.
        let claseColor = 'badge-light';

        if (imc === null || isNaN(imc)) {
            clasificacion = '';
        } else if (imc < 18.5) {
            clasificacion = 'Bajo Peso';
            claseColor = 'badge-warning';
        } else if (imc >= 18.5 && imc <= 24.9) {
            clasificacion = 'Peso Normal';
            claseColor = 'badge-success';
        } else if (imc >= 25 && imc <= 29.9) {
            clasificacion = 'Sobrepeso';
            claseColor = 'badge-warning';
        } else if (imc >= 30 && imc <= 34.9) {
            clasificacion = 'Obesidad Grado 1';
            claseColor = 'badge-danger';
        } else if (imc >= 35 && imc <= 39.9) {
            clasificacion = 'Obesidad Grado 2';
            claseColor = 'badge-danger';
        } else if (imc >= 40) {
            clasificacion = 'Obesidad Grado 3 (Mórbida)';
            claseColor = 'badge-danger';
        }

        imcAlerta.textContent = clasificacion;
        // CAMBIO: Se asigna la clase del badge. La clase base 'badge' viene del HTML.
        imcAlerta.className = `badge ${claseColor}`;
    }

    /**
     * Muestra la clasificación de HTA en la UI usando Badges.
     */
    function clasificarHTA() {
        if (!sistolicaInput || !diastolicaInput || !htaAlerta) return;

        const sistolica = parseInt(sistolicaInput.value, 10);
        const diastolica = parseInt(diastolicaInput.value, 10);

        let clasificacion = '';
        // CAMBIO: Se usan clases de 'badge' de Bootstrap
        let claseColor = 'badge-light';

        if (!sistolica || !diastolica) {
             clasificacion = '';
        } else if (sistolica > 180 || diastolica > 120) {
            clasificacion = 'Crisis Hipertensiva';
            claseColor = 'badge-danger';
        } else if (sistolica >= 140 || diastolica >= 90) {
            clasificacion = 'Hipertensión Grado 2';
            claseColor = 'badge-danger';
        } else if ((sistolica >= 130 && sistolica <= 139) || (diastolica >= 80 && diastolica <= 89)) {
            clasificacion = 'Hipertensión Grado 1';
            claseColor = 'badge-warning';
        } else if ((sistolica >= 120 && sistolica <= 129) && diastolica < 80) {
            clasificacion = 'Presión Arterial Elevada';
            claseColor = 'badge-warning';
        } else if (sistolica < 120 && diastolica < 80) {
            clasificacion = 'Presión Arterial Normal';
            claseColor = 'badge-success';
        } else {
            clasificacion = 'No clasificable';
            claseColor = 'badge-secondary';
        }
        
        htaAlerta.textContent = clasificacion;
        // CAMBIO: Se asigna la clase del badge
        htaAlerta.className = `badge ${claseColor}`;
    }

    /**
     * Calcula y muestra el Filtrado Glomerular (CKD-EPI) y su estadio usando Badges.
     * @param {number} creatinina - Creatinina sérica en mg/dL.
     * @param {number} edad - Edad en años.
     * @param {string} sexo - 'MASCULINO' o 'FEMENINO'.
     */
    function calcularCKD_EPI(creatinina, edad, sexo) {
        if (!egfrInput || !egfrAlerta) return;

        // Parámetros de la fórmula CKD-EPI
        const k = (sexo === 'FEMENINO') ? 0.7 : 0.9;
        const alpha = (sexo === 'FEMENINO') ? -0.329 : -0.411;
        const S = (sexo === 'FEMENINO') ? 1.018 : 1.0;
        const R = 1.0; // Asumimos raza no-negra (1.0).

        const ratio = creatinina / k;
        const egfr = 141 * Math.pow(Math.min(ratio, 1), alpha) * Math.pow(Math.max(ratio, 1), -1.209) * Math.pow(0.993, edad) * S * R;
        
        egfrInput.value = egfr.toFixed(2);

        // Clasificación de Estadios ERC (Enfermedad Renal Crónica)
        let clasificacion = '';
        // CAMBIO: Se usan clases de 'badge' de Bootstrap
        let claseColor = 'badge-light';
        
        if (egfr >= 90) {
            clasificacion = 'Estadio G1: Normal o Alto (TFG >= 90)';
            claseColor = 'badge-success';
        } else if (egfr >= 60 && egfr <= 89) {
            clasificacion = 'Estadio G2: Levemente Disminuido (TFG 60-89)';
            claseColor = 'badge-success';
        } else if (egfr >= 45 && egfr <= 59) {
            clasificacion = 'Estadio G3a: Leve a Moderadamente Disminuido (TFG 45-59)';
            claseColor = 'badge-warning';
        } else if (egfr >= 30 && egfr <= 44) {
            clasificacion = 'Estadio G3b: Moderada a Severamente Disminuido (TFG 30-44)';
            claseColor = 'badge-warning';
        } else if (egfr >= 15 && egfr <= 29) {
            clasificacion = 'Estadio G4: Severamente Disminuido (TFG 15-29)';
            claseColor = 'badge-danger';
        } else if (egfr < 15) {
            clasificacion = 'Estadio G5: Falla Renal (TFG < 15)';
            claseColor = 'badge-danger';
        }
        
        egfrAlerta.textContent = clasificacion;
        // CAMBIO: Se asigna la clase del badge
        egfrAlerta.className = `badge ${claseColor}`;
    }

    // =================================================================
    // LÓGICA DE MANEJO DEL FORMULARIO (GESTATIÓN, RECETA)
    // =================================================================

    // --- Lógica de Gestación ---
    function manejarVisibilidadGestacion() {
        if (!sexoSelect || !seccionGestacion) return;
        
        if (sexoSelect.value === 'FEMENINO') {
            seccionGestacion.style.display = 'flex';
        } else {
            seccionGestacion.style.display = 'none';
            if (embarazadaSelect) embarazadaSelect.value = '0';
            if (semanasInput) {
                semanasInput.value = '';
                semanasInput.disabled = true;
            }
        }
        actualizarCalculosClinicos(); // Recalcular CKD-EPI si cambia el sexo
    }

    if (sexoSelect) {
        sexoSelect.addEventListener('change', manejarVisibilidadGestacion);
    }

    if (embarazadaSelect) {
        embarazadaSelect.addEventListener('change', function() {
            if (semanasInput) {
                semanasInput.disabled = this.value !== '1';
                if (this.value !== '1') {
                    semanasInput.value = '';
                }
            }
        });
    }

    // --- Lógica de Receta Dinámica ---
    if (btnAgregarMedicamento) {
        btnAgregarMedicamento.addEventListener('click', function() {
            const nuevaFila = document.createElement('div');
            nuevaFila.classList.add('row', 'medicamento-row', 'align-items-end');
            nuevaFila.innerHTML = `
                <div class="col-md-4"><div class="form-group mb-0"><label>Medicamento</label><input type="text" class="form-control" name="medicamento_nombre[]"></div></div>
                <div class="col-md-4"><div class="form-group mb-0"><label>Horario/Dosis</label><input type="text" class="form-control" name="medicamento_dosis[]"></div></div>
                <div class="col-md-3"><div class="form-group mb-0"><label>Cantidad</label><input type="text" class="form-control" name="medicamento_cantidad[]"></div></div>
                <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm btn-remover-medicamento mb-3">X</button></div> 
            `;
            containerReceta.appendChild(nuevaFila);
        });
    }

    if (containerReceta) {
        containerReceta.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-remover-medicamento')) {
                e.target.closest('.medicamento-row').remove();
            }
        });
    }

    // =================================================================
    // ASIGNACIÓN DE EVENT LISTENERS PARA CÁLCULOS
    // =================================================================

    // Asignamos la función principal a todos los inputs que afectan los cálculos
    const inputsParaCalculo = [
        fechaNacimientoInput, pesoInput, tallaInput,
        sistolicaInput, diastolicaInput, creatininaInput, sexoSelect
    ];

    inputsParaCalculo.forEach(input => {
        if (input) {
            // Usamos 'input' para campos de texto/numéricos y 'change' para <select>
            const eventType = (input.tagName.toUpperCase() === 'SELECT') ? 'change' : 'input';
            input.addEventListener(eventType, actualizarCalculosClinicos);
        }
    });

    // =================================================================
    // LÓGICA PARA CARGAR PACIENTES EXISTENTES
    // =================================================================

    if (documentoACargarInput && documentoACargarInput.value) {
        const documento = documentoACargarInput.value;
        fetch(`../controllers/paciente_controller.php?action=get_one&documento=${documento}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    rellenarFormularioPaciente(data.paciente);
                } else {
                    document.getElementById('numero_documento').value = documento;
                }
            })
            .catch(error => {
                console.error('Error al cargar datos del paciente:', error);
            });
    }

    function rellenarFormularioPaciente(paciente) {
        document.getElementById('titulo-formulario').textContent = `Nueva Consulta para: ${paciente.nombre} ${paciente.apellido}`;
        
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

        for (const [id, valor] of Object.entries(campos)) {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = valor || '';
                if (id === 'numero_documento') {
                     campo.setAttribute('readonly', true);
                } else {
                    campo.removeAttribute('readonly');
                    campo.removeAttribute('disabled');
                }
            }
        }
        
        // Rellenar campos de gestación
        if (paciente.sexo === 'FEMENINO') {
            if (embarazadaSelect) {
                embarazadaSelect.value = paciente.embarazada || '0'; 
                embarazadaSelect.removeAttribute('disabled');
            }
            if (semanasInput && (paciente.embarazada == '1' || paciente.embarazada === 1)) {
                semanasInput.value = paciente.semanas_gestacion || '';
                semanasInput.disabled = false;
                semanasInput.removeAttribute('readonly');
            }
        }

        // Disparamos 'change' en los campos clave para forzar la actualización de cálculos y UI
        // Usamos 'change' para fecha y select, ya que 'input' no se dispara al setear .value
        if (fechaNacimientoInput) fechaNacimientoInput.dispatchEvent(new Event('input')); 
        if (sexoSelect) sexoSelect.dispatchEvent(new Event('change'));
        
        // Forzar un cálculo inicial con los datos cargados
        // Lo ponemos en un pequeño timeout para asegurar que el DOM se haya actualizado
        setTimeout(actualizarCalculosClinicos, 100);
    }
});