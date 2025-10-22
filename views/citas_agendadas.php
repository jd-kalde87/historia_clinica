<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php'; // Usamos el sidebar del médico
require_once '../core/db_connection.php';

$conexion = conectarDB();
$id_medico_logueado = $_SESSION['id_usuario']; // Obtenemos el ID del médico de la sesión

// Consulta para traer las citas de HOY asignadas a ESTE médico
$sql = "SELECT 
            c.hora_cita,
            p.nombre,
            p.apellido,
            p.numero_documento,
            c.notas_secretaria
        FROM citas c
        JOIN pacientes p ON c.paciente_documento = p.numero_documento
        WHERE c.id_medico_asignado = ? 
          AND c.fecha_cita = CURDATE() 
          AND c.estado_cita IN ('Agendada', 'Confirmada')
        ORDER BY c.hora_cita ASC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_medico_logueado);
$stmt->execute();
$resultado = $stmt->get_result();
$citas_hoy = [];
if ($resultado) {
    while($fila = $resultado->fetch_assoc()) {
        $citas_hoy[] = $fila;
    }
}
$stmt->close();
$conexion->close();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Citas Agendadas para Hoy (<?php echo date('d/m/Y'); ?>)</h1>
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
                            <?php if (empty($citas_hoy)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay citas agendadas para hoy.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas_hoy as $cita): ?>
                                    <tr>
                                        <td><?php echo date("h:i A", strtotime($cita['hora_cita'])); ?></td>
                                        <td><?php echo htmlspecialchars($cita['nombre'] . ' ' . $cita['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['numero_documento']); ?></td>
                                        <td><?php echo htmlspecialchars($cita['notas_secretaria']); ?></td>
                                        <td>
                                            <a href="nueva_historia.php?documento=<?php echo htmlspecialchars($cita['numero_documento']); ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-play-circle"></i> Iniciar Consulta
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
      "searching": false, // Desactivamos la búsqueda ya que son pocas citas
      "paging": false,    // Desactivamos la paginación
      "info": false,      // Desactivamos la información de "mostrando X de Y"
      "language": { "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json" }
    });
  });
</script>