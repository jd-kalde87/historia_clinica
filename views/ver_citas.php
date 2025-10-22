<?php
require_once 'includes/header.php';
require_once 'includes/sidebar_secretaria.php';
require_once '../core/db_connection.php';

$conexion = conectarDB();
// Consulta para traer las citas de HOY para TODOS los médicos
$sql = "SELECT 
            c.id_cita,
            c.hora_cita,
            p.nombre AS nombre_paciente,
            p.apellido AS apellido_paciente,
            u.nombre_medico,
            u.apellido_medico
        FROM citas c
        JOIN pacientes p ON c.paciente_documento = p.numero_documento
        JOIN usuarios u ON c.id_medico_asignado = u.id_medico
        WHERE c.fecha_cita = CURDATE() 
          AND c.estado_cita IN ('Agendada', 'Confirmada')
        ORDER BY c.hora_cita ASC";

$resultado = $conexion->query($sql);
$citas_hoy = [];
if ($resultado) {
    while($fila = $resultado->fetch_assoc()) {
        $citas_hoy[] = $fila;
    }
}
$conexion->close();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Citas para Hoy (<?php echo date('d/m/Y'); ?>)</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table id="tabla-citas-dia" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Médico Asignado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas_hoy as $cita): ?>
                                <tr>
                                    <td><?php echo date("h:i A", strtotime($cita['hora_cita'])); ?></td>
                                    <td><?php echo htmlspecialchars($cita['nombre_paciente'] . ' ' . $cita['apellido_paciente']); ?></td>
                                    <td><?php echo htmlspecialchars('Dr(a). ' . $cita['nombre_medico'] . ' ' . $cita['apellido_medico']); ?></td>
                                    <td>
                                        <a href="agendar_cita.php?id_cita_ver=<?php echo $cita['id_cita']; ?>" class="btn btn-sm btn-info">
                                            Ver / Editar
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
  $(function () { $("#tabla-citas-dia").DataTable({ "responsive": true, "lengthChange": false, "autoWidth": false, "language": { "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json" } }); });
</script>