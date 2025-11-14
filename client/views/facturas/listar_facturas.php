<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listar Facturas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Estilos adicionales si son necesarios */
        .table-responsive {
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .factura-tooltip-content {
            white-space: pre-wrap;
            text-align: left;
            font-size: 0.9em;
            /* Para que quepa más información */
        }
    </style>
</head>

<body>
    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $apiFacturas = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=facturas";
    $apiDeleteFactura = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=facturas";

    $facturas = [];
    $error_message = '';
    $success_message = '';

    // Manejar eliminación de factura
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_n_factura'])) {
        $n_factura_to_delete = $_POST['delete_n_factura'];
        $ch = curl_init($apiDeleteFactura . "&n_factura=" . urlencode($n_factura_to_delete));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 || $http_code === 204) { // 200 OK, 204 No Content
            $success_message = 'Factura ' . htmlspecialchars($n_factura_to_delete) . ' eliminada correctamente.';
        } else {
            $error_message = 'Error al eliminar factura ' . htmlspecialchars($n_factura_to_delete) . '. HTTP Code: ' . htmlspecialchars($http_code) . '. Error: ' . htmlspecialchars($curl_error ?: $response);
        }
    }

    // Obtener lista de facturas
    $ch = curl_init($apiFacturas);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $jsonFacturas = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200 && $jsonFacturas !== false) {
        $facturas = json_decode($jsonFacturas, true);
        if (!is_array($facturas)) {
            $facturas = [];
            $error_message = 'Respuesta inesperada de la API de facturas al decodificar JSON.';
        }
    } else {
        $error_message = 'Error al cargar facturas desde la API: ' . htmlspecialchars($curl_error ?: 'HTTP Error ' . $http_code);
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Listado de Facturas</h3>
    </div>

    <div class="container mt-4">
        <?php if ($success_message) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end mb-3">
            <button onclick="exportTableToExcel()" class="btn btn-success me-2">📥 Exportar a Excel</button>
            <button onclick="printTable()" class="btn btn-primary me-2">🖨️ Imprimir</button>
            <a href="nueva_factura.php" class="btn btn-success">Añadir Nueva Factura</a>
        </div>

        <?php if (empty($facturas)) : ?>
            <div class="alert alert-info text-center" role="alert">
                No hay facturas registradas en el sistema.
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nº Factura</th>
                            <th>Fecha</th>
                            <th>Cliente (CIF/NIF)</th>
                            <th>Razón Social</th>
                            <th>Base Imponible</th>
                            <th>IVA Total</th>
                            <th>Total Factura</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $factura) : ?>
                            <tr>
                                <td><?= htmlspecialchars($factura['n_factura'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['fecha'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['cif_nif'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['razon_social'] ?? 'N/A') ?></td>
                                <td><?= number_format($factura['base_imponible'] ?? 0, 2, ',', '.') ?> €</td>
                                <td><?= number_format($factura['iva_total'] ?? 0, 2, ',', '.') ?> €</td>
                                <td><?= number_format($factura['total_factura'] ?? 0, 2, ',', '.') ?> €</td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-info btn-sm"
                                            data-bs-toggle="popover"
                                            data-bs-placement="left"
                                            data-bs-trigger="hover"
                                            data-bs-html="true"
                                            data-bs-content="
                                                <div class='factura-tooltip-content'>
                                                    <strong>Nº Presupuesto:</strong> <?= htmlspecialchars($factura['n_presupuesto'] ?? 'N/A') ?><br>
                                                    <strong>Nº Pedido:</strong> <?= htmlspecialchars($factura['n_pedido'] ?? 'N/A') ?><br>
                                                    <strong>Nº Albarán:</strong> <?= htmlspecialchars($factura['n_albaran'] ?? 'N/A') ?><br>
                                                    <strong>IVA General:</strong> <?= htmlspecialchars($factura['iva'] ?? 'N/A') ?> %<br>
                                                    <strong>Dirección:</strong> <?= htmlspecialchars($factura['direccion'] ?? 'N/A') ?><br>
                                                    <strong>Población:</strong> <?= htmlspecialchars($factura['poblacion'] ?? 'N/A') ?><br>
                                                    <strong>Provincia:</strong> <?= htmlspecialchars($factura['provincia'] ?? 'N/A') ?><br>
                                                    <strong>Código Postal:</strong> <?= htmlspecialchars($factura['cp'] ?? 'N/A') ?><br>
                                                    <strong>Teléfono:</strong> <?= htmlspecialchars($factura['telefono'] ?? 'N/A') ?><br>
                                                    <strong>Email:</strong> <?= htmlspecialchars($factura['email'] ?? 'N/A') ?><br>
                                                    <strong>Forma de Pago:</strong> <?= htmlspecialchars($factura['forma_pago'] ?? 'N/A') ?>
                                                </div>">
                                        <i class="fas fa-info-circle"></i> Más
                                    </button>

                                    <a href="editar_factura.php?n_factura=<?= urlencode($factura['n_factura']) ?>" class="btn btn-primary btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar la factura Nº <?= htmlspecialchars($factura['n_factura']) ?>? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="delete_n_factura" value="<?= htmlspecialchars($factura['n_factura']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Tabla oculta para exportar e imprimir -->
                <table class="d-none" id="tablaFacturasExport">
                    <thead>
                        <tr>
                            <th>Nº Factura</th>
                            <th>Fecha</th>
                            <th>Cliente (CIF/NIF)</th>
                            <th>Razón Social</th>
                            <th>Base Imponible</th>
                            <th>IVA Total</th>
                            <th>Total Factura</th>
                            <th>Nº Presupuesto</th>
                            <th>Nº Pedido</th>
                            <th>Nº Albarán</th>
                            <th>IVA General (%)</th>
                            <th>Dirección</th>
                            <th>Población</th>
                            <th>Provincia</th>
                            <th>Código Postal</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Forma de Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $factura) : ?>
                            <tr>
                                <td><?= htmlspecialchars($factura['n_factura'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['fecha'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['cif_nif'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['razon_social'] ?? 'N/A') ?></td>
                                <td><?= number_format($factura['base_imponible'] ?? 0, 2, ',', '.') ?></td>
                                <td><?= number_format($factura['iva_total'] ?? 0, 2, ',', '.') ?></td>
                                <td><?= number_format($factura['total_factura'] ?? 0, 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($factura['n_presupuesto'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['n_pedido'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['n_albaran'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['iva'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['direccion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['poblacion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['provincia'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['cp'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['telefono'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($factura['forma_pago'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end mt-4 mb-3">
            <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit-id.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar todos los popovers en la página
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl)
        });

        function exportTableToExcel(tableID = 'tablaFacturasExport', filename = '') {
            const table = document.getElementById(tableID);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Facturas" });
            XLSX.writeFile(wb, filename ? filename + ".xlsx" : "facturas.xlsx");
        }

        function printTable(tableID = 'tablaFacturasExport') {
            const table = document.getElementById(tableID);
            if (!table) {
                alert("No se encontró la tabla para imprimir.");
                return;
            }
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Imprimir Facturas</title>');
            printWindow.document.write(`
                <style>
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            `);
            printWindow.document.write('</head><body >');
            printWindow.document.write('<h3>Listado de Facturas</h3>');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.onload = function () {
                printWindow.print();
                printWindow.close();
            };
        }
    </script>
</body>

</html>