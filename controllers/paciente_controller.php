<?php
header('Content-Type: application/json'); // La respuesta SIEMPRE es JSON
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; 

$conexion = conectarDB();
$response = ['status' => 'error', 'message' => 'Acción no válida.'];

/**
 * ===============================================
 * MANEJO DE SOLICITUDES GET (Leer datos)
 * ===============================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $action = $_GET['action'] ?? '';

    /**
     * ACCIÓN: OBTENER UN PACIENTE (get_one)
     */
    if ($action === 'get_one' && isset($_GET['documento'])) {
        $documento = trim($_GET['documento']);
        
        $sql_paciente = "SELECT * FROM pacientes WHERE numero_documento = ?";
        $stmt_paciente = $conexion->prepare($sql_paciente);
        $stmt_paciente->bind_param("s", $documento);
        $stmt_paciente->execute();
        $resultado_paciente = $stmt_paciente->get_result();

        if ($resultado_paciente->num_rows > 0) {
            $paciente = $resultado_paciente->fetch_assoc();
            $paciente['edad'] = calcularEdad($paciente['fecha_nacimiento']);
            $paciente['nombre'] = mb_convert_case($paciente['nombre'], MB_CASE_TITLE, 'UTF-8');
            $paciente['apellido'] = mb_convert_case($paciente['apellido'], MB_CASE_TITLE, 'UTF-8');
            
            $sql_consultas = "SELECT id_historia, codigo_historia, fecha_consulta, motivo_consulta 
                              FROM historias_clinicas 
                              WHERE paciente_documento = ? 
                              ORDER BY fecha_consulta DESC";
            $stmt_consultas = $conexion->prepare($sql_consultas);
            $stmt_consultas->bind_param("s", $documento);
            $stmt_consultas->execute();
            $resultado_consultas = $stmt_consultas->get_result();
            $consultas = [];
            while ($fila = $resultado_consultas->fetch_assoc()) {
                $consultas[] = $fila;
            }

            $response['status'] = 'success';
            $response['paciente'] = $paciente;
            $response['consultas'] = $consultas;
            unset($response['message']);
            $stmt_consultas->close();
        } else {
            $response['status'] = 'not_found';
            $response['message'] = 'No se encontró ningún paciente con ese documento.';
        }
        $stmt_paciente->close();

    /**
     * ACCIÓN: OBTENER TODOS LOS PACIENTES (get_all)
     */
    } elseif ($action === 'get_all') {
        
        $sql = "SELECT numero_documento, nombre, apellido, telefono_whatsapp FROM pacientes ORDER BY apellido ASC";
        $resultado = $conexion->query($sql);
        $pacientes = [];
        if ($resultado) {
            while ($fila = $resultado->fetch_assoc()) {
                $fila['nombre_completo'] = mb_convert_case($fila['nombre'] . ' ' . $fila['apellido'], MB_CASE_TITLE, 'UTF-8');
                $pacientes[] = $fila;
            }
        }
        $response = ['data' => $pacientes];

    /**
     * ACCIÓN: OBTENER DATOS COMPLETOS DE UN PACIENTE (get_paciente_completo)
     */
    } elseif ($action === 'get_paciente_completo' && isset($_GET['documento'])) {
        
        $documento = trim($_GET['documento']);
        $sql = "SELECT * FROM pacientes WHERE numero_documento = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $documento);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $paciente = $resultado->fetch_assoc();
            $paciente['nombre'] = mb_convert_case($paciente['nombre'], MB_CASE_TITLE, 'UTF-8');
            $paciente['apellido'] = mb_convert_case($paciente['apellido'], MB_CASE_TITLE, 'UTF-8');
            $response = ['status' => 'success', 'data' => $paciente];
        } else {
            $response = ['status' => 'error', 'message' => 'Paciente no encontrado.'];
        }
        $stmt->close();

    /**
     * ACCIÓN: OBTENER LISTADO (get_all_listado)
     */
    } elseif ($action === 'get_all_listado') {
        
        $sql = "SELECT numero_documento, nombre, apellido, sexo, fecha_nacimiento FROM pacientes ORDER BY apellido ASC";
        $resultado = $conexion->query($sql);
        $pacientes = [];
        
        if ($resultado) {
            while ($fila = $resultado->fetch_assoc()) {
                $fila['nombre'] = mb_convert_case($fila['nombre'], MB_CASE_TITLE, 'UTF-8');
                $fila['apellido'] = mb_convert_case($fila['apellido'], MB_CASE_TITLE, 'UTF-8');
                $fila['sexo'] = ucfirst(strtolower($fila['sexo'] ?? 'N/A'));
                $fila['fecha_nacimiento'] = date("d/m/Y", strtotime($fila['fecha_nacimiento']));
                $pacientes[] = $fila;
            }
        }
        $response = ['data' => $pacientes];
    }

