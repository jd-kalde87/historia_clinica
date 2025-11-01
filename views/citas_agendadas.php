<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php'; // Usamos el sidebar del médico
require_once '../core/db_connection.php';

// 1. Determinar la fecha a consultar
$fecha_seleccionada = date('Y-m-d'); // Por defecto, es hoy
if (isset($_GET['fecha']) && !empty($_GET['fecha'])) {
    $fecha_seleccionada = $_GET['fecha'];
}
$fecha_formateada_titulo = date("d/m/Y", strtotime($fecha_seleccionada));

$conexion = conectarDB();
$id_medico_logueado = $_SESSION['id_usuario']; 

// 2. Consulta SQL (sin cambios)
$sql = "SELECT 
            c.id_cita, c.hora_cita, p.nombre, p.apellido,
            p.numero_documento, c.notas_secretaria
        FROM citas c
        JOIN pacientes p ON c.paciente_documento = p.numero_documento
        WHERE c.id_medico_asignado = ? 
          AND c.fecha_cita = ?
          AND c.estado_cita IN ('Agendada', 'Confirmada')
        ORDER BY c.hora_cita ASC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("is", $id_medico_logueado, $fecha_seleccionada); 
$stmt->execute();
$resultado = $stmt->get_result();
$citas_dia = []; 
if ($resultado) {
    while($fila = $resultado->fetch_assoc()) {
        $citas_dia[] = $fila; 
    }
}
$stmt->close();
$conexion->close();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0">Citas Agendadas para: <?php echo $fecha_formateada_titulo; ?></h1>
                </div>
                <div class="col-sm-6">
                    <div class="form-group float-sm-right">
                        <label for="selector_fecha_citas">Seleccionar otro día:</label>
                        <input type="date" id="selector_fecha_citas" class="form-control" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pacientes agendados</h3>
                </div>
                <div class="card-body">
                    <table id="tabla-citas-hoy" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Documento</th>
                                <th>Motivo / Notas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Ya no usamos un 'if (empty(...))' aquí.
                            // Simplemente recorremos el array $citas_dia.
                            // Si está vacío, el bucle no se ejecuta y el <tbody> queda vacío.
                            // DataTables manejará esto automáticamente.
                            foreach ($citas_dia as $cita): 
                            ?>
                                <tr>
                                    <td><?php echo date("h:i A", strtotime($cita['hora_cita'])); ?></td>
                                    <td><?php echo htmlspecialchars($cita['nombre'] . ' ' . $cita['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['numero_documento']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['notas_secretaria']); ?></td>
                                    <td>
                                        <a href="nueva_historia.php?documento=<?php echo htmlspecialchars($cita['numero_documento']); ?>&id_cita=<?php echo $cita['id_cita']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-play-circle"></i> Iniciar Consulta
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
  $(function () {
    $("#tabla-citas-hoy").DataTable({
      "responsive": true,
      "lengthChange": false,
      "autoWidth": false,
      "searching": false, 
      "paging": false,    
      "info": false,      
      "language": { "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json" }
    });

    // Esta lógica para recargar la página está correcta
    $('#selector_fecha_citas').on('change', function() {
        var nuevaFecha = $(this).val(); 
        if (nuevaFecha) {
            window.location.href = 'citas_agendadas.php?fecha=' + nuevaFecha;
        }
    });
  });
</script>