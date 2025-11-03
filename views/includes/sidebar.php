<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard.php" class="brand-link">
        <img src="../img/cliniax2.png" alt="CLINIAX Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">CLINIAX</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-md fa-2x img-circle elevation-2" style="color:#007bff;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block" style="white-space: normal;"><?php echo 'Dr. ' . htmlspecialchars($nombreMedico); ?></a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>
                
                <li class="nav-header">GESTIÓN CLÍNICA</li>

                <li class="nav-item">
                    <a href="buscar_paciente.php" class="nav-link">
                        <i class="nav-icon fas fa-notes-medical"></i>
                        <p>
                            Iniciar Consulta
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="citas_agendadas.php" class="nav-link">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>
                            Citas Agendadas
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="listar_pacientes.php" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            Listado de Pacientes
                        </p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="gestionar_pacientes.php" class="nav-link">
                        <i class="nav-icon fas fa-user-edit"></i>
                        <p>
                            Gestionar Pacientes
                        </p>
                    </a>
                </li>
                <li class="nav-header">CONFIGURACIÓN</li>
                <li class="nav-item">
                    <a href="../controllers/logout_controller.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>
                            Cerrar Sesión
                        </p>
                    </a>
                </li>

            </ul>
        </nav>
        </div>
    </aside>