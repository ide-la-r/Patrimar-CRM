<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
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

        @media (max-width: 991.98px) {
            .hide-on-mobile {
                display: none;
            }
        }
    </style>
</head>

<body>
    <?php
    // La URL base para la API de pedidos
    $apiGeneral = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=pedidos";
    $message_from_api = "";

    // --- Borrar pedido ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
        $id_pedido_to_delete = $_POST["id_pedido"] ?? null;
        if ($id_pedido_to_delete) {
            $url_delete = $apiGeneral . "&id=" . $id_pedido_to_delete;
            $ch = curl_init($url_delete);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            $responseData = json_decode($response, true);

            echo '<div class="alert text-center m-3 ' . ($http_code === 200 ? 'alert-success' : 'alert-danger') . '">';
            if ($curl_error) {
                echo "<strong>Error cURL:</strong> " . htmlspecialchars($curl_error) . "<br>";
            }
            if (is_array($responseData) && isset($responseData['message'])) {
                echo htmlspecialchars($responseData['message']);
            } else {
                echo 'Respuesta inesperada de la API: ' . htmlspecialchars($response);
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-danger text-center m-3">⚠️ ID de pedido no proporcionado para eliminar.</div>';
        }
    }

    // --- Preparar filtros ---
    $queryParams = [];
    if (isset($_GET['n_pedido']) && !empty(trim($_GET['n_pedido']))) {
        $queryParams['n_pedido'] = trim($_GET['n_pedido']);
    }
    if (isset($_GET['razon_social']) && !empty(trim($_GET['razon_social']))) {
        $queryParams['razon_social'] = trim($_GET['razon_social']);
    }
    if (isset($_GET['fecha']) && !empty(trim($_GET['fecha']))) {
        $queryParams['fecha'] = trim($_GET['fecha']);
    }
    if (isset($_GET['completado']) && $_GET['completado'] !== '') {
        $queryParams['completado'] = trim($_GET['completado']);
    }

    $url = $apiGeneral;
    if (!empty($queryParams)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $pedidos = []; // Cambiado de $presupuestos a $pedidos
    if ($http_code === 200) {
        $api_response_data = json_decode($response, true);

        if (isset($api_response_data['message'])) {
            $message_from_api = htmlspecialchars($api_response_data['message']);
            $pedidos = [];
        } elseif (is_array($api_response_data)) {
            // Filtrado manual: solo mostrar lo que coincide exactamente con los filtros
            // (Tu API también debería filtrar, pero esto actúa como una doble capa)
            if (!empty($queryParams)) {
                $pedidos = [];
                foreach ($api_response_data as $pedido) {
                    $ok = true;
                    if (isset($queryParams['n_pedido']) && trim((string)($pedido['n_pedido'] ?? '')) !== trim((string)$queryParams['n_pedido'])) $ok = false;
                    if (isset($queryParams['razon_social']) && mb_strtolower(trim((string)($pedido['razon_social'] ?? ''))) !== mb_strtolower(trim((string)$queryParams['razon_social']))) $ok = false;
                    if (isset($queryParams['fecha']) && ($pedido['fecha'] ?? '') !== $queryParams['fecha']) $ok = false;
                    if (isset($queryParams['completado']) && (string)($pedido['completado'] ?? '') !== $queryParams['completado']) $ok = false;
                    if ($ok) $pedidos[] = $pedido;
                }
            } else {
                $pedidos = $api_response_data;
            }
        } else {
            $pedidos = [];
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API, pero el código HTTP es 200.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar pedidos desde la API (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Listado de Pedidos</h3>
    </div>

    <div class="container mt-4">
        <div class="filter-section">
            <h4 class="mb-3">Filtros</h4>
            <form action="" method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label for="n_pedido" class="form-label">Nº Pedido</label>
                    <input type="text" class="form-control" id="n_pedido" name="n_pedido" value="<?php echo htmlspecialchars($_GET['n_pedido'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="razon_social" class="form-label">Razón Social</label>
                    <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo htmlspecialchars($_GET['razon_social'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($_GET['fecha'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="completado" class="form-label">Completado</label>
                    <select class="form-select" id="completado" name="completado">
                        <option value="" <?php if (!isset($_GET['completado']) || $_GET['completado'] === '') echo 'selected'; ?>>Todos</option>
                        <option value="1" <?php if (isset($_GET['completado']) && $_GET['completado'] === '1') echo 'selected'; ?>>Sí</option>
                        <option value="0" <?php if (isset($_GET['completado']) && $_GET['completado'] === '0') echo 'selected'; ?>>No</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex justify-content-end mt-2">
                    <button type="submit" class="btn navbar-color text-white me-2">Buscar</button>
                    <a href="listado_pedidos.php" class="btn btn-secondary">Limpiar Filtros</a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button onclick="exportTableToExcel()" class="btn btn-success me-2">📥 Exportar a Excel</button>
            <button onclick="printTable()" class="btn btn-primary me-2">🖨️ Imprimir</button>
            <a href="nuevo_pedido.php" class="btn navbar-color text-white">Añadir Nuevo Pedido</a>
        </div>

        <div class="table-responsive">
            <?php if (!empty($pedidos)) : ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr class="table-dark">
                            <th>Nº Pedido</th>
                            <th>Razón Social</th>
                            <th>Fecha</th>
                            <th>IVA</th>
                            <th>Completado</th>
                            <th>Acciones</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido) : // Cambiado de $presupuestos a $pedidos ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pedido['n_pedido'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['razon_social'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['fecha'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['iva'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    if (isset($pedido['completado'])) {
                                        echo $pedido['completado'] ? 'Sí' : 'No';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <div class="d-flex flex-nowrap">
                                        <form action="editar_pedido.php" method="GET" class="me-2">
                                            <input type="hidden" name="id_pedido" value="<?php echo htmlspecialchars($pedido['id_pedido'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Editar</button>
                                        </form>
                                        <form action="" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este pedido?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_pedido" value="<?php echo htmlspecialchars($pedido['id_pedido'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo htmlspecialchars($pedido['id_pedido'] ?? ''); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            Más
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo htmlspecialchars($pedido['id_pedido'] ?? ''); ?>">
                                            <li>
                                                <h6 class="dropdown-header">Artículos del Pedido</h6>
                                            </li>
                                            <?php
                                            // Obtener artículos del pedido vía API
                                            $apiArticulos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_pedido&n_pedido=" . urlencode($pedido['n_pedido'] ?? '');
                                            $chArt = curl_init($apiArticulos);
                                            curl_setopt($chArt, CURLOPT_RETURNTRANSFER, true);
                                            $respArt = curl_exec($chArt);
                                            $curl_error_art = curl_error($chArt); // Captura el error de cURL para artículos
                                            curl_close($chArt);
                                            $articulos = json_decode($respArt, true);

                                            if ($curl_error_art) {
                                                echo '<li><span class="dropdown-item text-danger">Error al cargar artículos: ' . htmlspecialchars($curl_error_art) . '</span></li>';
                                            } elseif (is_array($articulos) && count($articulos) > 0) {
                                                foreach ($articulos as $art) {
                                                    // Mostrar tipo como texto
                                                    $tipoTxt = ($art['tipo'] == 0) ? 'Producto' : (($art['tipo'] == 1) ? 'Servicio' : 'Otro');
                                                    echo '<li><a class="dropdown-item" href="#">';
                                                    echo '<strong>Tipo:</strong> ' . htmlspecialchars($tipoTxt) . ' | ';
                                                    echo '<strong>Código:</strong> ' . htmlspecialchars($art['codigo_articulo']) . ' | ';
                                                    echo '<strong>Cantidad:</strong> ' . htmlspecialchars($art['cantidad']);
                                                    echo '</a></li>';
                                                }
                                            } else {
                                                echo '<li><span class="dropdown-item text-muted">Sin artículos</span></li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Tabla oculta para exportar e imprimir -->
                <table class="d-none" id="tablaPedidosExport">
                    <thead>
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Razón Social</th>
                            <th>Fecha</th>
                            <th>IVA</th>
                            <th>Completado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pedido['n_pedido'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['razon_social'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['fecha'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['iva'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    if (isset($pedido['completado'])) {
                                        echo $pedido['completado'] ? 'Sí' : 'No';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="text-center mt-4">No se encontraron pedidos con esos filtros.</p>
            <?php endif; ?>
            <div class="d-flex justify-content-end mb-3">
                <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportTableToExcel(tableID = 'tablaPedidosExport', filename = '') {
            const table = document.getElementById(tableID);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Pedidos" });
            XLSX.writeFile(wb, filename ? filename + ".xlsx" : "pedidos.xlsx");
        }

        function printTable(tableID = 'tablaPedidosExport') {
            const table = document.getElementById(tableID);
            if (!table) {
                alert("No se encontró la tabla para imprimir.");
                return;
            }
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Imprimir Pedidos</title>');
            printWindow.document.write(`
                <style>
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            `);
            printWindow.document.write('</head><body >');
            printWindow.document.write('<h3>Listado de Pedidos</h3>');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.onload = function () {
                printWindow.print();
                printWindow.close();
            };
        }

        document.querySelectorAll('#filtroForm input[type="text"], #filtroForm input[type="date"]').forEach(input => {
            input.addEventListener('keydown', function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>