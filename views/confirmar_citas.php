<?php
// --- INCLUDES Y CABECERAS ---
require_once 'includes/header.php';
require_once 'includes/sidebar_secretaria.php';
require_once '../core/db_connection.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Confirmar Citas Pendientes</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Listado de Próximas Citas</h3>
                </div>
                <div class="card-body">
                    <table id="tabla-confirmaciones" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Motivo de Cita</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
// --- FOOTER ---
require_once 'includes/footer.php'; 
?>


<script src="../assets/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script src="../assets/js/confirmaciones.js"></script>