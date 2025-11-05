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

// SQL (ya trae todos los campos que necesitamos)
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

// --- CAMBIO: Detectar si es una Nota de Control ---
$es_control = (isset($consulta['motivo_consulta']) && $consulta['motivo_consulta'] === 'NOTA DE CONTROL / EVOLUCION');


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
    private $esControl; // Variable para saber el tipo de PDF

    function setMedicoInfo($info) { $this->medicoInfo = $info; }
    function setTipoConsulta($es_control) { $this->esControl = $es_control; } // Setter para el tipo

    function Header() {
        $this->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode(NOMBRE_CENTRO_MEDICO), 0, 1, 'C');
        $this->Ln(5);

        $this->SetFillColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        
        $titulo = $this->esControl ? 'RESUMEN DE CONTROL MEDICO' : 'RESUMEN DE HISTORIA CLINICA';
        $this->Cell(0, 8, utf8_decode($titulo), 0, 1, 'C', true);
        
        $this->Ln(5);
    }

    // --- CAMBIO: Se modifica el Footer ---
    function Footer() {
        // El footer ahora solo imprime la dirección y contacto, en la parte más baja.
        $this->SetY(-15); // Posición a 1.5 cm del final
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, utf8_decode(DIRECCION_CENTRO_MEDICO . ' | ' . CONTACTO_CENTRO_MEDICO), 'T', 1, 'C');
    }
    // --- FIN DEL CAMBIO EN FOOTER ---

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
    
    function SetBadgeColor($tipo = 'normal') {
        switch ($tipo) {
            case 'success':
                $this->SetFillColor(212, 237, 218);
                $this->SetTextColor(21, 87, 36);
                break;
            case 'warning':
                $this->SetFillColor(255, 243, 205);
                $this->SetTextColor(133, 100, 4);
                break;
            case 'danger':
                $this.SetFillColor(248, 215, 218);
                $this.SetTextColor(114, 28, 36);
                break;
            default:
                $this.SetFillColor(230, 230, 230);
                $this.SetTextColor(50, 50, 50);
                break;
        }
    }

    function GetTipoPorClasificacion($clasificacion) {
        if (empty($clasificacion) || $clasificacion == 'N/A') return 'normal';
        $clasificacion = strtolower($clasificacion);
        
        if (strpos($clasificacion, 'normal') !== false) return 'success';
        if (strpos($clasificacion, 'sobrepeso') !== false || strpos($clasificacion, 'elevada') !== false || strpos($clasificacion, 'hipertensión grado 1') !== false) return 'warning';
        if (strpos($clasificacion, 'obesidad') !== false || strpos($clasificacion, 'crisis') !== false || strpos($clasificacion, 'hipertensión grado 2') !== false) return 'danger';
        return 'normal';
    }
    
    function GetClasificacionTFG($tfg_valor) {
        if ($tfg_valor === null || $tfg_valor == 0) return ['texto' => 'N/A', 'tipo' => 'normal'];
        if ($tfg_valor >= 90) return ['texto' => 'Estadio G1 (>= 90)', 'tipo' => 'success'];
        if ($tfg_valor >= 60) return ['texto' => 'Estadio G2 (60-89)', 'tipo' => 'success'];
        if ($tfg_valor >= 45) return ['texto' => 'Estadio G3a (45-59)', 'tipo' => 'warning'];
        if ($tfg_valor >= 30) return ['texto' => 'Estadio G3b (30-44)', 'tipo' => 'warning'];
        if ($tfg_valor >= 15) return ['texto' => 'Estadio G4 (15-29)', 'tipo' => 'danger'];
        return ['texto' => 'Estadio G5 (< 15)', 'tipo' => 'danger'];
    }
}

// Se establece el tamaño a Carta (Letter)
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->setMedicoInfo($consulta); 
$pdf->setTipoConsulta($es_control); 
$pdf->AddPage();
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);

// --- SECCIÓN DE DATOS DEL PACIENTE (Se muestra siempre) ---
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

// --- SECCIÓN DE DATOS DE LA CONSULTA ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 7, 'Fecha de Consulta:', 'LTB', 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, date("d/m/Y h:i A", strtotime($consulta['fecha_consulta'])), 'RTB', 1);
$pdf->Ln(8);

// --- Lógica condicional para mostrar secciones ---

