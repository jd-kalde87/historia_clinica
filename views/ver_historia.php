<?php
/**
 * ===============================================
 * INCLUDES Y CONFIGURACIÓN INICIAL
 * ===============================================
 * Se incluyen los archivos necesarios para la cabecera, el menú lateral,
 * la conexión a la base de datos, funciones auxiliares y el modelo de datos.
 */
require_once 'includes/header.php';
require_once 'includes/sidebar.php'; // Asegúrate que sea el sidebar correcto (¿quizás sidebar_medico.php?)
require_once '../core/db_connection.php';
require_once '../core/funciones.php'; // Necesario para calcularEdad y generarMensajeWhatsApp
require_once '../models/consulta_model.php'; // Modelo que obtiene los datos

/**
 * ===============================================
 * VALIDACIÓN DEL ID DE HISTORIA
 * ===============================================
 * Se verifica que se haya recibido un parámetro 'id' por GET y que sea un número entero válido.
 * Si no es válido, se muestra un error y se detiene la ejecución.
 */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-danger mt-3">ID de consulta no válido.</div></div></section></div>';
    require_once 'includes/footer.php';
    exit();
}
$id_historia = (int)$_GET['id']; // Se convierte a entero por seguridad.

/**
 * ===============================================
 * OBTENCIÓN DE DATOS DE LA CONSULTA
 * ===============================================
 * Se utiliza la función 'obtenerDetallesConsulta' del modelo para traer toda la información
 * relacionada con la historia clínica solicitada (datos del paciente, médico, consulta,
 * medicamentos recetados y archivos adjuntos).
 */
$conexion = conectarDB();
$datos = obtenerDetallesConsulta($conexion, $id_historia);
$conexion->close();

// Si la consulta no devuelve datos, significa que la historia no existe.
if (!$datos) {
    echo '<div class="content-wrapper"><section class="content"><div class="container-fluid"><div class="alert alert-warning mt-3">No se encontró la consulta solicitada.</div></div></section></div>';
    require_once 'includes/footer.php';
    exit();
}

// Se extraen los datos en variables separadas para facilitar su uso en el HTML.
$consulta = $datos['consulta'];
$medicamentos = $datos['medicamentos'];
$archivos = $datos['archivos'];

/**
 * ===============================================
 * LÓGICA PARA EL MENSAJE DE WHATSAPP
 * ===============================================
 * Se prepara el contenido y el número de teléfono para el botón de WhatsApp.
 */
// 1. Se genera el resumen completo (consulta + receta) usando la función 'generarMensajeWhatsApp'.
//    El segundo parámetro 'false' indica que no se debe codificar para URL todavía.
$mensaje_whatsapp_sin_codificar = generarMensajeWhatsApp($consulta, $medicamentos, false);

// 2. Se limpia el número de teléfono eliminando caracteres no numéricos
//    y se añade el prefijo de Colombia (+57) si tiene 10 dígitos.
$telefono_limpio_js = '';
if (!empty($consulta['telefono_whatsapp'])) {
    $telefono_limpio_js = preg_replace('/[^0-9]/', '', $consulta['telefono_whatsapp']);
    if (strlen($telefono_limpio_js) == 10) { $telefono_limpio_js = '57' . $telefono_limpio_js; } 
}
// --- FIN: LÓGICA DE WHATSAPP ---

/**
 * ===============================================
 * FORMATEO DE NOMBRES PARA MOSTRAR
 * ===============================================
 * Se convierten los nombres del paciente y del médico a "Estilo Título" para una presentación consistente.
 */
