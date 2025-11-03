<?php
// Es crucial iniciar la sesión para poder acceder al id del médico logueado.
session_start();

// Requerir archivos necesarios para conexión y funciones auxiliares.
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; // Necesario para mb_convert_case y calcularEdad

/**
 * 1. VERIFICACIÓN DE SEGURIDAD Y PERMISOS
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Medico') {
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/buscar_paciente.php' : '../views/buscar_paciente.php';
    header('Location: ' . $redirect_url . '?status=error&msg=' . urlencode('Acceso no autorizado o sesión inválida.'));
    exit();
}

/**
 * 2. CONEXIÓN E INICIO DE TRANSACCIÓN
 */
$conexion = conectarDB();
$conexion->autocommit(FALSE); // Desactivamos el autocommit para iniciar la transacción
$id_cita_recibido = (int)($_POST['id_cita_a_completar'] ?? 0);

$numero_documento = $conexion->real_escape_string(trim($_POST['numero_documento']));
$id_cita_a_completar = (int)($_POST['id_cita_a_completar'] ?? 0);
try {
    // --- LÓGICA PARA MANEJAR CAMPOS DE GESTACIÓN ---
    $embarazada = isset($_POST['embarazada']) ? (int)$_POST['embarazada'] : 0;
    $semanas_gestacion = ($embarazada === 1 && !empty($_POST['semanas_gestacion'])) ? (int)$_POST['semanas_gestacion'] : NULL;

    // --- 3. PROCESAR DATOS DEL PACIENTE (INSERTAR O ACTUALIZAR) ---
    $numero_documento = $conexion->real_escape_string(trim($_POST['numero_documento']));
    
    $nombre_paciente = mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, 'UTF-8');
    $apellido_paciente = mb_convert_case(trim($_POST['apellido']), MB_CASE_TITLE, 'UTF-8');

    $stmt_paciente_check = $conexion->prepare("SELECT numero_documento FROM pacientes WHERE numero_documento = ?");
    $stmt_paciente_check->bind_param("s", $numero_documento);
    $stmt_paciente_check->execute();
    $resultado_check = $stmt_paciente_check->get_result();
    $paciente_existe = $resultado_check->num_rows > 0;
    $stmt_paciente_check->close();

    if ($paciente_existe) {
        $sql_paciente = "UPDATE pacientes SET tipo_documento=?, nombre=?, apellido=?, fecha_nacimiento=?, sexo=?, estado_civil=?, direccion=?, profesion=?, telefono_whatsapp=?, embarazada=?, semanas_gestacion=? WHERE numero_documento=?";
        $stmt_paciente = $conexion->prepare($sql_paciente);
        $stmt_paciente->bind_param(
            "sssssssssiis",
            $_POST['tipo_documento'],
            $nombre_paciente,
            $apellido_paciente,
            $_POST['fecha_nacimiento'],
            $_POST['sexo'],
            $_POST['estado_civil'],
            $_POST['direccion'],
            $_POST['profesion'],
            $_POST['telefono_whatsapp'],
            $embarazada,
            $semanas_gestacion,
            $numero_documento
        );
    } else {
        $sql_paciente = "INSERT INTO pacientes (numero_documento, tipo_documento, nombre, apellido, fecha_nacimiento, sexo, estado_civil, direccion, profesion, telefono_whatsapp, embarazada, semanas_gestacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_paciente = $conexion->prepare($sql_paciente);
        $stmt_paciente->bind_param(
            "ssssssssssii",
            $numero_documento,
            $_POST['tipo_documento'],
            $nombre_paciente,
            $apellido_paciente,
            $_POST['fecha_nacimiento'],
            $_POST['sexo'],
            $_POST['estado_civil'],
            $_POST['direccion'],
            $_POST['profesion'],
            $_POST['telefono_whatsapp'],
            $embarazada,
            $semanas_gestacion
        );
    }
    
    if (!$stmt_paciente->execute()) {
        throw new Exception("Error al guardar datos del paciente: " . $stmt_paciente->error);
    }
    $stmt_paciente->close();


    // --- 4. GENERAR CÓDIGO Y GUARDAR LA NUEVA ENTRADA EN LA HISTORIA CLÍNICA ---
    $result_last_id = $conexion->query("SELECT MAX(id_historia) as last_id FROM historias_clinicas");
    $last_id = $result_last_id->fetch_assoc()['last_id'] ?? 0;
    $nuevo_id = $last_id + 1;
    $codigo_historia = 'HC-' . str_pad($nuevo_id, 5, '0', STR_PAD_LEFT);

    // --- CAMBIO 1: Recogemos las NUEVAS variables del formulario ---
    // Recogemos los signos vitales. Si están vacíos, los guardamos como NULL.
    $peso = !empty($_POST['peso_kg']) ? (float)$_POST['peso_kg'] : NULL;
    $talla = !empty($_POST['talla_cm']) ? (float)$_POST['talla_cm'] : NULL;
    $imc_calculado_js = !empty($_POST['imc']) ? (float)$_POST['imc'] : NULL; // El IMC que viene del JS
    
    $tension_sistolica = !empty($_POST['tension_sistolica']) ? (int)$_POST['tension_sistolica'] : NULL;
    $tension_diastolica = !empty($_POST['tension_diastolica']) ? (int)$_POST['tension_diastolica'] : NULL;
    
    $frecuencia_cardiaca = !empty($_POST['frecuencia_cardiaca']) ? (int)$_POST['frecuencia_cardiaca'] : NULL;
    $frecuencia_respiratoria = !empty($_POST['frecuencia_respiratoria']) ? (int)$_POST['frecuencia_respiratoria'] : NULL;
    $temperatura = !empty($_POST['temperatura_c']) ? (float)$_POST['temperatura_c'] : NULL;
    
    $creatinina_serica = !empty($_POST['creatinina_serica']) ? (float)$_POST['creatinina_serica'] : NULL;
    $hemoglobina_glicosilada = !empty($_POST['hemoglobina_glicosilada']) ? (float)$_POST['hemoglobina_glicosilada'] : NULL;
    // La variable antigua $tension_arterial (varchar) ya no se usa y se ha eliminado.


    // --- CAMBIO 2: LÓGICA DE CÁLCULO DEL LADO DEL SERVIDOR ---
    // Es una BUENA PRÁCTICA recalcular los valores en el servidor 
    // para asegurar la integridad de los datos, por si el JS falla.
    
    // Recalcular IMC
    $imc = NULL;
    if ($peso !== NULL && $talla !== NULL && $talla > 0) {
        $talla_m = $talla / 100;
        $imc = round($peso / ($talla_m * $talla_m), 2);
    } else {
        $imc = $imc_calculado_js; // Usar el del JS si no se pueden calcular aquí
    }

    // Clasificación de IMC
    $imc_clasificacion = NULL;
    if ($imc !== NULL) {
        if ($imc < 18.5) $imc_clasificacion = "Bajo Peso";
        elseif ($imc >= 18.5 && $imc <= 24.9) $imc_clasificacion = "Peso Normal";
        elseif ($imc >= 25 && $imc <= 29.9) $imc_clasificacion = "Sobrepeso";
        elseif ($imc >= 30 && $imc <= 34.9) $imc_clasificacion = "Obesidad Grado 1";
        elseif ($imc >= 35 && $imc <= 39.9) $imc_clasificacion = "Obesidad Grado 2";
        elseif ($imc >= 40) $imc_clasificacion = "Obesidad Grado 3 (Mórbida)";
    }

    // Clasificación de Hipertensión (HTA)
    $clasificacion_hta = NULL;
    if ($tension_sistolica !== NULL && $tension_diastolica !== NULL) {
        if ($tension_sistolica > 180 || $tension_diastolica > 120) $clasificacion_hta = "Crisis Hipertensiva";
        elseif ($tension_sistolica >= 140 || $tension_diastolica >= 90) $clasificacion_hta = "Hipertensión Grado 2";
        elseif (($tension_sistolica >= 130 && $tension_sistolica <= 139) || ($tension_diastolica >= 80 && $tension_diastolica <= 89)) $clasificacion_hta = "Hipertensión Grado 1";
        elseif (($tension_sistolica >= 120 && $tension_sistolica <= 129) && $tension_diastolica < 80) $clasificacion_hta = "Presión Arterial Elevada";
        elseif ($tension_sistolica < 120 && $tension_diastolica < 80) $clasificacion_hta = "Presión Arterial Normal";
    }

    // Cálculo de Filtrado Glomerular (CKD-EPI)
    $filtrado_glomerular_ckd_epi = NULL;
    $fecha_nacimiento_paciente = $_POST['fecha_nacimiento'];
    $sexo_paciente = $_POST['sexo']; // 'MASCULINO' o 'FEMENINO'
    
    // Usamos la función de 'funciones.php' (que ya estabas importando)
    $edad_paciente = calcularEdad($fecha_nacimiento_paciente); 

    if ($creatinina_serica !== NULL && $edad_paciente >= 18 && !empty($sexo_paciente)) {
        $k = ($sexo_paciente == 'FEMENINO') ? 0.7 : 0.9;
        $alpha = ($sexo_paciente == 'FEMENINO') ? -0.329 : -0.411;
        $S = ($sexo_paciente == 'FEMENINO') ? 1.018 : 1.0;
        $R = 1.0; // Asumimos no-negro

        $ratio = $creatinina_serica / $k;
        $egfr_raw = 141 * pow(min($ratio, 1), $alpha) * pow(max($ratio, 1), -1.209) * pow(0.993, $edad_paciente) * $S * $R;
        $filtrado_glomerular_ckd_epi = round($egfr_raw, 2);
    }
    // --- FIN DE CÁLCULOS DEL SERVIDOR ---


