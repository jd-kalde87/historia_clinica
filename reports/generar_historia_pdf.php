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
// NOTA: hc.* ya trae todas las nuevas columnas, no es necesario cambiar el SQL.
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
    
    // --- INICIO DE NUEVAS FUNCIONES HELPER ---

    /**
     * Establece los colores (fondo y texto) para un badge.
     * @param string $tipo 'success', 'warning', 'danger' o 'normal'.
     */
    function SetBadgeColor($tipo = 'normal') {
        switch ($tipo) {
            case 'success':
                // Verde (ej. Normal)
                $this->SetFillColor(212, 237, 218); // Verde claro (fondo)
                $this->SetTextColor(21, 87, 36);   // Verde oscuro (texto)
                break;
            case 'warning':
                // Amarillo (ej. Sobrepeso, Elevada)
                $this->SetFillColor(255, 243, 205); // Amarillo claro (fondo)
                $this->SetTextColor(133, 100, 4);  // Amarillo oscuro (texto)
                break;
            case 'danger':
                // Rojo (ej. Obesidad, HTA Grado 2)
                $this->SetFillColor(248, 215, 218); // Rojo claro (fondo)
                $this->SetTextColor(114, 28, 36);  // Rojo oscuro (texto)
                break;
            default:
                // Gris (ej. N/A)
                $this->SetFillColor(230, 230, 230); // Gris claro
                $this->SetTextColor(50, 50, 50);    // Gris oscuro
                break;
        }
    }

    /**
     * Devuelve el 'tipo' (success, warning, danger) basado en la clasificación guardada.
     * @param string $clasificacion El texto de la clasificación (ej. "Sobrepeso")
     * @return string
     */
    function GetTipoPorClasificacion($clasificacion) {
        if (empty($clasificacion) || $clasificacion == 'N/A') return 'normal';
        $clasificacion = strtolower($clasificacion);
        
        if (strpos($clasificacion, 'normal') !== false) {
            return 'success';
        }
        if (strpos($clasificacion, 'sobrepeso') !== false || strpos($clasificacion, 'elevada') !== false || strpos($clasificacion, 'hipertensión grado 1') !== false) {
            return 'warning';
        }
        if (strpos($clasificacion, 'obesidad') !== false || strpos($clasificacion, 'crisis') !== false || strpos($clasificacion, 'hipertensión grado 2') !== false) {
            return 'danger';
        }
        return 'normal'; // Default
    }
    
    /**
     * Calcula y devuelve la clasificación de TFG y su tipo de color.
     * @param float|null $tfg_valor El valor numérico del TFG.
     * @return array ['texto' => string, 'tipo' => string]
     */
    function GetClasificacionTFG($tfg_valor) {
        if ($tfg_valor === null || $tfg_valor == 0) {
            return ['texto' => 'N/A', 'tipo' => 'normal'];
        }

        if ($tfg_valor >= 90) {
            return ['texto' => 'Estadio G1 (>= 90)', 'tipo' => 'success'];
        } elseif ($tfg_valor >= 60) {
            return ['texto' => 'Estadio G2 (60-89)', 'tipo' => 'success'];
        } elseif ($tfg_valor >= 45) {
            return ['texto' => 'Estadio G3a (45-59)', 'tipo' => 'warning'];
        } elseif ($tfg_valor >= 30) {
            return ['texto' => 'Estadio G3b (30-44)', 'tipo' => 'warning'];
        } elseif ($tfg_valor >= 15) {
            return ['texto' => 'Estadio G4 (15-29)', 'tipo' => 'danger'];
        } else {
            return ['texto' => 'Estadio G5 (< 15)', 'tipo' => 'danger'];
        }
    }
    // --- FIN DE NUEVAS FUNCIONES HELPER ---
}

// Se establece el tamaño a Carta (Letter)
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->setMedicoInfo($consulta); // Pasamos la info del médico para el footer
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

// --- INICIO DEL BLOQUE DE SIGNOS VITALES (REEMPLAZADO) ---
// Este bloque reemplaza el antiguo $pdf->Seccion('Signos Vitales', ...)

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240); // Gris claro de fondo para el título
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]);
$pdf->Cell(0, 7, utf8_decode('Signos Vitales y Antropometría'), 'TLR', 1, 'L', true);

// Definimos el ancho de las 4 columnas (Ancho total página 'Letter' es 216mm, márgenes 10+10 = 196mm)
$c1 = 49; $c2 = 49; $c3 = 49; $c4 = 49; 
$alto_fila_titulo = 5; // Altura para títulos de campo
$alto_fila_valor = 6;  // Altura para valores

