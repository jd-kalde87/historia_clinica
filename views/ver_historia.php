<?php
/**
 * ===============================================
 * INCLUDES Y CONFIGURACIÓN INICIAL
 * ===============================================
 */
require_once 'includes/header.php';
require_once 'includes/sidebar.php'; 
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; 
require_once '../models/consulta_model.php'; 

/**
 * ===============================================
 * FUNCIONES HELPER PARA CLASIFICACIONES
 * ===============================================
 */

/**
 * Devuelve la clase CSS de Bootstrap para un badge según el texto de clasificación.
 * @param string $clasificacion_texto El texto guardado en la BD (ej. "Sobrepeso", "Hipertensión Grado 2")
 * @return string La clase CSS (ej. "badge-warning", "badge-danger")
 */
function getBadgeClass($clasificacion_texto) {
    if (empty($clasificacion_texto) || $clasificacion_texto == 'N/A') {
        return 'badge-light text-dark'; // Gris claro si está vacío
    }
    
    $texto = strtolower($clasificacion_texto);

    // Búsqueda de términos de peligro (rojo)
    if (strpos($texto, 'obesidad') !== false || strpos($texto, 'crisis') !== false || strpos($texto, 'grado 2') !== false || strpos($texto, 'grado 3') !== false || strpos($texto, 'severamente') !== false || strpos($texto, 'falla renal') !== false || strpos($texto, 'estadio g4') !== false || strpos($texto, 'estadio g5') !== false) {
        return 'badge-danger';
    }
    
    // Búsqueda de términos de advertencia (amarillo)
    if (strpos($texto, 'sobrepeso') !== false || strpos($texto, 'elevada') !== false || strpos($texto, 'grado 1') !== false || strpos($texto, 'leve a moderadamente') !== false || strpos($texto, 'estadio g3') !== false) {
        return 'badge-warning';
    }
    
    // Búsqueda de términos de éxito (verde)
    if (strpos($texto, 'normal') !== false || strpos($texto, 'levemente disminuido') !== false || strpos($texto, 'estadio g1') !== false || strpos($texto, 'estadio g2') !== false) {
        return 'badge-success';
    }

    return 'badge-secondary'; // Un color por defecto si no coincide
}

/**
 * Genera el texto de clasificación para TFG (basado en el valor numérico)
 * @param float|null $tfg_valor
 * @return string
 */
function getClasificacionTFGTexto($tfg_valor) {
    if ($tfg_valor === null || $tfg_valor == 0) {
        return 'N/A';
    }
    if ($tfg_valor >= 90) return 'Estadio G1 (>= 90)';
    if ($tfg_valor >= 60) return 'Estadio G2 (60-89)';
    if ($tfg_valor >= 45) return 'Estadio G3a (45-59)';
    if ($tfg_valor >= 30) return 'Estadio G3b (30-44)';
    if ($tfg_valor >= 15) return 'Estadio G4 (15-29)';
    return 'Estadio G5 (< 15)';
}
// --- FIN DE FUNCIONES HELPER ---


/**
 * ===============================================
 * VALIDACIÓN DEL ID DE HISTORIA
 * ===============================================
 */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger mt-3">ID de consulta no válido.</div></div></section></div>';
    require_once 'includes/footer.php';
    exit();
}
$id_historia = (int)$_GET['id']; 

/**
 * ===============================================
 * OBTENCIÓN DE DATOS DE LA CONSULTA
 * ===============================================
 */
$conexion = conectarDB();
$datos = obtenerDetallesConsulta($conexion, $id_historia); 
$conexion->close();

if (!$datos) {
    echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-warning mt-3">No se encontró la consulta solicitada.</div></div></section></div>';
    require_once 'includes/footer.php';
    exit();
}

$consulta = $datos['consulta'];
$medicamentos = $datos['medicamentos'];
$archivos = $datos['archivos'];

// --- CAMBIO LÓGICO: DETERMINAR SI ES UN CONTROL ---
// Usamos el valor que hardcodeamos en el formulario 'nuevo_control.php'
$es_control = (isset($consulta['motivo_consulta']) && $consulta['motivo_consulta'] === 'NOTA DE CONTROL / EVOLUCION');


/**
 * ===============================================
 * LÓGICA PARA EL MENSAJE DE WHATSAPP
 * ===============================================
 */
$mensaje_whatsapp_sin_codificar = generarMensajeWhatsApp($consulta, $medicamentos, false);
$telefono_limpio_js = '';
if (!empty($consulta['telefono_whatsapp'])) {
    $telefono_limpio_js = preg_replace('/[^0-9]/', '', $consulta['telefono_whatsapp']);
    if (strlen($telefono_limpio_js) == 10) { $telefono_limpio_js = '57' . $telefono_limpio_js; } 
}

