<?php
session_start();

// Basic session and role check
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

/*$rol_usuario_logueado = (int)$_SESSION['usuario']['rol'];

// Check if user is authorized (e.g., admin (1) or manager (2) can edit products)
if ($rol_usuario_logueado !== 1 && $rol_usuario_logueado !== 2) {
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
                <p>No tienes los permisos necesarios para editar servicios.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Volver al Panel Principal</a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit();
}*/

$apiservicios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=servicios";
$servicio = null;
$message = '';
$message_type = '';

// Get product ID from URL
$id_servicio = $_GET['id'] ?? null;

if (!$id_servicio) {
    $message = '❌ ID de servicio no proporcionado.';
    $message_type = 'alert-danger';
    // Optionally redirect back to listar_servicios.php
    // header("Location: listar_servicios.php?status=error&message=" . urlencode($message));
    // exit();
} else {
    // Fetch product data on page load
    $ch = curl_init($apiservicios . '&id=' . $id_servicio);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $servicio = json_decode($response, true);
        if (!$servicio) { // If product is not found or JSON is invalid
            $message = '❌ servicio no encontrado.';
            $message_type = 'alert-danger';
            $servicio = null; // Ensure $servicio is null
        }
    } else {
        $message = '❌ Error al cargar los datos del servicio: ' . ($response ? json_decode($response, true)['message'] ?? 'Error desconocido de la API.' : 'Error de conexión.');
        $message_type = 'alert-danger';
    }
}

// Handle form submission for updating product
if ($_SERVER["REQUEST_METHOD"] == "POST" && $id_servicio) {
    $codigo_servicio = $_POST["codigo_servicio"] ?? null;
    $servicio_nombre = $_POST["servicio_nombre"] ?? ''; // Usamos 'servicio_nombre' para el campo del formulario
    $importe = $_POST["importe"] ?? '';

    // Basic validation
    if (empty($servicio_nombre) || empty($importe)) {
        $message = '⚠️ Los campos <strong>Nombre del servicio</strong> e <strong>Importe</strong> son obligatorios.';
        $message_type = 'alert-warning';
    } else {
        $data = [
            "codigo_servicio" => $codigo_servicio,
            "servicio" => $servicio_nombre,
            "importe" => (float)$importe
        ];

        $ch = curl_init($apiservicios . '&id=' . $id_servicio);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Use PUT for updates
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($http_code === 200) {
            $message = '✅ servicio actualizado correctamente.';
            $message_type = 'alert-success';
            // Update the $servicio variable to reflect changes immediately
            $servicio = $data;
            $servicio['id_servicio'] = $id_servicio; // Add back the ID for consistent data
        } else {
            $message = '❌ Error al actualizar el servicio: ' . ($responseData['message'] ?? 'Respuesta inesperada de la API.');
            $message_type = 'alert-danger';
            if ($curl_error) {
                $message .= "<br>cURL Error: " . htmlspecialchars($curl_error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar servicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
    <div class="p-3 navbar-color">
        <h3 class="text-white">Editar servicio</h3>
    </div>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?> text-center m-3" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($servicio): ?>
            <div class="card-body rounded-4 p-4">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_servicio" class="form-label">ID del servicio</label>
                                <input type="text" class="form-control" id="id_servicio" value="<?php echo htmlspecialchars($servicio['id_servicio'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_servicio" class="form-label">Código del servicio</label>
                                <input name="codigo_servicio" type="text" class="form-control" id="codigo_servicio" value="<?php echo htmlspecialchars($servicio['codigo_servicio'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="servicio_nombre" class="form-label">Nombre del servicio*</label>
                                <input name="servicio_nombre" type="text" class="form-control" id="servicio_nombre" value="<?php echo htmlspecialchars($servicio['servicio'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="importe" class="form-label">Importe*</label>
                                <input name="importe" type="number" step="0.01" class="form-control" id="importe" value="<?php echo htmlspecialchars($servicio['importe'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn navbar-color text-white me-2">Actualizar servicio</button>
                        <a href="listar_servicios.php" class="btn btn-danger">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php elseif (!$message): ?>
            <p class="text-center">Cargando datos del servicio...</p>
        <?php endif; ?>
    </div>
    <div class="d-flex justify-content-end mb-3">
        <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>