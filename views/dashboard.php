<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once '../core/db_connection.php';

// --- INICIO: LÓGICA PARA OBTENER ESTADÍSTICAS ---
$conexion = conectarDB();

// 1. Contar el total de pacientes
$sql_total_pacientes = "SELECT COUNT(numero_documento) AS total FROM pacientes";
$resultado_total = $conexion->query($sql_total_pacientes);
$total_pacientes = $resultado_total->fetch_assoc()['total'];

// 2. Contar las consultas realizadas en el mes actual
$sql_consultas_mes = "SELECT COUNT(id_historia) AS total FROM historias_clinicas WHERE MONTH(fecha_consulta) = MONTH(CURRENT_DATE()) AND YEAR(fecha_consulta) = YEAR(CURRENT_DATE())";
$resultado_mes = $conexion->query($sql_consultas_mes);
$consultas_mes = $resultado_mes->fetch_assoc()['total'];

$conexion->close();
// --- FIN: LÓGICA PARA OBTENER ESTADÍSTICAS ---
?>

<style>
    .whatsapp-support-button {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #25D366; /* Color oficial de WhatsApp */
        color: white;
        padding: 12px 16px;
        border-radius: 50px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        font-size: 16px;
        font-weight: bold;
        text-decoration: none;
        z-index: 1000; /* Asegura que esté por encima de otros elementos */
        display: flex;
        align-items: center;
    }
    .whatsapp-support-button i {
        font-size: 24px;
        margin-right: 8px;
    }
    .whatsapp-support-button:hover {
        background-color: #128C7E; /* Color más oscuro al pasar el mouse */
        color: white;
        text-decoration: none;
    }
</style>

<a href="https://wa.me/573185957439?text=Hola%2C%20necesito%20soporte%20t%C3%A9cnico%20con%20el%20software%20CLINIAX." 
   class="whatsapp-support-button" 
   target="_blank">
    <i class="fab fa-whatsapp"></i>
    Soporte Técnico
</a>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard Principal</h1>
                </div>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-info"></i> ¡Bienvenido!</h5>
                        Hola Dr(a). <?php echo htmlspecialchars($nombreMedico); ?>, bienvenido(a) al sistema de gestión de historias clínicas.
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>Iniciar Consulta</h3>
                            <p>Atender a un paciente</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <a href="buscar_paciente.php" class="small-box-footer">Iniciar <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>Citas Agendadas</h3>
                            <p>Ver la agenda de hoy</p>
                        </div>
                        <div class="icon"><i class="fas fa-calendar-check"></i></div>
                        <a href="citas_agendadas.php" class="small-box-footer">Ver agenda <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>Ver Pacientes</h3>
                            <p>Listado de todos los registros</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="listar_pacientes.php" class="small-box-footer">Ver listado <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?php echo $total_pacientes; ?></h3>
                            <p>Pacientes Registrados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <a href="listar_pacientes.php" class="small-box-footer">Ver todos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="small-box bg-warning">  
                        <div class="inner">
                            <h3><?php echo $consultas_mes; ?></h3>
                            <p>Consultas Realizadas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <a href="reporte_mes.php" class="small-box-footer">Ver detalle <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        toastr.success('¡Registro guardado exitosamente!');
    }
</script>