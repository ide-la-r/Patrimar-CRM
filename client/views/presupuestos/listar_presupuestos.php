<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Presupuestos</title>
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
    $apiGeneral = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";
    $message_from_api = "";

    // --- Borrar presupuesto ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
        $id_presupuesto_to_delete = $_POST["id_presupuesto"] ?? null;
        if ($id_presupuesto_to_delete) {
            $url_delete = $apiGeneral . "&id=" . $id_presupuesto_to_delete;
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
            echo '<div class="alert alert-danger text-center m-3">⚠️ ID de presupuesto no proporcionado para eliminar.</div>';
        }
    }

    // --- Preparar filtros ---
    $queryParams = [];
    if (isset($_GET['n_presupuesto']) && !empty(trim($_GET['n_presupuesto']))) {
        $queryParams['n_presupuesto'] = trim($_GET['n_presupuesto']);
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

    $presupuestos = [];
    if ($http_code === 200) {
        $api_response_data = json_decode($response, true);

        if (isset($api_response_data['message'])) {
            $message_from_api = htmlspecialchars($api_response_data['message']);
            $presupuestos = [];
        } elseif (is_array($api_response_data)) {
            // Filtrado manual: solo mostrar lo que coincide exactamente con los filtros
            if (!empty($queryParams)) {
                $presupuestos = [];
                foreach ($api_response_data as $presupuesto) {
                    $ok = true;
                    if (isset($queryParams['n_presupuesto']) && trim((string)($presupuesto['n_presupuesto'] ?? '')) !== trim((string)$queryParams['n_presupuesto'])) $ok = false;
                    if (isset($queryParams['razon_social']) && mb_strtolower(trim((string)($presupuesto['razon_social'] ?? ''))) !== mb_strtolower(trim((string)$queryParams['razon_social']))) $ok = false;
                    if (isset($queryParams['fecha']) && ($presupuesto['fecha'] ?? '') !== $queryParams['fecha']) $ok = false;
                    if (isset($queryParams['completado']) && (string)($presupuesto['completado'] ?? '') !== $queryParams['completado']) $ok = false;
                    if ($ok) $presupuestos[] = $presupuesto;
                }
            } else {
                $presupuestos = $api_response_data;
            }
        } else {
            $presupuestos = [];
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API, pero el código HTTP es 200.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar presupuestos desde la API (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Listado de Presupuestos</h3>
    </div>

    <div class="container mt-4">
        <div class="filter-section">
            <h4 class="mb-3">Filtros</h4>
            <form action="" method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label for="n_presupuesto" class="form-label">Nº Presupuesto</label>
                    <input type="text" class="form-control" id="n_presupuesto" name="n_presupuesto" value="<?php echo htmlspecialchars($_GET['n_presupuesto'] ?? ''); ?>">
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
                    <a href="listar_presupuestos.php" class="btn btn-secondary">Buscar</a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button onclick="exportTableToExcel()" class="btn btn-success me-2">📥 Exportar a Excel</button>
            <button onclick="printTable()" class="btn btn-primary me-2">🖨️ Imprimir</button>
            <a href="nuevo_presupuesto.php" class="btn navbar-color text-white">Añadir Nuevo Presupuesto</a>
        </div>

        <div class="table-responsive">
            <?php if (!empty($presupuestos)) : ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr class="table-dark">
                            <th>Nº Presupuesto</th>
                            <th>Razón Social</th>
                            <th>Fecha</th>
                            <th>IVA</th>
                            <th>Completado</th>
                            <th>Acciones</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presupuestos as $presupuesto) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($presupuesto['n_presupuesto'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($presupuesto['razon_social'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($presupuesto['fecha'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($presupuesto['iva'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    if (isset($presupuesto['completado'])) {
                                        echo $presupuesto['completado'] ? 'Sí' : 'No';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <div class="d-flex flex-nowrap">
                                        <form action="editar_presupuesto.php" method="GET" class="me-2">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($presupuesto['id_presupuesto'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Editar</button>
                                        </form>
                                        <form action="" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este presupuesto?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_presupuesto" value="<?php echo htmlspecialchars($presupuesto['id_presupuesto'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo htmlspecialchars($presupuesto['id_presupuesto'] ?? ''); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            Más
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo htmlspecialchars($presupuesto['id_presupuesto'] ?? ''); ?>">
                                            <li>
                                                <h6 class="dropdown-header">Artículos del Presupuesto</h6>
                                            </li>
                                            <?php
                                            // Obtener artículos del presupuesto vía API
                                            $apiArticulos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_presupuesto&n_presupuesto=" . urlencode($presupuesto['n_presupuesto'] ?? '');
                                            $chArt = curl_init($apiArticulos);
                                            curl_setopt($chArt, CURLOPT_RETURNTRANSFER, true);
                                            $respArt = curl_exec($chArt);
                                            curl_close($chArt);
                                            $articulos = json_decode($respArt, true);
                                            if (is_array($articulos) && count($articulos) > 0) {
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
                <table class="d-none" id="tablaPresupuestosExport">
                    <thead>
                        <tr>
                            <th>Nº Presupuesto</th>
                            <th>Razón Social</th>
                            <th>Fecha</th>
                            <th>IVA</th>
                            <th>Completado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presupuestos as $presupuesto) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($presupuesto['n_presupuesto'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($presupuesto['razon_social'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($presupuesto['fecha'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($presupuesto['iva'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    if (isset($presupuesto['completado'])) {
                                        echo $presupuesto['completado'] ? 'Sí' : 'No';
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
                <p class="text-center mt-4">No se encontraron presupuestos con esos filtros.</p>
            <?php endif; ?>
            <div class="d-flex justify-content-end mb-3">
                <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportTableToExcel(tableID = 'tablaPresupuestosExport', filename = '') {
            const table = document.getElementById(tableID);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Presupuestos" });
            XLSX.writeFile(wb, filename ? filename + ".xlsx" : "presupuestos.xlsx");
        }

        function printTable(tableID = 'tablaPresupuestosExport') {
            const table = document.getElementById(tableID);
            if (!table) {
                alert("No se encontró la tabla para imprimir.");
                return;
            }
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Imprimir Presupuestos</title>');
            printWindow.document.write(`
                <style>
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            `);
            printWindow.document.write('</head><body >');
            printWindow.document.write('<h3>Listado de Presupuestos</h3>');
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
