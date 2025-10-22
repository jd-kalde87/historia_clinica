<?php
session_start();
// Usamos __DIR__ para crear una ruta absoluta y segura
require_once __DIR__ . '/../core/fpdf/fpdf.php';
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/config.php';


// Validar que se reciba un ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die('ID de consulta no válido.');
}
$id_historia = $_GET['id'];
$conexion = conectarDB();

// SQL actualizado para traer los nuevos datos del médico
$sql = "SELECT hc.*, p.*, 
            m.nombre_medico, m.apellido_medico, m.especialidad, m.registro_medico, m.cedula_medico
        FROM historias_clinicas hc
        JOIN pacientes p ON hc.paciente_documento = p.numero_documento
        JOIN usuarios m ON hc.id_medico = m.id_medico
        WHERE hc.id_historia = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_historia);
$stmt->execute();
$consulta = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();

function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento)) return 'N/A';
    $nacimiento = new DateTime($fechaNacimiento);
    $ahora = new DateTime();
    $edad = $ahora->diff($nacimiento);
    return $edad->y;
}

class PDF extends FPDF
{
    private $medicoInfo;
    function setMedicoInfo($info) { $this->medicoInfo = $info; }

    function Header() {
        $this->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode(NOMBRE_CENTRO_MEDICO), 0, 1, 'C');
        $this->Ln(5);

        $this->SetFillColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'RESUMEN DE HISTORIA CLINICA', 0, 1, 'C', true);
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-40); // Sube el footer para dar espacio
        // Firma del médico CENTRADA
        $this->Cell(0, 10, '_________________________', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 9);
        $nombreMedicoFirma = mb_convert_case($this->medicoInfo['nombre_medico'] . ' ' . $this->medicoInfo['apellido_medico'], MB_CASE_TITLE, 'UTF-8');
        $this->Cell(0, 5, utf8_decode('Dr(a). ' . $nombreMedicoFirma), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, utf8_decode($this->medicoInfo['especialidad'] ?? 'N/A'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Reg. Médico ' . ($this->medicoInfo['registro_medico'] ?? 'N/A') . ' - CC ' . ($this->medicoInfo['cedula_medico'] ?? 'N/A')), 0, 1, 'C');
        $this->Ln(3);
        // Pie de página con dirección y contacto
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode(DIRECCION_CENTRO_MEDICO . ' | ' . CONTACTO_CENTRO_MEDICO), 'T', 1, 'C');
    }

    function Seccion($titulo, $contenido) {
        $this->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240,240,240);
        $this->Cell(0, 7, utf8_decode($titulo), 'TLR', 1, 'L', true);
        $this->SetFont('Arial', '', 10);
        $contenido_seguro = !empty($contenido) ? $contenido : 'No registrado.';
        $this->MultiCell(0, 6, utf8_decode($contenido_seguro), 'LRB');
        $this->Ln(3);
    }
}

// Se establece el tamaño a Carta (Letter)
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->setMedicoInfo($consulta);
$pdf->AddPage();
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);

// --- SECCIÓN DE DATOS DEL PACIENTE (MODIFICADA) ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 7, 'Nombre Completo:', 'LTB', 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, utf8_decode(mb_convert_case($consulta['nombre'] . ' ' . $consulta['apellido'], MB_CASE_TITLE, 'UTF-8')), 'RTB', 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 7, 'Documento:', 'LB', 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(55, 7, utf8_decode($consulta['tipo_documento'] . ' ' . $consulta['numero_documento']), 'B', 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(15, 7, 'Edad:', 'B', 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(30, 7, calcularEdad($consulta['fecha_nacimiento']) . utf8_decode(' años'), 'B', 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(15, 7, 'Sexo:', 'B', 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, utf8_decode(ucfirst(strtolower($consulta['sexo'] ?? 'N/A'))), 'RB', 1);
$pdf->Ln(8);
// --- FIN DE SECCIÓN MODIFICADA ---

// --- SECCIÓN DE DATOS DE LA CONSULTA ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 7, 'Fecha de Consulta:', 'LTB', 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, date("d/m/Y h:i A", strtotime($consulta['fecha_consulta'])), 'RTB', 1);
$pdf->Ln(8);
// --- FIN DE SECCIÓN ---

// Secciones de la consulta
$pdf->Seccion('Motivo de Consulta:', $consulta['motivo_consulta'] ?? '');
$pdf->Seccion('Enfermedad Actual:', $consulta['enfermedad_actual'] ?? '');

// --- ESTE ES EL BLOQUE DE SIGNOS VITALES QUE YA ESTÁ INCLUIDO ---
$pdf->Seccion('Signos Vitales:', 
    'Peso: ' . ($consulta['peso_kg'] ?? 'N/A') . ' kg | ' .
    'Talla: ' . ($consulta['talla_cm'] ?? 'N/A') . ' cm | ' .
    'IMC: ' . ($consulta['imc'] ?? 'N/A') . "\n" .
    'T.A: ' . ($consulta['tension_arterial'] ?? 'N/A') . ' | ' .
    'F.C: ' . ($consulta['frecuencia_cardiaca'] ?? 'N/A') . ' lat/min | ' .
    'F.R: ' . ($consulta['frecuencia_respiratoria'] ?? 'N/A') . ' resp/min | ' .
    'Temp: ' . ($consulta['temperatura_c'] ?? 'N/A') . ' ' . utf8_decode('°C')
);
// --- FIN DEL BLOQUE DE SIGNOS VITALES ---

$pdf->Seccion('Hallazgos del Examen Fisico:', $consulta['hallazgos_examen_fisico'] ?? '');
$pdf->Seccion('Diagnostico Principal:', $consulta['diagnostico_principal'] ?? '');
$pdf->Seccion('Tratamiento a Seguir:', $consulta['tratamiento'] ?? '');
$pdf->Seccion('Solicitud de Examenes:', $consulta['solicitud_examenes'] ?? '');

$pdf->Output('I', 'Resumen_Consulta_'.$consulta['codigo_historia'].'.pdf');
?>