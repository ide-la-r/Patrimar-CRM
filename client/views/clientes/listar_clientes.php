<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Clientes</title>
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
        white-space: nowrap; /* Evita que los botones se apilen */
    }

    @media (max-width: 991.98px) {
        .hide-on-mobile {
            display: none;
        }
    }

    @media print {
    /* Ocultar elementos no deseados */
    .navbar-color,
    .filter-section,
    .btn,
    .dropdown,
    .hide-on-mobile,
    .action-buttons {
        display: none !important;
    }

    /* Mostrar todas las columnas ocultas para impresión */
    .hide-on-mobile {
        display: table-cell !important;
    }

    /* Ajustes para la tabla */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 12pt !important;
    }

    th, td {
        border: 1px solid #333 !important;
        padding: 6px !important;
        color: #000 !important;
    }

    /* Ajustar márgenes del body para impresión */
    body {
        padding: 10px !important;
        margin: 0 !important;
        background: none !important;
        color: #000 !important;
    }
}


    </style>
</head>

<body>
    <?php
    $apiGeneral = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";
    $message_from_api = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
        $cliente_id_to_delete = $_POST["id_cliente"] ?? null;
        if ($cliente_id_to_delete) {
            $url_delete = $apiGeneral . "&id=" . $cliente_id_to_delete;
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
            echo '<div class="alert alert-danger text-center m-3">⚠️ ID de cliente no proporcionado para eliminar.</div>';
        }
    }

    $queryParams = [];
    $campos_filtrables = ['cif_nif', 'poblacion', 'razon_social', 'nombre'];
    foreach ($campos_filtrables as $campo) {
        if (!empty($_GET[$campo])) {
            $queryParams[$campo] = trim($_GET[$campo]);
        }
    }

    $url = $apiGeneral;
    if (!empty($queryParams)) {
        $url .= '&' . http_build_query($queryParams);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $clientes = [];
    if ($http_code === 200) {
        $api_response_data = json_decode($response, true);
        if (isset($api_response_data['message'])) {
            $message_from_api = htmlspecialchars($api_response_data['message']);
        } elseif (is_array($api_response_data)) {
            $clientes = $api_response_data;
        } else {
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API, pero el código HTTP es 200.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar clientes desde la API (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Listado de Clientes</h3>
    </div>

    <div class="container mt-4">
        <div class="filter-section">
            <h4 class="mb-3">Filtros</h4>
            <form action="" method="GET" class="row g-3 align-items-end" id="filtroForm">
                <div class="col-md-3">
                    <label for="cif_nif" class="form-label">CIF/NIF</label>
                    <input type="text" class="form-control" id="cif_nif" name="cif_nif" value="<?php echo htmlspecialchars($_GET['cif_nif'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="poblacion" class="form-label">Población</label>
                    <input type="text" class="form-control" id="poblacion" name="poblacion" value="<?php echo htmlspecialchars($_GET['poblacion'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="razon_social" class="form-label">Razón Social</label>
                    <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo htmlspecialchars($_GET['razon_social'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_GET['nombre'] ?? ''); ?>">
                </div>
                <div class="col-md-12 d-flex justify-content-end mt-2">
                    <button type="submit" class="btn navbar-color text-white me-2">Buscar</button>
                    <a href="listar_clientes.php" class="btn btn-secondary" id="limpiarBtn">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button onclick="exportTableToExcel()" class="btn btn-success me-2">📥 Exportar a Excel</button>
            <button onclick="printTable()" class="btn btn-primary">🖨️ Imprimir</button>
            <a href="nuevo_cliente.php" class="btn navbar-color text-white">Añadir Nuevo Cliente</a>
        </div>

        <div class="table-responsive">
            <?php if (!empty($clientes)) : ?>
                <!-- Tabla visible para la web, con dropdown "Más" y acciones -->
                <table class="table table-striped table-hover" id="tablaClientes">
                    <thead class="table-dark">
                        <tr>
                            <th>Razón Social</th>
                            <th>Nombre</th>
                            <th>CIF/NIF</th>
                            <th class="hide-on-mobile">Población</th>
                            <th class="hide-on-mobile">Teléfono 1</th>
                            <th class="hide-on-mobile">Email</th>
                            <th>Acciones</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cliente['razon_social'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['nombre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['cif_nif'] ?? 'N/A'); ?></td>
                                <td class="hide-on-mobile"><?php echo htmlspecialchars($cliente['poblacion'] ?? 'N/A'); ?></td>
                                <td class="hide-on-mobile"><?php echo htmlspecialchars($cliente['telefono1'] ?? 'N/A'); ?></td>
                                <td class="hide-on-mobile"><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    <div class="d-flex flex-nowrap">
                                        <form action="editar_cliente.php" method="GET" class="me-2">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($cliente['id_cliente'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">Editar</button>
                                        </form>
                                        <form action="" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este cliente?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_cliente'] ?? ''); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo htmlspecialchars($cliente['id_cliente'] ?? ''); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            Más
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo htmlspecialchars($cliente['id_cliente'] ?? ''); ?>">
                                            <li><h6 class="dropdown-header">Detalles Adicionales</h6></li>
                                            <li><a class="dropdown-item" href="#"><strong>Apellidos:</strong> <?php echo htmlspecialchars($cliente['apellidos'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Dirección:</strong> <?php echo htmlspecialchars($cliente['direccion'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>C.P.:</strong> <?php echo htmlspecialchars($cliente['cp'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Provincia:</strong> <?php echo htmlspecialchars($cliente['provincia'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Persona Contacto 1:</strong> <?php echo htmlspecialchars($cliente['persona_contacto_1'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Teléfono 2:</strong> <?php echo htmlspecialchars($cliente['telefono2'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Persona Contacto 2:</strong> <?php echo htmlspecialchars($cliente['persona_contacto_2'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Fax:</strong> <?php echo htmlspecialchars($cliente['fax'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Web:</strong> <?php echo htmlspecialchars($cliente['web'] ?? 'N/A'); ?></a></li>
                                            <li><a class="dropdown-item" href="#"><strong>Observaciones:</strong> <?php echo htmlspecialchars($cliente['observaciones'] ?? 'N/A'); ?></a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Tabla oculta para exportar a Excel, sin botones ni dropdown, con columnas extras visibles -->
                <table class="d-none" id="tablaClientesExport">
                    <thead>
                        <tr>
                            <th>Razón Social</th>
                            <th>Nombre</th>
                            <th>CIF/NIF</th>
                            <th>Población</th>
                            <th>Teléfono 1</th>
                            <th>Email</th>
                            <th>Apellidos</th>
                            <th>Dirección</th>
                            <th>C.P.</th>
                            <th>Provincia</th>
                            <th>Persona Contacto 1</th>
                            <th>Teléfono 2</th>
                            <th>Persona Contacto 2</th>
                            <th>Fax</th>
                            <th>Web</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cliente['razon_social'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['nombre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['cif_nif'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['poblacion'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefono1'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['apellidos'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['direccion'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['cp'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['provincia'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['persona_contacto_1'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefono2'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['persona_contacto_2'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['fax'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['web'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cliente['observaciones'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="text-center mt-4">No se encontraron clientes con esos filtros.</p>
            <?php endif; ?>
            <div class="d-flex justify-content-end mb-3">
                <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportTableToExcel(tableID = 'tablaClientesExport', filename = '') {
            const table = document.getElementById(tableID);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Clientes" });
            XLSX.writeFile(wb, filename ? filename + ".xlsx" : "clientes.xlsx");
        }


        document.querySelectorAll('#filtroForm input[type="text"]').forEach(input => {
            input.addEventListener('keydown', function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    this.form.submit();
                }
            });
        });
        function printTable(tableID = 'tablaClientesExport') {
            const table = document.getElementById(tableID);
            if (!table) {
                alert("No se encontró la tabla para imprimir.");
                return;
            }

            // Creamos una nueva ventana para imprimir
            const printWindow = window.open('', '', 'height=600,width=800');

            printWindow.document.write('<html><head><title>Imprimir Clientes</title>');

            // Aquí puedes agregar estilos para la impresión, como bootstrap o propios
            printWindow.document.write(`
                <style>
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            `);

            printWindow.document.write('</head><body >');
            printWindow.document.write('<h3>Listado de Clientes</h3>');

            // Clonamos la tabla para no modificar la original
            printWindow.document.write(table.outerHTML);

            printWindow.document.write('</body></html>');

            printWindow.document.close();
            printWindow.focus();

            // Esperamos que cargue el contenido y lanzamos la impresión
            printWindow.onload = function () {
                printWindow.print();
                printWindow.close();
            };
        }

    </script>
</body>

</html>
