<?php
    require_once 'includes/header.php';
    require_once 'includes/sidebar_secretaria.php';
    require_once '../core/db_connection.php';

    // Obtenemos el total de pacientes para una de las tarjetas
    $conexion = conectarDB();
    $sql_total_pacientes = "SELECT COUNT(numero_documento) AS total FROM pacientes";
    $resultado_total = $conexion->query($sql_total_pacientes);
    $total_pacientes = $resultado_total->fetch_assoc()['total'];
    $conexion->close();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Panel de Secretaria</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>Agendar Cita</h3>
                            <p>Ir a la agenda principal</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <a href="agendar_cita.php" class="small-box-footer">
                            Agendar <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>Citas de Hoy</h3>
                            <p>Ver el listado del día actual</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <a href="ver_citas.php" class="small-box-footer">
                            Ver listado <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo $total_pacientes; ?></h3>
                            <p>Pacientes Registrados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="pacientes_secretaria.php" class="small-box-footer">
                            Ver todos <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="texto-a-copiar" style="display:none;"></div>
<?php require_once 'includes/footer.php'; ?>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const agendadoStatus = urlParams.get('agendado');

    if (agendadoStatus === 'ok') {
        const telefono = urlParams.get('telefono');
        const mensajeUrl = urlParams.get('mensaje_url');
        const mensajeRaw = urlParams.get('mensaje_raw'); // Este es el texto para copiar
        
        // Ponemos el texto sin codificar en nuestro div oculto
        document.getElementById('texto-a-copiar').textContent = decodeURIComponent(mensajeRaw);
        
        // Limpiamos el número de teléfono
        let telefonoLimpio = '';
        if (telefono) {
            telefonoLimpio = telefono.replace(/[^0-9]/g, '');
            if (telefonoLimpio.length === 10) {
                telefonoLimpio = '57' + telefonoLimpio;
            }
        }

        const whatsappLink = `https://wa.me/${telefonoLimpio}?text=${mensajeUrl}`;

        // Creamos el contenido HTML de la notificación con los dos botones
        const toastrContent = `
            Cita agendada exitosamente.<br><br>
            <div class="btn-group">
                <a href="${whatsappLink}" target="_blank" class="btn btn-sm btn-light" ${!telefono ? 'style="display:none;"' : ''}>
                    <i class="fab fa-whatsapp"></i><b> Enviar</b>
                </a>
                <button type="button" class="btn btn-sm btn-light" id="copiar-confirmacion-btn">
                    <i class="fas fa-copy"></i><b> Copiar</b>
                </button>
            </div>
        `;
        
        // Mostramos la notificación
        toastr.success(toastrContent, '¡Éxito!', {
            timeOut: 0, 
            extendedTimeOut: 0, 
            closeButton: true, 
            allowHtml: true,
            onShown: function() {
                // Añadimos el evento al botón de copiar DESPUÉS de que la notificación se ha mostrado
                $('#copiar-confirmacion-btn').on('click', function() {
                    const textoParaCopiar = document.getElementById('texto-a-copiar').textContent;
                    navigator.clipboard.writeText(textoParaCopiar).then(function() {
                        toastr.info('¡Confirmación copiada al portapapeles!');
                    });
                });
            }
        });
        
    } else if (agendadoStatus === 'error') {
        const msg = urlParams.get('msg');
        toastr.error(msg || 'Ocurrió un error al agendar la cita.');
    }
</script>