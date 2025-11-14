<?php
session_start();

// Basic session and role check
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

/*$rol_usuario_logueado = (int)$_SESSION['usuario']['rol'];

// Check if user is authorized (e.g., admin (1) or manager (2) can add products)
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
                <p>No tienes los permisos necesarios para añadir productos.</p>
                <a href="../index.php" class="btn btn-primary mt-3">Volver al Panel Principal</a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit();
}*/

$apiProductos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=productos";

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo_producto = $_POST["codigo_producto"] ?? null;
    $producto_nombre = $_POST["producto_nombre"] ?? ''; // Usamos 'producto_nombre' para el campo del formulario
    $importe = $_POST["importe"] ?? '';

    // Basic validation based on your table structure
    if (empty($producto_nombre) || empty($importe)) {
        $message = '⚠️ Los campos <strong>Nombre del Producto</strong> e <strong>Importe</strong> son obligatorios.';
        $message_type = 'alert-warning';
    } else {
        $data = [
            "codigo_producto" => $codigo_producto,
            "producto" => $producto_nombre, // Se envía como 'producto' a la API
            "importe" => (float)$importe
        ];

        $ch = curl_init($apiProductos);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($http_code === 201) {
            $message = '✅ Producto añadido correctamente.';
            $message_type = 'alert-success';
            // Clear form fields on success
            $_POST = [];
        } else {
            $message = '❌ Error al añadir producto: ' . ($responseData['message'] ?? 'Respuesta inesperada de la API.');
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
    <title>Añadir Nuevo Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
    <div class="p-3 navbar-color">
        <h3 class="text-white">Añadir Nuevo Producto</h3>
    </div>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?> text-center m-3" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card-body rounded-4 p-4">
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="codigo_producto" class="form-label">Código del Producto</label>
                            <input name="codigo_producto" type="text" class="form-control" id="codigo_producto" value="<?php echo htmlspecialchars($_POST['codigo_producto'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="producto_nombre" class="form-label">Nombre del Producto*</label>
                            <input name="producto_nombre" type="text" class="form-control" id="producto_nombre" value="<?php echo htmlspecialchars($_POST['producto_nombre'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="importe" class="form-label">Importe*</label>
                            <input name="importe" type="number" step="0.01" class="form-control" id="importe" value="<?php echo htmlspecialchars($_POST['importe'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn navbar-color text-white me-2">Añadir Producto</button>
                    <a href="../index.php" class="btn btn-danger">Cancelar</a>
                </div>
                <div class="d-flex justify-content-end mb-3">
                    <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>