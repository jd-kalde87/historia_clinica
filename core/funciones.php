<?php
require_once 'config.php'; // <-- AÑADIR ESTA LÍNEA
/**
 * Genera un mensaje de texto formateado y codificado para WhatsApp 
 * a partir de los datos de una consulta y una receta.
 *
 * @param array $consulta Array con los datos de la consulta y del paciente.
 * @param array $medicamentos Array con los medicamentos de la receta.
 * @return string El mensaje completo y codificado para usar en una URL.
 */
function generarMensajeWhatsApp($consulta, $medicamentos, $codificar = true) {
    // Construimos el mensaje de texto plano usando los datos recibidos
    $mensaje = "*Resumen de tu Consulta Médica* 🩺\n\n";
    $mensaje .= "*Paciente:* " . ($consulta['nombre'] ?? '') . " " . ($consulta['apellido'] ?? '') . "\n";
    $mensaje .= "*Fecha:* " . date("d/m/Y", strtotime($consulta['fecha_consulta'])) . "\n";
    
    // --- CORRECCIÓN AQUÍ: 'apellido_medico' ---
    $mensaje .= "*Médico:* Dr(a). " . ($consulta['nombre_medico'] ?? '') . " " . ($consulta['apellido_medico'] ?? '') . "\n\n";
    
    $mensaje .= "--------------------------------------\n\n";

    if (!empty($consulta['diagnostico_principal'])) {
        $mensaje .= "*Diagnóstico Principal:*\n" . $consulta['diagnostico_principal'] . "\n\n";
    }
    if (!empty($consulta['tratamiento'])) {
        $mensaje .= "*Tratamiento a Seguir:*\n" . $consulta['tratamiento'] . "\n\n";
    }

    if (!empty($medicamentos)) {
        $mensaje .= "*Receta Médica* 💊\n";
        foreach ($medicamentos as $medicamento) {
            $mensaje .= "• *" . $medicamento['nombre_medicamento'] . "*\n";
            $mensaje .= "  Dosis: " . $medicamento['horario_dosis'] . "\n";
            $mensaje .= "  Cantidad: " . $medicamento['cantidad'] . "\n\n";
        }
    }

    $mensaje .= "--------------------------------------\n";
    $mensaje .= "_Este es un resumen informativo. Guarda este mensaje para tus registros._";

    // Devolvemos el mensaje codificado o plano según se pida
    return $codificar ? urlencode($mensaje) : $mensaje;
}
function calcularEdad($fechaNacimiento) {
    if(!$fechaNacimiento) return 'N/A';
    $nacimiento = new DateTime($fechaNacimiento);
    $ahora = new DateTime();
    $edad = $ahora->diff($nacimiento);
    return $edad->y;
}
/**
 * Genera un mensaje de texto para confirmar una cita agendada.
 *
 * @param array $info Array con los datos de la cita (nombre paciente, médico, fecha, hora).
 * @param bool $codificar Si es true, codifica el mensaje para URL.
 * @return string El mensaje formateado.
 */



function generarMensajeConfirmacionCita($info, $codificar = true) {
    // --- INICIO DE LA CORRECCIÓN ---
    // 1. Especificamos la zona horaria de Colombia.
    $zonaHoraria = new DateTimeZone('America/Bogota');
    
    // 2. Creamos el objeto de fecha, asegurándonos de que se interprete en esa zona horaria.
    $fecha_objeto = new DateTime($info['fecha_cita'], $zonaHoraria);
    // --- FIN DE LA CORRECCIÓN ---

    // 3. Creamos un formateador para español (es_ES) con el formato deseado.
    $formateador = new IntlDateFormatter(
        'es_ES',
        IntlDateFormatter::FULL, // Formato de fecha completo
        IntlDateFormatter::NONE, // Sin formato de hora
        $zonaHoraria,            // Usamos la misma zona horaria
        IntlDateFormatter::GREGORIAN,
        'eeee, d \'de\' MMMM \'de\' yyyy' // Patrón: "lunes, 25 de diciembre de 2025"
    );
    
    // 4. Aplicamos el formato
    $fecha_formateada = $formateador->format($fecha_objeto);
    
    $hora_formateada = date('h:i A', strtotime($info['hora_cita']));

    $mensaje = "🗓️ *Confirmación de Cita*\n\n";
    $mensaje .= "Hola *" . ($info['nombre_paciente'] ?? '') . " " . ($info['apellido_paciente'] ?? '') . "*,\n\n";
    $mensaje .= "Te confirmamos tu cita en *" . NOMBRE_CENTRO_MEDICO . "*:\n\n";
    $mensaje .= "🩺 *Médico:* Dr(a). " . ($info['nombre_medico'] ?? '') . " " . ($info['apellido_medico'] ?? '') . "\n";
    $mensaje .= "🗓️ *Fecha:* " . ucfirst($fecha_formateada) . "\n";
    $mensaje .= "⏰ *Hora:* " . $hora_formateada . "\n\n";
    $mensaje .= "Por favor, llega 10 minutos antes. ¡Te esperamos!";

    return $codificar ? urlencode($mensaje) : $mensaje;

}