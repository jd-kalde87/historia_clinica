<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard_secretaria.php" class="brand-link">
        <img src="../img/cliniax2.png" alt="CLINIAX Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">CLINIAX</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-tie fa-2x img-circle elevation-2" style="color:#007bff;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block" style="white-space: normal;"><?php echo htmlspecialchars($nombreMedico); ?></a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="dashboard_secretaria.php" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-header">GESTIÓN DE CITAS</li>

                <li class="nav-item">
                    <a href="agendar_cita.php" class="nav-link">
                        <i class="nav-icon fas fa-calendar-plus"></i>
                        <p>Agenda General</p> </a>
                </li>

                <li class="nav-item">
                    <a href="confirmar_citas.php" class="nav-link">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Confirmar Citas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="ver_citas.php" class="nav-link">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Ver Citas del Día</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pacientes_secretaria.php" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Listado de Pacientes</p>
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