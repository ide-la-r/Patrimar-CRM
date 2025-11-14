<?php
// editar_presupuesto.php
$id_presupuesto = $_GET['id'] ?? null;

if (!$id_presupuesto) {
    echo "<div class='alert alert-danger m-3'>ID de presupuesto no proporcionado.</div>";
    exit;
}

$apiUrl = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos&id=" . urlencode($id_presupuesto);
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$presupuesto = json_decode($response, true);

// Si la API devuelve un array simple con los datos
if (!is_array($presupuesto) || isset($presupuesto['message'])) {
    echo "<div class='alert alert-danger m-3'>No se pudo obtener el presupuesto. Verifica el ID.</div>";
    exit;
}

// Obtener artículos asociados
$n_presupuesto = $presupuesto['n_presupuesto'] ?? '';
$apiArticulos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_presupuesto&n_presupuesto=" . urlencode($n_presupuesto);
$ch2 = curl_init($apiArticulos);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$respArt = curl_exec($ch2);
curl_close($ch2);
$articulos = json_decode($respArt, true);

// Aquí empieza el formulario HTML
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Presupuesto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
    <div class="p-3 navbar-color">
        <h3 class="text-white">Editar Presupuesto</h3>
    </div>

    <div class="container mt-4">
        <form action="guardar_presupuesto_editado.php" method="POST" id="formPresupuesto">
            <input type="hidden" name="id_presupuesto" value="<?php echo htmlspecialchars($presupuesto['id_presupuesto']); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="n_presupuesto" class="form-label">Número de Presupuesto</label>
                    <input type="text" class="form-control" name="n_presupuesto" value="<?php echo htmlspecialchars($presupuesto['n_presupuesto']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="razon_social" class="form-label">Razón Social</label>
                    <input type="text" class="form-control" name="razon_social" value="<?php echo htmlspecialchars($presupuesto['razon_social']); ?>" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="fecha" value="<?php echo htmlspecialchars($presupuesto['fecha']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="iva" class="form-label">IVA (%)</label>
                    <input type="number" class="form-control" name="iva" value="<?php echo htmlspecialchars($presupuesto['iva']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="completado" class="form-label">Completado</label>
                    <select class="form-select" name="completado" required>
                        <option value="1" <?php echo ($presupuesto['completado'] == 1) ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo ($presupuesto['completado'] == 0) ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
            </div>

            <hr>
            <h5>Artículos</h5>
            <div id="articulos-container">
                <?php
                if (is_array($articulos)) {
                    foreach ($articulos as $index => $articulo) {
                        echo '<div class="row mb-2 articulo-row">';
                        echo '<div class="col-md-4"><input type="text" class="form-control" name="articulos[' . $index . '][codigo_articulo]" value="' . htmlspecialchars($articulo['codigo_articulo']) . '" placeholder="Código" required></div>';
                        echo '<div class="col-md-4"><input type="number" step="any" class="form-control" name="articulos[' . $index . '][cantidad]" value="' . htmlspecialchars($articulo['cantidad']) . '" placeholder="Cantidad" required></div>';
                        echo '<div class="col-md-4"><select class="form-select" name="articulos[' . $index . '][tipo]" required>';
                        echo '<option value="0" ' . ($articulo['tipo'] == 0 ? 'selected' : '') . '>Producto</option>';
                        echo '<option value="1" ' . ($articulo['tipo'] == 1 ? 'selected' : '') . '>Servicio</option>';
                        echo '</select></div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-secondary" onclick="agregarArticulo()">+ Añadir Artículo</button>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn navbar-color text-white">Guardar Cambios</button>
                <a href="listar_presupuestos.php" class="btn btn-secondary ms-2">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        function agregarArticulo() {
            const container = document.getElementById('articulos-container');
            const index = container.querySelectorAll('.articulo-row').length;
            const row = document.createElement('div');
            row.classList.add('row', 'mb-2', 'articulo-row');
            row.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control" name="articulos[${index}][codigo_articulo]" placeholder="Código" required>
                </div>
                <div class="col-md-4">
                    <input type="number" step="any" class="form-control" name="articulos[${index}][cantidad]" placeholder="Cantidad" required>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="articulos[${index}][tipo]" required>
                        <option value="0">Producto</option>
                        <option value="1">Servicio</option>
                    </select>
                </div>
            `;
            container.appendChild(row);
        }
    </script>
</body>

</html>