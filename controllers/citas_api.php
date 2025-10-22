<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; 

$conexion = conectarDB();
$response = [];
$action = $_GET['action'] ?? 'get_all';

if ($action == 'get_cita' && isset($_GET['id'])) {
    $id_cita = $_GET['id'];
    $sql = "SELECT 
                c.*,
                p.nombre as nombre_paciente, p.apellido as apellido_paciente, p.telefono_whatsapp,
                u.nombre_medico, u.apellido_medico
            FROM citas c
            JOIN pacientes p ON c.paciente_documento = p.numero_documento
            JOIN usuarios u ON c.id_medico_asignado = u.id_medico
            WHERE c.id_cita = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_cita);
    $stmt->execute();
    $response = $stmt->get_result()->fetch_assoc();
    $stmt->close();

} elseif ($action == 'get_citas_pendientes') {
    $sql = "SELECT 
                c.id_cita,
                CONCAT(p.nombre, ' ', p.apellido) as paciente,
                c.fecha_cita,
                c.hora_cita,
                c.notas_secretaria as motivo
            FROM citas c
            JOIN pacientes p ON c.paciente_documento = p.numero_documento
            WHERE c.fecha_cita >= CURDATE() AND c.estado_cita != 'Cancelada'
            ORDER BY c.fecha_cita, c.hora_cita";

    $resultado = $conexion->query($sql);
    $citas = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $fila['hora_cita'] = date('h:i A', strtotime($fila['hora_cita']));
            // Se aplica el formato Estilo Título aquí también
            $fila['paciente'] = mb_convert_case($fila['paciente'], MB_CASE_TITLE, 'UTF-8');
            $citas[] = $fila;
        }
    }
    $response = ['data' => $citas];

} elseif ($action == 'get_citas_by_date' && isset($_GET['fecha'])) {
    $fecha = $_GET['fecha'];
    $sql = "SELECT c.id_cita, c.hora_cita, p.nombre, p.apellido 
            FROM citas c
            JOIN pacientes p ON c.paciente_documento = p.numero_documento
            WHERE c.fecha_cita = ? AND c.estado_cita != 'Cancelada'
            ORDER BY c.hora_cita ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while($fila = $resultado->fetch_assoc()){
        // Se aplica el formato Estilo Título aquí también
        $fila['nombre'] = mb_convert_case($fila['nombre'], MB_CASE_TITLE, 'UTF-8');
        $fila['apellido'] = mb_convert_case($fila['apellido'], MB_CASE_TITLE, 'UTF-8');
        $response[] = $fila;
    }
    $stmt->close();

} elseif ($action == 'generar_mensaje' && isset($_GET['id'])) {
    $id_cita = $_GET['id'];
    $sql = "SELECT 
                p.nombre as nombre_paciente, p.apellido as apellido_paciente, p.telefono_whatsapp,
                u.nombre_medico, u.apellido_medico,
                c.fecha_cita, c.hora_cita
            FROM citas c
            JOIN pacientes p ON c.paciente_documento = p.numero_documento
            JOIN usuarios u ON c.id_medico_asignado = u.id_medico
            WHERE c.id_cita = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_cita);
    $stmt->execute();
    $datos_cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($datos_cita) {
        $mensaje = generarMensajeConfirmacionCita($datos_cita, false);
        $response = [
            'success' => true,
            'telefono' => $datos_cita['telefono_whatsapp'],
            'mensaje' => $mensaje
        ];
    } else {
        $response = ['success' => false, 'message' => 'No se encontraron datos para la cita.'];
    }

} else { // Esta es la acción por defecto: cargar todas las citas para el calendario
    $sql = "SELECT c.id_cita, c.fecha_cita, c.hora_cita, p.nombre, p.apellido
            FROM citas c
            JOIN pacientes p ON c.paciente_documento = p.numero_documento
            WHERE c.estado_cita != 'Cancelada'";
    $resultado = $conexion->query($sql);
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            
            // --- INICIO DE LA MODIFICACIÓN ---
            // Se convierte el nombre y apellido a Estilo Título antes de unirlos
            $nombre_formateado = mb_convert_case($fila['nombre'], MB_CASE_TITLE, 'UTF-8');
            $apellido_formateado = mb_convert_case($fila['apellido'], MB_CASE_TITLE, 'UTF-8');
            // --- FIN DE LA MODIFICACIÓN ---

            $response[] = [
                'title' => $nombre_formateado . ' ' . $apellido_formateado,
                'start' => $fila['fecha_cita'] . 'T' . $fila['hora_cita'],
                'id'    => $fila['id_cita'],
                'color' => '#28a745'
            ];
        }
    }
}

$conexion->close();
echo json_encode($response);
?>