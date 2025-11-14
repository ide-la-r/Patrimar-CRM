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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <style>
        /* Estilos adicionales para mejorar la tabla y los filtros */
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }

        .filter-section h4 {
            color: #343a40;
        }

        .filter-section .form-label {
            font-weight: bold;
            color: #495057;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .action-buttons {
            min-width: 120px;
        }

        /* Ocultar columnas menos importantes por defecto para pantallas pequeñas */
        @media (max-width: 991.98px) {
            .hide-on-mobile {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php
    $apiGeneral = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=usuarios";
    $message_from_api = "";

    // --- Lógica para Borrar Usuario ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
        $usuario_id_to_delete = $_POST["id_usuario"] ?? null;

        if ($usuario_id_to_delete) {
            // No permitir que un administrador se elimine a sí mismo
            if ($usuario_id_to_delete == ($_SESSION['usuario']['id_usuario'] ?? null)) {
                echo '<div class="alert alert-danger text-center m-3">⚠️ No puedes eliminar tu propia cuenta de administrador.</div>';
            } else {
                $ch = curl_init($apiGeneral . "&id_usuario=" . urlencode($usuario_id_to_delete));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
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
            }
        } else {
            echo '<div class="alert alert-danger text-center m-3">⚠️ ID de usuario no proporcionado para eliminar.</div>';
        }
    }

    // --- Lógica para Obtener Usuarios (con filtros) ---
    $queryParams = [];
    if (isset($_GET['nombre']) && !empty($_GET['nombre'])) {
        $queryParams['nombre'] = $_GET['nombre'];
    }
    if (isset($_GET['rol']) && $_GET['rol'] !== '') {
        $queryParams['rol'] = $_GET['rol'];
    }

    $url = $apiGeneral;
    if (!empty($queryParams)) {
        $url .= "&" . http_build_query($queryParams);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $usuarios = [];
    if ($http_code === 200) {
        $api_response_data = json_decode($response, true);

        if (isset($api_response_data['message'])) {
            $message_from_api = htmlspecialchars($api_response_data['message']);
            $usuarios = [];
        } elseif (is_array($api_response_data)) {
            $usuarios = $api_response_data;
        } else {
            $usuarios = [];
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API, pero el código HTTP es 200.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar usuarios desde la API (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
    }

    // Función para traducir el rol a texto
    function get_rol_name($rol_id)
    {
        switch ($rol_id) {
            case 1:
                return 'Administrador';
            case 2:
                return 'Gerente';
            case 3:
                return 'Contable';
            case 4:
                return 'Comercial';
            case 5:
                return 'Básico/Empleado';
            default:
                return 'Desconocido';
        }
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Listado de Usuarios</h3>
    </div>

    <div class="container mt-4">
        <div class="filter-section">
            <h4 class="mb-3">Filtros</h4>
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="nombre" class="form-label">Nombre de Usuario</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_GET['nombre'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="rol" class="form-label">Rol</label>
                    <select class="form-select" id="rol" name="rol">
                        <option value="">Todos</option>
                        <option value="1" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '1') ? 'selected' : ''; ?>>Administrador</option>
                        <option value="2" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '2') ? 'selected' : ''; ?>>Gerente</option>
                        <option value="3" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '3') ? 'selected' : ''; ?>>Contable</option>
                        <option value="4" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '4') ? 'selected' : ''; ?>>Comercial</option>
                        <option value="5" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '5') ? 'selected' : ''; ?>>Básico/Empleado</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex justify-content-end">
                    <button type="submit" class="btn navbar-color text-white me-2">Aplicar Filtros</button>
                    <a href="listar_usuarios.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <a href="nuevo_usuario.php" class="btn navbar-color text-white">Añadir Nuevo Usuario</a>
        </div>

        <div class="table-responsive">
            <?php if (!empty($usuarios)) : ?>
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Usuario</th>
                            <th>Rol</th>
                            <th>Fecha de Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['id_usuario'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($usuario['nombre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(get_rol_name($usuario['rol'] ?? 0)); ?></td>
                                <td><?php echo htmlspecialchars($usuario['fecha_creacion'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <div class="d-flex flex-nowrap">
                                        <form action="editar_usuario.php" method="GET" class="me-2">
                                            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuario['id_usuario'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Editar</button>
                                        </form>
                                        <form action="" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este usuario?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuario['id_usuario'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                <?php echo ($usuario['id_usuario'] == ($_SESSION['usuario']['id_usuario'] ?? null)) ? 'disabled title="No puedes eliminar tu propia cuenta"' : ''; ?>>
                                                Borrar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="alert alert-info text-center mt-4">
                    <?php
                    echo $message_from_api ? $message_from_api : 'No se encontraron usuarios.';
                    echo (!empty($queryParams) && !$message_from_api ? ' Prueba a limpiar los filtros.' : '');
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <a href="../index.html" class="btn btn-danger">Volver</a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>