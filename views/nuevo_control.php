<?php
/**
 * ===============================================
 * NUEVO CONTROL / NOTA DE EVOLUCIÓN
 * ===============================================
 * Formulario simplificado para registrar una consulta de seguimiento.
 */

require_once 'includes/header.php';
require_once 'includes/sidebar.php'; 
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; 

/**
 * ===============================================
 * OBTENER DATOS DEL PACIENTE
 * ===============================================
 * Verificamos el documento de la URL y traemos los datos del paciente
 * para mostrar su nombre en el título.
 */
$nombre_paciente_titulo = 'Paciente no encontrado';
$documento_paciente = '';

if (isset($_GET['documento'])) {
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

// 1. Capturamos el ID de la cita que viene de la URL (si es que viene de una)
$id_cita_url = 0; 
if (isset($_GET['id_cita'])) {
    $id_cita_url = (int)$_GET['id_cita']; 
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" id="titulo-formulario">Nota de Control</h1>
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
                            <textarea class="form-control" rows="4" id="enfermedad_actual" name="enfermedad_actual" placeholder="Describa la evolución del paciente desde la última consulta..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="hallazgos_examen_fisico">Hallazgos del Examen Físico (Opcional)</label>
                            <textarea class="form-control" rows="3" id="hallazgos_examen_fisico" name="hallazgos_examen_fisico"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="diagnostico_principal">Diagnóstico (Actualizado)</label>
                            <textarea class="form-control" rows="3" id="diagnostico_principal" name="diagnostico_principal"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="solicitud_examenes">Solicitud de Exámenes</label>
                            <textarea class="form-control" rows="3" id="solicitud_examenes" name="solicitud_examenes"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tratamiento">Tratamiento a Seguir (Actualizado)</label>
                            <textarea class="form-control" rows="3" id="tratamiento" name="tratamiento"></textarea>
                        </div>
                    </div></div><div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Receta Médica</h3>
                    </div>
                    <div class="card-body" id="receta-medica-container">
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
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-success" id="btn-agregar-medicamento">Agregar Otro Medicamento</button>
                    </div>
                </div><div class="pb-5"> <button type="submit" class="btn btn-primary btn-lg btn-block">Guardar Nota de Control</button>
                </div>

            </form> </div></section></div><?php require_once 'includes/footer.php'; ?>

<script src="../assets/js/historia_clinica.js"></script>