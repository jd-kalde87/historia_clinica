<?php
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
// Ya no necesitamos la conexión a la BD ni la consulta PHP aquí
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Listado de Pacientes Registrados</h1>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pacientes en el sistema</h3>
                </div>
                <div class="card-body">
                    <table id="tabla-listar-pacientes" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nº Documento</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Sexo</th>
                                <th>Fecha de Nacimiento</th>
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
<?php require_once 'includes/footer.php'; ?>

<script src="../assets/js/listar_pacientes.js"></script>