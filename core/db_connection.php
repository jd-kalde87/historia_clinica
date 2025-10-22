<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Usuario por defecto de XAMPP
define('DB_PASS', '');     // Contraseña por defecto de XAMPP es vacía
define('DB_NAME', 'historia_clinica_db');

/**
 * Función para establecer la conexión con la base de datos.
 * @return mysqli|false El objeto de conexión en caso de éxito, o false si falla.
 */
function conectarDB() {
    // Crear la conexión
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Establecer el juego de caracteres a UTF-8 para evitar problemas con tildes y ñ
    $conexion->set_charset("utf8mb4");

    // Verificar si hubo un error en la conexión
    if ($conexion->connect_error) {
        // Detiene la ejecución y muestra el error. En un sistema en producción, 
        // esto debería manejarse de forma más elegante (ej. log de errores).
        die("Error de Conexión: " . $conexion->connect_error);
    }

    return $conexion;
}
?>