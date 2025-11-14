<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Albarán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <style>
        /* Estilo adicional si es necesario para el select de presupuesto */
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
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

    $apiClientes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";
    $apiPedidos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=pedidos";
    $apiPresupuestos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";
    $apiAlbaranes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=albaranes";
    $apiArticulosAlbaran = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_albaran"; // Nueva API para artículos de albarán

    // Obtener lista de clientes para el <select>
    $clientes = [];
    $chClientes = curl_init($apiClientes);
    curl_setopt($chClientes, CURLOPT_RETURNTRANSFER, true);
    $jsonClientes = curl_exec($chClientes);
    $http_code_clientes = curl_getinfo($chClientes, CURLINFO_HTTP_CODE);
    $curl_error_clientes = curl_error($chClientes);
    curl_close($chClientes);

    if ($http_code_clientes === 200 && $jsonClientes !== false) {
        $clientes = json_decode($jsonClientes, true);
        if (!is_array($clientes)) {
            $clientes = []; // Asegura que $clientes sea un array
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API de clientes al decodificar JSON.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar clientes desde la API: ' . htmlspecialchars($curl_error_clientes ?: 'HTTP Error ' . $http_code_clientes) . '</div>';
    }

    // Obtener lista de PRESUPUESTOS para el <select>
    $presupuestos = [];
    $chPresupuestos = curl_init($apiPresupuestos);
    curl_setopt($chPresupuestos, CURLOPT_RETURNTRANSFER, true);
    $jsonPresupuestos = curl_exec($chPresupuestos);
    $http_code_presupuestos = curl_getinfo($chPresupuestos, CURLINFO_HTTP_CODE);
    $curl_error_presupuestos = curl_error($chPresupuestos);
    curl_close($chPresupuestos);

    if ($http_code_presupuestos === 200 && $jsonPresupuestos !== false) {
        $presupuestos = json_decode($jsonPresupuestos, true);
        if (!is_array($presupuestos)) {
            $presupuestos = [];
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API de presupuestos al decodificar JSON.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar presupuestos desde la API: ' . htmlspecialchars($curl_error_presupuestos ?: 'HTTP Error ' . $http_code_presupuestos) . '</div>';
    }

    // Obtener lista de PEDIDOS para el <select>
    $pedidos = [];
    $chPedidos = curl_init($apiPedidos);
    curl_setopt($chPedidos, CURLOPT_RETURNTRANSFER, true);
    $jsonPedidos = curl_exec($chPedidos);
    $http_code_pedidos = curl_getinfo($chPedidos, CURLINFO_HTTP_CODE);
    $curl_error_pedidos = curl_error($chPedidos);
    curl_close($chPedidos);

    if ($http_code_pedidos === 200 && $jsonPedidos !== false) {
        $pedidos = json_decode($jsonPedidos, true);
        if (!is_array($pedidos)) {
            $pedidos = [];
            echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API de pedidos al decodificar JSON.</div>';
        }
    } else {
        echo '<div class="alert alert-danger text-center m-3">Error al cargar pedidos desde la API: ' . htmlspecialchars($curl_error_pedidos ?: 'HTTP Error ' . $http_code_pedidos) . '</div>';
    }


    // Obtener productos y servicios
    $apiProductos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=productos";
    $apiServicios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=servicios";

    $productos = [];
    $servicios = [];

    $chProd = curl_init($apiProductos);
    curl_setopt($chProd, CURLOPT_RETURNTRANSFER, true);
    $jsonProd = curl_exec($chProd);
    curl_close($chProd);
    if ($jsonProd !== false) {
        $productos = json_decode($jsonProd, true);
        if (!is_array($productos)) $productos = [];
    }

    $chServ = curl_init($apiServicios);
    curl_setopt($chServ, CURLOPT_RETURNTRANSFER, true);
    $jsonServ = curl_exec($chServ);
    curl_close($chServ);
    if ($jsonServ !== false) {
        $servicios = json_decode($jsonServ, true);
        if (!is_array($servicios)) $servicios = [];
    }


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST["n_albaran"]) && !empty($_POST["id_cliente"])) {
            $data = [
                "n_albaran" => $_POST["n_albaran"],
                "fecha" => $_POST["fecha"] ?? null,
                "id_cliente" => $_POST["id_cliente"],
                "razon_social" => $_POST["razon_social"] ?? null,
                "iva" => $_POST["iva"] ?? null,
                "n_presupuesto" => $_POST["n_presupuesto"] ?? null,
                "n_pedido" => $_POST["n_pedido"] ?? null
            ];

            // Filtra los campos que no son nulos y que no están vacíos, a menos que sean n_presupuesto o n_pedido (que pueden ser vacíos si son opcionales)
            $data = array_filter($data, fn($v, $k) => $v !== null && ($v !== "" || $k === "n_presupuesto" || $k === "n_pedido"), ARRAY_FILTER_USE_BOTH);

            $ch = curl_init($apiAlbaranes);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if (($http_code === 200 || $http_code === 201) && isset($_POST['tipo'], $_POST['articulo'], $_POST['cantidad'])) {
                // Aquí usamos la nueva API para artículos de albarán
                $tipos = $_POST['tipo'];
                $articulos = $_POST['articulo'];
                $cantidades = $_POST['cantidad'];
                $n_albaran_asociado = $_POST['n_albaran'];

                for ($i = 0; $i < count($tipos); $i++) {
                    // Mapea 'producto' a 0 y 'servicio' a 1
                    $tipo_valor = $tipos[$i] === 'producto' ? 0 : ($tipos[$i] === 'servicio' ? 1 : null);
                    $codigo_articulo = $articulos[$i] ?? null;
                    $cantidad = $cantidades[$i] ?? null;

                    if ($tipo_valor !== null && $codigo_articulo && $cantidad) {
                        $articuloData = [
                            "n_albaran" => $n_albaran_asociado,
                            "codigo_articulo" => $codigo_articulo,
                            "cantidad" => $cantidad,
                            "tipo" => $tipo_valor
                        ];
                        $ch2 = curl_init($apiArticulosAlbaran);
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch2, CURLOPT_POST, true);
                        curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                        curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($articuloData));
                        $responseArticulo = curl_exec($ch2);
                        $http_code_articulo = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        $curl_error_articulo = curl_error($ch2);
                        curl_close($ch2);

                        if ($http_code_articulo !== 200 && $http_code_articulo !== 201) {
                            echo '<div class="alert alert-warning text-center m-3">Atención: Fallo al guardar artículo ' . htmlspecialchars($codigo_articulo) . ' (HTTP ' . htmlspecialchars($http_code_articulo) . '). Error: ' . htmlspecialchars($curl_error_articulo ?: $responseArticulo) . '</div>';
                        }
                    }
                }
            }

            echo '<div class="alert text-center m-3 ' . ($http_code === 200 || $http_code === 201 ? 'alert-success' : 'alert-danger') . '">';
            $responseData = json_decode($response, true);
            if (is_array($responseData) && isset($responseData['message'])) {
                echo "<strong>Mensaje API:</strong> " . htmlspecialchars($responseData['message']);
            } elseif (is_array($responseData)) {
                foreach ($responseData as $clave => $valor) {
                    echo "<strong>" . htmlspecialchars($clave) . ":</strong> " . htmlspecialchars($valor) . "<br>";
                }
            } else {
                echo 'Respuesta inesperada de la API: ' . htmlspecialchars($response);
            }
            if ($curl_error) {
                echo "<br><strong>cURL Error:</strong> " . htmlspecialchars($curl_error);
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Nº Albarán</strong> y <strong>Cliente</strong> son obligatorios.</div>';
        }
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Nuevo Albarán</h3>
    </div>

    <div class="container mt-5">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nº Albarán*</label>
                    <input name="n_albaran" type="text" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input name="fecha" type="date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente*</label>
                    <select name="id_cliente" id="id_cliente" class="form-select" required>
                        <option value="">Seleccionar cliente</option>
                        <?php foreach ($clientes as $cliente) : ?>
                            <option value="<?= htmlspecialchars($cliente['id_cliente']) ?>">
                                <?= htmlspecialchars($cliente['cif_nif'] ?? "Sin NIF/CIF") ?> - <?= htmlspecialchars($cliente['razon_social'] ?? "Sin Razón Social") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Razón Social</label>
                    <input name="razon_social" id="razon_social" type="text" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">IVA</label>
                    <input name="iva" id="iva" type="number" step="0.001" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nº Presupuesto Asociado</label>
                    <select name="n_presupuesto" id="n_presupuesto" class="form-select">
                        <option value="">Seleccionar presupuesto (Opcional)</option>
                        <?php foreach ($presupuestos as $presupuesto) : ?>
                            <option value="<?= htmlspecialchars($presupuesto['n_presupuesto']) ?>">
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
                            <option value="<?= htmlspecialchars($pedido['n_pedido']) ?>">
                                <?= htmlspecialchars($pedido['n_pedido'] ?? 'N/A') ?> - <?= htmlspecialchars($pedido['razon_social'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="campos-dinamicos">
                <div class="row mb-2 grupo-campos">
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo[]" class="form-select tipo-select">
                            <option value="">Seleccionar tipo</option>
                            <option value="producto">Producto</option>
                            <option value="servicio">Servicio</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Producto/Servicio</label>
                        <select name="articulo[]" class="form-select articulo-select">
                            <option value="">Seleccione primero un tipo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantidad</label>
                        <input name="cantidad[]" type="number" min="1" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-success btn-add-campo">+</button>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn navbar-color text-white me-2">Añadir Albarán</button>
                <a href="../index.php" class="btn btn-danger">Cancelar</a>
            </div>
            <div class="d-flex justify-content-end mb-3 mt-2">
                <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
            </div>
        </form>
    </div>
    <script>
        const productos = <?php echo json_encode($productos); ?>;
        const servicios = <?php echo json_encode($servicios); ?>;
        const clientes = <?php echo json_encode($clientes); ?>;
        const apiPresupuestosUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";
        const apiPedidosUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=pedidos";
        const apiArticulosPresupuestoUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_presupuesto"; // API para artículos de presupuesto
        const apiArticulosPedidoUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_pedido"; // API para artículos de pedido

        /**
         * Rellena el select de artículos (productos o servicios) basado en el tipo seleccionado.
         * @param {string} tipo - 'producto' o 'servicio'.
         * @param {HTMLSelectElement} selectArticulo - El elemento select de artículos a rellenar.
         */
        function rellenarArticulos(tipo, selectArticulo) {
            let opciones = '<option value="">Seleccione una opción</option>';
            if (tipo === 'producto') {
                productos.forEach(function(prod) {
                    opciones += `<option value="${prod.codigo_producto}">${prod.producto}</option>`;
                });
            } else if (tipo === 'servicio') {
                servicios.forEach(function(serv) {
                    opciones += `<option value="${serv.codigo_servicio}">${serv.servicio}</option>`;
                });
            } else {
                opciones = '<option value="">Seleccione primero un tipo</option>';
            }
            selectArticulo.innerHTML = opciones;
        }

        /**
         * Limpia todas las filas de artículos, dejando solo una vacía.
         * Reinicia el estado de los campos de artículo a su configuración inicial.
         */
        function limpiarCamposArticulos() {
            const contenedor = document.getElementById('campos-dinamicos');
            // Eliminar todas las filas de artículos excepto la primera
            contenedor.querySelectorAll('.grupo-campos').forEach((row, index) => {
                if (index > 0) {
                    row.remove();
                } else {
                    // Limpiar la primera fila
                    row.querySelector('.tipo-select').value = '';
                    row.querySelector('.articulo-select').innerHTML = '<option value="">Seleccione primero un tipo</option>';
                    row.querySelector('input[name="cantidad[]"]').value = '';
                    // Asegurar que el botón de la primera fila sea '+'
                    const btn = row.querySelector('.btn-add-campo');
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-success');
                    btn.textContent = '+';
                }
            });
            console.log('Campos de artículos limpiados. Queda una fila vacía.');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const contenedor = document.getElementById('campos-dinamicos');
            const selectPresupuesto = document.getElementById('n_presupuesto');
            const selectPedido = document.getElementById('n_pedido');
            const inputIdCliente = document.getElementById('id_cliente');
            const inputRazonSocial = document.getElementById('razon_social');
            const inputIVA = document.getElementById('iva');

            // Llenar el primer select de artículo al cargar la página si ya hay un tipo seleccionado
            const firstTipoSelect = contenedor.querySelector('.tipo-select');
            const firstArticuloSelect = contenedor.querySelector('.articulo-select');
            if (firstTipoSelect && firstArticuloSelect) {
                rellenarArticulos(firstTipoSelect.value, firstArticuloSelect);
            }

            // Función para cargar datos de cliente (razón social e IVA)
            function cargarDatosCliente(clienteId) {
                const clienteAsociado = clientes.find(c => c.id_cliente == clienteId);
                if (clienteAsociado) {
                    inputIdCliente.value = clienteId; // Asegura que el ID de cliente esté puesto
                    inputRazonSocial.value = clienteAsociado.razon_social;
                    inputIVA.value = clienteAsociado.iva;
                } else {
                    inputIdCliente.value = '';
                    inputRazonSocial.value = '';
                    inputIVA.value = '';
                }
            }

            // Event listener para el select de presupuesto
            selectPresupuesto.addEventListener('change', async function() {
                const nPresupuesto = this.value;
                // No deseleccionamos el pedido aquí

                // Limpiar campos si no se selecciona ningún presupuesto Y NO HAY PEDIDO SELECCIONADO
                if (!nPresupuesto && !selectPedido.value) {
                    console.log('Ningún presupuesto ni pedido seleccionado. Limpiando campos.');
                    inputIdCliente.value = '';
                    inputRazonSocial.value = '';
                    inputIVA.value = '';
                    limpiarCamposArticulos();
                    return;
                }

                // Si hay presupuesto, cargar sus datos. Si no, y hay pedido, dejar los del pedido.
                if (nPresupuesto) {
                    const url = `${apiPresupuestosUrlBase}&n_presupuesto=${encodeURIComponent(nPresupuesto)}`;
                    console.log('Fetching URL (Presupuesto):', url);

                    try {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                        }
                        const data = await response.json();
                        const presupuesto = Array.isArray(data) ? data[0] : data;

                        if (presupuesto && presupuesto.n_presupuesto) {
                            cargarDatosCliente(presupuesto.id_cliente);
                            inputIVA.value = presupuesto.iva || ''; // Sobrescribir IVA con el del presupuesto si existe

                            // Limpiar y cargar artículos SÓLO si no hay un pedido seleccionado.
                            // Si ambos están seleccionados, los artículos del pedido prevalecerán o se sumarán.
                            // Para este caso, vamos a hacer que los artículos del presupuesto SE AÑADAN (no reemplacen)
                            // si ya hay artículos del pedido.
                            if (!selectPedido.value) { // Si no hay pedido seleccionado, cargamos solo los del presupuesto
                                limpiarCamposArticulos();
                                cargarArticulosDesdeAPI(apiArticulosPresupuestoUrlBase, 'n_presupuesto', nPresupuesto);
                            } else {
                                // Si ya hay un pedido seleccionado, añade los artículos del presupuesto a los existentes.
                                // Esto requiere un manejo más complejo si quieres evitar duplicados o sumarlos.
                                // Por simplicidad, aquí puedes decidir si los fusionas o si uno prevalece.
                                // Para esta implementación, vamos a hacer que el último en ser seleccionado (ya sea presupuesto o pedido)
                                // sea el que cargue sus artículos, a menos que ya estén cargados por el otro.
                                // La forma más simple es que el que se selecciona *último* determine los artículos.
                                // Si quieres fusionar, la lógica es más compleja. Por ahora, si hay pedido, NO sobreescribimos los artículos
                                // hasta que el pedido se deseleccione o se cambie el presupuesto.
                                console.log("Presupuesto seleccionado, pero ya hay un pedido. Los artículos del pedido tienen prioridad por ahora.");
                            }

                        } else {
                            console.warn('No se encontraron datos VÁLIDOS para el presupuesto seleccionado.');
                            // Si no hay presupuesto y no hay pedido, limpiar todo.
                            if (!selectPedido.value) {
                                inputIdCliente.value = '';
                                inputRazonSocial.value = '';
                                inputIVA.value = '';
                                limpiarCamposArticulos();
                            }
                        }
                    } catch (error) {
                        console.error('ERROR FATAL: Error al obtener o procesar los datos del presupuesto:', error);
                        alert('Hubo un error al cargar los datos del presupuesto. Revisa la consola (F12) para más detalles.');
                        if (!selectPedido.value) {
                            inputIdCliente.value = '';
                            inputRazonSocial.value = '';
                            inputIVA.value = '';
                            limpiarCamposArticulos();
                        }
                    }
                } else {
                    // Si el presupuesto se deselecciona y NO hay pedido seleccionado, limpiar todo.
                    if (!selectPedido.value) {
                        inputIdCliente.value = '';
                        inputRazonSocial.value = '';
                        inputIVA.value = '';
                        limpiarCamposArticulos();
                    }
                    // Si el presupuesto se deselecciona pero HAY un pedido, mantener los datos del pedido y recargar sus artículos
                    else {
                        cargarArticulosDesdeAPI(apiArticulosPedidoUrlBase, 'n_pedido', selectPedido.value);
                    }
                }
            });

            // Event listener para el select de pedido
            selectPedido.addEventListener('change', async function() {
                const nPedido = this.value;
                // No deseleccionamos el presupuesto aquí

                // Limpiar campos si no se selecciona ningún pedido Y NO HAY PRESUPUESTO SELECCIONADO
                if (!nPedido && !selectPresupuesto.value) {
                    console.log('Ningún pedido ni presupuesto seleccionado. Limpiando campos.');
                    inputIdCliente.value = '';
                    inputRazonSocial.value = '';
                    inputIVA.value = '';
                    limpiarCamposArticulos();
                    return;
                }

                // Si hay pedido, cargar sus datos. Si no, y hay presupuesto, dejar los del presupuesto.
                if (nPedido) {
                    const url = `${apiPedidosUrlBase}&n_pedido=${encodeURIComponent(nPedido)}`;
                    console.log('Fetching URL (Pedido):', url);

                    try {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                        }
                        const data = await response.json();
                        const pedido = Array.isArray(data) ? data[0] : data;

                        if (pedido && pedido.n_pedido) {
                            cargarDatosCliente(pedido.id_cliente);
                            inputIVA.value = pedido.iva || ''; // Sobrescribir IVA con el del pedido si existe

                            // Limpiar y cargar artículos SÓLO si no hay un presupuesto seleccionado.
                            if (!selectPresupuesto.value) { // Si no hay presupuesto, cargamos solo los del pedido
                                limpiarCamposArticulos();
                                cargarArticulosDesdeAPI(apiArticulosPedidoUrlBase, 'n_pedido', nPedido);
                            } else {
                                console.log("Pedido seleccionado, pero ya hay un presupuesto. Los artículos del presupuesto tienen prioridad por ahora.");
                            }

                        } else {
                            console.warn('No se encontraron datos VÁLIDOS para el pedido seleccionado.');
                            // Si no hay pedido y no hay presupuesto, limpiar todo.
                            if (!selectPresupuesto.value) {
                                inputIdCliente.value = '';
                                inputRazonSocial.value = '';
                                inputIVA.value = '';
                                limpiarCamposArticulos();
                            }
                        }
                    } catch (error) {
                        console.error('ERROR FATAL: Error al obtener o procesar los datos del pedido:', error);
                        alert('Hubo un error al cargar los datos del pedido. Revisa la consola (F12) para más detalles.');
                        if (!selectPresupuesto.value) {
                            inputIdCliente.value = '';
                            inputRazonSocial.value = '';
                            inputIVA.value = '';
                            limpiarCamposArticulos();
                        }
                    }
                } else {
                    // Si el pedido se deselecciona y NO hay presupuesto seleccionado, limpiar todo.
                    if (!selectPresupuesto.value) {
                        inputIdCliente.value = '';
                        inputRazonSocial.value = '';
                        inputIVA.value = '';
                        limpiarCamposArticulos();
                    }
                    // Si el pedido se deselecciona pero HAY un presupuesto, mantener los datos del presupuesto y recargar sus artículos
                    else {
                        cargarArticulosDesdeAPI(apiArticulosPresupuestoUrlBase, 'n_presupuesto', selectPresupuesto.value);
                    }
                }
            });

            /**
             * Carga artículos desde una API y los añade a los campos dinámicos.
             * @param {string} apiUrlBase - La URL base de la API de artículos (presupuesto o pedido).
             * @param {string} paramName - El nombre del parámetro en la URL (e.g., 'n_presupuesto', 'n_pedido').
             * @param {string} paramValue - El valor del parámetro.
             */
            async function cargarArticulosDesdeAPI(apiUrlBase, paramName, paramValue) {
                const urlArticulos = `${apiUrlBase}&${paramName}=${encodeURIComponent(paramValue)}`;
                console.log(`Workspaceing artículos desde: ${urlArticulos}`);
                try {
                    const resArticulos = await fetch(urlArticulos);
                    if (!resArticulos.ok) {
                        throw new Error(`HTTP error! status: ${resArticulos.status}`);
                    }
                    const articulos = await resArticulos.json();

                    if (articulos && Array.isArray(articulos) && articulos.length > 0) {
                        limpiarCamposArticulos(); // Limpiamos antes de cargar para evitar duplicados si se recargan
                        articulos.forEach((articulo, index) => {
                            let tipoString = '';
                            if (articulo.tipo === 0 || articulo.tipo === '0') {
                                tipoString = 'producto';
                            } else if (articulo.tipo === 1 || articulo.tipo === '1') {
                                tipoString = 'servicio';
                            } else {
                                console.warn(`Tipo de artículo desconocido para el artículo ${articulo.codigo_articulo}: ${articulo.tipo}`);
                                return;
                            }

                            if (index === 0) {
                                // Rellenar la primera fila existente
                                const tipoSelect = contenedor.querySelector('.grupo-campos:first-child .tipo-select');
                                const articuloSelect = contenedor.querySelector('.grupo-campos:first-child .articulo-select');
                                const cantidadInput = contenedor.querySelector('.grupo-campos:first-child input[name="cantidad[]"]');

                                tipoSelect.value = tipoString;
                                rellenarArticulos(tipoSelect.value, articuloSelect);
                                if (articuloSelect.querySelector(`option[value="${articulo.codigo_articulo}"]`)) {
                                    articuloSelect.value = articulo.codigo_articulo;
                                }
                                cantidadInput.value = articulo.cantidad;
                            } else {
                                // Añadir nuevas filas para los artículos restantes
                                const addBtn = contenedor.querySelector('.grupo-campos:last-child .btn-add-campo');
                                if (addBtn) {
                                    addBtn.click(); // Simula clic para añadir nueva fila
                                    const newGrupo = contenedor.querySelector('.grupo-campos:last-child');
                                    const tipoSelect = newGrupo.querySelector('.tipo-select');
                                    const articuloSelect = newGrupo.querySelector('.articulo-select');
                                    const cantidadInput = newGrupo.querySelector('input[name="cantidad[]"]');

                                    tipoSelect.value = tipoString;
                                    rellenarArticulos(tipoSelect.value, articuloSelect);
                                    if (articuloSelect.querySelector(`option[value="${articulo.codigo_articulo}"]`)) {
                                        articuloSelect.value = articulo.codigo_articulo;
                                    }
                                    cantidadInput.value = articulo.cantidad;
                                }
                            }
                        });
                    } else {
                        console.log(`No se encontraron artículos para ${paramName}: ${paramValue}`);
                        // Si no hay artículos en la API para el elemento seleccionado y NO hay otra referencia seleccionada, limpiar.
                        if (!selectPresupuesto.value && !selectPedido.value) {
                             limpiarCamposArticulos();
                        }
                    }
                } catch (error) {
                    console.error(`Error al cargar artículos de ${paramName}:`, error);
                    alert(`Hubo un error al cargar los artículos asociados al ${paramName}.`);
                    // Si hay un error, limpiar los campos de artículos si no hay otra referencia válida.
                    if (!selectPresupuesto.value && !selectPedido.value) {
                        limpiarCamposArticulos();
                    }
                }
            }


            // Delegación de eventos para los select de tipo (para artículos)
            contenedor.addEventListener('change', function(e) {
                if (e.target.classList.contains('tipo-select')) {
                    const grupo = e.target.closest('.grupo-campos');
                    const selectArticulo = grupo.querySelector('.articulo-select');
                    rellenarArticulos(e.target.value, selectArticulo);
                }
            });

            // Lógica para añadir/eliminar campos de artículo dinámicamente
            contenedor.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-add-campo')) {
                    if (e.target.textContent === '+') {
                        const originalRow = e.target.closest('.grupo-campos');
                        const newRow = originalRow.cloneNode(true);
                        newRow.querySelectorAll('input, select').forEach(input => input.value = ''); // Limpiar valores
                        newRow.querySelector('.articulo-select').innerHTML = '<option value="">Seleccione primero un tipo</option>';

                        // Cambiar el botón de la fila original a '-'
                        e.target.classList.remove('btn-success');
                        e.target.classList.add('btn-danger');
                        e.target.textContent = '-';

                        contenedor.appendChild(newRow);
                        // Asegurar que el nuevo botón sea '+'
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

            // Inicializar albarán con datos de cliente si se selecciona al principio
            inputIdCliente.addEventListener('change', function() {
                cargarDatosCliente(this.value);
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>