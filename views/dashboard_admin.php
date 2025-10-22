<?php
session_start();

// Verificamos si el usuario está autenticado y si es Administrador
if (!isset($_SESSION['autenticado']) || $_SESSION['rol'] !== 'Administrador') {
    // Si no está autenticado o no es administrador, lo redirigimos a la página de login
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
                    <h1 class="m-0">Panel de Administración</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="alert alert-info">
                <h5>¡Bienvenido, Administrador!</h5>
                <p>Desde aquí podrás gestionar los usuarios del sistema y otras configuraciones importantes.</p>
            </div>
        </div>
    </section>
</div>

<?php
// Incluimos el pie de página
require_once 'includes/footer.php'; 
?>