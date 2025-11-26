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
function getBadgeClass($clasificacion_texto) {
    if (empty($clasificacion_texto) || $clasificacion_texto == 'N/A') {
        return 'badge-light text-dark'; 
    }
    $texto = strtolower($clasificacion_texto);
    if (strpos($texto, 'obesidad') !== false || strpos($texto, 'crisis') !== false || strpos($texto, 'grado 2') !== false || strpos($texto, 'grado 3') !== false || strpos($texto, 'severamente') !== false || strpos($texto, 'falla renal') !== false || strpos($texto, 'estadio g4') !== false || strpos($texto, 'estadio g5') !== false) {
        return 'badge-danger';
    }
    if (strpos($texto, 'sobrepeso') !== false || strpos($texto, 'elevada') !== false || strpos($texto, 'grado 1') !== false || strpos($texto, 'leve a moderadamente') !== false || strpos($texto, 'estadio g3') !== false) {
        return 'badge-warning';
    }
    if (strpos($texto, 'normal') !== false || strpos($texto, 'levemente disminuido') !== false || strpos($texto, 'estadio g1') !== false || strpos($texto, 'estadio g2') !== false) {
        return 'badge-success';
    }
    return 'badge-secondary'; 
}

function getClasificacionTFGTexto($tfg_valor) {
    if ($tfg_valor === null || $tfg_valor == 0) { return 'N/A'; }
    if ($tfg_valor >= 90) return 'Estadio G1 (>= 90)';
    if ($tfg_valor >= 60) return 'Estadio G2 (60-89)';
    if ($tfg_valor >= 45) return 'Estadio G3a (45-59)';
    if ($tfg_valor >= 30) return 'Estadio G3b (30-44)';
    if ($tfg_valor >= 15) return 'Estadio G4 (15-29)';
    return 'Estadio G5 (< 15)';
}

/**
 * ===============================================
 * VALIDACIÓN Y OBTENCIÓN DE DATOS
 * ===============================================
 */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger mt-3">ID de consulta no válido.</div></div></section></div>';
    require_once 'includes/footer.php'; exit();
}
$id_historia = (int)$_GET['id']; 

$conexion = conectarDB();
$datos = obtenerDetallesConsulta($conexion, $id_historia); 
$conexion->close();

if (!$datos) {
    echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-warning mt-3">No se encontró la consulta solicitada.</div></div></section></div>';
    require_once 'includes/footer.php'; exit();
}

$consulta = $datos['consulta'];
$medicamentos = $datos['medicamentos'];
$archivos = $datos['archivos'];
$es_control = (isset($consulta['motivo_consulta']) && $consulta['motivo_consulta'] === 'NOTA DE CONTROL / EVOLUCION');

