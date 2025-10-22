<?php
// Es crucial iniciar la sesión para poder acceder al id del médico logueado.
session_start();

// Requerir archivos necesarios para conexión y funciones auxiliares.
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; // Necesario para mb_convert_case y calcularEdad

/**
 * 1. VERIFICACIÓN DE SEGURIDAD Y PERMISOS
 * Aseguramos que la solicitud sea por POST y que el usuario
 * tenga una sesión activa con el rol de 'Medico'.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Medico') {
    // Si no cumple, redirigimos con un mensaje de error claro.
    // Usamos BASE_URL si está definida, sino una ruta relativa.
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/buscar_paciente.php' : '../views/buscar_paciente.php';
    header('Location: ' . $redirect_url . '?status=error&msg=' . urlencode('Acceso no autorizado o sesión inválida.'));
    exit();
}

/**
 * 2. CONEXIÓN E INICIO DE TRANSACCIÓN
 * Usamos una transacción para asegurar que todos los datos se guarden correctamente,
 * o ninguno si ocurre un error.
 */
$conexion = conectarDB();
$conexion->autocommit(FALSE); // Desactivamos el autocommit para iniciar la transacción

// Variable para guardar el número de documento y usarlo en la redirección de error
$numero_documento = ''; 

try {
    // --- LÓGICA PARA MANEJAR CAMPOS DE GESTACIÓN ---
    // Recogemos el valor de 'embarazada'. Si no existe, asumimos 0 (No).
    $embarazada = isset($_POST['embarazada']) ? (int)$_POST['embarazada'] : 0;
    // Las semanas solo se guardan si 'embarazada' es 1 y el campo no está vacío.
    $semanas_gestacion = ($embarazada === 1 && !empty($_POST['semanas_gestacion'])) ? (int)$_POST['semanas_gestacion'] : NULL;

    // --- 3. PROCESAR DATOS DEL PACIENTE (INSERTAR O ACTUALIZAR) ---
    // Limpiamos y escapamos el número de documento para seguridad.
    $numero_documento = $conexion->real_escape_string(trim($_POST['numero_documento']));
    
    // --- MEJORA: Formateamos nombre y apellido a "Estilo Título" ---
    $nombre_paciente = mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, 'UTF-8');
    $apellido_paciente = mb_convert_case(trim($_POST['apellido']), MB_CASE_TITLE, 'UTF-8');

    // Verificamos si el paciente ya existe en la base de datos.
    $stmt_paciente_check = $conexion->prepare("SELECT numero_documento FROM pacientes WHERE numero_documento = ?");
    $stmt_paciente_check->bind_param("s", $numero_documento);
    $stmt_paciente_check->execute();
    $resultado_check = $stmt_paciente_check->get_result();
    $paciente_existe = $resultado_check->num_rows > 0;
    $stmt_paciente_check->close();

    if ($paciente_existe) {
        // Si el paciente ya existe, ACTUALIZAMOS sus datos (excepto el documento).
        $sql_paciente = "UPDATE pacientes SET tipo_documento=?, nombre=?, apellido=?, fecha_nacimiento=?, sexo=?, estado_civil=?, direccion=?, profesion=?, telefono_whatsapp=?, embarazada=?, semanas_gestacion=? WHERE numero_documento=?";
        $stmt_paciente = $conexion->prepare($sql_paciente);
        $stmt_paciente->bind_param(
            "sssssssssiis", // s=string, i=integer
            $_POST['tipo_documento'],
            $nombre_paciente, // Usamos la variable formateada
            $apellido_paciente, // Usamos la variable formateada
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
        // Si el paciente es nuevo, lo INSERTAMOS con todos sus datos.
        $sql_paciente = "INSERT INTO pacientes (numero_documento, tipo_documento, nombre, apellido, fecha_nacimiento, sexo, estado_civil, direccion, profesion, telefono_whatsapp, embarazada, semanas_gestacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_paciente = $conexion->prepare($sql_paciente);
        $stmt_paciente->bind_param(
            "ssssssssssii", // s=string, i=integer
            $numero_documento,
            $_POST['tipo_documento'],
            $nombre_paciente, // Usamos la variable formateada
            $apellido_paciente, // Usamos la variable formateada
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
    
    // Ejecutamos la consulta para el paciente y verificamos si hubo error.
    if (!$stmt_paciente->execute()) {
        throw new Exception("Error al guardar datos del paciente: " . $stmt_paciente->error);
    }
    $stmt_paciente->close();


    // --- 4. GENERAR CÓDIGO Y GUARDAR LA NUEVA ENTRADA EN LA HISTORIA CLÍNICA ---
    // Generamos un código único para esta consulta (ej. HC-00031).
    $result_last_id = $conexion->query("SELECT MAX(id_historia) as last_id FROM historias_clinicas");
    $last_id = $result_last_id->fetch_assoc()['last_id'] ?? 0;
    $nuevo_id = $last_id + 1;
    $codigo_historia = 'HC-' . str_pad($nuevo_id, 5, '0', STR_PAD_LEFT);

    // Recogemos los signos vitales y otros datos clínicos. Si están vacíos, los guardamos como NULL.
    $peso = !empty($_POST['peso_kg']) ? $_POST['peso_kg'] : NULL;
    $talla = !empty($_POST['talla_cm']) ? $_POST['talla_cm'] : NULL;
    $imc = !empty($_POST['imc']) ? $_POST['imc'] : NULL;
    $tension_arterial = !empty($_POST['tension_arterial']) ? $_POST['tension_arterial'] : NULL;
    $frecuencia_cardiaca = !empty($_POST['frecuencia_cardiaca']) ? $_POST['frecuencia_cardiaca'] : NULL;
    $frecuencia_respiratoria = !empty($_POST['frecuencia_respiratoria']) ? $_POST['frecuencia_respiratoria'] : NULL;
    $temperatura = !empty($_POST['temperatura_c']) ? $_POST['temperatura_c'] : NULL;

    // Preparamos la consulta para insertar la nueva entrada en la historia.
    $sql_historia = "INSERT INTO historias_clinicas (codigo_historia, paciente_documento, id_medico, fecha_consulta, motivo_consulta, enfermedad_actual, antecedentes_personales, antecedentes_familiares, examen_fisico, hallazgos_examen_fisico, diagnostico_principal, solicitud_examenes, tratamiento, peso_kg, talla_cm, imc, tension_arterial, frecuencia_cardiaca, frecuencia_respiratoria, temperatura_c) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_historia = $conexion->prepare($sql_historia);
    // Obtenemos el ID del médico que está registrando la consulta desde la sesión.
    $id_medico_sesion = $_SESSION['id_usuario']; 
    
    // Vinculamos los parámetros a la consulta, especificando el tipo de dato (s, i, d).
    $stmt_historia->bind_param(
        "ssisssssssssdddsiid", // s=string, i=integer, d=double(decimal)
        $codigo_historia, $numero_documento, $id_medico_sesion,
        $_POST['motivo_consulta'], $_POST['enfermedad_actual'], $_POST['antecedentes_personales'],
        $_POST['antecedentes_familiares'], $_POST['examen_fisico'], $_POST['hallazgos_examen_fisico'],
        $_POST['diagnostico_principal'], $_POST['solicitud_examenes'], $_POST['tratamiento'],
        $peso, $talla, $imc, $tension_arterial, $frecuencia_cardiaca, $frecuencia_respiratoria, $temperatura
    );

    // Ejecutamos la consulta y verificamos errores.
    if (!$stmt_historia->execute()) {
        throw new Exception("Error al guardar la historia clínica: " . $stmt_historia->error);
    }
    
    // Guardamos el ID de la historia recién creada para usarlo en la receta.
    $id_historia_creada = $conexion->insert_id;
    $stmt_historia->close();


    // --- 5. PROCESAR RECETA MÉDICA (SI SE AÑADIERON MEDICAMENTOS) ---
    $medicamentos_nombres = $_POST['medicamento_nombre'] ?? [];
    // Verificamos si se envió al menos un nombre de medicamento y no está vacío.
    if (!empty($medicamentos_nombres) && !empty(trim($medicamentos_nombres[0]))) { 

        // Creamos la cabecera de la receta.
        $sql_receta = "INSERT INTO recetas_medicas (id_historia, fecha_emision) VALUES (?, CURDATE())";
        $stmt_receta = $conexion->prepare($sql_receta);
        $stmt_receta->bind_param("i", $id_historia_creada);
        if (!$stmt_receta->execute()) {
            throw new Exception("Error al crear la receta: " . $stmt_receta->error);
        }
        
        $id_receta_creada = $conexion->insert_id; // Obtenemos el ID de la receta.
        $stmt_receta->close();
        
        // Preparamos la consulta para insertar cada medicamento.
        $sql_medicamento = "INSERT INTO medicamentos_recetados (id_receta, nombre_medicamento, horario_dosis, cantidad) VALUES (?, ?, ?, ?)";
        $stmt_medicamento = $conexion->prepare($sql_medicamento);

        // Recogemos los arrays de dosis y cantidad.
        $medicamentos_dosis = $_POST['medicamento_dosis'];
        $medicamentos_cantidad = $_POST['medicamento_cantidad'];

        // Recorremos la lista de nombres de medicamentos.
        foreach ($medicamentos_nombres as $key => $nombre) {
            $nombre_limpio = trim($nombre);
            // Solo guardamos el medicamento si el nombre no está vacío.
            if (!empty($nombre_limpio)) { 
                $stmt_medicamento->bind_param(
                    "isss",
                    $id_receta_creada,
                    $nombre_limpio,
                    $medicamentos_dosis[$key],
                    $medicamentos_cantidad[$key]
                );
                // Verificamos errores al guardar cada medicamento.
                if (!$stmt_medicamento->execute()) {
                    throw new Exception("Error al guardar medicamento: " . $stmt_medicamento->error);
                }
            }
        }
        $stmt_medicamento->close();
    }

    // --- 6. CONFIRMAR TRANSACCIÓN Y REDIRIGIR CON ÉXITO ---
    // Si llegamos aquí sin errores, confirmamos todos los cambios en la base de datos.
    $conexion->commit();
    // Redirigimos al dashboard del médico mostrando un mensaje de éxito.
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/dashboard.php' : '../views/dashboard.php';
    header('Location: ' . $redirect_url . '?status=success&msg=' . urlencode('Historia clínica guardada exitosamente. Código: ' . $codigo_historia));

} catch (Exception $e) {
    // --- 7. REVERTIR TRANSACCIÓN EN CASO DE ERROR ---
    // Si ocurrió cualquier error (Exception), revertimos todos los cambios hechos.
    $conexion->rollback();
    // Redirigimos de vuelta al formulario, manteniendo el documento del paciente
    // y mostrando el mensaje de error específico.
    $redirect_url = defined('BASE_URL') ? BASE_URL . 'views/nueva_historia.php' : '../views/nueva_historia.php';
    header('Location: ' . $redirect_url . '?documento=' . urlencode($numero_documento) . '&status=error&msg=' . urlencode('Error al guardar: ' . $e->getMessage()));

} finally {
    // --- 8. CERRAR CONEXIÓN Y SENTENCIAS PREPARADAS ---
    // Aseguramos que todas las sentencias preparadas y la conexión se cierren
    // correctamente, sin importar si hubo éxito o error.
    if (isset($stmt_paciente_check) && $stmt_paciente_check instanceof mysqli_stmt) $stmt_paciente_check->close();
    if (isset($stmt_paciente) && $stmt_paciente instanceof mysqli_stmt) $stmt_paciente->close();
    if (isset($stmt_historia) && $stmt_historia instanceof mysqli_stmt) $stmt_historia->close();
    if (isset($stmt_receta) && $stmt_receta instanceof mysqli_stmt) $stmt_receta->close();
    if (isset($stmt_medicamento) && $stmt_medicamento instanceof mysqli_stmt) $stmt_medicamento->close();
    if (isset($conexion) && $conexion instanceof mysqli) $conexion->close();
}

exit(); // Terminamos la ejecución del script.
?>