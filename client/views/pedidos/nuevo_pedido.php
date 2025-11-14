<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Pedido</title>
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
    $apiPresupuestos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos"; // API para obtener presupuestos

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
        if (!empty($_POST["n_pedido"]) && !empty($_POST["id_cliente"])) {
            $data = [
                "n_pedido" => $_POST["n_pedido"],
                "fecha" => $_POST["fecha"] ?? null,
                "id_cliente" => $_POST["id_cliente"],
                "razon_social" => $_POST["razon_social"] ?? null,
                "iva" => $_POST["iva"] ?? null,
                "n_presupuesto" => $_POST["n_presupuesto"] ?? null
            ];

            // Filtra los campos que no son nulos y que no están vacíos, a menos que sea n_presupuesto (que puede ser vacío si es opcional)
            $data = array_filter($data, fn($v, $k) => $v !== null && ($v !== "" || $k === "n_presupuesto"), ARRAY_FILTER_USE_BOTH);

            $ch = curl_init($apiPedidos);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if (($http_code === 200 || $http_code === 201) && isset($_POST['tipo'], $_POST['articulo'], $_POST['cantidad'])) {
                $apiArticulosPedido = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_pedido";
                $tipos = $_POST['tipo'];
                $articulos = $_POST['articulo'];
                $cantidades = $_POST['cantidad'];
                $n_pedido_asociado = $_POST['n_pedido'];

                for ($i = 0; $i < count($tipos); $i++) {
                    // Mapea 'producto' a 0 y 'servicio' a 1
                    $tipo_valor = $tipos[$i] === 'producto' ? 0 : ($tipos[$i] === 'servicio' ? 1 : null);
                    $codigo_articulo = $articulos[$i] ?? null;
                    $cantidad = $cantidades[$i] ?? null;

                    if ($tipo_valor !== null && $codigo_articulo && $cantidad) {
                        $articuloData = [
                            "n_pedido" => $n_pedido_asociado,
                            "codigo_articulo" => $codigo_articulo,
                            "cantidad" => $cantidad,
                            "tipo" => $tipo_valor
                        ];
                        $ch2 = curl_init($apiArticulosPedido);
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
            echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Nº pedido</strong> y <strong>Cliente</strong> son obligatorios.</div>';
        }
    }
    ?>

    <div class="p-3 navbar-color">
        <h3 class="text-white">Nuevo Pedido</h3>
    </div>

    <div class="container mt-5">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nº Pedido*</label>
                    <input name="n_pedido" type="text" class="form-control" required>
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
                <div class="col-md-3">
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
                <button type="submit" class="btn navbar-color text-white me-2">Añadir Pedido</button>
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
        const clientes = <?php echo json_encode($clientes); ?>; // Pasar clientes a JS para encontrar razón social
        const apiPresupuestosUrlBase = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos"; // Base para la API de presupuestos

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
            const inputIdCliente = document.getElementById('id_cliente');
            const inputRazonSocial = document.getElementById('razon_social');
            const inputIVA = document.getElementById('iva');

            // Llenar el primer select de artículo al cargar la página si ya hay un tipo seleccionado
            const firstTipoSelect = contenedor.querySelector('.tipo-select');
            const firstArticuloSelect = contenedor.querySelector('.articulo-select');
            if (firstTipoSelect && firstArticuloSelect) {
                rellenarArticulos(firstTipoSelect.value, firstArticuloSelect);
            }

            // Event listener para el select de presupuesto
            selectPresupuesto.addEventListener('change', async function() {
                const nPresupuesto = this.value;

                // Limpiar campos si no se selecciona ningún presupuesto
                if (!nPresupuesto) {
                    console.log('Ningún presupuesto seleccionado. Limpiando campos.');
                    inputIdCliente.value = '';
                    inputRazonSocial.value = '';
                    inputIVA.value = '';
                    limpiarCamposArticulos();
                    return;
                }

                // Cargar datos del presupuesto seleccionado
                const url = `${apiPresupuestosUrlBase}&n_presupuesto=${encodeURIComponent(nPresupuesto)}`;
                console.log('Fetching URL:', url); // DEBUG: Ver la URL de la API

                try {
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
                    }
                    const data = await response.json();

                    console.log('API Raw Response Data:', data); // DEBUG: Ver la respuesta cruda de la API

                    // La API debería devolver un array con un solo presupuesto o un objeto directamente
                    // Nos aseguramos de que 'presupuesto' sea el objeto de datos que esperamos.
                    const presupuesto = Array.isArray(data) ? data[0] : data;

                    console.log('Processed Presupuesto Object:', presupuesto); // DEBUG: Ver el objeto presupuesto final

                    if (presupuesto && presupuesto.n_presupuesto) { // Asegúrate de que el objeto no sea nulo y tenga una propiedad clave
                        // Rellenar campos principales del pedido
                        inputIdCliente.value = presupuesto.id_cliente || '';
                        inputIVA.value = presupuesto.iva || '';

                        // Buscar la razón social del cliente seleccionado por id_cliente si no viene directamente
                        const clienteAsociado = clientes.find(c => c.id_cliente == presupuesto.id_cliente);
                        inputRazonSocial.value = clienteAsociado ? clienteAsociado.razon_social : (presupuesto.razon_social || '');
                        console.log('Datos principales del presupuesto rellenados.');

                        limpiarCamposArticulos(); // Limpia los artículos actuales antes de añadir los del presupuesto

                        if (presupuesto.articulos && Array.isArray(presupuesto.articulos) && presupuesto.articulos.length > 0) {
                            console.log('Artículos a cargar:', presupuesto.articulos); // DEBUG: Ver los artículos del presupuesto

                            presupuesto.articulos.forEach((articulo, index) => {
                                console.log(`Intentando cargar artículo ${index + 1}:`, articulo); // DEBUG: Ver cada artículo individualmente

                                // Normalizar el tipo de artículo si viene como número (0 o 1)
                                let tipoString = '';
                                if (articulo.tipo === 0 || articulo.tipo === '0') {
                                    tipoString = 'producto';
                                } else if (articulo.tipo === 1 || articulo.tipo === '1') {
                                    tipoString = 'servicio';
                                } else {
                                    console.warn(`Tipo de artículo desconocido para el artículo ${articulo.codigo_articulo}: ${articulo.tipo}`);
                                    return; // Saltar este artículo si el tipo no es válido
                                }


                                // Para la primera fila, solo actualiza; para las siguientes, añade una nueva fila
                                if (index === 0) {
                                    const tipoSelect = contenedor.querySelector('.tipo-select');
                                    const articuloSelect = contenedor.querySelector('.articulo-select');
                                    const cantidadInput = contenedor.querySelector('input[name="cantidad[]"]');

                                    tipoSelect.value = tipoString;
                                    rellenarArticulos(tipoSelect.value, articuloSelect);
                                    // Asegurarse de que la opción exista antes de intentar seleccionarla
                                    if (articuloSelect.querySelector(`option[value="${articulo.codigo_articulo}"]`)) {
                                        articuloSelect.value = articulo.codigo_articulo;
                                    } else {
                                        console.warn(`Opción de artículo no encontrada para el código: ${articulo.codigo_articulo}. Tipo: ${tipoString}`);
                                        // Opcional: añadir la opción si no existe, o dejarlo en blanco.
                                    }
                                    cantidadInput.value = articulo.cantidad;
                                    console.log('  -> Primer artículo rellenado:', {
                                        tipo: tipoSelect.value,
                                        articulo: articuloSelect.value,
                                        cantidad: cantidadInput.value
                                    });
                                } else {
                                    // Simular clic en el botón '+' para añadir una nueva fila
                                    const addButton = contenedor.querySelector('.grupo-campos:last-child .btn-add-campo');
                                    if (addButton && addButton.classList.contains('btn-success')) {
                                        addButton.click(); // Esto creará una nueva fila vacía

                                        // Rellenar la nueva fila creada
                                        const newGrupo = contenedor.querySelector('.grupo-campos:last-child');
                                        const tipoSelect = newGrupo.querySelector('.tipo-select');
                                        const articuloSelect = newGrupo.querySelector('.articulo-select');
                                        const cantidadInput = newGrupo.querySelector('input[name="cantidad[]"]');

                                        tipoSelect.value = tipoString;
                                        rellenarArticulos(tipoSelect.value, articuloSelect);
                                        // Asegurarse de que la opción exista antes de intentar seleccionarla
                                        if (articuloSelect.querySelector(`option[value="${articulo.codigo_articulo}"]`)) {
                                            articuloSelect.value = articulo.codigo_articulo;
                                        } else {
                                            console.warn(`Opción de artículo no encontrada para el código: ${articulo.codigo_articulo}. Tipo: ${tipoString} en nueva fila.`);
                                        }
                                        cantidadInput.value = articulo.cantidad;
                                        console.log('  -> Nuevo artículo añadido y rellenado:', {
                                            tipo: tipoSelect.value,
                                            articulo: articuloSelect.value,
                                            cantidad: cantidadInput.value
                                        });
                                    } else {
                                        console.warn('No se encontró el botón de añadir para el artículo adicional o no era el correcto (debería ser "+").');
                                    }
                                }
                            });
                        } else {
                            console.log('El presupuesto NO tiene artículos o el array "articulos" está vacío/inválido.');
                            // No es un error, simplemente no hay artículos para cargar. Los campos ya están limpios.
                        }
                    } else {
                        console.warn('No se encontraron datos VÁLIDOS para el presupuesto seleccionado en el objeto final (presupuesto es nulo o no tiene n_presupuesto).');
                        inputIdCliente.value = '';
                        inputRazonSocial.value = '';
                        inputIVA.value = '';
                        limpiarCamposArticulos();
                    }
                } catch (error) {
                    console.error('ERROR FATAL: Error al obtener o procesar los datos del presupuesto:', error);
                    alert('Hubo un error al cargar los datos del presupuesto. Revisa la consola (F12) para más detalles.');
                    inputIdCliente.value = '';
                    inputRazonSocial.value = '';
                    inputIVA.value = '';
                    limpiarCamposArticulos();
                }
            });

            // Delegación de eventos para los select de tipo (para artículos)
            contenedor.addEventListener('change', function(e) {
                if (e.target.classList.contains('tipo-select')) {
                    const grupo = e.target.closest('.grupo-campos');
                    const selectArticulo = grupo.querySelector('.articulo-select');
                    rellenarArticulos(e.target.value, selectArticulo);
                    console.log('Cambio de tipo detectado. Rellenando select de artículo para la fila:', grupo);
                }
            });

            // Delegación de eventos para añadir y quitar campos de artículos
            contenedor.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-add-campo') && e.target.classList.contains('btn-success')) {
                    e.preventDefault();
                    console.log('Botón "+" clicado. Añadiendo nueva fila de artículos.');
                    const grupo = e.target.closest('.grupo-campos');
                    const nuevoGrupo = grupo.cloneNode(true);

                    // Limpiar valores del nuevo grupo
                    nuevoGrupo.querySelectorAll('select, input').forEach(el => el.value = '');
                    const nuevoTipoSelect = nuevoGrupo.querySelector('.tipo-select');
                    const nuevoArticuloSelect = nuevoGrupo.querySelector('.articulo-select');
                    nuevoArticuloSelect.innerHTML = '<option value="">Seleccione primero un tipo</option>'; // Restablecer opciones del select de artículo

                    // Cambiar el botón "+" por "-"
                    const btn = nuevoGrupo.querySelector('.btn-add-campo');
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-danger');
                    btn.textContent = '-';
                    contenedor.appendChild(nuevoGrupo);
                } else if (e.target.classList.contains('btn-add-campo') && e.target.classList.contains('btn-danger')) {
                    e.preventDefault();
                    const grupo = e.target.closest('.grupo-campos');
                    if (contenedor.querySelectorAll('.grupo-campos').length > 1) {
                        console.log('Botón "-" clicado. Eliminando fila de artículos.');
                        grupo.remove();
                    } else {
                        alert('Debe haber al menos un artículo en el pedido.');
                        console.log('Intento de eliminar la última fila de artículos. Acción denegada.');
                    }
                }
            });
        });
    </script>
</body>

</html>