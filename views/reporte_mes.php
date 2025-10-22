<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once '../core/db_connection.php';

$conexion = conectarDB();
// Consulta para traer las consultas del mes actual, uniendo la tabla de pacientes para obtener el nombre.
$sql = "SELECT 
            hc.id_historia, 
            hc.codigo_historia, 
            hc.fecha_consulta, 
            hc.motivo_consulta, 
            p.nombre, 
            p.apellido, 
            p.numero_documento
        FROM historias_clinicas hc
        JOIN pacientes p ON hc.paciente_documento = p.numero_documento
        WHERE MONTH(hc.fecha_consulta) = MONTH(CURRENT_DATE()) 
          AND YEAR(hc.fecha_consulta) = YEAR(CURRENT_DATE())
        ORDER BY hc.fecha_consulta DESC";

$resultado = $conexion->query($sql);
$consultas = [];
if ($resultado->num_rows > 0) {
    while($fila = $resultado->fetch_assoc()) {
        $consultas[] = $fila;
    }
}
$conexion->close();

// Obtener el nombre del mes actual en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'Spanish');
$nombre_mes = strftime('%B de %Y');
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Reporte de Consultas de <?php echo ucfirst($nombre_mes); ?></h1>
                </div>
            </div></div></div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Consultas realizadas este mes</h3>
                </div>
                <div class="card-body">
                    <table id="tabla-reporte" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Código Consulta</th>
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th>Documento</th>
                                <th>Motivo de Consulta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultas as $consulta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($consulta['codigo_historia']); ?></td>
                                    <td><?php echo date("d/m/Y h:i A", strtotime($consulta['fecha_consulta'])); ?></td>
                                    <td><?php echo htmlspecialchars($consulta['nombre'] . ' ' . $consulta['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($consulta['numero_documento']); ?></td>
                                    <td><?php echo htmlspecialchars($consulta['motivo_consulta']); ?></td>
                                    <td>
                                        <a href="ver_historia.php?id=<?php echo $consulta['id_historia']; ?>" class="btn btn-sm btn-info">
                                            Ver Detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div></section>
    </div>
<?php require_once 'includes/footer.php'; ?>

<script>
  $(function () {
    $("#tabla-reporte").DataTable({
      "responsive": true,
      "lengthChange": false,
      "autoWidth": false,
      "order": [[ 1, "desc" ]], // Ordenar por fecha descendente por defecto
      "language": { "url": "../assets/adminlte/plugins/datatables/i18n/Spanish.json" }
    });
  });
</script>