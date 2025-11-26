<?php 
// --- INCLUDES INICIALES Y CONFIGURACIÓN ---
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; // Asegúrate que sea el sidebar correcto (¿quizás sidebar_medico.php?)
require_once '../core/db_connection.php'; // Necesario para consultar datos al editar
require_once '../core/funciones.php';     // Necesario para calcularEdad

// Verificamos si se pasó un número de documento por la URL (desde la búsqueda)
$documento_url = '';
if (isset($_GET['documento'])) {
    // Limpiamos el dato para seguridad
    $documento_url = htmlspecialchars(trim($_GET['documento']));
}

// 1. Capturamos el ID de la cita que viene de la URL
$id_cita_url = 0; // Valor por defecto
if (isset($_GET['id_cita'])) {
    $id_cita_url = (int)$_GET['id_cita']; // Lo convertimos a entero
}

// --- MODO EDICIÓN: LÓGICA PARA CARGAR DATOS ---
$datos_editar = null;
$meds_editar = [];
$es_edicion = false;

if (isset($_GET['id_historia_editar'])) {
    $id_edit = (int)$_GET['id_historia_editar'];
    $conn = conectarDB();
    
    // Consultamos los datos de la historia y del paciente unido
    $sql = "SELECT h.*, p.*, p.numero_documento as doc_paciente 
            FROM historias_clinicas h 
            JOIN pacientes p ON h.paciente_documento = p.numero_documento 
            WHERE h.id_historia = $id_edit";
    $res = $conn->query($sql);
    
    if ($res && $res->num_rows > 0) {
        $datos_editar = $res->fetch_assoc();
        $datos_editar['edad_calculada'] = calcularEdad($datos_editar['fecha_nacimiento']);
        $es_edicion = true;
        // Sobrescribimos documento_url para que el JS también pueda cargar info si es necesario
        $documento_url = $datos_editar['doc_paciente']; 
    }

    // Consultamos los medicamentos de esta historia
    $sql_meds = "SELECT mr.* FROM medicamentos_recetados mr 
                 JOIN recetas_medicas rm ON mr.id_receta = rm.id_receta 
                 WHERE rm.id_historia = $id_edit";
    $res_m = $conn->query($sql_meds);
    while($m = $res_m->fetch_assoc()) {
        $meds_editar[] = $m;
    }
    
    $conn->close();
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" id="titulo-formulario">
                        <?php echo $es_edicion ? 'Editar Historia Clínica' : 'Registrar Nueva Historia Clínica'; ?>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form action="../controllers/historia_controller.php" method="POST" id="form_historia_clinica">
                
                <input type="hidden" id="documento_a_cargar" value="<?php echo $documento_url; ?>">
                <input type="hidden" name="id_cita_a_completar" value="<?php echo $id_cita_url; ?>">
                <input type="hidden" name="form_type" value="completa">
                
                <?php if($es_edicion): ?>
                    <input type="hidden" name="id_historia_editar" value="<?php echo $datos_editar['id_historia']; ?>">
                <?php endif; ?>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información General del Paciente</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_documento">Tipo de Identificación</label>
                                    <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                                        <option value="CC">Cédula de Ciudadanía (CC)</option>
                                        <option value="TI">Tarjeta de Identidad (TI)</option>
                                        <option value="RC">Registro Civil (RC)</option>
                                        <option value="CE">Cédula de Extranjería (CE)</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_documento">Nº de Documento</label>
                                    <input type="text" class="form-control" id="numero_documento" name="numero_documento" required 
                                           value="<?php echo $es_edicion ? $datos_editar['doc_paciente'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nombre">Nombres</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required 
                                           value="<?php echo $es_edicion ? $datos_editar['nombre'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="apellido">Apellidos</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required 
                                           value="<?php echo $es_edicion ? $datos_editar['apellido'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required 
                                           value="<?php echo $es_edicion ? $datos_editar['fecha_nacimiento'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label for="edad">Edad</label>
                                    <input type="text" class="form-control" id="edad" name="edad" readonly 
                                           value="<?php echo $es_edicion ? $datos_editar['edad_calculada'] : ''; ?>">
                                </div>
                            </div>
                             <div class="col-md-2">
                                <div class="form-group">
                                    <label for="sexo">Sexo</label>
                                    <select class="form-control" id="sexo" name="sexo" required>
                                        <option value="FEMENINO">Femenino</option>
                                        <option value="MASCULINO">Masculino</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_civil">Estado Civil</label>
                                    <select class="form-control" id="estado_civil" name="estado_civil">
                                        <option value="SOLTERO">Soltero(a)</option>
                                        <option value="CASADO">Casado(a)</option>
                                        <option value="UNION LIBRE">Unión Libre</option>
                                        <option value="VIUDO(A)">Viudo(a)</option>
                                        <option value="SEPARADO">Separado(a)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="telefono_whatsapp">Teléfono (WhatsApp)</label>
                                    <input type="tel" class="form-control" id="telefono_whatsapp" name="telefono_whatsapp" 
                                           value="<?php echo $es_edicion ? $datos_editar['telefono_whatsapp'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direccion">Dirección de Residencia</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" 
                                           value="<?php echo $es_edicion ? $datos_editar['direccion'] : ''; ?>">
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profesion">Profesión u Ocupación</label>
                                    <input type="text" class="form-control" id="profesion" name="profesion" 
                                           value="<?php echo $es_edicion ? $datos_editar['profesion'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                         <div class="row" id="seccion-gestacion" style="display: none;">
                            <div class="col-md-6"> 
                                <div class="form-group">
                                    <label for="embarazada">¿Paciente en gestación?</label>
                                    <select class="form-control" id="embarazada" name="embarazada">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6"> 
                                <div class="form-group">
                                    <label for="semanas_gestacion">Semanas de Gestación</label>
                                    <input type="number" class="form-control" id="semanas_gestacion" name="semanas_gestacion" min="1" max="45" disabled 
                                           value="<?php echo $es_edicion ? $datos_editar['semanas_gestacion'] : ''; ?>">
                                </div>
                            </div>
                        </div> </div> </div> <div class="card card-info">
                    <div class="card-header"><h3 class="card-title">Anamnesis</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Motivo de Consulta</label>
                            <textarea class="form-control" rows="2" name="motivo_consulta"><?php echo $es_edicion ? htmlspecialchars($datos_editar['motivo_consulta']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Enfermedad Actual</label>
                            <textarea class="form-control" rows="3" name="enfermedad_actual"><?php echo $es_edicion ? htmlspecialchars($datos_editar['enfermedad_actual']) : ''; ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Antecedentes Personales</label>
                                    <textarea class="form-control" rows="3" name="antecedentes_personales"><?php echo $es_edicion ? htmlspecialchars($datos_editar['antecedentes_personales']) : ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Antecedentes Familiares</label>
                                    <textarea class="form-control" rows="3" name="antecedentes_familiares"><?php echo $es_edicion ? htmlspecialchars($datos_editar['antecedentes_familiares']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-purple">
                    <div class="card-header"><h3 class="card-title">Signos Vitales</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3"><label>Peso (kg)</label><input type="number" step="0.01" class="form-control" id="peso_kg" name="peso_kg" value="<?php echo $es_edicion ? $datos_editar['peso_kg'] : ''; ?>"></div>
                            <div class="col-md-3"><label>Talla (cm)</label><input type="number" step="0.1" class="form-control" id="talla_cm" name="talla_cm" value="<?php echo $es_edicion ? $datos_editar['talla_cm'] : ''; ?>"></div>
                            <div class="col-md-3"><label>IMC</label><input type="text" class="form-control" id="imc" name="imc" readonly value="<?php echo $es_edicion ? $datos_editar['imc'] : ''; ?>"></div>
                            <div class="col-md-3"><label>Temp (°C)</label><input type="number" step="0.1" class="form-control" name="temperatura_c" value="<?php echo $es_edicion ? $datos_editar['temperatura_c'] : ''; ?>"></div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3"><label>FC (lat/min)</label><input type="number" class="form-control" name="frecuencia_cardiaca" value="<?php echo $es_edicion ? $datos_editar['frecuencia_cardiaca'] : ''; ?>"></div>
                            <div class="col-md-3"><label>FR (resp/min)</label><input type="number" class="form-control" name="frecuencia_respiratoria" value="<?php echo $es_edicion ? $datos_editar['frecuencia_respiratoria'] : ''; ?>"></div>
                            <div class="col-md-3"><label>Sistólica</label><input type="number" class="form-control" name="tension_sistolica" value="<?php echo $es_edicion ? $datos_editar['tension_sistolica'] : ''; ?>"></div>
                            <div class="col-md-3"><label>Diastólica</label><input type="number" class="form-control" name="tension_diastolica" value="<?php echo $es_edicion ? $datos_editar['tension_diastolica'] : ''; ?>"></div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6"><label>Creatinina</label><input type="number" step="0.01" class="form-control" name="creatinina_serica" value="<?php echo $es_edicion ? $datos_editar['creatinina_serica'] : ''; ?>"></div>
                            <div class="col-md-6"><label>HbA1c (%)</label><input type="number" step="0.1" class="form-control" name="hemoglobina_glicosilada" value="<?php echo $es_edicion ? $datos_editar['hemoglobina_glicosilada'] : ''; ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="card card-warning">
                     <div class="card-header">
                        <h3 class="card-title">Examen Médico</h3>
                    </div>
                     <div class="card-body">
                        <div class="form-group">
                            <label for="examen_fisico">Examen físico</label>
                            <textarea class="form-control" rows="3" id="examen_fisico" name="examen_fisico"><?php echo $es_edicion ? htmlspecialchars($datos_editar['examen_fisico']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="hallazgos_examen_fisico">Hallazgos del examen físico</label>
                            <textarea class="form-control" rows="3" id="hallazgos_examen_fisico" name="hallazgos_examen_fisico"><?php echo $es_edicion ? htmlspecialchars($datos_editar['hallazgos_examen_fisico']) : ''; ?></textarea>
                        </div>
                    </div></div><div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Hallazgos y Plan a Seguir</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="diagnostico_principal">Diagnóstico Principal / Hallazgos de la Consulta</label>
                            <textarea class="form-control" rows="3" id="diagnostico_principal" name="diagnostico_principal"><?php echo $es_edicion ? htmlspecialchars($datos_editar['diagnostico_principal']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="solicitud_examenes">Solicitud de Exámenes</label>
                            <textarea class="form-control" rows="3" id="solicitud_examenes" name="solicitud_examenes"><?php echo $es_edicion ? htmlspecialchars($datos_editar['solicitud_examenes']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tratamiento">Tratamiento a Seguir</label>
                            <textarea class="form-control" rows="3" id="tratamiento" name="tratamiento"><?php echo $es_edicion ? htmlspecialchars($datos_editar['tratamiento']) : ''; ?></textarea>
                        </div>
                    </div></div><div class="card card-danger">
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
                            <div class="row medicamento-row mb-2">
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
                </div><div class="pb-5"> <button type="submit" class="btn btn-primary btn-lg btn-block">Guardar Consulta</button>
                </div>

            </form> </div></section></div><?php require_once 'includes/footer.php'; ?>

<script src="../assets/js/historia_clinica.js"></script>