if ($es_control) {
    // --- VISTA SI ES UN CONTROL ---
    $pdf->Seccion('Evolucion / Nota de Control:', $consulta['enfermedad_actual'] ?? '');
    
} else {
    // --- VISTA SI ES UNA CONSULTA COMPLETA ---
    $pdf->Seccion('Motivo de Consulta:', $consulta['motivo_consulta'] ?? '');
    $pdf->Seccion('Enfermedad Actual:', $consulta['enfermedad_actual'] ?? '');
    
    // --- INICIO DEL BLOQUE DE SIGNOS VITALES (Solo para consulta completa) ---
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
    $pdf->Cell(0, 7, utf8_decode('Signos Vitales y Antropometría'), 'TLR', 1, 'L', true);

    $c1 = 49; $c2 = 49; $c3 = 49; $c4 = 49; 
    $alto_fila_titulo = 5; 
    $alto_fila_valor = 6;  

    // Fila 1: Antropometría
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
    $pdf->Cell($c1, $alto_fila_titulo, 'Peso:', 'L', 0);
    $pdf->Cell($c2, $alto_fila_titulo, 'Talla:', 0, 0);
    $pdf->Cell($c3, $alto_fila_titulo, 'IMC:', 0, 0);
    $pdf->Cell($c4, $alto_fila_titulo, utf8_decode('Clasificación IMC:'), 'R', 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['peso_kg'] ?? 'N/A') . ' kg', 'L', 0);
    $pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['talla_cm'] ?? 'N/A') . ' cm', 0, 0);
    $pdf->Cell($c3, $alto_fila_valor, utf8_decode($consulta['imc'] ?? 'N/A'), 0, 0);
    $clasif_imc = $consulta['imc_clasificacion'] ?? 'N/A';
    $pdf->SetBadgeColor($pdf->GetTipoPorClasificacion($clasif_imc));
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($c4, $alto_fila_valor, utf8_decode($clasif_imc), 'R', 1, 'C', true);
    $pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);

    // Fila 2: Tensión Arterial
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($c1, $alto_fila_titulo, utf8_decode('T.A. Sistólica:'), 'L', 0);
    $pdf->Cell($c2, $alto_fila_titulo, utf8_decode('T.A. Diastólica:'), 0, 0);
    $pdf->Cell($c3 + $c4, $alto_fila_titulo, utf8_decode('Clasificación HTA:'), 'R', 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['tension_sistolica'] ?? 'N/A') . ' mmHg', 'L', 0);
    $pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['tension_diastolica'] ?? 'N/A') . ' mmHg', 0, 0);
    $clasif_hta = $consulta['clasificacion_hta'] ?? 'N/A';
    $pdf->SetBadgeColor($pdf->GetTipoPorClasificacion($clasif_hta));
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($c3 + $c4, $alto_fila_valor, utf8_decode($clasif_hta), 'R', 1, 'C', true);
    $pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);

    // Fila 3: Vitales Complementarios
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($c1, $alto_fila_titulo, 'Frec. Cardiaca:', 'L', 0);
    $pdf->Cell($c2, $alto_fila_titulo, 'Frec. Respiratoria:', 0, 0);
    $pdf->Cell($c3 + $c4, $alto_fila_titulo, 'Temperatura:', 'R', 1); 
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['frecuencia_cardiaca'] ?? 'N/A') . ' lat/min', 'L', 0);
    $pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['frecuencia_respiratoria'] ?? 'N/A') . ' resp/min', 0, 0);
    $pdf->Cell($c3 + $c4, $alto_fila_valor, utf8_decode($consulta['temperatura_c'] ?? 'N/A') . ' ' . utf8_decode('°C'), 'R', 1);

    // Fila 4: Laboratorios y Cálculos
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($c1, $alto_fila_titulo, 'Hb Glicosilada (HbA1c %):', 'L', 0);
    $pdf->Cell($c2, $alto_fila_titulo, utf8_decode('Creatinina Sérica (mg/dL):'), 0, 0);
    $pdf->Cell($c3, $alto_fila_titulo, 'TFG (CKD-EPI):', 0, 0);
    $pdf->Cell($c4, $alto_fila_titulo, utf8_decode('Clasificación TFG:'), 'R', 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['hemoglobina_glicosilada'] ?? 'N/A') . ' %', 'LB', 0);
    $pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['creatinina_serica'] ?? 'N/A') . ' mg/dL', 'B', 0);
    $pdf->Cell($c3, $alto_fila_valor, utf8_decode($consulta['filtrado_glomerular_ckd_epi'] ?? 'N/A'), 'B', 0);
    $clasif_tfg = $pdf->GetClasificacionTFG($consulta['filtrado_glomerular_ckd_epi'] ?? null);
    $pdf->SetBadgeColor($clasif_tfg['tipo']);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($c4, $alto_fila_valor, utf8_decode($clasif_tfg['texto']), 'RB', 1, 'C', true);
    $pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
    $pdf->Ln(3);
    // --- FIN DEL BLOQUE DE SIGNOS VITALES ---

    $pdf->Seccion('Hallazgos del Examen Fisico:', $consulta['hallazgos_examen_fisico'] ?? '');
    
} // --- FIN DEL IF/ELSE PRINCIPAL ---


// --- Secciones Comunes (se muestran siempre) ---
$pdf->Seccion('Diagnostico Principal:', $consulta['diagnostico_principal'] ?? '');
$pdf->Seccion('Tratamiento a Seguir:', $consulta['tratamiento'] ?? '');
$pdf->Seccion('Solicitud de Examenes:', $consulta['solicitud_examenes'] ?? '');


// --- CAMBIO: Mover la firma del Footer al cuerpo principal ---
// (Esto evita que se superponga con el contenido si es muy largo)
// Se añade un espacio generoso antes de la firma.
$pdf->Ln(15); 

// Asegurarse de que haya suficiente espacio, si no, añadir una página nueva
// (40 mm es el alto aproximado de la firma)
if ($pdf->GetY() > ($pdf->GetPageHeight() - 40)) {
    $pdf->AddPage();
}

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, '_________________________', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 9);
$nombreMedicoFirma = mb_convert_case($consulta['nombre_medico'] . ' ' . $consulta['apellido_medico'], MB_CASE_TITLE, 'UTF-8');
$pdf->Cell(0, 5, utf8_decode('Dr(a). ' . $nombreMedicoFirma), 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, utf8_decode($consulta['especialidad'] ?? 'N/A'), 0, 1, 'C');
$pdf->Cell(0, 4, utf8_decode('Reg. Médico ' . ($consulta['registro_medico'] ?? 'N/A') . ' - CC ' . ($consulta['cedula_medico'] ?? 'N/A')), 0, 1, 'C');
// --- FIN DEL BLOQUE DE FIRMA ---


$pdf->Output('I', 'Resumen_Consulta_'.$consulta['codigo_historia'].'.pdf');
?>