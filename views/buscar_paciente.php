<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Buscar Paciente</h1>
                </div>
            </div></div></div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Ingrese el número de documento</h3>
                        </div>
                        <div class="card-body">
                            <form id="form-buscar-paciente">
                                <div class="input-group">
                                    <input type="search" id="numero_documento_busqueda" class="form-control form-control-lg" placeholder="Documento del paciente..." required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-lg btn-default">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="resultado-busqueda" class="mt-4">
                        </div>

                </div>
            </div>
        </div></section>
    </div>
<?php require_once 'includes/footer.php'; ?>

<script src="../assets/js/buscar_paciente.js"></script>