// --- CAMBIO 3: Actualizamos la consulta INSERT ---
    // Se elimina 'tension_arterial' (varchar)
    // Se añaden 6 nuevas columnas al final
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
    
    $stmt_historia = $conexion->prepare($sql_historia);
    $id_medico_sesion = $_SESSION['id_usuario']; 
    
    // --- CAMBIO 4: Actualizamos el bind_param (ESTA ES LA LÍNEA CORREGIDA) ---
    // El string ahora es "ssisssssssssdddiidiissddd" (25 caracteres)
    $stmt_historia->bind_param(
        "ssisssssssssdddiidiissddd", // s=string, i=integer, d=double(decimal)
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

    // Ejecutamos la consulta y verificamos errores.
    if (!$stmt_historia->execute()) {
        throw new Exception("Error al guardar la historia clínica: " ->error);
    }
    
    $id_historia_creada = $conexion->insert_id;
    $stmt_historia->close();


    // --- 5. PROCESAR RECETA MÉDICA (SI SE AÑADIERON MEDICAMENTOS) ---
    $medicamentos_nombres = $_POST['medicamento_nombre'] ?? [];
    if (!empty($medicamentos_nombres) && !empty(trim($medicamentos_nombres[0]))) { 

        $sql_receta = "INSERT INTO recetas_medicas (id_historia, fecha_emision) VALUES (?, CURDATE())";
        $stmt_receta = $conexion->prepare($sql_receta);
        $stmt_receta->bind_param("i", $id_historia_creada);
        if (!$stmt_receta->execute()) {
            throw new Exception("Error al crear la receta: " . $stmt_receta->error);
        }
        
        $id_receta_creada = $conexion->insert_id;
        $stmt_receta->close();
        
        $sql_medicamento = "INSERT INTO medicamentos_recetados (id_receta, nombre_medicamento, horario_dosis, cantidad) VALUES (?, ?, ?, ?)";
        $stmt_medicamento = $conexion->prepare($sql_medicamento);

        $medicamentos_dosis = $_POST['medicamento_dosis'];
        $medicamentos_cantidad = $_POST['medicamento_cantidad'];

        foreach ($medicamentos_nombres as $key => $nombre) {
            $nombre_limpio = trim($nombre);
            if (!empty($nombre_limpio)) { 
                $stmt_medicamento->bind_param(
                    "isss",
                    $id_receta_creada,
                    $nombre_limpio,
                    $medicamentos_dosis[$key],
                    $medicamentos_cantidad[$key]
                );
                if (!$stmt_medicamento->execute()) {
                    throw new Exception("Error al guardar medicamento: " . $stmt_medicamento->error);
                }
            }
        }
        $stmt_medicamento->close();
    }
    
    // 6. ACTUALIZAR EL ESTADO DE LA CITA (SI VIENE DE UNA)
    if ($id_cita_a_completar > 0) {
        $sql_update_cita = "UPDATE citas SET estado_cita = 'Completada' WHERE id_cita = ?";
        $stmt_update_cita = $conexion->prepare($sql_update_cita);
        $stmt_update_cita->bind_param("i", $id_cita_a_completar);
        $stmt_update_cita->execute();
        $stmt_update_cita->close();
    }    

    // --- 7. CONFIRMAR TRANSACCIÓN Y REDIRIGIR CON ÉXITO ---
    $conexion->commit();
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/dashboard.php' : '../views/dashboard.php';
    header('Location: ' . $redirect_url . '?status=success&msg=' . urlencode('Historia clínica guardada exitosamente. Código: ' . $codigo_historia));

} catch (Exception $e) {
    // --- 8. REVERTIR TRANSACCIÓN EN CASO DE ERROR ---
    $conexion->rollback();
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/nueva_historia.php' : '../views/nueva_historia.php';
    header('Location: ' . $redirect_url . '?documento=' . urlencode($numero_documento) . '&status=error&msg=' . urlencode('Error al guardar: ' . $e->getMessage()));

} finally {
    // --- 9. CERRAR CONEXIÓN Y SENTENCIAS PREPARADAS ---
    if (isset($stmt_paciente_check) && $stmt_paciente_check instanceof mysqli_stmt) $stmt_paciente_check->close();
    if (isset($stmt_paciente) && $stmt_paciente instanceof mysqli_stmt) $stmt_paciente->close();
    if (isset($stmt_historia) && $stmt_historia instanceof mysqli_stmt) $stmt_historia->close();
    if (isset($stmt_receta) && $stmt_receta instanceof mysqli_stmt) $stmt_receta->close();
    if (isset($stmt_medicamento) && $stmt_medicamento instanceof mysqli_stmt) $stmt_medicamento->close();
    if (isset($conexion) && $conexion instanceof mysqli) $conexion->close();
}

exit(); // Terminamos la ejecución del script.
?>