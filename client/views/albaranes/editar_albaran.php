<?php
// editar_albaran.php

// --- Configuración de errores para depuración (QUITAR EN PRODUCCIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ----------------------------------------------------------------------

$apiClientes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";
$apiPedidos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=pedidos";
$apiPresupuestos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";
$apiAlbaranes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=albaranes";
$apiArticulosAlbaran = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_albaran";

// Verificar si se ha pasado un ID de albarán
if (!isset($_GET['id_albaran'])) {
    header('Location: listar_albaran.php');
    exit();
}

$id_albaran = $_GET['id_albaran'];

// Obtener datos del albarán
$albaran = [];
$chAlbaran = curl_init($apiAlbaranes . "&id_albaran=" . urlencode($id_albaran));
curl_setopt($chAlbaran, CURLOPT_RETURNTRANSFER, true);
$jsonAlbaran = curl_exec($chAlbaran);
$http_code_albaran = curl_getinfo($chAlbaran, CURLINFO_HTTP_CODE);
curl_close($chAlbaran);

if ($http_code_albaran === 200 && $jsonAlbaran !== false) {
    $albaranData = json_decode($jsonAlbaran, true);
    $albaran = is_array($albaranData) ? (isset($albaranData[0]) ? $albaranData[0] : $albaranData) : [];
}

if (empty($albaran)) {
    header('Location: listar_albaran.php');
    exit();
}

// Obtener artículos del albarán
$articulosAlbaran = [];
$chArticulos = curl_init($apiArticulosAlbaran . "&id_albaran=" . urlencode($id_albaran));
curl_setopt($chArticulos, CURLOPT_RETURNTRANSFER, true);
$jsonArticulos = curl_exec($chArticulos);
$http_code_articulos = curl_getinfo($chArticulos, CURLINFO_HTTP_CODE);
curl_close($chArticulos);

if ($http_code_articulos === 200 && $jsonArticulos !== false) {
    $articulosAlbaran = json_decode($jsonArticulos, true);
    if (!is_array($articulosAlbaran)) {
        $articulosAlbaran = [];
    }
}

// Obtener listas para selects
$clientes = $presupuestos = $pedidos = $productos = $servicios = [];
$apis = [
    'clientes' => $apiClientes,
    'presupuestos' => $apiPresupuestos,
    'pedidos' => $apiPedidos,
    'productos' => "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=productos",
    'servicios' => "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=servicios"
];