// Lógica WhatsApp
$mensaje_whatsapp_sin_codificar = generarMensajeWhatsApp($consulta, $medicamentos, false);
$telefono_limpio_js = '';
if (!empty($consulta['telefono_whatsapp'])) {
    $telefono_limpio_js = preg_replace('/[^0-9]/', '', $consulta['telefono_whatsapp']);
    if (strlen($telefono_limpio_js) == 10) { $telefono_limpio_js = '57' . $telefono_limpio_js; } 
}

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
                <div class="col-md-4 col-sm-12 mb-2">
                    <h1 class="m-0 text-dark" style="font-size: 1.8rem;">
                        <?php echo $es_control ? 'Control' : 'Consulta'; ?> <small class="text-muted">#<?php echo htmlspecialchars($consulta['codigo_historia']); ?></small>
                    </h1>
                </div>
                
                <div class="col-md-8 col-sm-12">
                    <div class="d-flex justify-content-end flex-wrap">
                        
                        <a href="buscar_paciente.php?documento=<?php echo htmlspecialchars($consulta['numero_documento']); ?>" class="btn btn-secondary mr-2 mb-2 shadow-sm" title="Volver al historial del paciente">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>

                        <div class="btn-group mr-2 mb-2 shadow-sm">
                            <?php 
                            // --- CAMBIO: REDIRECCIÓN DINÁMICA SEGÚN TIPO DE CONSULTA ---
                            $link_editar = $es_control 
                                ? 'nuevo_control.php?id_historia_editar=' . $id_historia 
                                : 'nueva_historia.php?id_historia_editar=' . $id_historia;
                            ?>
                            <a href="<?php echo $link_editar; ?>" class="btn btn-outline-info" title="Editar Historia Clínica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" id="btn-eliminar-historia" data-id="<?php echo $id_historia; ?>" title="Eliminar Historia">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <a href="nuevo_control.php?documento=<?php echo htmlspecialchars($consulta['numero_documento']); ?>" class="btn btn-warning mr-2 mb-2 shadow-sm" title="Registrar Nueva Consulta de Control">
                            <i class="fas fa-plus-circle"></i> Nuevo Control
                        </a>

                        <div class="btn-group mb-2 shadow-sm">
                            <a href="../reports/generar_historia_pdf.php?id=<?php echo $id_historia; ?>" target="_blank" class="btn btn-primary" title="Imprimir Consulta">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            
                            <?php if (!empty($medicamentos)): ?>
                                <a href="../reports/generar_receta_pdf.php?id=<?php echo $id_historia; ?>" target="_blank" class="btn btn-success" title="Imprimir Receta">
                                    <i class="fas fa-prescription-bottle-alt"></i> Receta
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($consulta['telefono_whatsapp'])): ?>
                                <button type="button" class="btn btn-success" style="background-color: #25D366; border-color: #25D366;" id="btn-mostrar-resumen-whatsapp" 
                                        data-telefono="<?php echo htmlspecialchars($telefono_limpio_js); ?>" 
                                        title="Enviar por WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled title="No hay teléfono registrado"><i class="fab fa-whatsapp"></i></button>
                            <?php endif; ?>
                        </div>

                    </div> 
                </div> 
            </div> 
        </div> 
    </div> 

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user-circle mr-2"></i>Datos del Paciente</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($nombre_paciente_titulo); ?></p>
                            <p><strong>Documento:</strong> <?php echo htmlspecialchars($consulta['tipo_documento'] . ' - ' . $consulta['numero_documento']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($consulta['telefono_whatsapp'] ?? 'No registrado'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Edad:</strong> <?php echo calcularEdad($consulta['fecha_nacimiento']); ?> años</p>
                            <p><strong>Sexo:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($consulta['sexo'] ?? 'N/A'))); ?></p>
                            <p><strong>Estado Civil:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($consulta['estado_civil'] ?? 'N/A'))); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-notes-medical mr-2"></i>Detalles de la Consulta</h3></div>
                <div class="card-body">
                    <div class="callout callout-info">
                        <p><strong>Fecha:</strong> <?php echo date("d/m/Y h:i A", strtotime($consulta['fecha_consulta'])); ?> &nbsp;|&nbsp; <strong>Atendido por:</strong> Dr(a). <?php echo htmlspecialchars($nombre_medico_titulo); ?></p>
                    </div>
                    
                    <?php if ($es_control): ?>
                        <p><strong>Evolución:</strong><br><?php echo nl2br(htmlspecialchars($consulta['enfermedad_actual'] ?? '')); ?></p>
                        <p><strong>Hallazgos Examen:</strong><br><?php echo nl2br(htmlspecialchars($consulta['hallazgos_examen_fisico'] ?? '')); ?></p>
                        <hr>
                        <p><strong>Diagnóstico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['diagnostico_principal'] ?? '')); ?></p>
                        <p><strong>Tratamiento:</strong><br><?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?? '')); ?></p>
                        <p><strong>Exámenes Solicitados:</strong><br><?php echo nl2br(htmlspecialchars($consulta['solicitud_examenes'] ?? '')); ?></p>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-12">
                                <p><strong>Motivo de Consulta:</strong><br><?php echo nl2br(htmlspecialchars($consulta['motivo_consulta'] ?? '')); ?></p>
                                <p><strong>Enfermedad Actual:</strong><br><?php echo nl2br(htmlspecialchars($consulta['enfermedad_actual'] ?? '')); ?></p>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6"><strong>Ant. Personales:</strong><br><?php echo nl2br(htmlspecialchars($consulta['antecedentes_personales'] ?? '')); ?></div>
                            <div class="col-md-6"><strong>Ant. Familiares:</strong><br><?php echo nl2br(htmlspecialchars($consulta['antecedentes_familiares'] ?? '')); ?></div>
                        </div>
                        <hr>
                        <p><strong>Examen Físico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['examen_fisico'] ?? '')); ?></p>
                        <p><strong>Hallazgos:</strong><br><?php echo nl2br(htmlspecialchars($consulta['hallazgos_examen_fisico'] ?? '')); ?></p>
                        <hr>
                        <p><strong>Diagnóstico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['diagnostico_principal'] ?? '')); ?></p>
                        <p><strong>Tratamiento:</strong><br><?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?? '')); ?></p>
                        <p><strong>Exámenes Solicitados:</strong><br><?php echo nl2br(htmlspecialchars($consulta['solicitud_examenes'] ?? '')); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$es_control): ?>
            <div class="card card-purple card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-heartbeat mr-2"></i>Signos Vitales y Antropometría</h3></div>
                <div class="card-body" style="font-size: 1.1em;">
                    <?php
                        $imc_clase = getBadgeClass($consulta['imc_clasificacion'] ?? 'N/A');
                        $hta_clase = getBadgeClass($consulta['clasificacion_hta'] ?? 'N/A');
                        $tfg_texto = getClasificacionTFGTexto($consulta['filtrado_glomerular_ckd_epi'] ?? null);
                        $tfg_clase = getBadgeClass($tfg_texto);
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Peso:</strong> <?php echo htmlspecialchars($consulta['peso_kg'] ?? 'N/A'); ?> kg</div>
                        <div class="col-md-4"><strong>Talla:</strong> <?php echo htmlspecialchars($consulta['talla_cm'] ?? 'N/A'); ?> cm</div>
                        <div class="col-md-4"><strong>IMC:</strong> <?php echo htmlspecialchars($consulta['imc'] ?? 'N/A'); ?> <span class="badge <?php echo $imc_clase; ?> ml-2"><?php echo htmlspecialchars($consulta['imc_clasificacion'] ?? 'N/A'); ?></span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>TA Sistólica:</strong> <?php echo htmlspecialchars($consulta['tension_sistolica'] ?? 'N/A'); ?> mmHg</div>
                        <div class="col-md-4"><strong>TA Diastólica:</strong> <?php echo htmlspecialchars($consulta['tension_diastolica'] ?? 'N/A'); ?> mmHg</div>
                        <div class="col-md-4"><strong>HTA:</strong> <span class="badge <?php echo $hta_clase; ?> ml-2"><?php echo htmlspecialchars($consulta['clasificacion_hta'] ?? 'N/A'); ?></span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>FC:</strong> <?php echo htmlspecialchars($consulta['frecuencia_cardiaca'] ?? 'N/A'); ?> lat/min</div>
                        <div class="col-md-4"><strong>FR:</strong> <?php echo htmlspecialchars($consulta['frecuencia_respiratoria'] ?? 'N/A'); ?> resp/min</div>
                        <div class="col-md-4"><strong>Temp:</strong> <?php echo htmlspecialchars($consulta['temperatura_c'] ?? 'N/A'); ?> °C</div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><strong>HbA1c:</strong> <?php echo htmlspecialchars($consulta['hemoglobina_glicosilada'] ?? 'N/A'); ?> %</div>
                        <div class="col-md-4"><strong>Creatinina:</strong> <?php echo htmlspecialchars($consulta['creatinina_serica'] ?? 'N/A'); ?> mg/dL</div>
                        <div class="col-md-4"><strong>TFG:</strong> <?php echo htmlspecialchars($consulta['filtrado_glomerular_ckd_epi'] ?? 'N/A'); ?> <span class="badge <?php echo $tfg_clase; ?> ml-2"><?php echo htmlspecialchars($tfg_texto); ?></span></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($medicamentos)): ?>
            <div class="card card-danger card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-pills mr-2"></i>Receta Médica</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-hover"> 
                        <thead class="thead-light"><tr><th>Medicamento</th><th>Dosis / Horario</th><th>Cantidad</th></tr></thead>
                        <tbody>
                            <?php foreach ($medicamentos as $med): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($med['nombre_medicamento']); ?></td>
                                <td><?php echo htmlspecialchars($med['horario_dosis']); ?></td>
                                <td><?php echo htmlspecialchars($med['cantidad']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="card card-secondary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-paperclip mr-2"></i>Archivos Adjuntos</h3></div>
                <div class="card-body">
                    <form action="../controllers/archivo_controller.php" method="post" enctype="multipart/form-data" class="mb-4 border-bottom pb-4">
                        <input type="hidden" name="id_historia" value="<?php echo $id_historia; ?>">
                        <input type="hidden" name="documento_paciente" value="<?php echo htmlspecialchars($consulta['numero_documento']); ?>">
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="archivo" name="archivo" required>
                                <label class="custom-file-label" for="archivo">Seleccionar archivo...</label>
                            </div>
                            <div class="input-group-append"><button class="btn btn-primary" type="submit">Subir</button></div>
                        </div>
                        <small class="form-text text-muted">Tipos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB.</small>
                    </form>
                    <ul class="list-group list-group-flush"> 
                        <?php if (empty($archivos)): ?><li class="list-group-item">No hay archivos.</li><?php endif; ?>
                        <?php foreach ($archivos as $archivo): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-alt mr-2"></i> <?php echo htmlspecialchars($archivo['nombre_archivo']); ?> <small class="text-muted">(<?php echo date("d/m/Y", strtotime($archivo['fecha_carga'])); ?>)</small></span>
                                <a href="<?php echo (defined('BASE_URL') ? BASE_URL : '../') . htmlspecialchars($archivo['ruta_archivo']); ?>" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Ver</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div> 
    </section> 
</div> 
<div id="texto-whatsapp-oculto" style="display:none;"><?php echo htmlspecialchars($mensaje_whatsapp_sin_codificar); ?></div>

<?php require_once 'includes/footer.php'; ?>
<script src="../assets/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script> 
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Input File Label
    const inputFile = document.querySelector('.custom-file-input');
    if (inputFile) {
        inputFile.addEventListener('change', function(e){
            var fileName = e.target.files[0]?.name || "Seleccionar archivo..."; 
            e.target.nextElementSibling.innerText = fileName; 
        });
    }

    // Botón WhatsApp
    const btnWa = document.getElementById('btn-mostrar-resumen-whatsapp');
    if (btnWa) {
        btnWa.addEventListener('click', function() {
            const texto = document.getElementById('texto-whatsapp-oculto').textContent;
            const tel = this.getAttribute('data-telefono'); 
            Swal.fire({
                title: 'Resumen WhatsApp', html: `<pre style="text-align:left; white-space: pre-wrap;">${texto}</pre>`, showCancelButton: true, confirmButtonText: 'Enviar', cancelButtonText: 'Copiar'
            }).then((res) => {
                if (res.isConfirmed) window.open(`https://wa.me/${tel}?text=${encodeURIComponent(texto)}`, '_blank');
                else if (res.dismiss === Swal.DismissReason.cancel) navigator.clipboard.writeText(texto).then(()=>toastr.success('Copiado'));
            });
        });
    }

    // --- LÓGICA PARA ELIMINAR HISTORIA ---
    $('#btn-eliminar-historia').on('click', function() {
        const idHistoria = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro de eliminar esta historia?',
            text: "Se borrarán todos los datos asociados (consulta, recetas y archivos). ¡Esta acción no se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../controllers/historia_controller.php', { 
                    accion: 'eliminar_historia', 
                    id_historia: idHistoria 
                }, function(response) {
                    if(response.success) {
                        Swal.fire('Eliminado', 'La historia clínica ha sido eliminada correctamente.', 'success')
                        .then(() => { 
                            window.location.href = 'buscar_paciente.php'; 
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Hubo un error al eliminar la historia.', 'error');
                    }
                }, 'json')
                .fail(function() {
                    Swal.fire('Error', 'Fallo de conexión con el servidor.', 'error');
                });
            }
        });
    });
});
</script>