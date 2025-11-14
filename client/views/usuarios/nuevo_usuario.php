<?php
session_start(); // ¡DEBE SER LA PRIMERA LÍNEA SIN NADA ANTES!

// 1. Verificación básica: Si no hay sesión, redirige al login.
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener el rol del usuario logueado
$rol_usuario_logueado = (int)$_SESSION['usuario']['rol'];

// 2. Verificación de rol para esta página (solo si se está logueado)
if ($rol_usuario_logueado !== 1) {
    // Si no es rol 1 (Administrador), muestra un mensaje de error y detiene la ejecución del contenido.
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Acceso Denegado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../stylesheets/style.css">
    </head>

    <body>
        <div class="container mt-5">
            <div class="alert alert-danger text-center">
                <h3>🚫 Acceso Denegado</h3>
                <p>No tienes los permisos necesarios para acceder a esta sección.</p>
                <p>Solo los administradores pueden añadir nuevos usuarios.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Volver al Panel Principal</a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>
<?php
    exit(); // Detiene completamente la ejecución del script aquí
}

// 3. Si el usuario es administrador (rol 1), el script continúa con el contenido de la página.
// Este es el resto del código que ya tienes para el formulario de añadir usuario
$apiUsuarios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=usuarios";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["nombre"]) && !empty($_POST["contrasena"]) && isset($_POST["rol"])) {
        $nombre = $_POST["nombre"];
        $contrasena_plana = $_POST["contrasena"];
        $rol = $_POST["rol"];

        // ¡IMPORTANTE! NUNCA ALMACENES CONTRASEÑAS EN TEXTO PLANO EN PRODUCCIÓN.
        // Usa password_hash($contrasena_plana, PASSWORD_DEFAULT);
        // Y el campo 'contrasena' en la DB debe ser VARCHAR(255).
        $data = [
            "nombre" => $nombre,
            "contrasena" => $contrasena_plana, // CAMBIAR A $contrasena_hasheada EN PRODUCCIÓN
            "fecha_creacion" => date("Y-m-d"), // Fecha actual
            "rol" => $rol
        ];

        $ch = curl_init($apiUsuarios);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        echo '<div class="alert text-center m-3 ' . ($http_code === 201 ? 'alert-success' : 'alert-danger') . '">';
        if (is_array($responseData)) {
            foreach ($responseData as $clave => $valor) {
                echo "<strong>" . htmlspecialchars($clave) . ":</strong> " . htmlspecialchars($valor) . "<br>";
            }
        } else {
            echo 'Respuesta inesperada de la API: ' . htmlspecialchars($response);
        }
        if ($curl_error) {
            echo "<br><strong>cURL Error:</strong> " . htmlspecialchars($curl_error);
        }
        echo '</div>';

        if ($http_code === 201) {
            // Podrías redirigir a listar_usuarios.php aquí si el usuario se creó correctamente
            // header("Location: listar_usuarios.php?status=success&message=" . urlencode("Usuario añadido correctamente."));
            // exit();
        }
    } else {
        echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Nombre</strong>, <strong>Contraseña</strong> y <strong>Rol</strong> son obligatorios.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Añadir Nuevo Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
    <div class="p-3 navbar-color">
        <h3 class="text-white">Añadir Nuevo Usuario</h3>
    </div>

    <div class="container mt-4">
        <div class="card-body rounded-4 p-4">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de Usuario*</label>
                            <input name="nombre" type="text" class="form-control" id="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña*</label>
                            <input name="contrasena" type="password" class="form-control" id="contrasena" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol*</label>
                            <select name="rol" class="form-select" id="rol" required>
                                <option value="">Selecciona un rol</option>
                                <option value="1" <?php echo (isset($_POST['rol']) && $_POST['rol'] == '1') ? 'selected' : ''; ?>>Administrador</option>
                                <option value="2" <?php echo (isset($_POST['rol']) && $_POST['rol'] == '2') ? 'selected' : ''; ?>>Gerente</option>
                                <option value="5" <?php echo (isset($_POST['rol']) && $_POST['rol'] == '3') ? 'selected' : ''; ?>>Básico/Empleado</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn navbar-color text-white me-2">Añadir Usuario</button>
                    <a href="listar_usuarios.php" class="btn btn-danger">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>