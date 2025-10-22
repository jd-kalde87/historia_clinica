<?php
// 1. Incluimos la conexión a la base de datos
require_once __DIR__ . '/db_connection.php';

try {
    // 2. Nos conectamos a la BD
    $conexion_settings = conectarDB();
    
    // 3. Obtenemos todos los ajustes de la nueva tabla 'configuracion'
    $sql = "SELECT clave, valor FROM configuracion";
    $resultado = $conexion_settings->query($sql);
    
    $configuraciones = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $configuraciones[$fila['clave']] = $fila['valor'];
        }
    }
    $conexion_settings->close();

    // 4. Definimos las constantes dinámicamente
    define('NOMBRE_CENTRO_MEDICO', $configuraciones['NOMBRE_CENTRO_MEDICO'] ?? 'Nombre no definido');
    define('DIRECCION_CENTRO_MEDICO', $configuraciones['DIRECCION_CENTRO_MEDICO'] ?? 'Dirección no definida');
    define('CONTACTO_CENTRO_MEDICO', $configuraciones['CONTACTO_CENTRO_MEDICO'] ?? 'Contacto no definido');
    
    // Definimos los colores (los guardamos como arrays)
    define('COLOR_PRIMARY', [
        $configuraciones['COLOR_PRIMARY_R'] ?? 23,
        $configuraciones['COLOR_PRIMARY_G'] ?? 162,
        $configuraciones['COLOR_PRIMARY_B'] ?? 184
    ]);
    
    define('COLOR_DARK', [
        $configuraciones['COLOR_DARK_R'] ?? 52,
        $configuraciones['COLOR_DARK_G'] ?? 58,
        $configuraciones['COLOR_DARK_B'] ?? 64
    ]);

} catch (Exception $e) {
    // Si la BD falla, definimos valores de emergencia para que la app no se rompa
    define('NOMBRE_CENTRO_MEDICO', 'Error al Cargar Configuración');
    define('DIRECCION_CENTRO_MEDICO', 'Error de BD');
    define('CONTACTO_CENTRO_MEDICO', $e->getMessage());
    define('COLOR_PRIMARY', [23, 162, 184]);
    define('COLOR_DARK', [52, 58, 64]);
}

// También incluimos BASE_URL desde el archivo config.php original
require_once __DIR__ . '/config.php';
?>