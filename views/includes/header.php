<?php
// Iniciar la sesión solo si no hay una sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LÍNEA CORREGIDA ---
// Incluimos la configuración para poder usar BASE_URL (esta línea faltaba)
require_once __DIR__ . '/../../core/config.php';

// Comprobar si el usuario está autenticado
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Obtener el nombre del médico de la sesión para mostrarlo
$nombreMedico = $_SESSION['nombre_completo_medico'] ?? 'Médico';


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Clínica | Dashboard</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/adminlte/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/adminlte/plugins/fullcalendar/main.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">

    <script>
        const BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">