<?php
session_start();
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; 

// -----------------------------------------------------------------------
// 1. BLOQUE AJAX: ELIMINAR HISTORIA CLÍNICA
// -----------------------------------------------------------------------
if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_historia') {
    header('Content-Type: application/json');
    
    // Verificación de seguridad básica
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Medico') {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit();
    }

    $conexion = conectarDB();
    $id_borrar = (int)$_POST['id_historia'];
    
    try {
        $conexion->begin_transaction();

        // 1. Borrar medicamentos asociados a recetas de esta historia
        $sql_meds = "DELETE mr FROM medicamentos_recetados mr 
                     JOIN recetas_medicas rm ON mr.id_receta = rm.id_receta 
                     WHERE rm.id_historia = ?";
        $stmt = $conexion->prepare($sql_meds);
        $stmt->bind_param("i", $id_borrar);
        $stmt->execute();
        $stmt->close();

        // 2. Borrar recetas médicas de esta historia
        $sql_recetas = "DELETE FROM recetas_medicas WHERE id_historia = ?";
        $stmt = $conexion->prepare($sql_recetas);
        $stmt->bind_param("i", $id_borrar);
        $stmt->execute();
        $stmt->close();

        // 3. Borrar archivos adjuntos (Solo registro en BD)
        $sql_archivos = "DELETE FROM archivos_adjuntos WHERE id_historia = ?";
        $stmt = $conexion->prepare($sql_archivos);
        $stmt->bind_param("i", $id_borrar);
        $stmt->execute();
        $stmt->close();

        // 4. Finalmente borrar la historia
        $sql_historia = "DELETE FROM historias_clinicas WHERE id_historia = ?";
        $stmt = $conexion->prepare($sql_historia);
        $stmt->bind_param("i", $id_borrar);
        $stmt->execute();
        $stmt->close();

        $conexion->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    $conexion->close();
    exit();
}

// -----------------------------------------------------------------------
// 2. BLOQUE POST: GUARDAR O EDITAR HISTORIA
// -----------------------------------------------------------------------

// Verificación de acceso
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Medico') {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/buscar_paciente.php' : '../views/buscar_paciente.php';
    header('Location: ' . $redirect_url . '?status=error&msg=' . urlencode('Acceso no autorizado.'));
    exit();
}

$conexion = conectarDB();
$conexion->autocommit(FALSE); 

$numero_documento = $conexion->real_escape_string(trim($_POST['numero_documento']));
$id_cita_a_completar = (int)($_POST['id_cita_a_completar'] ?? 0);
$form_type = $_POST['form_type'] ?? 'completa';
$id_historia_editar = isset($_POST['id_historia_editar']) ? (int)$_POST['id_historia_editar'] : 0;

