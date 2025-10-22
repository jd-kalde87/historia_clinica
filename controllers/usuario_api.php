<?php
header('Content-Type: application/json');
require_once '../core/db_connection.php';
// Es buena práctica incluir el archivo de funciones por si se necesita
require_once '../core/funciones.php';
session_start();

// Verificamos que el usuario sea Administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    echo json_encode(['data' => []]); // Devolvemos datos vacíos si no hay permiso
    exit();
}

$conexion = conectarDB();
$response = [];
$action = $_GET['action'] ?? 'get_all';

if ($action == 'get_all') {
    // Buscamos todos los usuarios que no sean Administradores
    $sql = "SELECT id_medico, CONCAT(nombre_medico, ' ', apellido_medico) as nombre_completo, rol, usuario
            FROM usuarios 
            WHERE rol != 'Administrador'
            ORDER BY nombre_medico";
    
    $resultado = $conexion->query($sql);
    $usuarios = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            // Convertimos el nombre a Estilo Título para consistencia
            $fila['nombre_completo'] = mb_convert_case($fila['nombre_completo'], MB_CASE_TITLE, 'UTF-8');
            $usuarios[] = $fila;
        }
    }
    $response = ['data' => $usuarios];

} elseif ($action == 'get_usuario' && isset($_GET['id'])) {
    $id_usuario = $_GET['id'];
    $sql = "SELECT id_medico, rol, nombre_medico, apellido_medico, usuario, especialidad, registro_medico 
            FROM usuarios WHERE id_medico = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $response = ['success' => true, 'data' => $resultado->fetch_assoc()];
    } else {
        $response = ['success' => false, 'message' => 'Usuario no encontrado.'];
    }
    $stmt->close();
}

$conexion->close();
echo json_encode($response);
?>