foreach ($apis as $key => $api) {
    $ch = curl_init($api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    if ($json !== false) {
        ${$key} = json_decode($json, true);
        if (!is_array(${$key})) ${$key} = [];
    }
    curl_close($ch);
}

// Procesar actualización del albarán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_albaran'])) {
    $data = [
        "id_albaran" => $_POST['id_albaran'],
        "n_albaran" => $_POST['n_albaran'],
        "fecha" => $_POST['fecha'] ?? null,
        "id_cliente" => $_POST['id_cliente'],
        "razon_social" => $_POST['razon_social'] ?? null,
        "iva" => $_POST['iva'] ?? null,
        "n_presupuesto" => $_POST['n_presupuesto'] ?? null,
        "n_pedido" => $_POST['n_pedido'] ?? null
    ];

    $data = array_filter($data, fn($v, $k) => $v !== null && ($v !== "" || $k === "n_presupuesto" || $k === "n_pedido"), ARRAY_FILTER_USE_BOTH);

    // Actualizar albarán
    $ch = curl_init($apiAlbaranes);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Procesar respuesta de la API
    $responseData = json_decode($response, true);
    if ($http_code === 200) {
        $success_message = 'Albarán actualizado correctamente.';
    } else {
        $error_message = 'Error al actualizar albarán: ' . ($responseData['message'] ?? 'Error desconocido');
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Albarán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <style>
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
        }
    </style>
</head>

<body>
    <div class="p-3 navbar-color">
        <h3 class="text-white">Editar Albarán <?= htmlspecialchars($albaran['n_albaran'] ?? '') ?></h3>
    </div>

    <div class="container mt-5">
        <?php if (isset($success_message)) : ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id_albaran" value="<?= htmlspecialchars($albaran['id_albaran'] ?? '') ?>">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nº Albarán*</label>
                    <input name="n_albaran" type="text" class="form-control" value="<?= htmlspecialchars($albaran['n_albaran'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input name="fecha" type="date" class="form-control" value="<?= htmlspecialchars($albaran['fecha'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente*</label>
                    <select name="id_cliente" id="id_cliente" class="form-select" required>
                        <option value="">Seleccionar cliente</option>
                        <?php foreach ($clientes as $cliente) : ?>
                            <option value="<?= htmlspecialchars($cliente['id_cliente']) ?>" <?= ($cliente['id_cliente'] == ($albaran['id_cliente'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cliente['cif_nif'] ?? "Sin NIF/CIF") ?> - <?= htmlspecialchars($cliente['razon_social'] ?? "Sin Razón Social") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Razón Social</label>
                    <input name="razon_social" id="razon_social" type="text" class="form-control" value="<?= htmlspecialchars($albaran['razon_social'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">IVA</label>
                    <input name="iva" id="iva" type="number" step="0.001" class="form-control" value="<?= htmlspecialchars($albaran['iva'] ?? '') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nº Presupuesto Asociado</label>
                    <select name="n_presupuesto" id="n_presupuesto" class="form-select">
                        <option value="">Seleccionar presupuesto (Opcional)</option>
                        <?php foreach ($presupuestos as $presupuesto) : ?>
                            <option value="<?= htmlspecialchars($presupuesto['n_presupuesto']) ?>" <?= ($presupuesto['n_presupuesto'] == ($albaran['n_presupuesto'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($presupuesto['n_presupuesto'] ?? 'N/A') ?> - <?= htmlspecialchars($presupuesto['razon_social'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nº Pedido Asociado</label>
                    <select name="n_pedido" id="n_pedido" class="form-select">
                        <option value="">Seleccionar pedido (Opcional)</option>
                        <?php foreach ($pedidos as $pedido) : ?>
                            <option value="<?= htmlspecialchars($pedido['n_pedido']) ?>" <?= ($pedido['n_pedido'] == ($albaran['n_pedido'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pedido['n_pedido'] ?? 'N/A') ?> - <?= htmlspecialchars($pedido['razon_social'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="campos-dinamicos">
                <?php foreach ($articulosAlbaran as $index => $articulo) : ?>
                    <div class="row mb-2 grupo-campos">
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo[]" class="form-select tipo-select">
                                <option value="producto" <?= ($articulo['tipo'] == 0) ? 'selected' : '' ?>>Producto</option>
                                <option value="servicio" <?= ($articulo['tipo'] == 1) ? 'selected' : '' ?>>Servicio</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Producto/Servicio</label>
                            <select name="articulo[]" class="form-select articulo-select">
                                <?php if ($articulo['tipo'] == 0) : ?>
                                    <?php foreach ($productos as $producto) : ?>
                                        <option value="<?= htmlspecialchars($producto['codigo_producto']) ?>" <?= ($producto['codigo_producto'] == $articulo['codigo_articulo']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($producto['producto']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <?php foreach ($servicios as $servicio) : ?>
                                        <option value="<?= htmlspecialchars($servicio['codigo_servicio']) ?>" <?= ($servicio['codigo_servicio'] == $articulo['codigo_articulo']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($servicio['servicio']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cantidad</label>
                            <input name="cantidad[]" type="number" min="1" class="form-control" value="<?= htmlspecialchars($articulo['cantidad'] ?? 1) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <?php if ($index === 0) : ?>
                                <button type="button" class="btn btn-success btn-add-campo">+</button>
                            <?php else : ?>
                                <button type="button" class="btn btn-danger btn-add-campo">-</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn navbar-color text-white me-2">Guardar Cambios</button>
                <a href="./listar_albaranes.php" class="btn btn-danger">Volver</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const productos = <?= json_encode($productos) ?>;
        const servicios = <?= json_encode($servicios) ?>;
        const clientes = <?= json_encode($clientes) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const contenedor = document.getElementById('campos-dinamicos');

            // Delegación de eventos para los select de tipo
            contenedor.addEventListener('change', function(e) {
                if (e.target.classList.contains('tipo-select')) {
                    const grupo = e.target.closest('.grupo-campos');
                    const selectArticulo = grupo.querySelector('.articulo-select');
                    rellenarArticulos(e.target.value, selectArticulo);
                }
            });

            // Lógica para añadir/eliminar campos de artículo
            contenedor.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-add-campo')) {
                    if (e.target.textContent === '+') {
                        const originalRow = e.target.closest('.grupo-campos');
                        const newRow = originalRow.cloneNode(true);
                        newRow.querySelectorAll('input, select').forEach(input => input.value = '');
                        newRow.querySelector('.articulo-select').innerHTML = '<option value="">Seleccione primero un tipo</option>';

                        e.target.classList.remove('btn-success');
                        e.target.classList.add('btn-danger');
                        e.target.textContent = '-';

                        contenedor.appendChild(newRow);
                        const newAddBtn = newRow.querySelector('.btn-add-campo');
                        newAddBtn.classList.remove('btn-danger');
                        newAddBtn.classList.add('btn-success');
                        newAddBtn.textContent = '+';
                    } else if (e.target.textContent === '-') {
                        const rowToRemove = e.target.closest('.grupo-campos');
                        rowToRemove.remove();
                    }
                }
            });

            // Función para rellenar select de artículos
            function rellenarArticulos(tipo, selectArticulo) {
                let opciones = '<option value="">Seleccione una opción</option>';
                const items = tipo === 'producto' ? productos : servicios;

                items.forEach(function(item) {
                    const codigo = tipo === 'producto' ? item.codigo_producto : item.codigo_servicio;
                    const nombre = tipo === 'producto' ? item.producto : item.servicio;
                    opciones += `<option value="${codigo}">${nombre}</option>`;
                });

                selectArticulo.innerHTML = opciones;
            }
        });
    </script>
</body>

</html>