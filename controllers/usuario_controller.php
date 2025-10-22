<?php
session_start();
require_once '../core/db_connection.php';

// Establecemos que la respuesta será en formato JSON para comunicarnos con JavaScript.
header('Content-Type: application/json');

/**
 * 1. VERIFICACIÓN DE SEGURIDAD Y PERMISOS
 * Nos aseguramos de que la solicitud sea por POST y que el usuario
 * tenga una sesión activa con el rol de 'Administrador'.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit();
}

$conexion = conectarDB();
$response = [];

try {
    // Verificamos si se envió una 'action' específica, sino, asumimos que es para guardar/editar.
    $action = $_POST['action'] ?? 'guardar';

    /**
     * 2. LÓGICA PARA ELIMINAR UN USUARIO
     * Si la acción es 'eliminar', entra en este bloque y termina la ejecución.
     */
    if ($action === 'eliminar') {
        $id_usuario = (int)($_POST['id_usuario'] ?? 0);

        if ($id_usuario === 0) {
            throw new Exception("ID de usuario no válido para eliminar.");
        }

        // Preparamos la consulta para borrar al usuario de forma segura.
        $sql = "DELETE FROM usuarios WHERE id_medico = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response = ['success' => true, 'message' => '¡Usuario eliminado exitosamente!'];
            } else {
                throw new Exception("El usuario no fue encontrado o ya había sido eliminado.");
            }
        } else {
            throw new Exception("Error al eliminar el usuario.");
        }
        $stmt->close();

    } else {
        /**
         * 3. LÓGICA PARA CREAR O ACTUALIZAR UN USUARIO
         * Si la acción no es 'eliminar', el código continúa aquí.
         */
        
        // Recolección y limpieza de datos del formulario.
        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        $rol = $_POST['rol'] ?? '';
        $nombre = trim($_POST['nombre_medico'] ?? '');
        $apellido = trim($_POST['apellido_medico'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $especialidad = ($_POST['rol'] === 'Medico') ? trim($_POST['especialidad'] ?? '') : null;
        $registro_medico = ($_POST['rol'] === 'Medico') ? trim($_POST['registro_medico'] ?? '') : null;

        // Validación de campos obligatorios.
        if (empty($rol) || empty($nombre) || empty($apellido) || empty($usuario)) {
            throw new Exception("Los campos de nombre, apellido, usuario y rol son obligatorios.");
        }
        if ($id_usuario === 0 && empty($password)) {
            throw new Exception("La contraseña es obligatoria para usuarios nuevos.");
        }

        // Verificación de que el nombre de usuario no esté repetido.
        $sql_check = "SELECT id_medico FROM usuarios WHERE usuario = ? AND id_medico != ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("si", $usuario, $id_usuario);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("El nombre de usuario '" . htmlspecialchars($usuario) . "' ya está en uso.");
        }
        $stmt_check->close();

        // Diferenciamos entre crear (INSERT) o actualizar (UPDATE).
        if ($id_usuario === 0) {
            // -- CREAR NUEVO USUARIO --
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (rol, nombre_medico, apellido_medico, usuario, password, especialidad, registro_medico) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssssss", $rol, $nombre, $apellido, $usuario, $password_hashed, $especialidad, $registro_medico);
            $success_message = '¡Usuario registrado exitosamente!';
        } else {
            // -- ACTUALIZAR USUARIO EXISTENTE --
            if (!empty($password)) {
                // Si se envió una nueva contraseña, se actualiza.
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET rol=?, nombre_medico=?, apellido_medico=?, usuario=?, password=?, especialidad=?, registro_medico=? WHERE id_medico=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssssssi", $rol, $nombre, $apellido, $usuario, $password_hashed, $especialidad, $registro_medico, $id_usuario);
            } else {
                // Si la contraseña está vacía, no se modifica en la base de datos.
                $sql = "UPDATE usuarios SET rol=?, nombre_medico=?, apellido_medico=?, usuario=?, especialidad=?, registro_medico=? WHERE id_medico=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssssssi", $rol, $nombre, $apellido, $usuario, $especialidad, $registro_medico, $id_usuario);
            }
            $success_message = '¡Usuario actualizado exitosamente!';
        }
        
        // Ejecución de la consulta de creación/actualización.
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => $success_message];
        } else {
            throw new Exception("Error al procesar la solicitud: " . $stmt->error);
        }
        $stmt->close();
    }

} catch (Exception $e) {
    // Si ocurre cualquier error en el proceso, se captura aquí.
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Se cierra la conexión y se envía la respuesta final en formato JSON.
$conexion->close();
echo json_encode($response);
?>