/**
 * ===============================================
 * FORMATEO DE NOMBRES PARA MOSTRAR
 * ===============================================
 */
$nombre_paciente_titulo = mb_convert_case(($consulta['nombre'] ?? '') . ' ' . ($consulta['apellido'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$nombre_medico_titulo = mb_convert_case(($consulta['nombre_medico'] ?? '') . ' ' . ($consulta['apellido_medico'] ?? ''), MB_CASE_TITLE, 'UTF-8');

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <?php if(isset($_GET['upload_status']) && $_GET['upload_status'] == 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Archivo subido exitosamente.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php elseif(isset($_GET['upload_status']) && $_GET['upload_status'] == 'error'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo 'Error al subir: ' . htmlspecialchars($_GET['msg']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <?php echo $es_control ? 'Detalle de Control' : 'Detalle de Consulta'; ?>
                        - <?php echo htmlspecialchars($consulta['codigo_historia']); ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        
                        <a href="nuevo_control.php?documento=<?php echo htmlspecialchars($consulta['numero_documento']); ?>" class="btn btn-warning" title="Registrar Nueva Consulta de Control">
                            <i class="fas fa-plus"></i> Agregar Control
                        </a>

                        <a href="../reports/generar_historia_pdf.php?id=<?php echo $id_historia; ?>" target="_blank" class="btn btn-primary" title="Imprimir Resumen de Consulta"><i class="fas fa-print"></i> Consulta</a>
                        
                        <?php if (!empty($medicamentos)): ?>
                            <a href="../reports/generar_receta_pdf.php?id=<?php echo $id_historia; ?>" target="_blank" class="btn btn-success" title="Imprimir Receta Médica"><i class="fas fa-prescription-bottle-alt"></i> Receta</a>
                        <?php endif; ?>

                        <?php if (!empty($consulta['telefono_whatsapp'])): ?>
                            <button type="button" class="btn btn-info" id="btn-mostrar-resumen-whatsapp" 
                                    data-telefono="<?php echo htmlspecialchars($telefono_limpio_js); ?>" 
                                    title="Ver resumen y enviar por WhatsApp">
                                <i class="fab fa-whatsapp"></i> Enviar Resumen
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled title="No hay teléfono registrado"><i class="fab fa-whatsapp"></i> Enviar Resumen</button>
                        <?php endif; ?>
                    </div> </div> </div> </div> </div> <section class="content">
        <div class="container-fluid">
            
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Datos del Paciente</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($nombre_paciente_titulo); ?></p>
                            <p><strong>Documento:</strong> <?php echo htmlspecialchars($consulta['tipo_documento'] . ' - ' . $consulta['numero_documento']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($consulta['telefono_whatsapp'] ?? 'No registrado'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Edad:</strong> <?php echo calcularEdad($consulta['fecha_nacimiento']); ?> años (Nacido el: <?php echo date("d/m/Y", strtotime($consulta['fecha_nacimiento'])); ?>)</p>
                            <p><strong>Sexo:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($consulta['sexo'] ?? 'N/A'))); ?></p>
                            <p><strong>Estado Civil:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($consulta['estado_civil'] ?? 'N/A'))); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header"><h3 class="card-title">Detalles de la Consulta</h3></div>
                <div class="card-body">
                    <p><strong>Fecha y Hora de Consulta:</strong> <?php echo date("d/m/Y h:i A", strtotime($consulta['fecha_consulta'])); ?></p>
                    <p><strong>Médico Tratante:</strong> Dr(a). <?php echo htmlspecialchars($nombre_medico_titulo); ?></p>
                    <hr>

                    <?php if ($es_control): // --- VISTA SI ES UN CONTROL --- ?>
                        
                        <p><strong>Evolución / Nota de Control:</strong><br><?php echo nl2br(htmlspecialchars($consulta['enfermedad_actual'] ?? 'No registrado')); ?></p>
                        <p><strong>Hallazgos del Examen Físico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['hallazgos_examen_fisico'] ?? 'No registrado')); ?></p>
                        <hr>
                        <p><strong>Diagnóstico Principal:</strong><br><?php echo nl2br(htmlspecialchars($consulta['diagnostico_principal'] ?? 'No registrado')); ?></p>
                        <p><strong>Tratamiento a Seguir:</strong><br><?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?? 'No registrado')); ?></p>
                        <p><strong>Solicitud de Exámenes:</strong><br><?php echo nl2br(htmlspecialchars($consulta['solicitud_examenes'] ?? 'No registrado')); ?></p>

                    <?php else: // --- VISTA SI ES UNA CONSULTA COMPLETA --- ?>
                        
                        <p><strong>Motivo de Consulta:</strong><br><?php echo nl2br(htmlspecialchars($consulta['motivo_consulta'] ?? 'No registrado')); ?></p>
                        <p><strong>Enfermedad Actual:</strong><br><?php echo nl2br(htmlspecialchars($consulta['enfermedad_actual'] ?? 'No registrado')); ?></p>
                        <p><strong>Antecedentes Personales:</strong><br><?php echo nl2br(htmlspecialchars($consulta['antecedentes_personales'] ?? 'No registrado')); ?></p>
                        <p><strong>Antecedentes Familiares:</strong><br><?php echo nl2br(htmlspecialchars($consulta['antecedentes_familiares'] ?? 'No registrado')); ?></p>
                        <hr>
                        <p><strong>Examen Físico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['examen_fisico'] ?? 'No registrado')); ?></p>
                        <p><strong>Hallazgos del Examen Físico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['hallazgos_examen_fisico'] ?? 'No registrado')); ?></p>
                        <hr>
                        <p><strong>Diagnóstico Principal:</strong><br><?php echo nl2br(htmlspecialchars($consulta['diagnostico_principal'] ?? 'No registrado')); ?></p>
                        <p><strong>Tratamiento a Seguir:</strong><br><?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?? 'No registrado')); ?></p>
                        <p><strong>Solicitud de Exámenes:</strong><br><?php echo nl2br(htmlspecialchars($consulta['solicitud_examenes'] ?? 'No registrado')); ?></p>

                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$es_control): // --- MOSTRAR SIGNOS VITALES SOLO SI NO ES UN CONTROL --- ?>
           <div class="card card-purple">
                <div class="card-header"><h3 class="card-title">Signos Vitales y Antropometría</h3></div>
                <div class="card-body" style="font-size: 1.1em;">
                    
                    <?php
                        // Obtenemos las clasificaciones y sus clases para los badges
                        $imc_texto = $consulta['imc_clasificacion'] ?? 'N/A';
                        $imc_clase = getBadgeClass($imc_texto);
                        
                        $hta_texto = $consulta['clasificacion_hta'] ?? 'N/A';
                        $hta_clase = getBadgeClass($hta_texto);
                        
                        $tfg_texto = getClasificacionTFGTexto($consulta['filtrado_glomerular_ckd_epi'] ?? null);
                        $tfg_clase = getBadgeClass($tfg_texto);
                    ?>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Peso:</strong> <?php echo htmlspecialchars($consulta['peso_kg'] ?? 'N/A'); ?> kg
                        </div>
                        <div class="col-md-4">
                            <strong>Talla:</strong> <?php echo htmlspecialchars($consulta['talla_cm'] ?? 'N/A'); ?> cm
                        </div>
                        <div class="col-md-4">
                            <strong>IMC:</strong> <?php echo htmlspecialchars($consulta['imc'] ?? 'N/A'); ?>
                            <span class="badge <?php echo $imc_clase; ?> ml-2"><?php echo htmlspecialchars($imc_texto); ?></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>T.A. Sistólica:</strong> <?php echo htmlspecialchars($consulta['tension_sistolica'] ?? 'N/A'); ?> mmHg
                        </div>
                        <div class="col-md-4">
                            <strong>T.A. Diastólica:</strong> <?php echo htmlspecialchars($consulta['tension_diastolica'] ?? 'N/A'); ?> mmHg
                        </div>
                        <div class="col-md-4">
                            <strong>Clasificación HTA:</strong>
                            <span class="badge <?php echo $hta_clase; ?> ml-2"><?php echo htmlspecialchars($hta_texto); ?></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Frec. Cardíaca:</strong> <?php echo htmlspecialchars($consulta['frecuencia_cardiaca'] ?? 'N/A'); ?> lat/min
                        </div>
                        <div class="col-md-4">
                            <strong>Frec. Respiratoria:</strong> <?php echo htmlspecialchars($consulta['frecuencia_respiratoria'] ?? 'N/A'); ?> resp/min
                        </div>
                        <div class="col-md-4">
                            <strong>Temperatura:</strong> <?php echo htmlspecialchars($consulta['temperatura_c'] ?? 'N/A'); ?> °C
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <strong>Hb Glicosilada (HbA1c %):</strong> <?php echo htmlspecialchars($consulta['hemoglobina_glicosilada'] ?? 'N/A'); ?> %
                        </div>
                        <div class="col-md-4">
                            <strong>Creatinina Sérica:</strong> <?php echo htmlspecialchars($consulta['creatinina_serica'] ?? 'N/A'); ?> mg/dL
                        </div>
                        <div class="col-md-4">
                            <strong>Filtrado Glomerular:</strong> <?php echo htmlspecialchars($consulta['filtrado_glomerular_ckd_epi'] ?? 'N/A'); ?>
                            <span class="badge <?php echo $tfg_clase; ?> ml-2" style="font-size: 0.9em;"><?php echo htmlspecialchars($tfg_texto); ?></span>
                        </div>
                    </div>

                </div>
            </div>
           <?php endif; // --- FIN DE LA CONDICIÓN !$es_control --- ?>
            <?php if (!empty($medicamentos)): // Solo se muestra si hay medicamentos recetados ?>
            <div class="card card-danger">
                <div class="card-header"><h3 class="card-title">Receta Médica</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-hover"> 
                        <thead class="thead-light"> 
                            <tr>
                                <th>Medicamento</th>
                                <th>Dosis / Horario</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicamentos as $medicamento): // Recorre cada medicamento y lo muestra en una fila ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicamento['nombre_medicamento']); ?></td>
                                <td><?php echo htmlspecialchars($medicamento['horario_dosis']); ?></td>
                                <td><?php echo htmlspecialchars($medicamento['cantidad']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Archivos Adjuntos (Exámenes, etc.)</h3>
                </div>
                <div class="card-body">
                    <form action="../controllers/archivo_controller.php" method="post" enctype="multipart/form-data" class="mb-4 border-bottom pb-4">
                        <p><strong>Adjuntar nuevo archivo:</strong></p>
                        <div class="form-group">
                            <input type="hidden" name="id_historia" value="<?php echo $id_historia; ?>">
                            <input type="hidden" name="documento_paciente" value="<?php echo htmlspecialchars($consulta['numero_documento']); ?>">
                            
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="archivo" name="archivo" required>
                                    <label class="custom-file-label" for="archivo">Seleccionar archivo...</label>
                                </div>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="submit">Subir Archivo</button>
                                </div>
                            </div>
                             <small class="form-text text-muted">Tipos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB.</small>
                        </div>
                    </form>

                    <h5>Archivos existentes:</h5>
                    <?php if (empty($archivos)): // Si no hay archivos, muestra un mensaje ?>
                        <p>No hay archivos adjuntos para esta consulta.</p>
                    <?php else: // Si hay archivos, los lista ?>
                        <ul class="list-group list-group-flush"> 
                            <?php foreach ($archivos as $archivo): // Recorre cada archivo ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span> 
                                        <i class="fas fa-paperclip mr-2"></i> <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                                        <small class="text-muted ml-2">(Subido: <?php echo date("d/m/Y", strtotime($archivo['fecha_carga'])); ?>)</small>
                                    </span>
                                    <a href="<?php echo (defined('BASE_URL') ? BASE_URL : '../') . htmlspecialchars($archivo['ruta_archivo']); ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div> </div> </div> </section> </div> <div id="texto-whatsapp-oculto" style="display:none;"><?php echo htmlspecialchars($mensaje_whatsapp_sin_codificar); ?></div>

<?php require_once 'includes/footer.php'; ?>

<script src="../assets/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script> <script>
document.addEventListener('DOMContentLoaded', function () {
    /**
     * SCRIPT PARA ACTUALIZAR EL LABEL DEL INPUT FILE
     */
    const inputFile = document.querySelector('.custom-file-input');
    if (inputFile) {
        inputFile.addEventListener('change', function(e){
            var fileName = e.target.files[0]?.name || "Seleccionar archivo..."; 
            var nextSibling = e.target.nextElementSibling; 
            nextSibling.innerText = fileName; 
        });
    }

    /**
     * SCRIPT PARA EL BOTÓN DE MOSTRAR RESUMEN Y ENVIAR POR WHATSAPP
     */
    const btnMostrarResumen = document.getElementById('btn-mostrar-resumen-whatsapp');
    if (btnMostrarResumen) {
        btnMostrarResumen.addEventListener('click', function() {
            const textoParaEnviar = document.getElementById('texto-whatsapp-oculto').textContent;
            const telefono = this.getAttribute('data-telefono'); 
            const mensajeUrl = encodeURIComponent(textoParaEnviar);
            const whatsappLink = `https://wa.me/${telefono}?text=${mensajeUrl}`;

            Swal.fire({
                title: '<strong>Resumen para WhatsApp</strong>',
                icon: 'info',
                html: `<pre style="white-space: pre-wrap; text-align: left;">${textoParaEnviar}</pre>`, 
                showCloseButton: true,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: '<i class="fab fa-whatsapp"></i> Enviar',
                confirmButtonAriaLabel: 'Enviar por WhatsApp',
                cancelButtonText: '<i class="fas fa-copy"></i> Copiar',
                cancelButtonAriaLabel: 'Copiar texto'
            }).then((result) => {
                if (result.isConfirmed) { 
                    window.open(whatsappLink, '_blank'); 
                } else if (result.dismiss === Swal.DismissReason.cancel) { 
                    navigator.clipboard.writeText(textoParaEnviar).then(() => {
                        toastr.success('¡Resumen copiado al portapapeles!'); 
                    }, (err) => {
                        toastr.error('No se pudo copiar el texto.'); 
                        console.error('Error al copiar: ', err); 
                    });
                }
            });
        });
    }
});
</script>