$nombre_paciente_titulo = mb_convert_case(($consulta['nombre'] ?? '') . ' ' . ($consulta['apellido'] ?? ''), MB_CASE_TITLE, 'UTF-8');
$nombre_medico_titulo = mb_convert_case(($consulta['nombre_medico'] ?? '') . ' ' . ($consulta['apellido_medico'] ?? ''), MB_CASE_TITLE, 'UTF-8');

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <?php if(isset($_GET['upload_status']) && $_GET['upload_status'] == 'success'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Archivo subido exitosamente.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php elseif(isset($_GET['upload_status']) && $_GET['upload_status'] == 'error'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo 'Error al subir: ' . htmlspecialchars($_GET['msg']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle de Consulta - <?php echo htmlspecialchars($consulta['codigo_historia']); ?></h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-sm-right">
                        <a href="../reports/generar_historia_pdf.php?id=<?php echo $id_historia; ?>" target="_blank" class="btn btn-primary" title="Imprimir Resumen de Consulta"><i class="fas fa-print"></i> Consulta</a>
                        
                        <?php if (!empty($medicamentos)): ?>
                            <a href="../reports/generar_receta_pdf.php?id=<?php echo $id_historia; ?>" target="_blank" class="btn btn-success" title="Imprimir Receta Médica"><i class="fas fa-prescription-bottle-alt"></i> Receta</a>
                        <?php endif; ?>

                        <?php if (!empty($consulta['telefono_whatsapp'])): ?>
                            <button type="button" class="btn btn-info" id="btn-mostrar-resumen-whatsapp" 
                                    data-telefono="<?php echo htmlspecialchars($telefono_limpio_js); ?>" 
                                    title="Ver resumen y enviar por WhatsApp">
                                <i class="fab fa-whatsapp"></i> Enviar Resumen
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled title="No hay teléfono registrado"><i class="fab fa-whatsapp"></i> Enviar Resumen</button>
                        <?php endif; ?>
                    </div> </div> </div> </div> </div> <section class="content">
        <div class="container-fluid">
            
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Datos del Paciente</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($nombre_paciente_titulo); ?></p>
                            <p><strong>Documento:</strong> <?php echo htmlspecialchars($consulta['tipo_documento'] . ' - ' . $consulta['numero_documento']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($consulta['telefono_whatsapp'] ?? 'No registrado'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Edad:</strong> <?php echo calcularEdad($consulta['fecha_nacimiento']); ?> años (Nacido el: <?php echo date("d/m/Y", strtotime($consulta['fecha_nacimiento'])); ?>)</p>
                            <p><strong>Sexo:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($consulta['sexo'] ?? 'N/A'))); ?></p>
                            <p><strong>Estado Civil:</strong> <?php echo htmlspecialchars(ucfirst(strtolower($consulta['estado_civil'] ?? 'N/A'))); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header"><h3 class="card-title">Detalles de la Consulta</h3></div>
                <div class="card-body">
                    <p><strong>Fecha y Hora de Consulta:</strong> <?php echo date("d/m/Y h:i A", strtotime($consulta['fecha_consulta'])); ?></p>
                    <p><strong>Médico Tratante:</strong> Dr(a). <?php echo htmlspecialchars($nombre_medico_titulo); ?></p>
                    <hr>
                    <p><strong>Motivo de Consulta:</strong><br><?php echo nl2br(htmlspecialchars($consulta['motivo_consulta'] ?? 'No registrado')); ?></p>
                    <p><strong>Enfermedad Actual:</strong><br><?php echo nl2br(htmlspecialchars($consulta['enfermedad_actual'] ?? 'No registrado')); ?></p>
                    <p><strong>Antecedentes Personales:</strong><br><?php echo nl2br(htmlspecialchars($consulta['antecedentes_personales'] ?? 'No registrado')); ?></p>
                    <p><strong>Antecedentes Familiares:</strong><br><?php echo nl2br(htmlspecialchars($consulta['antecedentes_familiares'] ?? 'No registrado')); ?></p>
                    <hr>
                    <p><strong>Examen Físico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['examen_fisico'] ?? 'No registrado')); ?></p>
                    <p><strong>Hallazgos del Examen Físico:</strong><br><?php echo nl2br(htmlspecialchars($consulta['hallazgos_examen_fisico'] ?? 'No registrado')); ?></p>
                    <hr>
                    <p><strong>Diagnóstico Principal:</strong><br><?php echo nl2br(htmlspecialchars($consulta['diagnostico_principal'] ?? 'No registrado')); ?></p>
                    <p><strong>Tratamiento a Seguir:</strong><br><?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?? 'No registrado')); ?></p>
                    <p><strong>Solicitud de Exámenes:</strong><br><?php echo nl2br(htmlspecialchars($consulta['solicitud_examenes'] ?? 'No registrado')); ?></p>
                </div>
            </div>

           <div class="card card-purple">
                <div class="card-header"><h3 class="card-title">Signos Vitales de esta Consulta</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><p><strong>Peso:</strong> <?php echo htmlspecialchars($consulta['peso_kg'] ?? 'N/A'); ?> kg</p></div>
                        <div class="col-md-3"><p><strong>Talla:</strong> <?php echo htmlspecialchars($consulta['talla_cm'] ?? 'N/A'); ?> cm</p></div>
                        <div class="col-md-3"><p><strong>IMC:</strong> <?php echo htmlspecialchars($consulta['imc'] ?? 'N/A'); ?></p></div>
                        <div class="col-md-3"><p><strong>Tensión Arterial:</strong> <?php echo htmlspecialchars($consulta['tension_arterial'] ?? 'N/A'); ?></p></div>
                    </div>
                     <div class="row">
                        <div class="col-md-4"><p><strong>Frec. Cardíaca:</strong> <?php echo htmlspecialchars($consulta['frecuencia_cardiaca'] ?? 'N/A'); ?> lat/min</p></div>
                        <div class="col-md-4"><p><strong>Frec. Respiratoria:</strong> <?php echo htmlspecialchars($consulta['frecuencia_respiratoria'] ?? 'N/A'); ?> resp/min</p></div>
                        <div class="col-md-4"><p><strong>Temperatura:</strong> <?php echo htmlspecialchars($consulta['temperatura_c'] ?? 'N/A'); ?> °C</p></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($medicamentos)): // Solo se muestra si hay medicamentos recetados ?>
            <div class="card card-danger">
                <div class="card-header"><h3 class="card-title">Receta Médica</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-hover"> 
                        <thead class="thead-light"> 
                            <tr>
                                <th>Medicamento</th>
                                <th>Dosis / Horario</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicamentos as $medicamento): // Recorre cada medicamento y lo muestra en una fila ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicamento['nombre_medicamento']); ?></td>
                                <td><?php echo htmlspecialchars($medicamento['horario_dosis']); ?></td>
                                <td><?php echo htmlspecialchars($medicamento['cantidad']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Archivos Adjuntos (Exámenes, etc.)</h3>
                </div>
                <div class="card-body">
                    <form action="../controllers/archivo_controller.php" method="post" enctype="multipart/form-data" class="mb-4 border-bottom pb-4">
                        <p><strong>Adjuntar nuevo archivo:</strong></p>
                        <div class="form-group">
                            <input type="hidden" name="id_historia" value="<?php echo $id_historia; ?>">
                            <input type="hidden" name="documento_paciente" value="<?php echo htmlspecialchars($consulta['numero_documento']); ?>">
                            
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="archivo" name="archivo" required>
                                    <label class="custom-file-label" for="archivo">Seleccionar archivo...</label>
                                </div>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="submit">Subir Archivo</button>
                                </div>
                            </div>
                             <small class="form-text text-muted">Tipos permitidos: PDF, JPG, PNG. Tamaño máximo: 5MB.</small>
                        </div>
                    </form>

                    <h5>Archivos existentes:</h5>
                    <?php if (empty($archivos)): // Si no hay archivos, muestra un mensaje ?>
                        <p>No hay archivos adjuntos para esta consulta.</p>
                    <?php else: // Si hay archivos, los lista ?>
                        <ul class="list-group list-group-flush"> 
                            <?php foreach ($archivos as $archivo): // Recorre cada archivo ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span> 
                                        <i class="fas fa-paperclip mr-2"></i> <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                                        <small class="text-muted ml-2">(Subido: <?php echo date("d/m/Y", strtotime($archivo['fecha_carga'])); ?>)</small>
                                    </span>
                                    <a href="<?php echo (defined('BASE_URL') ? BASE_URL : '../') . htmlspecialchars($archivo['ruta_archivo']); ?>" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div> </div> </div> </section> </div> <div id="texto-whatsapp-oculto" style="display:none;"><?php echo htmlspecialchars($mensaje_whatsapp_sin_codificar); ?></div>

<?php require_once 'includes/footer.php'; ?>

<script src="../assets/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script> <script>
document.addEventListener('DOMContentLoaded', function () {
    /**
     * SCRIPT PARA ACTUALIZAR EL LABEL DEL INPUT FILE
     * Muestra el nombre del archivo seleccionado por el usuario en el campo de subida.
     */
    const inputFile = document.querySelector('.custom-file-input');
    if (inputFile) {
        inputFile.addEventListener('change', function(e){
            var fileName = e.target.files[0]?.name || "Seleccionar archivo..."; 
            var nextSibling = e.target.nextElementSibling; 
            nextSibling.innerText = fileName; 
        });
    }

    /**
     * SCRIPT PARA EL BOTÓN DE MOSTRAR RESUMEN Y ENVIAR POR WHATSAPP
     * Se activa al hacer clic en el botón con id 'btn-mostrar-resumen-whatsapp'.
     */
    const btnMostrarResumen = document.getElementById('btn-mostrar-resumen-whatsapp');
    if (btnMostrarResumen) {
        btnMostrarResumen.addEventListener('click', function() {
            // Obtiene el texto del resumen desde el div oculto
            const textoParaEnviar = document.getElementById('texto-whatsapp-oculto').textContent;
            // Obtiene el número de teléfono desde el atributo 'data-telefono' del botón
            const telefono = this.getAttribute('data-telefono'); 
            // Codifica el mensaje para la URL
            const mensajeUrl = encodeURIComponent(textoParaEnviar);
            // Construye el enlace de WhatsApp
            const whatsappLink = `https://wa.me/${telefono}?text=${mensajeUrl}`;

            // Muestra la ventana emergente de SweetAlert2
            Swal.fire({
                title: '<strong>Resumen para WhatsApp</strong>',
                icon: 'info',
                html: `<pre style="white-space: pre-wrap; text-align: left;">${textoParaEnviar}</pre>`, // Muestra el resumen
                showCloseButton: true,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: '<i class="fab fa-whatsapp"></i> Enviar',
                confirmButtonAriaLabel: 'Enviar por WhatsApp',
                cancelButtonText: '<i class="fas fa-copy"></i> Copiar',
                cancelButtonAriaLabel: 'Copiar texto'
            }).then((result) => {
                if (result.isConfirmed) { // Si hace clic en 'Enviar'
                    window.open(whatsappLink, '_blank'); // Abre WhatsApp
                } else if (result.dismiss === Swal.DismissReason.cancel) { // Si hace clic en 'Copiar'
                    navigator.clipboard.writeText(textoParaEnviar).then(() => {
                        toastr.success('¡Resumen copiado al portapapeles!'); // Muestra notificación de éxito
                    }, (err) => {
                        toastr.error('No se pudo copiar el texto.'); // Muestra notificación de error
                        console.error('Error al copiar: ', err); // Registra el error en consola
                    });
                }
            });
        });
    }
});
</script>