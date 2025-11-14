<?php
session_start();

// Basic session and role check
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

/*$rol_usuario_logueado = (int)$_SESSION['usuario']['rol'];

// Check if user is authorized (e.g., admin (1), manager (2), contable (3) can view products)
// Adjust roles as needed for viewing.
if ($rol_usuario_logueado !== 1 && $rol_usuario_logueado !== 2 && $rol_usuario_logueado !== 3) {
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
                <p>No tienes los permisos necesarios para ver el listado de productos.</p>
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

// Handle DELETE request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_producto_a_eliminar = $_GET['id'];

    $ch = curl_init($apiProductos . '&id=' . $id_producto_a_eliminar);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($http_code === 200) {
        $message = '✅ Producto eliminado correctamente.';
        $message_type = 'alert-success';
    } else {
        $message = '❌ Error al eliminar el producto: ' . ($responseData['message'] ?? 'Respuesta inesperada de la API.');
        $message_type = 'alert-danger';
        if ($curl_error) {
            $message .= "<br>cURL Error: " . htmlspecialchars($curl_error);
        }
    }
}


// Fetch products
$productos = [];
$ch = curl_init($apiProductos);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $productos = json_decode($response, true);
    if (!is_array($productos)) {
        $productos = []; // Ensure it's an array even if API returns empty JSON object
        $message = '⚠️ No se encontraron productos o la respuesta de la API no es válida.';
        $message_type = 'alert-warning';
    }
} else {
    $message = '❌ Error al cargar productos: ' . ($response ? json_decode($response, true)['message'] ?? 'Error desconocido de la API.' : 'Error de conexión.');
    $message_type = 'alert-danger';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Listado de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        .table-responsive {
            margin-top: 20px;
        }
        .action-buttons a {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="p-3 navbar-color d-flex justify-content-between align-items-center">
        <h3 class="text-white mb-0">Listado de Productos</h3>
        <a href="nuevo_producto.php" class="btn btn-success">➕ Añadir Producto</a>
    </div>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?> text-center m-3" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end mb-3">
            <button onclick="exportTableToExcel()" class="btn btn-success me-2">📥 Exportar a Excel</button>
            <button onclick="printTable()" class="btn btn-primary">🖨️ Imprimir</button>
        </div>

        <div class="table-responsive card-body rounded-4 p-4">
            <?php if (!empty($productos)): ?>
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre del Producto</th>
                            <th>Importe</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['id_producto']); ?></td>
                                <td><?php echo htmlspecialchars($producto['codigo_producto'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($producto['producto']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($producto['importe'], 2, ',', '.')) . ' €'; ?></td>
                                <td class="action-buttons">
                                    <a href="editar_producto.php?id=<?php echo htmlspecialchars($producto['id_producto']); ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?php echo htmlspecialchars($producto['id_producto']); ?>">Eliminar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Tabla oculta para exportar e imprimir -->
                <table class="d-none" id="tablaProductosExport">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre del Producto</th>
                            <th>Importe (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['id_producto']); ?></td>
                                <td><?php echo htmlspecialchars($producto['codigo_producto'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($producto['producto']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($producto['importe'], 2, ',', '.')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-white text-center">No hay productos registrados.</p>
            <?php endif; ?>
        </div>

        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <a href="#" id="deleteProductLink" class="btn btn-danger">Eliminar</a>
                    </div>
                </div>  
            </div>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var confirmDeleteModal = document.getElementById('confirmDeleteModal');
        confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var productId = button.getAttribute('data-id');
            var deleteLink = document.getElementById('deleteProductLink');
            deleteLink.href = 'listar_productos.php?action=delete&id=' + productId;
        });

        function exportTableToExcel(tableID = 'tablaProductosExport', filename = '') {
            const table = document.getElementById(tableID);
            const wb = XLSX.utils.table_to_book(table, { sheet: "Productos" });
            XLSX.writeFile(wb, filename ? filename + ".xlsx" : "productos.xlsx");
        }

        function printTable(tableID = 'tablaProductosExport') {
            const table = document.getElementById(tableID);
            if (!table) {
                alert("No se encontró la tabla para imprimir.");
                return;
            }
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Imprimir Productos</title>');
            printWindow.document.write(`
                <style>
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            `);
            printWindow.document.write('</head><body >');
            printWindow.document.write('<h3>Listado de Productos</h3>');
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