<?php
session_start();
// Usamos __DIR__ para crear rutas absolutas y seguras
require_once __DIR__ . '/../core/fpdf/fpdf.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/funciones.php'; // Necesario para calcularEdad

/**
 * 1. VALIDACIÓN DEL ID DE HISTORIA
 * Aseguramos que se reciba un ID numérico válido por GET.
 */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) { 
    die('ID de consulta no válido.'); 
}
$id_historia = (int)$_GET['id'];

/**
 * 2. CONEXIÓN Y CONSULTAS A LA BASE DE DATOS
 * Obtenemos datos del paciente, médico y la fecha de emisión de la receta.
 */
$conexion = conectarDB();
$sql_info = "SELECT p.nombre, p.apellido, p.numero_documento, p.sexo, p.fecha_nacimiento, 
                    m.nombre_medico, m.apellido_medico, m.especialidad, m.registro_medico, m.cedula_medico, 
                    rm.fecha_emision
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

// Si no se encontró información básica, detenemos la ejecución.
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

/**
 * 3. CLASE PDF PERSONALIZADA (HEREDA DE FPDF)
 * Define la estructura del Header (cabecera) y Footer (pie de página) del PDF de la receta.
 */
class PDF extends FPDF {
    private $medicoInfo; // Variable para guardar datos del médico para el footer
    function setMedicoInfo($info) { $this->medicoInfo = $info; }

    // Cabecera del documento
    function Header() {
        $this->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode(NOMBRE_CENTRO_MEDICO), 0, 1, 'C');
        $this->Ln(5);
        $this->SetFillColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'RECETARIO MEDICO', 0, 1, 'C', true);
        $this->Ln(5);
    }

    // Pie de página del documento
    function Footer() {
        $this->SetY(-35); // Posiciona el pie de página
        // Firma del médico centrada
        $this->Cell(0, 10, '_________________________', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, utf8_decode('Dr. ' . $this->medicoInfo['nombre_medico'] . ' ' . $this->medicoInfo['apellido_medico']), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, utf8_decode($this->medicoInfo['especialidad'] ?? ''), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Reg. Médico ' . ($this->medicoInfo['registro_medico'] ?? 'N/A') . ' - CC ' . ($this->medicoInfo['cedula_medico'] ?? 'N/A')), 0, 1, 'C');
        // Información de contacto del centro médico
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 6, utf8_decode(DIRECCION_CENTRO_MEDICO . ' | ' . CONTACTO_CENTRO_MEDICO), 'T', 1, 'C'); // Línea superior (T)
    }
}

/**
 * 4. CREACIÓN Y CONFIGURACIÓN DEL DOCUMENTO PDF
 * Se instancia la clase PDF en tamaño A5 Horizontal ('L') y se configuran márgenes/fuentes.
 */
$pdf = new PDF('L', 'mm', 'A5'); // L=Landscape(Horizontal), mm=milímetros, A5=Tamaño A5
$pdf->setMedicoInfo($info); // Pasamos los datos del médico al PDF
$pdf->AddPage(); // Añadimos la primera página
$pdf->SetMargins(10, 10, 10); // Márgenes más pequeños para A5
$pdf->SetAutoPageBreak(true, 40); // Salto de página con margen inferior de 40mm
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);

/**
 * 5. SECCIÓN DE DATOS DEL PACIENTE
 * Se imprime la información básica del paciente y la fecha de emisión.
 */
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 6, 'Paciente:', 0, 0);
$pdf->SetFont('Arial', '', 10);
// Nombre del paciente en Estilo Título
$pdf->Cell(85, 6, utf8_decode(mb_convert_case($info['nombre'] . ' ' . $info['apellido'], MB_CASE_TITLE, 'UTF-8')), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 6, 'Fecha:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, date("d/m/Y", strtotime($info['fecha_emision'])), 0, 1); // Nueva línea

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(25, 6, 'Documento:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(85, 6, $info['numero_documento'], 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 6, 'Edad:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, calcularEdad($info['fecha_nacimiento']) . utf8_decode(' años'), 0, 1); // Nueva línea
$pdf->Ln(5); // Espacio antes de la tabla

/**
 * 6. TABLA DE MEDICAMENTOS
 * Se crea la cabecera de la tabla y se itera sobre los resultados de la consulta
 * para añadir cada medicamento a la tabla.
 */
$pdf->SetFillColor(230,230,230); // Color de fondo gris claro para la cabecera
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(60, 7, 'Medicamento', 1, 0, 'C', true); // Celda con borde (1), centrada (C), con fondo (true)
$pdf->Cell(100, 7, 'Dosis / Horario', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Cantidad', 1, 1, 'C', true); // El último '1' indica nueva línea

$pdf->SetFont('Arial', '', 9); // Fuente más pequeña para el contenido
if ($medicamentos_result->num_rows > 0) {
    while($med = $medicamentos_result->fetch_assoc()) {
        // Añadimos cada fila de medicamento
        $pdf->Cell(60, 6, utf8_decode($med['nombre_medicamento']), 1, 0);
        $pdf->Cell(100, 6, utf8_decode($med['horario_dosis']), 1, 0);
        $pdf->Cell(30, 6, utf8_decode($med['cantidad']), 1, 1); // Nueva línea
    }
} else {
    // Mensaje si no hay medicamentos
    $pdf->Cell(190, 6, 'No se recetaron medicamentos.', 1, 1, 'C');
}
$pdf->Ln(5); // Espacio después de la tabla

/**
 * 7. GENERACIÓN DEL ARCHIVO PDF
 * Se envía el PDF directamente al navegador para visualización ('I').
 */
$pdf->Output('I', 'Receta_'.$info['numero_documento'].'.pdf');
?>