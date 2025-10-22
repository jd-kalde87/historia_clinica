<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard_admin.php" class="brand-link">
        <img src="../assets/adminlte/dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Admin C.M. Majestic</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-shield fa-2x img-circle elevation-2" style="color:#a9a9a9;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block" style="white-space: normal;"><?php echo htmlspecialchars($_SESSION['nombre_completo_medico'] ?? 'Administrador'); ?></a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="dashboard_admin.php" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-header">ADMINISTRACIÓN</li>

                <li class="nav-item">
                    <a href="gestionar_usuarios.php" class="nav-link">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>Gestionar Usuarios</p>
                    </a>
                </li>
                
                <li class="nav-header">CONFIGURACIÓN</li>
                <li class="nav-item">
                    <a href="../controllers/logout_controller.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Cerrar Sesión</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>