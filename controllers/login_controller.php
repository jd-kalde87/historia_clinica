<?php
// Iniciar la sesión es SÚPER IMPORTANTE.
session_start();

// Requerir el archivo de conexión a la base de datos
require_once '../core/db_connection.php';

// Verificar si se enviaron datos por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (empty($usuario) || empty($password)) {
        header('Location: ../index.php?error=campos_vacios');
        exit();
    }
    
    $conexion = conectarDB();

    $sql = "SELECT id_medico, nombre_medico, apellido_medico, password, rol FROM usuarios WHERE usuario = ?";
    $stmt = $conexion->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario_data = $resultado->fetch_assoc();
            
            // --- INICIO DE LA MODIFICACIÓN: Lógica de Contraseña ---
            // Verificamos la contraseña. Usamos una comprobación simple para el admin temporalmente.
            $password_valida = false;
            if ($usuario_data['rol'] === 'Administrador' && $password === $usuario_data['password']) {
                // Si es el admin, comparamos la contraseña en texto plano (temporal)
                $password_valida = true;
            } elseif (password_verify($password, $usuario_data['password'])) {
                // Para todos los demás, usamos el método seguro
                $password_valida = true;
            }
            // --- FIN DE LA MODIFICACIÓN ---

            if ($password_valida) {
                // Contraseña correcta. ¡Acceso concedido!
                
                $_SESSION['id_usuario'] = $usuario_data['id_medico'];
                $_SESSION['nombre_completo_medico'] = $usuario_data['nombre_medico'] . ' ' . $usuario_data['apellido_medico'];
                $_SESSION['rol'] = $usuario_data['rol'];
                $_SESSION['autenticado'] = true;

                // --- INICIO DE LA MODIFICACIÓN: Redirección por Rol ---
                switch ($usuario_data['rol']) {
                    case 'Medico':
                        header('Location: ../views/dashboard.php');
                        break;
                    case 'Secretaria':
                        header('Location: ../views/dashboard_secretaria.php');
                        break;
                    case 'Administrador':
                        // Redirigimos al nuevo dashboard de administración
                        header('Location: ../views/dashboard_admin.php');
                        break;
                    default:
                        // Rol no reconocido
                        header('Location: ../index.php?error=rol_invalido');
                        break;
                }
                // --- FIN DE LA MODIFICACIÓN ---
                exit();

            } else {
                header('Location: ../index.php?error=credenciales_invalidas');
                exit();
            }
        } else {
            header('Location: ../index.php?error=credenciales_invalidas');
            exit();
        }
        $stmt->close();
    }
    $conexion->close();

} else {
    header('Location: ../index.php');
    exit();
}
?>