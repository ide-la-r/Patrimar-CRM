<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva Factura</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../stylesheets/style.css">
    <style>
        /* Estilo adicional si es necesario */
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

    // Rutas de API para facturas, clientes, pedidos, presupuestos y albaranes
    $apiFacturas = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=facturas";
    $apiClientes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";
    $apiPedidos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=pedidos";
    $apiPresupuestos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";
    $apiAlbaranes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=albaranes";
    $apiArticulosFactura = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_factura"; // Nueva API para artículos de factura
    $apiArticulosAlbaran = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_albaran";
    $apiArticulosPedido = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_pedido";
    $apiArticulosPresupuesto = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_presupuesto";

    // Obtener listas para los <select>
    function fetchData($apiUrl, $errorMessage) {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && $json !== false) {
            $data = json_decode($json, true);
            if (!is_array($data)) {
                echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API al decodificar JSON para ' . $errorMessage . '.</div>';
                return [];
            }
            return $data;
        } else {
            echo '<div class="alert alert-danger text-center m-3">Error al cargar ' . $errorMessage . ' desde la API: ' . htmlspecialchars($curl_error ?: 'HTTP Error ' . $http_code) . '</div>';
            return [];
        }
    }

    $clientes = fetchData($apiClientes, 'clientes');
    $presupuestos = fetchData($apiPresupuestos, 'presupuestos');
    $pedidos = fetchData($apiPedidos, 'pedidos');
    $albaranes = fetchData($apiAlbaranes, 'albaranes');

    // Obtener productos y servicios para los selectores dinámicos
    $apiProductos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=productos";
    $apiServicios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=servicios";
    $productos = fetchData($apiProductos, 'productos');
    $servicios = fetchData($apiServicios, 'servicios');


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST["n_factura"]) && !empty($_POST["id_cliente"])) {
            $data = [
                "n_factura" => $_POST["n_factura"],
                "fecha" => $_POST["fecha"] ?? null,
                "id_cliente" => $_POST["id_cliente"],
                "razon_social" => $_POST["razon_social"] ?? null,
                "iva" => $_POST["iva"] ?? null,
                "n_presupuesto" => $_POST["n_presupuesto"] ?? null,
                "n_pedido" => $_POST["n_pedido"] ?? null,
                "n_albaran" => $_POST["n_albaran"] ?? null
            ];

            // Filtrar campos nulos o vacíos, excepto las referencias opcionales
            $data = array_filter($data, fn($v, $k) => $v !== null && ($v !== "" || in_array($k, ["n_presupuesto", "n_pedido", "n_albaran"])), ARRAY_FILTER_USE_BOTH);

            $ch = curl_init($apiFacturas);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if (($http_code === 200 || $http_code === 201) && isset($_POST['tipo'], $_POST['articulo'], $_POST['cantidad'])) {
                $tipos = $_POST['tipo'];
                $articulos = $_POST['articulo'];
                $cantidades = $_POST['cantidad'];
                $n_factura_asociado = $_POST['n_factura'];

                for ($i = 0; $i < count($tipos); $i++) {
                    $tipo_valor = $tipos[$i] === 'producto' ? 0 : ($tipos[$i] === 'servicio' ? 1 : null);
                    $codigo_articulo = $articulos[$i] ?? null;
                    $cantidad = $cantidades[$i] ?? null;

                    if ($tipo_valor !== null && $codigo_articulo && $cantidad) {
                        $articuloData = [
                            "n_factura" => $n_factura_asociado,
                            "codigo_articulo" => $codigo_articulo,
                            "cantidad" => $cantidad,
                            "tipo" => $tipo_valor
                        ];
                        $ch2 = curl_init($apiArticulosFactura);
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
            echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Nº Factura</strong> y <strong>Cliente</strong> son obligatorios.</div>';
        }
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Nueva Factura</h3>
    </div>

    <div class="container mt-5">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nº Factura*</label>
                    <input name="n_factura" type="text" class="form-control" required>
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
                    <input name="razon_social" id="razon_social" type="text" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">IVA</label>
                    <input name="iva" id="iva" type="number" step="0.001" class="form-control" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Asociar Presupuesto</label>
                    <select name="n_presupuesto" id="n_presupuesto" class="form-select">
                        <option value="">Seleccionar presupuesto (Opcional)</option>
                        <?php foreach ($presupuestos as $presupuesto) : ?>
                            <option value="<?= htmlspecialchars($presupuesto['n_presupuesto']) ?>">
                                <?= htmlspecialchars($presupuesto['n_presupuesto'] ?? 'N/A') ?> - <?= htmlspecialchars($presupuesto['razon_social'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Asociar Pedido</label>
                    <select name="n_pedido" id="n_pedido" class="form-select">
                        <option value="">Seleccionar pedido (Opcional)</option>
                        <?php foreach ($pedidos as $pedido) : ?>
                            <option value="<?= htmlspecialchars($pedido['n_pedido']) ?>">
                                <?= htmlspecialchars($pedido['n_pedido'] ?? 'N/A') ?> - <?= htmlspecialchars($pedido['razon_social'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Asociar Albarán</label>
                    <select name="n_albaran" id="n_albaran" class="form-select">
                        <option value="">Seleccionar albarán (Opcional)</option>
                        <?php foreach ($albaranes as $albaran) : ?>
                            <option value="<?= htmlspecialchars($albaran['n_albaran']) ?>">
                                <?= htmlspecialchars($albaran['n_albaran'] ?? 'N/A') ?> - <?= htmlspecialchars($albaran['razon_social'] ?? 'N/A') ?>
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
                <button type="submit" class="btn navbar-color text-white me-2">Añadir Factura</button>
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

        const apiArticulosPresupuestoUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_presupuesto";
        const apiArticulosPedidoUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_pedido";
        const apiArticulosAlbaranUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_albaran";

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

        /**
         * Carga artículos desde una API y los añade a los campos dinámicos.
         * @param {string} apiUrlBase - La URL base de la API de artículos (presupuesto, pedido o albarán).
         * @param {string} paramName - El nombre del parámetro en la URL (e.g., 'n_presupuesto', 'n_pedido', 'n_albaran').
         * @param {string} paramValue - El valor del parámetro.
         */
        async function cargarArticulosDesdeAPI(apiUrlBase, paramName, paramValue) {
            const urlArticulos = `${apiUrlBase}&${paramName}=${encodeURIComponent(paramValue)}`;
            console.log(`Cargando artículos desde: ${urlArticulos}`);
            try {
                const resArticulos = await fetch(urlArticulos);
                if (!resArticulos.ok) {
                    throw new Error(`HTTP error! status: ${resArticulos.status}`);
                }
                const articulos = await resArticulos.json();

                if (articulos && Array.isArray(articulos) && articulos.length > 0) {
                    limpiarCamposArticulos(); // Limpiamos antes de cargar para evitar duplicados

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

                        let targetRow;
                        if (index === 0) {
                            // Usar la primera fila existente
                            targetRow = document.querySelector('#campos-dinamicos .grupo-campos:first-child');
                        } else {
                            // Añadir una nueva fila para los artículos adicionales
                            targetRow = crearNuevoCampoArticulo();
                        }

                        const tipoSelect = targetRow.querySelector('.tipo-select');
                        const articuloSelect = targetRow.querySelector('.articulo-select');
                        const cantidadInput = targetRow.querySelector('input[name="cantidad[]"]');

                        tipoSelect.value = tipoString;
                        rellenarArticulos(tipoSelect.value, articuloSelect);
                        if (articuloSelect.querySelector(`option[value="${articulo.codigo_articulo}"]`)) {
                            articuloSelect.value = articulo.codigo_articulo;
                        }
                        cantidadInput.value = articulo.cantidad;
                    });
                } else {
                    console.log('No se encontraron artículos para la referencia seleccionada.');
                    // Si no hay artículos, se mantienen los campos limpios
                }
            } catch (error) {
                console.error('Error al obtener o procesar los datos de artículos:', error);
                alert('Hubo un error al cargar los artículos asociados. Revisa la consola (F12) para más detalles.');
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            const contenedor = document.getElementById('campos-dinamicos');
            const selectPresupuesto = document.getElementById('n_presupuesto');
            const selectPedido = document.getElementById('n_pedido');
            const selectAlbaran = document.getElementById('n_albaran');
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

            // --- Lógica de asociación y carga de datos ---

            // Función principal para manejar la carga de datos al cambiar los selectores asociados
            async function handleAssociationChange() {
                const nPresupuesto = selectPresupuesto.value;
                const nPedido = selectPedido.value;
                const nAlbaran = selectAlbaran.value;

                let sourceData = null; // Guardará los datos del cliente y el IVA de la fuente de datos (presupuesto, pedido, albarán)
                let articlesToLoad = []; // Guardará las URLs y parámetros de los artículos a cargar

                // Prioridad: Albarán > Pedido > Presupuesto
                if (nAlbaran) {
                    console.log('Albarán seleccionado:', nAlbaran);
                    sourceData = await fetchSourceData('albaranes', nAlbaran);
                    if (sourceData) {
                        articlesToLoad.push({ api: apiArticulosAlbaranUrlBase, paramName: 'n_albaran', paramValue: nAlbaran });
                    }
                } else if (nPedido) {
                    console.log('Pedido seleccionado:', nPedido);
                    sourceData = await fetchSourceData('pedidos', nPedido);
                    if (sourceData) {
                        articlesToLoad.push({ api: apiArticulosPedidoUrlBase, paramName: 'n_pedido', paramValue: nPedido });
                    }
                } else if (nPresupuesto) {
                    console.log('Presupuesto seleccionado:', nPresupuesto);
                    sourceData = await fetchSourceData('presupuestos', nPresupuesto);
                    if (sourceData) {
                        articlesToLoad.push({ api: apiArticulosPresupuestoUrlBase, paramName: 'n_presupuesto', paramValue: nPresupuesto });
                    }
                }

                // Cargar datos de cliente y IVA
                if (sourceData && sourceData.id_cliente) {
                    cargarDatosCliente(sourceData.id_cliente);
                    inputIVA.value = sourceData.iva || '';
                } else {
                    // Si no hay asociación, limpiar campos de cliente/IVA
                    if (!nPresupuesto && !nPedido && !nAlbaran) {
                        inputIdCliente.value = '';
                        inputRazonSocial.value = '';
                        inputIVA.value = '';
                    }
                }

                // Cargar artículos
                if (articlesToLoad.length > 0) {
                    limpiarCamposArticulos(); // Limpiar antes de cargar nuevos
                    for (const { api, paramName, paramValue } of articlesToLoad) {
                        await cargarArticulosDesdeAPI(api, paramName, paramValue);
                    }
                } else {
                    // Si no hay ninguna asociación o los datos de la asociación no tienen artículos, limpiar campos de artículos
                    limpiarCamposArticulos();
                }
            }

            // Función genérica para obtener datos de una fuente (presupuesto, pedido, albarán)
            async function fetchSourceData(tableName, identifier) {
                let apiUrl = `http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=${tableName}`;
                if (tableName === 'presupuestos') apiUrl += `&n_presupuesto=${encodeURIComponent(identifier)}`;
                else if (tableName === 'pedidos') apiUrl += `&n_pedido=${encodeURIComponent(identifier)}`;
                else if (tableName === 'albaranes') apiUrl += `&n_albaran=${encodeURIComponent(identifier)}`;
                else return null;

                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) {
                        console.error(`Error al obtener ${tableName} (${identifier}): HTTP ${response.status}`);
                        return null;
                    }
                    const data = await response.json();
                    return Array.isArray(data) ? data[0] : data; // La API podría devolver un array
                } catch (error) {
                    console.error(`Error de red o procesamiento al obtener ${tableName} (${identifier}):`, error);
                    return null;
                }
            }


            // Event listeners para los selectores de asociación
            selectPresupuesto.addEventListener('change', handleAssociationChange);
            selectPedido.addEventListener('change', handleAssociationChange);
            selectAlbaran.addEventListener('change', handleAssociationChange);
            inputIdCliente.addEventListener('change', function() {
                // Si el cliente se selecciona directamente, sobrescribir razón social e IVA
                if (!selectPresupuesto.value && !selectPedido.value && !selectAlbaran.value) {
                    cargarDatosCliente(this.value);
                }
            });


            // --- Lógica para añadir/eliminar campos de artículo dinámicamente ---
            contenedor.addEventListener('change', function(e) {
                if (e.target.classList.contains('tipo-select')) {
                    const selectArticulo = e.target.closest('.grupo-campos').querySelector('.articulo-select');
                    rellenarArticulos(e.target.value, selectArticulo);
                }
            });

            contenedor.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-add-campo')) {
                    if (e.target.textContent === '+') {
                        e.target.textContent = '-';
                        e.target.classList.remove('btn-success');
                        e.target.classList.add('btn-danger');
                        crearNuevoCampoArticulo();
                    } else {
                        e.target.closest('.grupo-campos').remove();
                    }
                }
            });

            function crearNuevoCampoArticulo() {
                const nuevoCampo = document.createElement('div');
                nuevoCampo.classList.add('row', 'mb-2', 'grupo-campos');
                nuevoCampo.innerHTML = `
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
                        <button type="button" class="btn btn-danger btn-add-campo">-</button>
                    </div>
                `;
                contenedor.appendChild(nuevoCampo);
                return nuevoCampo; // Devolver el nuevo elemento para poder rellenarlo si es necesario
            }
        });
    </script>
</body>

</html>