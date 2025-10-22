<?php
require_once 'includes/header.php';
require_once 'includes/sidebar_secretaria.php';
require_once '../core/db_connection.php';

$conexion = conectarDB();
$sql = "SELECT * FROM pacientes ORDER BY apellido ASC";
$resultado = $conexion->query($sql);
$pacientes = [];
if ($resultado) {
    while($fila = $resultado->fetch_assoc()) {
        $pacientes[] = $fila;
    }
}
$conexion->close();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1 class="m-0">Listado de Pacientes</h1></div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <table id="tabla-pacientes-sec" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nº Documento</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Teléfono</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pacientes as $paciente): ?>
                                <tr>
                                    <td>
                                        <a href="agendar_cita.php?preseleccionar_paciente=<?php echo htmlspecialchars($paciente['numero_documento']); ?>">
                                            <?php echo htmlspecialchars($paciente['numero_documento']); ?>
                                        </a>
                                    
                                    <td><?php echo htmlspecialchars(mb_convert_case($paciente['nombre'], MB_CASE_TITLE, 'UTF-8')); ?></td>
                                    <td><?php echo htmlspecialchars(mb_convert_case($paciente['apellido'], MB_CASE_TITLE, 'UTF-8')); ?></td>
                                    <td><?php echo htmlspecialchars($paciente['telefono_whatsapp'] ?? 'N/A'); ?></td>
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
    $("#tabla-pacientes-sec").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "language": { "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json" } // Apuntará al archivo local
    });
  });
</script>