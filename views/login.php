<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>CLINIAX | Login</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="assets/adminlte/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        
        <div class="login-logo">
            <img src="img/cliniax1.png" alt="CLINIAX Logo" style="width:300px; height: auto;">
        </div>
        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Inicia sesión para comenzar</p>
                     <?php
                        if (isset($_GET['error'])) {
                            $error = $_GET['error'];
                            $mensaje = 'Error desconocido.';
                            if ($error == 'campos_vacios') {
                                $mensaje = 'Por favor, completa todos los campos.';
                            } elseif ($error == 'credenciales_invalidas') {
                                $mensaje = 'Usuario o contraseña incorrectos.';
                            }
                            echo "<div class='alert alert-danger'>$mensaje</div>";
                        }
                    ?>
                <form action="controllers/login_controller.php" method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="usuario" placeholder="Usuario" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>                  
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                        </div>
                    </div>
                </form>

            </div>
            </div>
    </div>
    <script src="assets/adminlte/plugins/jquery/jquery.min.js"></script>
    <script src="assets/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/adminlte/dist/js/adminlte.min.js"></script>
</body>
</html>