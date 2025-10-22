
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../core/db_connection.php';
// Ya no necesitamos 'funciones.php' ni 'config.php' aquí
// require_once '../core/funciones.php';
// require_once '../core/config.php';

// Indicamos que la respuesta será en formato JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Secretaria') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

$conexion = conectarDB();
$conexion->autocommit(FALSE); // Iniciamos la transacción

try {
    $action = $_POST['action'] ?? 'guardar';

    if ($action === 'cancelar') {
        // --- LÓGICA PARA CANCELAR CITA ---
        $id_cita_cancelar = $_POST['id_cita'];
        if (empty($id_cita_cancelar)) {
            throw new Exception("No se proporcionó un ID de cita para cancelar.");
        }
        $sql_cancelar = "UPDATE citas SET estado_cita = 'Cancelada' WHERE id_cita = ?";
        $stmt_cancelar = $conexion->prepare($sql_cancelar);
        $stmt_cancelar->bind_param("i", $id_cita_cancelar);
        if (!$stmt_cancelar->execute()) {
            throw new Exception("Error al cancelar la cita.");
        }
        $stmt_cancelar->close();
        $conexion->commit(); // Confirmamos la transacción
        echo json_encode(['success' => true, 'message' => 'Cita cancelada exitosamente.']);

    } elseif ($action === 'guardar') {
        // --- LÓGICA PARA GUARDAR O ACTUALIZAR CITA ---
        $tipo_registro = $_POST['tipo_registro'];
        $paciente_documento = '';
        
        if ($tipo_registro === 'nuevo') {
            // Lógica para crear paciente nuevo
            $paciente_documento = $_POST['nuevo_numero_documento'];
            $nombre = mb_convert_case($_POST['nuevo_nombre'], MB_CASE_TITLE, 'UTF-8');
            $apellido = mb_convert_case($_POST['nuevo_apellido'], MB_CASE_TITLE, 'UTF-8');
            $telefono = $_POST['nuevo_telefono_whatsapp'];

            if (empty($paciente_documento) || empty($nombre) || empty($apellido)) {
                throw new Exception("Los datos del nuevo paciente (documento, nombre, apellido) son obligatorios.");
            }

            // 1. Verificar que el paciente no exista
            $stmt_check = $conexion->prepare("SELECT numero_documento FROM pacientes WHERE numero_documento = ?");
            $stmt_check->bind_param("s", $paciente_documento);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Ya existe un paciente con el documento " . $paciente_documento);
            }
            $stmt_check->close();

            // 2. Insertar el nuevo paciente
            $sql_paciente = "INSERT INTO pacientes (numero_documento, nombre, apellido, telefono_whatsapp) VALUES (?, ?, ?, ?)";
            $stmt_paciente = $conexion->prepare($sql_paciente);
            $stmt_paciente->bind_param("ssss", $paciente_documento, $nombre, $apellido, $telefono);
            if (!$stmt_paciente->execute()) {
                throw new Exception("Error al registrar el nuevo paciente.");
            }
            $stmt_paciente->close();

        } else {
            // Si el paciente es existente, solo tomamos su documento
            $paciente_documento = $_POST['paciente_documento'];
        }

        $id_medico = $_POST['id_medico'];
        $fecha_cita = $_POST['fecha_cita'];
        $hora_cita = $_POST['hora_cita'];
        $id_cita_actual = $_POST['id_cita'] ?? 0;

        // Validación anti-duplicados
        $sql_check_cita = "SELECT id_cita FROM citas WHERE id_medico_asignado = ? AND fecha_cita = ? AND hora_cita = ? AND estado_cita != 'Cancelada' AND id_cita != ?";
        $stmt_check_cita = $conexion->prepare($sql_check_cita);
        $stmt_check_cita->bind_param("issi", $id_medico, $fecha_cita, $hora_cita, $id_cita_actual);
        $stmt_check_cita->execute();
        if ($stmt_check_cita->get_result()->num_rows > 0) {
            throw new Exception("El médico seleccionado ya tiene una cita agendada a esa misma hora.");
        }
        $stmt_check_cita->close();

        // Guardar o actualizar
        $notas_secretaria = $_POST['notas_secretaria'];
        $id_secretaria = $_SESSION['id_usuario'];

        if (empty($id_cita_actual)) {
            // INSERTAR
            $sql_cita = "INSERT INTO citas (paciente_documento, id_medico_asignado, id_secretaria_agendo, fecha_cita, hora_cita, notas_secretaria) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_cita = $conexion->prepare($sql_cita);
            $stmt_cita->bind_param("siisss", $paciente_documento, $id_medico, $id_secretaria, $fecha_cita, $hora_cita, $notas_secretaria);
        } else {
            // ACTUALIZAR
            $sql_cita = "UPDATE citas SET paciente_documento=?, id_medico_asignado=?, fecha_cita=?, hora_cita=?, notas_secretaria=? WHERE id_cita=?";
            $stmt_cita = $conexion->prepare($sql_cita);
            $stmt_cita->bind_param("sisssi", $paciente_documento, $id_medico, $fecha_cita, $hora_cita, $notas_secretaria, $id_cita_actual);
        }

        if ($stmt_cita->execute()) {
            $stmt_cita->close();
            $conexion->commit(); // Confirmamos la transacción
            
            // Enviamos una respuesta de éxito simple
            echo json_encode(['success' => true, 'message' => '¡Cita guardada con éxito!']);

        } else {
            throw new Exception("Error al guardar la cita.");
        }
    }

} catch (Exception $e) {
    $conexion->rollback(); // Revertimos la transacción si algo falló
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
exit();
?>