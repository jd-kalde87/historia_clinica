<?php
/**
 * ===============================================
 * NUEVO CONTROL / NOTA DE EVOLUCIÓN (Y EDICIÓN)
 * ===============================================
 * Formulario simplificado para registrar o editar una consulta de seguimiento.
 */

require_once 'includes/header.php';
require_once 'includes/sidebar.php'; 
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; 

// --- 1. LÓGICA DE EDICIÓN ---
$datos_editar = null;
$meds_editar = [];
$es_edicion = false;
$documento_paciente = '';
$nombre_paciente_titulo = 'Paciente no encontrado';

// Si viene un ID para editar, cargamos los datos
if (isset($_GET['id_historia_editar'])) {
    $id_edit = (int)$_GET['id_historia_editar'];
    $conexion = conectarDB();
    
    // Traer datos de la historia
    $sql = "SELECT h.*, p.nombre, p.apellido, p.numero_documento 
            FROM historias_clinicas h 
            JOIN pacientes p ON h.paciente_documento = p.numero_documento 
            WHERE h.id_historia = $id_edit";
    $res = $conexion->query($sql);
    
    if ($res && $res->num_rows > 0) {
        $datos_editar = $res->fetch_assoc();
        $es_edicion = true;
        $documento_paciente = $datos_editar['numero_documento'];
        $nombre_paciente_titulo = mb_convert_case(($datos_editar['nombre'] ?? '') . ' ' . ($datos_editar['apellido'] ?? ''), MB_CASE_TITLE, 'UTF-8');
    }

    // Traer medicamentos
    $sql_meds = "SELECT mr.* FROM medicamentos_recetados mr 
                 JOIN recetas_medicas rm ON mr.id_receta = rm.id_receta 
                 WHERE rm.id_historia = $id_edit";
    $res_m = $conexion->query($sql_meds);
    while($m = $res_m->fetch_assoc()) {
        $meds_editar[] = $m;
    }
    
    $conexion->close();

// Si NO es edición, buscamos el paciente por URL (modo creación)
} elseif (isset($_GET['documento'])) {
    $documento_paciente = htmlspecialchars(trim($_GET['documento']));
    $conexion = conectarDB();
    
    $stmt = $conexion->prepare("SELECT nombre, apellido FROM pacientes WHERE numero_documento = ?");
    $stmt->bind_param("s", $documento_paciente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $paciente = $resultado->fetch_assoc();
        $nombre_paciente_titulo = mb_convert_case(($paciente['nombre'] ?? '') . ' ' . ($paciente['apellido'] ?? ''), MB_CASE_TITLE, 'UTF-8');
    }
    $stmt->close();
    $conexion->close();
}

// Capturamos el ID de la cita si existe (solo en modo creación)
$id_cita_url = isset($_GET['id_cita']) ? (int)$_GET['id_cita'] : 0; 
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" id="titulo-formulario"><?php echo $es_edicion ? 'Editar Nota de Control' : 'Nota de Control'; ?></h1>
                    <h5>Paciente: <?php echo htmlspecialchars($nombre_paciente_titulo); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form action="../controllers/historia_controller.php" method="POST" id="form_control_medico">
                
                <input type="hidden" name="numero_documento" value="<?php echo htmlspecialchars($documento_paciente); ?>">
                <input type="hidden" name="id_cita_a_completar" value="<?php echo $id_cita_url; ?>">
                <input type="hidden" name="form_type" value="control">
                
                <?php if ($es_edicion): ?>
                    <input type="hidden" name="id_historia_editar" value="<?php echo $datos_editar['id_historia']; ?>">
                <?php endif; ?>

                <input type="hidden" name="tipo_documento" value="">
                <input type="hidden" name="nombre" value="">
                <input type="hidden" name="apellido" value="">
                <input type="hidden" name="fecha_nacimiento" value="">
                <input type="hidden" name="sexo" value="">
                <input type="hidden" name="estado_civil" value="">
                <input type="hidden" name="motivo_consulta" value="NOTA DE CONTROL / EVOLUCION">
                <input type="hidden" name="antecedentes_personales" value="">
                <input type="hidden" name="antecedentes_familiares" value="">
                <input type="hidden" name="examen_fisico" value="">

                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Evolución y Plan a Seguir</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="enfermedad_actual">Evolución / Nota de Control</label>
                            <textarea class="form-control" rows="4" id="enfermedad_actual" name="enfermedad_actual" placeholder="Describa la evolución del paciente desde la última consulta..."><?php echo $es_edicion ? htmlspecialchars($datos_editar['enfermedad_actual']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="hallazgos_examen_fisico">Hallazgos del Examen Físico (Opcional)</label>
                            <textarea class="form-control" rows="3" id="hallazgos_examen_fisico" name="hallazgos_examen_fisico"><?php echo $es_edicion ? htmlspecialchars($datos_editar['hallazgos_examen_fisico']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="diagnostico_principal">Diagnóstico (Actualizado)</label>
                            <textarea class="form-control" rows="3" id="diagnostico_principal" name="diagnostico_principal"><?php echo $es_edicion ? htmlspecialchars($datos_editar['diagnostico_principal']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="solicitud_examenes">Solicitud de Exámenes</label>
                            <textarea class="form-control" rows="3" id="solicitud_examenes" name="solicitud_examenes"><?php echo $es_edicion ? htmlspecialchars($datos_editar['solicitud_examenes']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tratamiento">Tratamiento a Seguir (Actualizado)</label>
                            <textarea class="form-control" rows="3" id="tratamiento" name="tratamiento"><?php echo $es_edicion ? htmlspecialchars($datos_editar['tratamiento']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Receta Médica</h3>
                    </div>
                    <div class="card-body" id="receta-medica-container">
                        <?php if($es_edicion && !empty($meds_editar)): ?>
                            <?php foreach($meds_editar as $med): ?>
                                <div class="row medicamento-row mb-2">
                                     <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Nombre del Medicamento</label>
                                            <input type="text" class="form-control" name="medicamento_nombre[]" value="<?php echo htmlspecialchars($med['nombre_medicamento']); ?>">
                                        </div>
                                    </div>
                                     <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Horario / Dosis</label>
                                            <input type="text" class="form-control" name="medicamento_dosis[]" value="<?php echo htmlspecialchars($med['horario_dosis']); ?>">
                                        </div>
                                    </div>
                                     <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Cantidad</label>
                                            <input type="text" class="form-control" name="medicamento_cantidad[]" value="<?php echo htmlspecialchars($med['cantidad']); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="row medicamento-row">
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nombre del Medicamento</label>
                                        <input type="text" class="form-control" name="medicamento_nombre[]">
                                    </div>
                                </div>
                                 <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Horario / Dosis</label>
                                        <input type="text" class="form-control" name="medicamento_dosis[]">
                                    </div>
                                </div>
                                 <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Cantidad</label>
                                        <input type="text" class="form-control" name="medicamento_cantidad[]">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-success" id="btn-agregar-medicamento">Agregar Otro Medicamento</button>
                    </div>
                </div>
                
                <div class="pb-5"> 
                    <button type="submit" class="btn btn-primary btn-lg btn-block">Guardar Nota de Control</button>
                </div>

            </form> 
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="../assets/js/historia_clinica.js"></script>