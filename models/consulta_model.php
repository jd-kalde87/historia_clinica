<?php

/**
 * Obtiene todos los detalles de una consulta específica por su ID.
 *
 * @param mysqli $conexion El objeto de conexión a la base de datos.
 * @param int $id_historia El ID de la consulta a buscar.
 * @return array|false Un array con los datos de la consulta, medicamentos y archivos, o false si no se encuentra.
 */
function obtenerDetallesConsulta($conexion, $id_historia) {
    // 1. Buscar la información principal de la consulta
    // === INICIO DE LA CORRECCIÓN ===
    // Se cambió 'JOIN medicos m' por 'JOIN usuarios u'
    $sql_consulta = "SELECT 
                        hc.*, 
                        p.nombre, p.apellido, p.tipo_documento, p.numero_documento, 
                        p.fecha_nacimiento, p.sexo, p.telefono_whatsapp,
                        u.nombre_medico, u.apellido_medico
                     FROM historias_clinicas hc
                     JOIN pacientes p ON hc.paciente_documento = p.numero_documento
                     JOIN usuarios u ON hc.id_medico = u.id_medico
                     WHERE hc.id_historia = ?";
    // === FIN DE LA CORRECCIÓN ===
    
    $stmt_consulta = $conexion->prepare($sql_consulta);
    $stmt_consulta->bind_param("i", $id_historia);
    $stmt_consulta->execute();
    $resultado = $stmt_consulta->get_result();

    if ($resultado->num_rows === 0) {
        $stmt_consulta->close();
        return false; // Si no se encuentra la consulta, devolvemos false.
    }
    $consulta = $resultado->fetch_assoc();
    $stmt_consulta->close();

    // 2. Buscar los medicamentos de la receta
    $sql_receta = "SELECT mr.* FROM medicamentos_recetados mr
                   JOIN recetas_medicas rm ON mr.id_receta = rm.id_receta
                   WHERE rm.id_historia = ?";
    $stmt_receta = $conexion->prepare($sql_receta);
    $stmt_receta->bind_param("i", $id_historia);
    $stmt_receta->execute();
    $resultado_receta = $stmt_receta->get_result();
    $medicamentos = [];
    while ($fila = $resultado_receta->fetch_assoc()) {
        $medicamentos[] = $fila;
    }
    $stmt_receta->close();

    // 3. Buscar los archivos adjuntos
    $sql_archivos = "SELECT id_archivo, nombre_archivo, ruta_archivo FROM archivos_adjuntos WHERE id_historia = ?";
    $stmt_archivos = $conexion->prepare($sql_archivos);
    $stmt_archivos->bind_param("i", $id_historia);
    $stmt_archivos->execute();
    $resultado_archivos = $stmt_archivos->get_result();
    $archivos = [];
    while($fila = $resultado_archivos->fetch_assoc()){
        $archivos[] = $fila;
    }
    $stmt_archivos->close();

    // 4. Devolver todos los datos en un solo array
    return [
        'consulta' => $consulta,
        'medicamentos' => $medicamentos,
        'archivos' => $archivos
    ];
}

?>