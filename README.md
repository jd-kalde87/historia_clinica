# Sistema de Gestión de Citas e Historias Clínicas 🏥

Este es un sistema web integral desarrollado desde cero en PHP y MySQL, diseñado para la administración completa de un consultorio médico. El sistema gestiona tres roles de usuario principales (Administrador, Secretaria y Médico), cada uno con un panel y funcionalidades específicas.

---

## ✨ Características Principales (Detalles Funcionales)

El sistema está dividido en tres módulos principales según el rol del usuario:

### 👨‍💼 Módulo de Administración
* **Gestión de Usuarios (CRUD):** Creación, lectura, actualización y eliminación de cuentas de usuario (Médicos y Secretarias).
* **Gestión de Pacientes (CRUD):** Módulo para buscar y editar la información demográfica de cualquier paciente en el sistema.

### 👩‍💼 Módulo de Secretaría
* **Agenda Interactiva (FullCalendar):** Un calendario completo para visualizar todas las citas por mes, semana y día.
* **Gestión de Citas:** Permite agendar nuevas citas, reprogramarlas (editar) y cancelarlas.
* **Registro de Pacientes:** Permite registrar nuevos pacientes directamente desde el formulario de agendamiento de citas.
* **Módulo de Confirmación:** Una vista de tabla dedicada para ver las citas futuras y enviar recordatorios/confirmaciones por WhatsApp.

### 👨‍⚕️ Módulo del Médico
* **Agenda del Día:** Muestra una lista de las citas agendadas para el médico que ha iniciado sesión.
* **Selector de Fecha:** Permite al médico ver su agenda de citas para hoy, mañana o cualquier fecha futura.
* **Flujo de Consulta:** Al hacer clic en "Iniciar Consulta", la cita se marca automáticamente como "Completada" y desaparece de la lista de pendientes.
* **Historia Clínica Digital:**
    * Formulario completo para el registro de la consulta (Motivo, Enfermedad Actual, Antecedentes, Signos Vitales, etc.).
    * Carga automática de los datos del paciente.
    * Permite la **actualización de los datos demográficos** del paciente (teléfono, dirección, etc.) al guardar la consulta.
* **Receta Médica:** Creación dinámica de recetas con múltiples medicamentos.
* **Archivos Adjuntos:** Permite subir archivos (PDFs, JPG, PNG) a la consulta del paciente (ej. exámenes de laboratorio, radiografías).
* **Generación de Reportes (FPDF):**
    * Generación de un PDF profesional de la **Historia Clínica** (con paginación y firma del médico solo en la última hoja).
    * Generación de un PDF de la **Receta Médica**.
* **Envío por WhatsApp:** Muestra un resumen de la consulta y la receta en una ventana modal (SweetAlert2) listo para ser enviado al paciente.

---

## 🛠️ Detalles Técnicos (Stack)

* **Backend:** PHP 8.x (Nativo, sin frameworks).
* **Frontend:** HTML5, CSS3, JavaScript (ES6+).
* **Base de Datos:** MySQL / MariaDB.
* **Servidor Local:** XAMPP.

### 🚀 Librerías Utilizadas
* **AdminLTE 3:** Plantilla principal para el dashboard y la interfaz de usuario.
* **jQuery:** Requerido por AdminLTE y DataTables.
* **FullCalendar.js:** Para la creación y visualización de la agenda de citas.
* **DataTables.js:** Para la creación de tablas interactivas (con búsqueda y paginación).
* **SweetAlert2:** Para todas las ventanas modales (pop-ups) y alertas.
* **FPDF:** Librería de PHP para la generación de reportes en PDF del lado del servidor.
* **Fetch API (JavaScript):** Para la comunicación asíncrona con el backend (AJAX) en los módulos de gestión.

### 🔒 Seguridad y Buenas Prácticas
* **Sentencias Preparadas (MySQLi):** Todas las consultas a la base de datos están parametrizadas para prevenir inyección SQL.
* **Transacciones SQL:** Se utilizan en operaciones críticas (como guardar una historia clínica) para asegurar la integridad de los datos. Si algo falla, se revierte toda la operación.
* **Control de Sesiones por Rol:** El sistema verifica el rol del usuario (`$_SESSION['rol']`) en cada página y controlador para prevenir acceso no autorizado.
* **Hashing de Contraseñas:** Las contraseñas de los usuarios se almacenan en la base de datos usando `password_hash()` de PHP.

---

## 🔧 Instalación y Puesta en Marcha

1.  Clonar o descargar este repositorio en tu carpeta `htdocs` de XAMPP (ej. `C:\xampp\htdocs\clinical_system`).
2.  Iniciar los servicios de **Apache** y **MySQL** en el panel de control de XAMPP.
3.  Abrir **phpMyAdmin** (normalmente `http://localhost/phpmyadmin`).
4.  Crear una nueva base de datos llamada `historia_clinica_db`.
5.  Importar el archivo `historia_clinica_db.sql` en la base de datos que acabas de crear.
6.  **(Importante)** Crear el archivo de configuración:
    * Ir a la carpeta `core/`.
    * Crear un archivo llamado `config.php`.
    * Copiar y pegar el siguiente contenido (ajustando la `BASE_URL` y las credenciales si es necesario):

    ```php
    <?php
    // URL base de tu proyecto
    define('BASE_URL', 'http://localhost/clinical_system/');
    
    // Credenciales de la Base de Datos
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'historia_clinica_db');
    ?>
    ```
7.  Acceder al sistema desde tu navegador en la `BASE_URL` (ej. `http://localhost/clinical_system/`).

---