// ----- Fila 1: Antropometría -----
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

// Badge de IMC
$clasif_imc = $consulta['imc_clasificacion'] ?? 'N/A';
$pdf->SetBadgeColor($pdf->GetTipoPorClasificacion($clasif_imc));
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($c4, $alto_fila_valor, utf8_decode($clasif_imc), 'R', 1, 'C', true); // Celda con fondo
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]); // Resetear color texto

// ----- Fila 2: Tensión Arterial -----
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($c1, $alto_fila_titulo, utf8_decode('T.A. Sistólica:'), 'L', 0);
$pdf->Cell($c2, $alto_fila_titulo, utf8_decode('T.A. Diastólica:'), 0, 0);
$pdf->Cell($c3 + $c4, $alto_fila_titulo, utf8_decode('Clasificación HTA:'), 'R', 1); // Ocupa 2 celdas

$pdf->SetFont('Arial', '', 10);
$pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['tension_sistolica'] ?? 'N/A') . ' mmHg', 'L', 0);
$pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['tension_diastolica'] ?? 'N/A') . ' mmHg', 0, 0);

// Badge de HTA
$clasif_hta = $consulta['clasificacion_hta'] ?? 'N/A';
$pdf->SetBadgeColor($pdf->GetTipoPorClasificacion($clasif_hta));
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($c3 + $c4, $alto_fila_valor, utf8_decode($clasif_hta), 'R', 1, 'C', true); // Celda con fondo
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]); // Resetear color texto

// ----- Fila 3: Vitales Complementarios -----
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($c1, $alto_fila_titulo, 'Frec. Cardiaca:', 'L', 0);
$pdf->Cell($c2, $alto_fila_titulo, 'Frec. Respiratoria:', 0, 0);
$pdf->Cell($c3 + $c4, $alto_fila_titulo, 'Temperatura:', 'R', 1); 

$pdf->SetFont('Arial', '', 10);
$pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['frecuencia_cardiaca'] ?? 'N/A') . ' lat/min', 'L', 0);
$pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['frecuencia_respiratoria'] ?? 'N/A') . ' resp/min', 0, 0);
$pdf->Cell($c3 + $c4, $alto_fila_valor, utf8_decode($consulta['temperatura_c'] ?? 'N/A') . ' ' . utf8_decode('°C'), 'R', 1);

// ----- Fila 4: Laboratorios y Cálculos -----
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($c1, $alto_fila_titulo, 'Hb Glicosilada (HbA1c %):', 'L', 0);
$pdf->Cell($c2, $alto_fila_titulo, utf8_decode('Creatinina Sérica (mg/dL):'), 0, 0);
$pdf->Cell($c3, $alto_fila_titulo, 'TFG (CKD-EPI):', 0, 0);
$pdf->Cell($c4, $alto_fila_titulo, utf8_decode('Clasificación TFG:'), 'R', 1);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell($c1, $alto_fila_valor, utf8_decode($consulta['hemoglobina_glicosilada'] ?? 'N/A') . ' %', 'LB', 0);
$pdf->Cell($c2, $alto_fila_valor, utf8_decode($consulta['creatinina_serica'] ?? 'N/A') . ' mg/dL', 'B', 0);
$pdf->Cell($c3, $alto_fila_valor, utf8_decode($consulta['filtrado_glomerular_ckd_epi'] ?? 'N/A'), 'B', 0);

// Badge de TFG (Calculado al vuelo)
$clasif_tfg = $pdf->GetClasificacionTFG($consulta['filtrado_glomerular_ckd_epi'] ?? null);
$pdf->SetBadgeColor($clasif_tfg['tipo']);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($c4, $alto_fila_valor, utf8_decode($clasif_tfg['texto']), 'RB', 1, 'C', true);
$pdf->SetTextColor(COLOR_DARK[0], COLOR_DARK[1], COLOR_DARK[2]); // Resetear color texto

$pdf->Ln(3); // Espacio después de la sección
// --- FIN DEL BLOQUE DE SIGNOS VITALES ---


$pdf->Seccion('Hallazgos del Examen Fisico:', $consulta['hallazgos_examen_fisico'] ?? '');
$pdf->Seccion('Diagnostico Principal:', $consulta['diagnostico_principal'] ?? '');
$pdf->Seccion('Tratamiento a Seguir:', $consulta['tratamiento'] ?? '');
$pdf->Seccion('Solicitud de Examenes:', $consulta['solicitud_examenes'] ?? '');

$pdf->Output('I', 'Resumen_Consulta_'.$consulta['codigo_historia'].'.pdf');
?>