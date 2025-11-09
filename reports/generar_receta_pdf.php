<?php
session_start();
// Usamos __DIR__ para crear rutas absolutas y seguras
require_once __DIR__ . '/../core/fpdf/fpdf.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/funciones.php'; // Necesario para calcularEdad

/**
 * 1. VALIDACIÓN DEL ID DE HISTORIA
 */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) { 
    die('ID de consulta no válido.'); 
}
$id_historia = (int)$_GET['id'];

/**
 * 2. CONEXIÓN Y CONSULTAS A LA BASE DE DATOS (Se mantiene tu lógica)
 */
$conexion = conectarDB();

$sql_info = "SELECT p.nombre, p.apellido, p.numero_documento, p.sexo, p.fecha_nacimiento, 
                    m.nombre_medico, m.apellido_medico, m.especialidad, m.registro_medico, m.cedula_medico, 
                    rm.fecha_emision, hc.motivo_consulta
             FROM historias_clinicas hc
             JOIN pacientes p ON hc.paciente_documento = p.numero_documento
             JOIN usuarios m ON hc.id_medico = m.id_medico
             JOIN recetas_medicas rm ON hc.id_historia = rm.id_historia
             WHERE hc.id_historia = ?";
$stmt_info = $conexion->prepare($sql_info);
$stmt_info->bind_param("i", $id_historia);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$info) {
    die('No se encontró información de la receta para esta consulta.');
}

// Obtenemos la lista de medicamentos recetados para esta historia.
$sql_meds = "SELECT mr.nombre_medicamento, mr.horario_dosis, mr.cantidad 
             FROM medicamentos_recetados mr
             JOIN recetas_medicas rm ON mr.id_receta = rm.id_receta
             WHERE rm.id_historia = ?";
$stmt_meds = $conexion->prepare($sql_meds);
$stmt_meds->bind_param("i", $id_historia);
$stmt_meds->execute();
$medicamentos_result = $stmt_meds->get_result(); // Guardamos el resultado
$stmt_meds->close();
$conexion->close();

// Convertimos el resultado de medicamentos a un array para usarlo
$medicamentos = [];
while ($med = $medicamentos_result->fetch_assoc()) {
    $medicamentos[] = $med;
}


// --- INICIO DE MEDIDAS ---
// Estas son las medidas que me diste, convertidas a milímetros (divididas por 10).
// Si algo no cuadra en la impresión, solo ajusta estos números.

// Caja del Paciente
$pac_y = 26.5;  // <-- AJUSTE: 27mm - 0.5mm = 26.5mm
$pac_x = 39.5;  // <-- AJUSTE: 40mm - 0.5mm = 39.5mm
$date_x = 150;  // (Tu medida: 1500) -> 150mm desde el borde IZQUIERDO

// Tabla de Medicamentos
$table_y = 45;   // (Tu medida: 450) -> 45mm desde el borde SUPERIOR
$table_x = 15;   // (Tu medida: 150) -> 15mm desde el borde IZQUIERDO

// Anchos de las columnas
$w_col1 = 60;    // (Tu medida: 600) -> Ancho Columna Nombre
$w_col2 = 75;    // (Tu medida: 750) -> Ancho Columna Dosis
$w_col3 = 45;    // (Tu medida: 450) -> Ancho Columna Cantidad
// --- FIN DE MEDIDAS ---


/**
 * 3. CLASE PDF PERSONALIZADA (MODIFICADA PARA EL FORMATO)
 */
class PDF extends FPDF {
    
    // --- CAMBIO: Header vacío para papel membretado ---
    function Header() {
        // No imprimir nada. El papel ya tiene el encabezado.
    }

    // --- CAMBIO: Footer vacío para papel membretado ---
    function Footer() {
        // No imprimir nada. El papel ya tiene el pie de página.
    }

    // --- CAMBIO: Eliminamos la función NbLines() ya que no es fiable ---

} // Fin de la clase PDF


/**
 * 4. CREACIÓN Y CONFIGURACIÓN DEL DOCUMENTO PDF
 */
// --- CAMBIO CLAVE: Tamaño de página personalizado ---
// 'L' = Landscape (Horizontal)
// 'mm' = Milímetros
// [212, 163] = Ancho 21.2 cm, Alto 16.3 cm
$pdf = new PDF('L', 'mm', [212, 163]); 
$pdf->AddPage();
$pdf->SetMargins(0, 0, 0); // Sin márgenes, controlaremos todo con SetXY
$pdf->SetAutoPageBreak(false); // No queremos saltos de página automáticos
$pdf->SetTextColor(0,0,0); // Texto negro

/**
 * 5. SECCIÓN DE DATOS DEL PACIENTE (POSICIONAMIENTO ABSOLUTO)
 */

$nombre_completo = utf8_decode(mb_convert_case($info['nombre'] . ' ' . $info['apellido'], MB_CASE_TITLE, 'UTF-8'));
$fecha_emision = date("d/m/Y", strtotime($info['fecha_emision']));
$edad_paciente = calcularEdad($info['fecha_nacimiento']) . ' ' . utf8_decode('años'); // <-- AJUSTE: "años"
$documento_paciente = $info['numero_documento'];

// Fuentes (Usamos Helvetica, es estándar en FPDF y evita errores)
$pdf->SetFont('Helvetica', 'B', 10); // Fuente Negrita para Títulos
$ancho_titulo = 22; // Ancho fijo para "Paciente:", "Documento:"
$ancho_titulo_fecha = 12; // Ancho para "Fecha:", "Edad:"

