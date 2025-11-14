<?php
session_start(); // ¡DEBE SER LA PRIMERA LÍNEA SIN NADA ANTES!

// 1. Verificación básica: Si no hay sesión, redirige al login.
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: ../login.php"); // Asegúrate de que la ruta a login.php sea correcta
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
                <p>Solo los administradores pueden gestionar usuarios.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Volver al Panel Principal</a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>

    </html>
<?php
    exit(); // Detiene completamente la ejecución del script aquí
}

$apiUsuarios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=usuarios";
$usuario_id = $_GET["id_usuario"] ?? null;
$usuario_data = [];

// --- Lógica para Cargar Datos del Usuario (GET request) ---
if ($usuario_id && $_SERVER["REQUEST_METHOD"] == "GET") {
    $ch = curl_init($apiUsuarios . "&id_usuario=" . urlencode($usuario_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200) {
        $api_response_data = json_decode($response, true);
        if (is_array($api_response_data) && !empty($api_response_data)) {
            $usuario_data = $api_response_data[0];
            if (!isset($usuario_data['id_usuario'])) {
                echo '<div class="alert alert-danger text-center m-3">Error: ID del usuario no coincide con los datos cargados.</div>';
                $usuario_data = [];
            }
        } else {
            echo '<div class="alert alert-danger text-center m-3">Error: No se encontró el usuario con el ID especificado.</div>';
            $usuario_data = [];
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar el usuario para editar (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
    }
}

// --- Lógica para Procesar la Edición (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id_post = $_POST["id_usuario_hidden"] ?? null;
    if (!$usuario_id_post) {
        echo '<div class="alert alert-danger text-center m-3">⚠️ ID de usuario no proporcionado para actualizar.</div>';
    } elseif (!empty($_POST["nombre"]) && isset($_POST["rol"])) {
        $nombre = $_POST["nombre"];
        $rol = $_POST["rol"];
        $contrasena_plana = $_POST["contrasena"] ?? null; // La contraseña puede no ser modificada

        $data = [
            "id_usuario" => $usuario_id_post,
            "nombre" => $nombre,
            "rol" => $rol
        ];

        // Si se proporcionó una nueva contraseña, la incluimos y la hasheamos (idealmente)
        if (!empty($contrasena_plana)) {
            // $data["contrasena"] = password_hash($contrasena_plana, PASSWORD_DEFAULT); // USAR EN PRODUCCIÓN
            $data["contrasena"] = $contrasena_plana; // Para esta demo
        }

        $ch = curl_init($apiUsuarios);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        echo '<div class="alert text-center m-3 ' . ($http_code === 200 ? 'alert-success' : 'alert-danger') . '">';
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

        if ($http_code === 200) {
            header("Location: listar_usuarios.php?status=success&message=" . urlencode("Usuario actualizado correctamente."));
            exit();
        } else {
            // Recargar datos para que el formulario no se borre en caso de error
            if ($usuario_id_post) {
                $ch_reload = curl_init($apiUsuarios . "&id_usuario=" . urlencode($usuario_id_post));
                curl_setopt($ch_reload, CURLOPT_RETURNTRANSFER, true);
                $response_reload = curl_exec($ch_reload);
                curl_close($ch_reload);
                $api_response_data_reload = json_decode($response_reload, true);
                if (is_array($api_response_data_reload) && !empty($api_response_data_reload)) {
                    $usuario_data = $api_response_data_reload[0];
                }
            }
        }
    } else {
        echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Nombre</strong> y <strong>Rol</strong> son obligatorios.</div>';
        // Recargar datos para que el formulario no se borre en caso de error
        if ($usuario_id_post) {
            $ch_reload = curl_init($apiUsuarios . "&id_usuario=" . urlencode($usuario_id_post));
            curl_setopt($ch_reload, CURLOPT_RETURNTRANSFER, true);
            $response_reload = curl_exec($ch_reload);
            curl_close($ch_reload);
            $api_response_data_reload = json_decode($response_reload, true);
            if (is_array($api_response_data_reload) && !empty($api_response_data_reload)) {
                $usuario_data = $api_response_data_reload[0];
            }
        }
    }
}
?>

<div class="p-3 navbar-color">
    <h3 class="text-white">Editar Usuario</h3>
</div>

<div class="container mt-4">
    <div class="card-body rounded-4 p-4">
        <form action="" method="POST">
            <input type="hidden" name="id_usuario_hidden" value="<?php echo htmlspecialchars($usuario_id ?? ''); ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de Usuario*</label>
                        <input name="nombre" type="text" class="form-control" id="nombre" value="<?php echo htmlspecialchars($usuario_data['nombre'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                        <input name="contrasena" type="password" class="form-control" id="contrasena">
                        <small class="form-text text-muted">Deja este campo en blanco para mantener la contraseña actual.</small>
                    </div>
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol*</label>
                        <select name="rol" class="form-select" id="rol" required>
                            <option value="">Selecciona un rol</option>
                            <option value="1" <?php echo (isset($usuario_data['rol']) && $usuario_data['rol'] == '1') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="2" <?php echo (isset($usuario_data['rol']) && $usuario_data['rol'] == '2') ? 'selected' : ''; ?>>Gerente</option>
                            <option value="3" <?php echo (isset($usuario_data['rol']) && $usuario_data['rol'] == '3') ? 'selected' : ''; ?>>Contable</option>
                            <option value="4" <?php echo (isset($usuario_data['rol']) && $usuario_data['rol'] == '4') ? 'selected' : ''; ?>>Comercial</option>
                            <option value="5" <?php echo (isset($usuario_data['rol']) && $usuario_data['rol'] == '5') ? 'selected' : ''; ?>>Básico/Empleado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Creación</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['fecha_creacion'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn navbar-color text-white me-2">Confirmar Cambios</button>
                <a href="listar_usuarios.php" class="btn btn-danger">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</body>

</html>