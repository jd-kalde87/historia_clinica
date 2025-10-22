<?php
session_start(); // Es necesario iniciar la sesión para leer la variable $_SESSION['rol']
require_once '../core/db_connection.php';

// --- INICIO DE LA CORRECCIÓN: Verificación de Permisos ---
// 1. Verificamos que el usuario esté autenticado y tenga el rol de 'Medico'.
if (!isset($_SESSION['autenticado']) || $_SESSION['rol'] !== 'Medico') {
    // Si no tiene permiso, preparamos un mensaje de error
    $id_historia = $_POST['id_historia'] ?? 0;
    header('Location: ../views/ver_historia.php?id=' . $id_historia . '&upload_status=error&msg=' . urlencode('Acceso no autorizado.'));
    exit();
}
// --- FIN DE LA CORRECCIÓN ---

// Validar que la petición sea POST y que se haya enviado un archivo
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo'])) {
    header('Location: ../views/ver_historia.php?id=' . $_POST['id_historia'] . '&upload_status=error&msg=' . urlencode('Petición no válida.'));
    exit();
}

$id_historia = $_POST['id_historia'];
$documento_paciente = $_POST['documento_paciente'];

// --- PROCESO DE VALIDACIÓN Y SUBIDA DEL ARCHIVO ---
$directorio_base = '../uploads/';
$directorio_paciente = $directorio_base . $documento_paciente . '/';

// 1. Crear el directorio del paciente si no existe
if (!is_dir($directorio_paciente)) {
    mkdir($directorio_paciente, 0755, true);
}

$nombre_original = basename($_FILES['archivo']['name']);
$tipo_archivo = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
$nombre_unico = uniqid() . '-' . time() . '.' . $tipo_archivo;
$ruta_destino = $directorio_paciente . $nombre_unico;

// 2. Validar tamaño del archivo (ej. 5MB máximo)
if ($_FILES['archivo']['size'] > 5 * 1024 * 1024) {
    header('Location: ../views/ver_historia.php?id=' . $id_historia . '&upload_status=error&msg=' . urlencode('El archivo es demasiado grande (máximo 5MB).'));
    exit();
}

// 3. Validar tipo de archivo (solo PDF e imágenes)
$tipos_permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($tipo_archivo, $tipos_permitidos)) {
    header('Location: ../views/ver_historia.php?id=' . $id_historia . '&upload_status=error&msg=' . urlencode('Tipo de archivo no permitido.'));
    exit();
}

// 4. Mover el archivo al directorio de destino
if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_destino)) {
    
    // 5. Guardar la información en la base de datos
    $conexion = conectarDB();
    $sql = "INSERT INTO archivos_adjuntos (id_historia, nombre_archivo, ruta_archivo, tipo_archivo) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    
    // Guardamos la ruta relativa sin el '../' para que sea más portable
    $ruta_para_db = 'uploads/' . $documento_paciente . '/' . $nombre_unico;
    
    $stmt->bind_param("isss", $id_historia, $nombre_original, $ruta_para_db, $tipo_archivo);
    
    if($stmt->execute()){
        // Éxito: redirigir a la vista de detalle con mensaje de éxito
        header('Location: ../views/ver_historia.php?id=' . $id_historia . '&upload_status=success');
    } else {
        // Error al guardar en BD: redirigir con mensaje de error
        header('Location: ../views/ver_historia.php?id=' . $id_historia . '&upload_status=error&msg=' . urlencode('Error al guardar en la base de datos.'));
    }
    $stmt->close();
    $conexion->close();

} else {
    // Error al mover el archivo: redirigir con mensaje de error
    header('Location: ../views/ver_historia.php?id=' . $id_historia . '&upload_status=error&msg=' . urlencode('Error al subir el archivo.'));
}
exit();
?>