// --- Primera Fila: Paciente y Fecha ---
$pdf->SetXY($pac_x, $pac_y);
$pdf->Cell($ancho_titulo, 6, 'Paciente:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(80, 6, $nombre_completo, 0, 0, 'L'); // 80mm de ancho para el nombre

$pdf->SetXY($date_x, $pac_y);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell($ancho_titulo_fecha, 6, 'Fecha:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(30, 6, $fecha_emision, 0, 1, 'L');

// --- Segunda Fila: Documento y Edad ---
$pdf->SetXY($pac_x, $pac_y + 6); // Bajar 6mm para la segunda línea
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell($ancho_titulo, 6, 'Documento:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(80, 6, $documento_paciente, 0, 0, 'L');

$pdf->SetXY($date_x, $pac_y + 6); // Bajar 6mm
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell($ancho_titulo_fecha, 6, 'Edad:', 0, 0, 'L');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(30, 6, $edad_paciente, 0, 1, 'L');


/**
 * 6. TABLA DE MEDICAMENTOS (CON LÓGICA DE FILA FLEXIBLE)
 */
 
// --- Títulos de las columnas ---
$pdf->SetXY($table_x, $table_y);
$pdf->SetFont('Helvetica', 'B', 10); // Negrita para títulos
$pdf->SetFillColor(230,230,230); // Un fondo gris claro
$border_header = 1; // <-- 1 = con borde
$fill_header = true; // <-- true = con fondo gris

$pdf->Cell($w_col1, 7, 'Medicamento', $border_header, 0, 'C', $fill_header); 
$pdf->Cell($w_col2, 7, 'Dosis / Horario', $border_header, 0, 'C', $fill_header);
$pdf->Cell($w_col3, 7, 'Cantidad', $border_header, 1, 'C', $fill_header); 
// --- FIN TÍTULOS ---

$pdf->SetFont('Helvetica', '', 9); 
$lineHeight = 6; // Altura de CADA línea de texto (en mm)
$widths = [$w_col1, $w_col2, $w_col3];

// --- CORRECCIÓN SUPERPOSICIÓN: 1. Obtener Y ANTES del loop ---
$current_y = $pdf->GetY();

if (count($medicamentos) > 0) {
    
    foreach($medicamentos as $med) {
        
        // --- INICIO DE LA CORRECCIÓN "GetMaxY" ---
        
        // 1. Guardar la Y inicial de la fila
        // (ya la tenemos en $current_y)
        
        // 2. Preparar textos
        $txt1 = utf8_decode($med['nombre_medicamento']);
        $txt2 = utf8_decode($med['horario_dosis']);
        $txt3 = utf8_decode($med['cantidad']);

        // 3. Dibujar las 3 celdas MultiCell en paralelo
        
        // Col 1: Medicamento
        $pdf->SetXY($table_x, $current_y);
        $pdf->MultiCell($widths[0], $lineHeight, $txt1, 0, 'J');
        $y_final_celda1 = $pdf->GetY(); // Guardar Y después de celda 1
        
        // Col 2: Dosis
        $pdf->SetXY($table_x + $widths[0], $current_y);
        $pdf->MultiCell($widths[1], $lineHeight, $txt2, 0, 'J');
        $y_final_celda2 = $pdf->GetY(); // Guardar Y después de celda 2
        
        // Col 3: Cantidad
        $pdf->SetXY($table_x + $widths[0] + $widths[1], $current_y);
        $pdf->MultiCell($widths[2], $lineHeight, $txt3, 0, 'J');
        $y_final_celda3 = $pdf->GetY(); // Guardar Y después de celda 3
        
        // 4. Determinar la Y máxima (la más baja en la página)
        $y_siguiente_fila = max($y_final_celda1, $y_final_celda2, $y_final_celda3);
        
        // 5. Calcular la altura real de la fila (para los bordes)
        $rowHeight = $y_siguiente_fila - $current_y;

        // 6. Dibujar los Bordes (Rectángulos)
        $border_debug = 1; // <-- ¡¡AQUÍ ESTÁ EL CAMBIO!!
        
        $pdf->Rect($table_x, $current_y, $widths[0], $rowHeight, $border_debug);
        $pdf->Rect($table_x + $widths[0], $current_y, $widths[1], $rowHeight, $border_debug);
        $pdf->Rect($table_x + $widths[0] + $widths[1], $current_y, $widths[2], $rowHeight, $border_debug);
        
        // 7. Mover el cursor a la posición de la siguiente fila
        $pdf->SetY($y_siguiente_fila);

        // --- FIN DE LA CORRECCIÓN ---
        
        // 8. Actualizar $current_y para el próximo loop
        $current_y = $y_siguiente_fila;
    }
    
    // Guardar la Y final para la firma
    $final_y = $current_y;

} else {
    // Si no hay medicamentos
    $pdf->Cell(array_sum($widths), 6, 'No se recetaron medicamentos.', 1, 1, 'C'); // Borde 1
    $final_y = $pdf->GetY();
}

// --- AÑADIDO: Bloque de la firma del médico ---
// Se posiciona 10mm debajo de la última fila de la tabla
$pdf->SetY($final_y + 10); 
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 10, '_________________________', 0, 1, 'C');
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->Cell(0, 5, utf8_decode('Dr. ' . $info['nombre_medico'] . ' ' . $info['apellido_medico']), 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell(0, 4, utf8_decode($info['especialidad'] ?? ''), 0, 1, 'C');
$pdf->Cell(0, 4, utf8_decode('Reg. Médico ' . ($info['registro_medico'] ?? 'N/A') . ' - CC ' . ($info['cedula_medico'] ?? 'N/A')), 0, 1, 'C');
// --- FIN DEL BLOQUE DE FIRMA ---


/**
 * 7. GENERACIÓN DEL ARCHIVO PDF
 */
$pdf->Output('I', 'Receta_'.$info['numero_documento'].'.pdf');
?>