<?php
session_start();

// Verificamos si el usuario está autenticado y si es Médico
if (!isset($_SESSION['autenticado']) || $_SESSION['rol'] !== 'Medico') {
    header('Location: ../index.php?error=acceso_denegado');
    exit();
}

// Incluimos los componentes de la cabecera y el menú lateral del médico
require_once 'includes/header.php';
require_once 'includes/sidebar.php'; 
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestionar Pacientes</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Listado de Pacientes Registrados</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo-paciente">
                            <i class="fas fa-user-plus"></i> Nuevo Paciente
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tabla-gestionar-pacientes" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
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
// Incluimos el pie de página
require_once 'includes/footer.php'; 
?>

<script src="../assets/adminlte/plugins/sweetalert2/sweetalert2.all.min.js"></script>
<script src="../assets/js/gestionar_pacientes.js"></script>

<div id="formulario-paciente-template" style="display: none;">
    <form id="form-editar-paciente" method="POST" class="text-left">
        <input type="hidden" name="numero_documento_original" id="numero_documento_original">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="numero_documento">Nº de Documento</label>
                    <input type="text" id="numero_documento" name="numero_documento" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-6">
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
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="nombre">Nombres</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="apellido">Apellidos</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="telefono_whatsapp">Teléfono (WhatsApp)</label>
                    <input type="tel" class="form-control" id="telefono_whatsapp" name="telefono_whatsapp">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sexo">Sexo</label>
                    <select class="form-control" id="sexo" name="sexo" required>
                        <option value="FEMENINO">Femenino</option>
                        <option value="MASCULINO">Masculino</option>
                    </select>
                </div>
            </div>
             <div class="col-md-6">
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
        </div>

        <div class="form-group">
            <label for="direccion">Dirección de Residencia</label>
            <input type="text" class="form-control" id="direccion" name="direccion">
        </div>
        <div class="form-group">
            <label for="profesion">Profesión u Ocupación</label>
            <input type="text" class="form-control" id="profesion" name="profesion">
        </div>

        <div class="row" id="seccion-gestacion-editar" style="display: none;">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="embarazada">¿Paciente en gestación?</label>
                    <select class="form-control" id="embarazada" name="embarazada">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="semanas_gestacion">Semanas de Gestación</label>
                    <input type="number" class="form-control" id="semanas_gestacion" name="semanas_gestacion" min="1" max="45" disabled>
                </div>
            </div>
        </div>
    </form>
</div>