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
 * 2. CONEXIÓN Y CONSULTAS A LA BASE DE DATOS
 */
$conexion = conectarDB();

// --- CAMBIO: Se añade 'hc.motivo_consulta' a la consulta ---
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

// --- CAMBIO: Detectar si es una Nota de Control ---
$es_control = (isset($info['motivo_consulta']) && $info['motivo_consulta'] === 'NOTA DE CONTROL / EVOLUCION');

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

/**
 * 3. CLASE PDF PERSONALIZADA (HEREDA DE FPDF)
 */
class PDF extends FPDF {
    private $medicoInfo;
    private $esControl; // <-- NUEVO: Variable para saber el tipo

    function setMedicoInfo($info) { $this->medicoInfo = $info; }
    function setTipoConsulta($es_control) { $this->esControl = $es_control; } // <-- NUEVO: Setter

    // Cabecera del documento
    function Header() {
        $this->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode(NOMBRE_CENTRO_MEDICO), 0, 1, 'C');
        $this->Ln(5);
        $this->SetFillColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        
        // --- CAMBIO: Título del Header dinámico ---
        $titulo = $this->esControl ? 'RECETA DE CONTROL' : 'RECETARIO MEDICO';
        $this->Cell(0, 8, utf8_decode($titulo), 0, 1, 'C', true);
        
        $this->Ln(5);
    }

    // Pie de página del documento
    function Footer() {
        // --- CAMBIO: Se saca la firma de aquí ---
        $this->SetY(-15); // Posición a 1.5 cm del final
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 6, utf8_decode(DIRECCION_CENTRO_MEDICO . ' | ' . CONTACTO_CENTRO_MEDICO), 'T', 1, 'C'); // Línea superior (T)
    }
}

/**
 * 4. CREACIÓN Y CONFIGURACIÓN DEL DOCUMENTO PDF
 */
$pdf = new PDF('L', 'mm', 'A5'); // L=Landscape(Horizontal), A5
$pdf->setMedicoInfo($info); // Pasamos los datos del médico al PDF
$pdf->setTipoConsulta($es_control); // <-- NUEVO: Pasamos el tipo de consulta
$pdf->AddPage(); // Añadimos la primera página
$pdf->SetMargins(10, 10, 10); 
$pdf->SetAutoPageBreak(true, 20); // Margen inferior de 20mm (ya que el footer es más pequeño)
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);

/**
 * 5. SECCIÓN DE DATOS DEL PACIENTE
 */
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 6, 'Paciente:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(85, 6, utf8_decode(mb_convert_case($info['nombre'] . ' ' . $info['apellido'], MB_CASE_TITLE, 'UTF-8')), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 6, 'Fecha:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, date("d/m/Y", strtotime($info['fecha_emision'])), 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 6, 'Documento:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(85, 6, $info['numero_documento'], 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 6, 'Edad:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, calcularEdad($info['fecha_nacimiento']) . utf8_decode(' años'), 0, 1);
$pdf->Ln(5); 

/**
 * 6. TABLA DE MEDICAMENTOS
 */
$pdf->SetFillColor(230,230,230); 
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(60, 7, 'Medicamento', 1, 0, 'C', true); 
$pdf->Cell(100, 7, 'Dosis / Horario', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Cantidad', 1, 1, 'C', true); 

$pdf->SetFont('Arial', '', 9); 
if ($medicamentos_result->num_rows > 0) {
    while($med = $medicamentos_result->fetch_assoc()) {
        $pdf->Cell(60, 6, utf8_decode($med['nombre_medicamento']), 1, 0);
        $pdf->Cell(100, 6, utf8_decode($med['horario_dosis']), 1, 0);
        $pdf->Cell(30, 6, utf8_decode($med['cantidad']), 1, 1);
    }
} else {
    $pdf->Cell(190, 6, 'No se recetaron medicamentos.', 1, 1, 'C');
}
$pdf->Ln(5);

// --- CAMBIO: Mover la firma del Footer al cuerpo principal ---
// (Esto evita que se superponga con la tabla si es muy larga)
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, '_________________________', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 5, utf8_decode('Dr. ' . $info['nombre_medico'] . ' ' . $info['apellido_medico']), 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, utf8_decode($info['especialidad'] ?? ''), 0, 1, 'C');
$pdf->Cell(0, 4, utf8_decode('Reg. Médico ' . ($info['registro_medico'] ?? 'N/A') . ' - CC ' . ($info['cedula_medico'] ?? 'N/A')), 0, 1, 'C');
// --- FIN DEL BLOQUE DE FIRMA ---


/**
 * 7. GENERACIÓN DEL ARCHIVO PDF
 */
$pdf->Output('I', 'Receta_'.$info['numero_documento'].'.pdf');
?>