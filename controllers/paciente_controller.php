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
     * Para el buscador de pacientes (módulo de médico).
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
     * Para la tabla "Gestionar Pacientes" (el nuevo módulo de edición).
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
     * Para rellenar el formulario de edición.
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
     * Para la tabla "Listado de Pacientes" (la que ya existía).
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
    // Esta es la llave '}' que cierra el 'if ($_SERVER['REQUEST_METHOD'] === 'GET')'

/**
 * ===============================================
 * MANEJO DE SOLICITUDES POST (Actualizar datos)
 * ===============================================
 */
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Inicia una transacción para asegurar la integridad de los datos
    $conexion->autocommit(FALSE);
    try {
        // Recogemos los datos del formulario (igual que en 'historia_controller.php')
        $embarazada = $_POST['embarazada'] ?? 0;
        $semanas_gestacion = ($embarazada == 1 && !empty($_POST['semanas_gestacion'])) ? $_POST['semanas_gestacion'] : NULL;
        $numero_documento = $conexion->real_escape_string(trim($_POST['numero_documento_original'])); // El ID original
        
        $nombre_paciente = mb_convert_case(trim($_POST['nombre']), MB_CASE_TITLE, 'UTF-8');
        $apellido_paciente = mb_convert_case(trim($_POST['apellido']), MB_CASE_TITLE, 'UTF-8');

        // Preparamos la consulta UPDATE
        $sql_paciente = "UPDATE pacientes SET tipo_documento=?, nombre=?, apellido=?, fecha_nacimiento=?, sexo=?, estado_civil=?, direccion=?, profesion=?, telefono_whatsapp=?, embarazada=?, semanas_gestacion=? 
                         WHERE numero_documento=?";
        
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
            $numero_documento // El WHERE se basa en el documento original
        );

        if (!$stmt_paciente->execute()) {
            throw new Exception("Error al actualizar los datos: " . $stmt_paciente->error);
        }

        $conexion->commit(); // Si todo va bien, confirma los cambios
        $response = ['success' => true, 'message' => '¡Datos del paciente actualizados exitosamente!'];
        $stmt_paciente->close();

    } catch (Exception $e) {
        $conexion->rollback(); // Si algo falla, revierte los cambios
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}
// Esta es la llave '}' que estaba "Unmatched" (sin pareja) en la línea 92.
// Ahora todo el código está dentro de la estructura GET o POST.

$conexion->close();
echo json_encode($response);
?>