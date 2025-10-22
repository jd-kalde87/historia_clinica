<?php
session_start();

// Verificamos si el usuario está autenticado y si es Administrador
if (!isset($_SESSION['autenticado']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../index.php?error=acceso_denegado');
    exit();
}

// Incluimos los componentes de la cabecera y el menú lateral del admin
require_once 'includes/header.php';
require_once 'includes/sidebar_admin.php'; 
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestionar Usuarios</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Listado de Médicos y Secretarias</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" id="btn-nuevo-usuario">
                            <i class="fas fa-plus"></i> Añadir Nuevo Usuario
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tabla-usuarios" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Rol</th>
                                <th>Usuario</th>
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

<script src="../assets/js/gestion_usuarios.js"></script>

<div id="formulario-usuario-template" style="display: none;">
    <form id="form-usuario" method="POST" class="text-left">
        <input type="hidden" name="id_usuario" id="id_usuario" value="0">
        
        <div class="form-group">
            <label for="rol">Rol del Usuario</label>
            <select id="rol" name="rol" class="form-control" required>
                <option value="Secretaria">Secretaria</option>
                <option value="Medico">Médico</option>
            </select>
        </div>

        <div class="form-group">
            <label for="nombre_medico">Nombres</label>
            <input type="text" id="nombre_medico" name="nombre_medico" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="apellido_medico">Apellidos</label>
            <input type="text" id="apellido_medico" name="apellido_medico" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="usuario">Nombre de Usuario</label>
            <input type="text" id="usuario" name="usuario" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control">
            <small id="passwordHelp" class="form-text text-muted"></small>
        </div>
        
        <div id="campos-medico" style="display: none;">
             <div class="form-group">
                <label for="especialidad">Especialidad</label>
                <input type="text" id="especialidad" name="especialidad" class="form-control">
            </div>
             <div class="form-group">
                <label for="registro_medico">Registro Médico</label>
                <input type="text" id="registro_medico" name="registro_medico" class="form-control">
            </div>
        </div>
    </form>
</div>
