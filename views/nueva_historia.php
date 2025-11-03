<?php 
// --- INCLUDES INICIALES Y CONFIGURACIÓN ---
require_once 'includes/header.php'; 
require_once 'includes/sidebar.php'; // Asegúrate que sea el sidebar correcto (¿quizás sidebar_medico.php?)

// Verificamos si se pasó un número de documento por la URL (desde la búsqueda)
$documento_url = '';
if (isset($_GET['documento'])) {
    // Limpiamos el dato para seguridad
    $documento_url = htmlspecialchars(trim($_GET['documento']));
}

// 1. Capturamos el ID de la cita que viene de la URL
$id_cita_url = 0; // Valor por defecto
if (isset($_GET['id_cita'])) {
    $id_cita_url = (int)$_GET['id_cita']; // Lo convertimos a entero
}
?>

<!-- =============================================== -->
<!--         CONTENIDO PRINCIPAL DE LA PÁGINA        -->
<!-- =============================================== -->
<div class="content-wrapper">
    <!-- Encabezado de la página -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0" id="titulo-formulario">Registrar Nueva Historia Clínica</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección principal del formulario -->
    <section class="content">
        <div class="container-fluid">
            <!-- Formulario que envía los datos al controlador 'historia_controller.php' usando el método POST -->
            <form action="../controllers/historia_controller.php" method="POST" id="form_historia_clinica">
                
                <!-- Campo oculto para pasar el número de documento si viene de la página de búsqueda -->
                <input type="hidden" id="documento_a_cargar" value="<?php echo $documento_url; ?>">
                <input type="hidden" name="id_cita_a_completar" value="<?php echo $id_cita_url; ?>">
                <!-- =============================================== -->
                <!--    TARJETA: INFORMACIÓN GENERAL DEL PACIENTE    -->
                <!-- =============================================== -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información General del Paciente</h3>
                    </div>
                    <div class="card-body">
                        <!-- Fila 1: Documento y Nombres -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_documento">Tipo de Identificación</label>
                                    <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                                        <option value="CC">Cédula de Ciudadanía (CC)</option>
                                        <option value="TI">Tarjeta de Identidad (TI)</option>
                                        <option value="RC">Registro Civil (RC)</option>
                                        <option value="CE">Cédula de Extranjería (CE)</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_documento">Nº de Documento</label>
                                    <input type="text" class="form-control" id="numero_documento" name="numero_documento" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nombre">Nombres</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="apellido">Apellidos</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required>
                                </div>
                            </div>
                        </div>
                        <!-- Fila 2: Nacimiento, Edad, Sexo, Estado Civil, Teléfono -->
                        <div class="row">
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label for="edad">Edad</label>
                                    <input type="text" class="form-control" id="edad" name="edad" readonly>
                                </div>
                            </div>
                             <div class="col-md-2">
                                <div class="form-group">
                                    <label for="sexo">Sexo</label>
                                    <select class="form-control" id="sexo" name="sexo" required>
                                        <option value="FEMENINO">Femenino</option>
                                        <option value="MASCULINO">Masculino</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_civil">Estado Civil</label>
                                    <select class="form-control" id="estado_civil" name="estado_civil" required>
                                        <option value="SOLTERO">Soltero(a)</option>
                                        <option value="CASADO">Casado(a)</option>
                                        <option value="UNION LIBRE">Unión Libre</option>
                                        <option value="VIUDO(A)">Viudo(a)</option>
                                        <option value="SEPARADO">Separado(a)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="telefono_whatsapp">Teléfono (WhatsApp)</label>
                                    <input type="tel" class="form-control" id="telefono_whatsapp" name="telefono_whatsapp">
                                </div>
                            </div>
                        </div>
                        <!-- Fila 3: Dirección y Profesión -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direccion">Dirección de Residencia</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion">
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profesion">Profesión u Ocupación</label>
                                    <input type="text" class="form-control" id="profesion" name="profesion">
                                </div>
                            </div>
                        </div>
                         <!-- Fila 4: Sección de Gestación (se muestra solo si Sexo es Femenino) -->
                        <div class="row" id="seccion-gestacion" style="display: none;">
                            <div class="col-md-6"> <!-- Corregido: col-md-6 para ocupar mitad -->
                                <div class="form-group">
                                    <label for="embarazada">¿Paciente en gestación?</label>
                                    <select class="form-control" id="embarazada" name="embarazada">
                                        <option value="0">No</option>
                                        <option value="1">Sí</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6"> <!-- Corregido: col-md-6 para ocupar mitad -->
                                <div class="form-group">
                                    <label for="semanas_gestacion">Semanas de Gestación</label>
                                    <input type="number" class="form-control" id="semanas_gestacion" name="semanas_gestacion" min="1" max="45" disabled>
                                </div>
                            </div>
                        </div> <!-- Fin Fila 4 -->
                    </div> <!-- /.card-body -->
                </div> <!-- /.card card-primary -->

                <!-- =============================================== -->
                <!--          TARJETA: ANANMESIS -->
                <!-- =============================================== -->               
                <?php 
                // Cargamos el componente del formulario de anamnesis
                require_once 'includes/form_anamnesis.php'; 
                ?>

                <!-- =============================================== -->
                <!--          TARJETA: SIGNOS VITALES Y ANTROPROMETRIA -->
                <!-- =============================================== -->               
                <?php 
                // Cargamos el componente del formulario de signos vitales
                require_once 'includes/form_signos_vitales.php'; 
                ?>    

                <!-- =============================================== -->
                <!--          TARJETA: EXAMEN MÉDICO                 -->
                <!-- =============================================== -->
                <div class="card card-warning">
                     <div class="card-header">
                        <h3 class="card-title">Examen Médico</h3>
                    </div>
                     <div class="card-body">
                        <div class="form-group">
                            <label for="examen_fisico">Examen físico</label>
                            <textarea class="form-control" rows="3" id="examen_fisico" name="examen_fisico"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="hallazgos_examen_fisico">Hallazgos del examen físico</label>
                            <textarea class="form-control" rows="3" id="hallazgos_examen_fisico" name="hallazgos_examen_fisico"></textarea>
                        </div>
                    </div><!-- /.card-body -->
                </div><!-- /.card card-warning -->

                <!-- =============================================== -->
                <!--        TARJETA: HALLAZGOS Y PLAN A SEGUIR       -->
                <!-- =============================================== -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Hallazgos y Plan a Seguir</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="diagnostico_principal">Diagnóstico Principal / Hallazgos de la Consulta</label>
                            <textarea class="form-control" rows="3" id="diagnostico_principal" name="diagnostico_principal"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="solicitud_examenes">Solicitud de Exámenes</label>
                            <textarea class="form-control" rows="3" id="solicitud_examenes" name="solicitud_examenes"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tratamiento">Tratamiento a Seguir</label>
                            <textarea class="form-control" rows="3" id="tratamiento" name="tratamiento"></textarea>
                        </div>
                    </div><!-- /.card-body -->
                </div><!-- /.card card-success -->

                <!-- =============================================== -->
                <!--           TARJETA: RECETA MÉDICA                -->
                <!-- =============================================== -->
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Receta Médica</h3>
                    </div>
                    <div class="card-body" id="receta-medica-container">
                        <!-- La primera fila de medicamento siempre está presente -->
                        <div class="row medicamento-row">
                             <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nombre del Medicamento</label>
                                    <input type="text" class="form-control" name="medicamento_nombre[]">
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="form-group">
                                    <label>Horario / Dosis</label>
                                    <input type="text" class="form-control" name="medicamento_dosis[]">
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="text" class="form-control" name="medicamento_cantidad[]">
                                </div>
                            </div>
                            <!-- El botón de eliminar se añadirá con JS si se agregan más filas -->
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-success" id="btn-agregar-medicamento">Agregar Otro Medicamento</button>
                    </div>
                </div><!-- /.card card-danger -->
                
                <!-- =============================================== -->
                <!--              BOTÓN DE GUARDAR                   -->
                <!-- =============================================== -->
                <div class="pb-5"> <!-- Padding-bottom para dar espacio al final -->
                    <button type="submit" class="btn btn-primary btn-lg btn-block">Guardar Consulta</button>
                </div>

            </form> <!-- Fin del formulario principal -->
        </div><!-- /.container-fluid -->
    </section><!-- /.content -->
</div><!-- /.content-wrapper -->

<?php require_once 'includes/footer.php'; ?>

<!-- Se incluye el script específico para esta página -->
<script src="../assets/js/historia_clinica.js"></script>