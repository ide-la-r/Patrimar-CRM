<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listar Albaranes</title>
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

        /* Estilo para el contenido del tooltip (opcional, para mejor lectura) */
        .albaran-tooltip-content {
            white-space: pre-wrap;
            /* Mantiene saltos de línea si los añadimos */
            text-align: left;
        }
    </style>
</head>

<body>
    <?php
    // --- Configuración de errores para depuración (QUITAR EN PRODUCCIÓN) ---
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // ----------------------------------------------------------------------

    $apiAlbaranes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=albaranes";
    $apiDeleteAlbaran = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=albaranes"; // Para eliminar

    $albaranes = [];
    $error_message = '';
    $success_message = '';

    // Manejar eliminación de albarán
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_n_albaran'])) {
        $n_albaran_to_delete = $_POST['delete_n_albaran'];
        $ch = curl_init($apiDeleteAlbaran . "&n_albaran=" . urlencode($n_albaran_to_delete));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 || $http_code === 204) { // 200 OK, 204 No Content
            $success_message = 'Albarán ' . htmlspecialchars($n_albaran_to_delete) . ' eliminado correctamente.';
        } else {
            $error_message = 'Error al eliminar albarán ' . htmlspecialchars($n_albaran_to_delete) . '. HTTP Code: ' . htmlspecialchars($http_code) . '. Error: ' . htmlspecialchars($curl_error ?: $response);
        }
    }

    // Obtener lista de albaranes
    $ch = curl_init($apiAlbaranes);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $jsonAlbaranes = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200 && $jsonAlbaranes !== false) {
        $albaranes = json_decode($jsonAlbaranes, true);
        if (!is_array($albaranes)) {
            $albaranes = []; // Asegura que $albaranes sea un array
            $error_message = 'Respuesta inesperada de la API de albaranes al decodificar JSON.';
        }
    } else {
        $error_message = 'Error al cargar albaranes desde la API: ' . htmlspecialchars($curl_error ?: 'HTTP Error ' . $http_code);
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Listado de Albaranes</h3>
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
            <a href="nuevo_albaran.php" class="btn btn-success">Añadir Nuevo Albarán</a>
        </div>

        <?php if (empty($albaranes)) : ?>
            <div class="alert alert-info text-center" role="alert">
                No hay albaranes registrados en el sistema.
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Nº Albarán</th>
                            <th>Fecha</th>
                            <th>Cliente (CIF/NIF)</th>
                            <th>Razón Social</th>
                            <th>IVA</th>
                            <th>Nº Presupuesto</th>
                            <th>Nº Pedido</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($albaranes as $albaran) : ?>
                            <tr>
                                <td><?= htmlspecialchars($albaran['n_albaran'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['fecha'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['cif_nif'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['razon_social'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['iva'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['n_presupuesto'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['n_pedido'] ?? 'N/A') ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-info btn-sm"
                                            data-bs-toggle="popover"
                                            data-bs-placement="left"
                                            data-bs-trigger="hover"
                                            data-bs-html="true"
                                            data-bs-content="
                                                <div class='albaran-tooltip-content'>
                                                    <strong>Dirección:</strong> <?= htmlspecialchars($albaran['direccion'] ?? 'N/A') ?><br>
                                                    <strong>Población:</strong> <?= htmlspecialchars($albaran['poblacion'] ?? 'N/A') ?><br>
                                                    <strong>Provincia:</strong> <?= htmlspecialchars($albaran['provincia'] ?? 'N/A') ?><br>
                                                    <strong>Código Postal:</strong> <?= htmlspecialchars($albaran['cp'] ?? 'N/A') ?><br>
                                                    <strong>Teléfono:</strong> <?= htmlspecialchars($albaran['telefono'] ?? 'N/A') ?><br>
                                                    <strong>Email:</strong> <?= htmlspecialchars($albaran['email'] ?? 'N/A') ?><br>
                                                    <strong>Forma de Pago:</strong> <?= htmlspecialchars($albaran['forma_pago'] ?? 'N/A') ?><br>
                                                    <strong>Base Imponible:</strong> <?= number_format($albaran['base_imponible'] ?? 0, 2, ',', '.') ?> €<br>
                                                    <strong>IVA Total:</strong> <?= number_format($albaran['iva_total'] ?? 0, 2, ',', '.') ?> €<br>
                                                    <strong>Total Albarán:</strong> <?= number_format($albaran['total_albaran'] ?? 0, 2, ',', '.') ?> €
                                                </div>">
                                        <i class="fas fa-info-circle"></i> Más
                                    </button>
                                    
                                    <a href="editar_albaran.php?id_albaran=<?= urlencode($albaran['id_albaran']) ?>" class="btn btn-primary btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar el albarán Nº <?= htmlspecialchars($albaran['n_albaran']) ?>? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="delete_n_albaran" value="<?= htmlspecialchars($albaran['n_albaran']) ?>">
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
                <table class="d-none" id="tablaAlbaranesExport">
                    <thead>
                        <tr>
                            <th>Nº Albarán</th>
                            <th>Fecha</th>
                            <th>Cliente (CIF/NIF)</th>
                            <th>Razón Social</th>
                            <th>IVA</th>
                            <th>Nº Presupuesto</th>
                            <th>Nº Pedido</th>
                            <th>Dirección</th>
                            <th>Población</th>
                            <th>Provincia</th>
                            <th>Código Postal</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Forma de Pago</th>
                            <th>Base Imponible</th>
                            <th>IVA Total</th>
                            <th>Total Albarán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($albaranes as $albaran) : ?>
                            <tr>
                                <td><?= htmlspecialchars($albaran['n_albaran'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['fecha'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['cif_nif'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['razon_social'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['iva'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['n_presupuesto'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['n_pedido'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['direccion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['poblacion'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['provincia'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['cp'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['telefono'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($albaran['forma_pago'] ?? 'N/A') ?></td>
                                <td><?= number_format($albaran['base_imponible'] ?? 0, 2, ',', '.') ?></td>
                                <td><?= number_format($albaran['iva_total'] ?? 0, 2, ',', '.') ?></td>
                                <td><?= number_format($albaran['total_albaran'] ?? 0, 2, ',', '.') ?></td>
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

        function exportTableToExcel(tableID = 'tablaAlbaranesExport', filename = '') {
            const table = document.getElementById(tableID);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Albaranes" });
            XLSX.writeFile(wb, filename ? filename + ".xlsx" : "albaranes.xlsx");
        }

        function printTable(tableID = 'tablaAlbaranesExport') {
            const table = document.getElementById(tableID);
            if (!table) {
                alert("No se encontró la tabla para imprimir.");
                return;
            }
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Imprimir Albaranes</title>');
            printWindow.document.write(`
                <style>
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            `);
            printWindow.document.write('</head><body >');
            printWindow.document.write('<h3>Listado de Albaranes</h3>');
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