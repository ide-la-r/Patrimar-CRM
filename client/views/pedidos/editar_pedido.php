<?php
// --- Configuración de errores ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$apiPedidos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=pedidos";
$apiClientes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";
$apiPresupuestos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";

$id_pedido = $_GET['id_pedido'] ?? null;
$pedido = null;

if ($id_pedido) {
    $ch = curl_init("$apiPedidos&id=$id_pedido");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $pedido = json_decode($response, true);
    if (!is_array($pedido)) {
        echo '<div class="alert alert-danger m-3">No se pudo cargar el pedido.</div>';
        exit;
    }
} else {
    echo '<div class="alert alert-danger m-3">ID de pedido no especificado.</div>';
    exit;
}

// Obtener clientes
$ch = curl_init($apiClientes);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$clientes = json_decode(curl_exec($ch), true);
curl_close($ch);

// Obtener presupuestos
$ch = curl_init($apiPresupuestos);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$presupuestos = json_decode(curl_exec($ch), true);
curl_close($ch);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos_actualizados = [
        'id_pedido' => $id_pedido,
        'n_pedido' => $_POST['n_pedido'] ?? '',
        'fecha' => $_POST['fecha'] ?? '',
        'id_cliente' => $_POST['id_cliente'] ?? '',
        'razon_social' => $_POST['razon_social'] ?? '',
        'iva' => $_POST['iva'] ?? '',
        'n_presupuesto' => $_POST['n_presupuesto'] ?? '',
    ];

    $ch = curl_init($apiPedidos);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos_actualizados));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        echo '<div class="alert alert-success text-center m-3">Pedido actualizado correctamente.</div>';
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al actualizar el pedido.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Editar Pedido</h2>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Nº Pedido</label>
            <input type="text" name="n_pedido" class="form-control" value="<?= htmlspecialchars($pedido['n_pedido']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($pedido['fecha']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cliente</label>
            <select name="id_cliente" class="form-select" required>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= $cliente['id_cliente'] ?>" <?= $cliente['id_cliente'] == $pedido['id_cliente'] ? 'selected' : '' ?>><?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['razon_social']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Razón Social</label>
            <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($pedido['razon_social']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">IVA</label>
            <input type="number" name="iva" class="form-control" step="0.01" value="<?= htmlspecialchars($pedido['iva']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Nº Presupuesto</label>
            <select name="n_presupuesto" class="form-select">
                <option value="">-- Selecciona Presupuesto --</option>
                <?php foreach ($presupuestos as $presupuesto): ?>
                    <option value="<?= $presupuesto['n_presupuesto'] ?>" <?= $presupuesto['n_presupuesto'] == $pedido['n_presupuesto'] ? 'selected' : '' ?>><?= htmlspecialchars($presupuesto['n_presupuesto']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="listar_pedidos.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>