try {

    // --- A. ACTUALIZAR DATOS DEL PACIENTE (Solo si es historia completa) ---
    if ($form_type === 'completa') {
        $embarazada = isset($_POST['embarazada']) ? (int)$_POST['embarazada'] : 0;
        $semanas_gestacion = ($embarazada === 1 && !empty($_POST['semanas_gestacion'])) ? (int)$_POST['semanas_gestacion'] : NULL;
        
        $nombre_paciente = mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, 'UTF-8');
        $apellido_paciente = mb_convert_case(trim($_POST['apellido']), MB_CASE_TITLE, 'UTF-8');

        // Verificar existencia del paciente
        $check = $conexion->query("SELECT numero_documento FROM pacientes WHERE numero_documento = '$numero_documento'");
        $paciente_existe = $check->num_rows > 0;

        if ($paciente_existe) {
            $sql_paciente = "UPDATE pacientes SET tipo_documento=?, nombre=?, apellido=?, fecha_nacimiento=?, sexo=?, estado_civil=?, direccion=?, profesion=?, telefono_whatsapp=?, embarazada=?, semanas_gestacion=? WHERE numero_documento=?";
            $stmt_p = $conexion->prepare($sql_paciente);
            $stmt_p->bind_param("sssssssssiis", $_POST['tipo_documento'], $nombre_paciente, $apellido_paciente, $_POST['fecha_nacimiento'], $_POST['sexo'], $_POST['estado_civil'], $_POST['direccion'], $_POST['profesion'], $_POST['telefono_whatsapp'], $embarazada, $semanas_gestacion, $numero_documento);
        } else {
            $sql_paciente = "INSERT INTO pacientes (numero_documento, tipo_documento, nombre, apellido, fecha_nacimiento, sexo, estado_civil, direccion, profesion, telefono_whatsapp, embarazada, semanas_gestacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_p = $conexion->prepare($sql_paciente);
            $stmt_p->bind_param("ssssssssssii", $numero_documento, $_POST['tipo_documento'], $nombre_paciente, $apellido_paciente, $_POST['fecha_nacimiento'], $_POST['sexo'], $_POST['estado_civil'], $_POST['direccion'], $_POST['profesion'], $_POST['telefono_whatsapp'], $embarazada, $semanas_gestacion);
        }
        if (!$stmt_p->execute()) throw new Exception("Error paciente: " . $stmt_p->error);
        $stmt_p->close();
    }

    // --- B. CÁLCULOS MÉDICOS (IMC, TFG, ETC) ---
    $peso = !empty($_POST['peso_kg']) ? (float)$_POST['peso_kg'] : NULL;
    $talla = !empty($_POST['talla_cm']) ? (float)$_POST['talla_cm'] : NULL;
    $imc_calculado_js = !empty($_POST['imc']) ? (float)$_POST['imc'] : NULL;
    $tension_sistolica = !empty($_POST['tension_sistolica']) ? (int)$_POST['tension_sistolica'] : NULL;
    $tension_diastolica = !empty($_POST['tension_diastolica']) ? (int)$_POST['tension_diastolica'] : NULL;
    $frecuencia_cardiaca = !empty($_POST['frecuencia_cardiaca']) ? (int)$_POST['frecuencia_cardiaca'] : NULL;
    $frecuencia_respiratoria = !empty($_POST['frecuencia_respiratoria']) ? (int)$_POST['frecuencia_respiratoria'] : NULL;
    $temperatura = !empty($_POST['temperatura_c']) ? (float)$_POST['temperatura_c'] : NULL;
    $creatinina_serica = !empty($_POST['creatinina_serica']) ? (float)$_POST['creatinina_serica'] : NULL;
    $hemoglobina_glicosilada = !empty($_POST['hemoglobina_glicosilada']) ? (float)$_POST['hemoglobina_glicosilada'] : NULL;

    // Lógica IMC
    $imc = ($peso && $talla && $talla > 0) ? round($peso / (($talla/100) * ($talla/100)), 2) : $imc_calculado_js;
    
    $imc_clasificacion = NULL;
    if ($imc !== NULL) {
        if ($imc < 18.5) $imc_clasificacion = "Bajo Peso";
        elseif ($imc <= 24.9) $imc_clasificacion = "Peso Normal";
        elseif ($imc <= 29.9) $imc_clasificacion = "Sobrepeso";
        elseif ($imc <= 34.9) $imc_clasificacion = "Obesidad Grado 1";
        elseif ($imc <= 39.9) $imc_clasificacion = "Obesidad Grado 2";
        else $imc_clasificacion = "Obesidad Grado 3 (Mórbida)";
    }

    // Lógica HTA
    $clasificacion_hta = NULL;
    if ($tension_sistolica !== NULL && $tension_diastolica !== NULL) {
        if ($tension_sistolica > 180 || $tension_diastolica > 120) $clasificacion_hta = "Crisis Hipertensiva";
        elseif ($tension_sistolica >= 140 || $tension_diastolica >= 90) $clasificacion_hta = "Hipertensión Grado 2";
        elseif (($tension_sistolica >= 130 && $tension_sistolica <= 139) || ($tension_diastolica >= 80 && $tension_diastolica <= 89)) $clasificacion_hta = "Hipertensión Grado 1";
        elseif (($tension_sistolica >= 120 && $tension_sistolica <= 129) && $tension_diastolica < 80) $clasificacion_hta = "Presión Arterial Elevada";
        elseif ($tension_sistolica < 120 && $tension_diastolica < 80) $clasificacion_hta = "Presión Arterial Normal";
    }

    // Lógica TFG
    $filtrado_glomerular_ckd_epi = NULL;
    if ($form_type === 'completa' && !empty($_POST['fecha_nacimiento']) && !empty($_POST['sexo'])) {
        $edad_paciente = calcularEdad($_POST['fecha_nacimiento']); 
        if ($creatinina_serica !== NULL && $edad_paciente >= 18) {
            $es_mujer = ($_POST['sexo'] == 'FEMENINO');
            $k = $es_mujer ? 0.7 : 0.9;
            $alpha = $es_mujer ? -0.329 : -0.411;
            $S = $es_mujer ? 1.018 : 1.0;
            $ratio = $creatinina_serica / $k;
            $egfr_raw = 141 * pow(min($ratio, 1), $alpha) * pow(max($ratio, 1), -1.209) * pow(0.993, $edad_paciente) * $S;
            $filtrado_glomerular_ckd_epi = round($egfr_raw, 2);
        }
    }

    // --- C. INSERTAR O ACTUALIZAR HISTORIA ---
    $id_medico_sesion = $_SESSION['id_usuario'];
    
    if ($id_historia_editar > 0) {
        // --- MODO EDICIÓN (UPDATE) ---
        $sql_historia = "UPDATE historias_clinicas SET 
            motivo_consulta=?, enfermedad_actual=?, antecedentes_personales=?, antecedentes_familiares=?, 
            examen_fisico=?, hallazgos_examen_fisico=?, diagnostico_principal=?, 
            solicitud_examenes=?, tratamiento=?, 
            peso_kg=?, talla_cm=?, imc=?, 
            frecuencia_cardiaca=?, frecuencia_respiratoria=?, temperatura_c=?,
            tension_sistolica=?, tension_diastolica=?, imc_clasificacion=?,
            clasificacion_hta=?, creatinina_serica=?, filtrado_glomerular_ckd_epi=?, 
            hemoglobina_glicosilada=?
            WHERE id_historia=?";
            
        $stmt_h = $conexion->prepare($sql_historia);
        
        // CORRECCIÓN APLICADA AQUÍ: Se añadió una 's' más al principio de la cadena de tipos.
        // Antes: "ssssssssdddiidi..." (8 's')
        // Ahora: "sssssssssdddiidi..." (9 's') para cubrir los 9 campos de texto.
        $stmt_h->bind_param(
            "sssssssssdddiidiissdddi", 
            $_POST['motivo_consulta'], 
            $_POST['enfermedad_actual'], 
            $_POST['antecedentes_personales'],
            $_POST['antecedentes_familiares'], 
            $_POST['examen_fisico'], 
            $_POST['hallazgos_examen_fisico'],
            $_POST['diagnostico_principal'], 
            $_POST['solicitud_examenes'], 
            $_POST['tratamiento'],
            $peso, $talla, $imc, 
            $frecuencia_cardiaca, $frecuencia_respiratoria, $temperatura,
            $tension_sistolica, $tension_diastolica, $imc_clasificacion,
            $clasificacion_hta, $creatinina_serica, $filtrado_glomerular_ckd_epi,
            $hemoglobina_glicosilada, $id_historia_editar
        );
        
        $id_historia_final = $id_historia_editar;
        
        // Limpiar recetas previas para re-insertarlas limpias
        $conexion->query("DELETE mr FROM medicamentos_recetados mr JOIN recetas_medicas rm ON mr.id_receta = rm.id_receta WHERE rm.id_historia = $id_historia_editar");
        $conexion->query("DELETE FROM recetas_medicas WHERE id_historia = $id_historia_editar");
        $msg_final = "Historia actualizada correctamente.";

    } else {
        // --- MODO CREACIÓN (INSERT) ---
        // Generar código
        $last = $conexion->query("SELECT MAX(id_historia) as last FROM historias_clinicas")->fetch_assoc()['last'] ?? 0;
        $codigo_historia = 'HC-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);

        $sql_historia = "INSERT INTO historias_clinicas (
            codigo_historia, paciente_documento, id_medico, fecha_consulta, 
            motivo_consulta, enfermedad_actual, antecedentes_personales, antecedentes_familiares, 
            examen_fisico, hallazgos_examen_fisico, diagnostico_principal, 
            solicitud_examenes, tratamiento, 
            peso_kg, talla_cm, imc, 
            frecuencia_cardiaca, frecuencia_respiratoria, temperatura_c,
            tension_sistolica, tension_diastolica, imc_clasificacion,
            clasificacion_hta, creatinina_serica, filtrado_glomerular_ckd_epi, 
            hemoglobina_glicosilada
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_h = $conexion->prepare($sql_historia);
        $stmt_h->bind_param(
            "ssisssssssssdddiidiissddd",
            $codigo_historia, $numero_documento, $id_medico_sesion,
            $_POST['motivo_consulta'], $_POST['enfermedad_actual'], $_POST['antecedentes_personales'],
            $_POST['antecedentes_familiares'], $_POST['examen_fisico'], $_POST['hallazgos_examen_fisico'],
            $_POST['diagnostico_principal'], $_POST['solicitud_examenes'], $_POST['tratamiento'],
            $peso, $talla, $imc, 
            $frecuencia_cardiaca, $frecuencia_respiratoria, $temperatura,
            $tension_sistolica, $tension_diastolica, $imc_clasificacion,
            $clasificacion_hta, $creatinina_serica, $filtrado_glomerular_ckd_epi,
            $hemoglobina_glicosilada
        );
        
        $id_historia_final = 0;
        if ($stmt_h->execute()) {
            $id_historia_final = $conexion->insert_id;
        }
        $msg_final = "Historia creada correctamente. Código: " . $codigo_historia;
    }

    if (!$stmt_h->execute() && $id_historia_final === 0) {
        throw new Exception("Error guardando historia: " . $stmt_h->error);
    }
    $stmt_h->close();


    // --- D. GUARDAR RECETAS (Común para Insert y Update) ---
    $medicamentos_nombres = $_POST['medicamento_nombre'] ?? [];
    
    // Solo procesamos si hay al menos un medicamento con nombre
    if (!empty($medicamentos_nombres) && !empty(trim($medicamentos_nombres[0]))) { 
        
        $sql_receta = "INSERT INTO recetas_medicas (id_historia, fecha_emision) VALUES (?, CURDATE())";
        $stmt_r = $conexion->prepare($sql_receta);
        $stmt_r->bind_param("i", $id_historia_final);
        $stmt_r->execute();
        $id_receta_creada = $conexion->insert_id;
        $stmt_r->close();
        
        $sql_med = "INSERT INTO medicamentos_recetados (id_receta, nombre_medicamento, horario_dosis, cantidad) VALUES (?, ?, ?, ?)";
        $stmt_m = $conexion->prepare($sql_med);
        
        $dosis = $_POST['medicamento_dosis'];
        $cantidad = $_POST['medicamento_cantidad'];

        foreach ($medicamentos_nombres as $k => $nombre) {
            $nom = trim($nombre);
            if (!empty($nom)) {
                $stmt_m->bind_param("isss", $id_receta_creada, $nom, $dosis[$k], $cantidad[$k]);
                $stmt_m->execute();
            }
        }
        $stmt_m->close();
    }

    // --- E. ACTUALIZAR CITA (Solo si aplica) ---
    if ($id_cita_a_completar > 0) {
        $conexion->query("UPDATE citas SET estado_cita = 'Completada' WHERE id_cita = $id_cita_a_completar");
    }

    $conexion->commit();
    
    // Redirección final
    $base_url = defined('BASE_URL') ? BASE_URL . 'views/' : '../views/';
    // Si editamos, volvemos a ver la historia. Si creamos, vamos al dashboard.
    if ($id_historia_editar > 0) {
        header('Location: ' . $base_url . 'ver_historia.php?id=' . $id_historia_final . '&status=success&msg=' . urlencode($msg_final));
    } else {
        header('Location: ' . $base_url . 'dashboard.php?status=success&msg=' . urlencode($msg_final));
    }

} catch (Exception $e) {
    $conexion->rollback();
    $redirect = ($form_type === 'control') ? 'nuevo_control.php' : 'nueva_historia.php';
    $base_url = defined('BASE_URL') ? BASE_URL . 'views/' : '../views/';
    header('Location: ' . $base_url . $redirect . '?documento=' . urlencode($numero_documento) . '&status=error&msg=' . urlencode('Error: ' . $e->getMessage()));
}

$conexion->close();
exit();
?>