/**
 * ===============================================
 * MANEJO DE SOLICITUDES POST (Crear y Actualizar datos)
 * ===============================================
 */
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $conexion->autocommit(FALSE);
    try {
        // Si el campo oculto 'numero_documento_original' está vacío, es un registro NUEVO
        $es_nuevo = empty($_POST['numero_documento_original']); 
        
        $embarazada = $_POST['embarazada'] ?? 0;
        $semanas_gestacion = ($embarazada == 1 && !empty($_POST['semanas_gestacion'])) ? $_POST['semanas_gestacion'] : NULL;
        $nombre_paciente = mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, 'UTF-8');
        $apellido_paciente = mb_convert_case(trim($_POST['apellido']), MB_CASE_TITLE, 'UTF-8');
        $numero_documento = trim($_POST['numero_documento']); 

        if ($es_nuevo) {
            // --- MODO INSERTAR (NUEVO) ---
            // 1. Verificar si ya existe
            $check = $conexion->prepare("SELECT numero_documento FROM pacientes WHERE numero_documento = ?");
            $check->bind_param("s", $numero_documento);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("El paciente con este documento ya existe.");
            }
            $check->close();

            // 2. Insertar
            $sql_paciente = "INSERT INTO pacientes (numero_documento, tipo_documento, nombre, apellido, fecha_nacimiento, sexo, estado_civil, direccion, profesion, telefono_whatsapp, embarazada, semanas_gestacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_paciente = $conexion->prepare($sql_paciente);
            $stmt_paciente->bind_param("sssssssssiis", $numero_documento, $_POST['tipo_documento'], $nombre_paciente, $apellido_paciente, $_POST['fecha_nacimiento'], $_POST['sexo'], $_POST['estado_civil'], $_POST['direccion'], $_POST['profesion'], $_POST['telefono_whatsapp'], $embarazada, $semanas_gestacion);
            $msg_exito = "¡Paciente registrado exitosamente!";

        } else {
            // --- MODO ACTUALIZAR (EDITAR) ---
            $doc_original = trim($_POST['numero_documento_original']);
            $sql_paciente = "UPDATE pacientes SET tipo_documento=?, nombre=?, apellido=?, fecha_nacimiento=?, sexo=?, estado_civil=?, direccion=?, profesion=?, telefono_whatsapp=?, embarazada=?, semanas_gestacion=? WHERE numero_documento=?";
            $stmt_paciente = $conexion->prepare($sql_paciente);
            $stmt_paciente->bind_param("sssssssssiis", $_POST['tipo_documento'], $nombre_paciente, $apellido_paciente, $_POST['fecha_nacimiento'], $_POST['sexo'], $_POST['estado_civil'], $_POST['direccion'], $_POST['profesion'], $_POST['telefono_whatsapp'], $embarazada, $semanas_gestacion, $doc_original);
            $msg_exito = "¡Datos actualizados exitosamente!";
        }

        if (!$stmt_paciente->execute()) {
            throw new Exception("Error en BD: " . $stmt_paciente->error);
        }

        $conexion->commit();
        $response = ['success' => true, 'message' => $msg_exito];
        $stmt_paciente->close();

    } catch (Exception $e) {
        $conexion->rollback();
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

$conexion->close();
echo json_encode($response);
?>