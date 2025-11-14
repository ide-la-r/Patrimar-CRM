<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Presupuesto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheets/style.css">
</head>
<body>
  <?php
  $apiClientes = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";
  $apiPresupuestos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=presupuestos";

  // Obtener lista de clientes para el <select>
  $clientes = [];
  $json = file_get_contents($apiClientes);
  if ($json !== false) {
    $clientes = json_decode($json, true);
  }

  // Obtener productos y servicios de la base de datos
  $apiProductos = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=productos";
  $apiServicios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=servicios";

  $productos = [];
  $servicios = [];
  $jsonProd = file_get_contents($apiProductos);
  if ($jsonProd !== false) {
    $productos = json_decode($jsonProd, true);
  }
  $jsonServ = file_get_contents($apiServicios);
  if ($jsonServ !== false) {
    $servicios = json_decode($jsonServ, true);
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["n_presupuesto"]) && !empty($_POST["id_cliente"])) {
      $data = [
        "n_presupuesto" => $_POST["n_presupuesto"],
        "fecha"         => $_POST["fecha"] ?? null,
        "id_cliente"    => $_POST["id_cliente"],
        "razon_social"  => $_POST["razon_social"] ?? null,
        "iva"           => $_POST["iva"] ?? null,
        "completado"    => isset($_POST["completado"]) ? 1 : 0
      ];

      $data = array_filter($data, fn($v) => $v !== null && $v !== "");

      $ch = curl_init($apiPresupuestos);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curl_error = curl_error($ch);
      curl_close($ch);

      // Guardar artículos en articulos_presupuesto si el presupuesto se creó correctamente
      if (($http_code === 200 || $http_code === 201) && isset($_POST['tipo'], $_POST['articulo'], $_POST['cantidad'])) {
        $apiArticulosPresupuesto = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=articulos_presupuesto";
        $tipos = $_POST['tipo'];
        $articulos = $_POST['articulo'];
        $cantidades = $_POST['cantidad'];
        $n_presupuesto = $_POST['n_presupuesto'];

        for ($i = 0; $i < count($tipos); $i++) {
          $tipo_valor = $tipos[$i] === 'producto' ? 0 : ($tipos[$i] === 'servicio' ? 1 : null);
          $codigo_articulo = $articulos[$i] ?? null;
          $cantidad = $cantidades[$i] ?? null;
          if ($tipo_valor !== null && $codigo_articulo && $cantidad) {
            $articuloData = [
              "n_presupuesto" => $n_presupuesto,
              "codigo_articulo" => $codigo_articulo,
              "cantidad" => $cantidad,
              "tipo" => $tipo_valor
            ];
            $ch2 = curl_init($apiArticulosPresupuesto);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($articuloData));
            curl_exec($ch2);
            curl_close($ch2);
          }
        }
      }

      echo '<div class="alert text-center m-3 ' . ($http_code === 200 || $http_code === 201 ? 'alert-success' : 'alert-danger') . '">';
      $responseData = json_decode($response, true);
      if (is_array($responseData)) {
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
      echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Nº presupuesto</strong> y <strong>Cliente</strong> son obligatorios.</div>';
    }
  }
  ?>

  <div class="p-3 navbar-color">
    <h3 class="text-white">Nuevo Presupuesto</h3>
  </div>

  <div class="container mt-5">
    <form method="POST">
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">Nº Presupuesto*</label>
          <input name="n_presupuesto" type="text" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Fecha</label>
          <input name="fecha" type="date" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Cliente*</label>
          <select name="id_cliente" class="form-select">
            <option value="">Seleccionar cliente</option>
            <?php foreach ($clientes as $cliente): ?>
              <option value="<?= htmlspecialchars($cliente['id_cliente']) ?>">
                <?= htmlspecialchars($cliente['cif_nif'] ?? "Sin NIF/CIF") ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Razón Social</label>
          <input name="razon_social" type="text" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">IVA</label>
          <input name="iva" type="number" step="0.001" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-center">
          <div class="form-check mt-4">
            <input name="completado" class="form-check-input" type="checkbox" value="1" id="completado">
            <label class="form-check-label" for="completado">Completado</label>
          </div>
        </div>
      </div>

      <!-- Campos dinámicos -->
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
      <!-- Fin campos dinámicos -->

      <div class="d-flex justify-content-end">
        <button type="submit" class="btn navbar-color text-white me-2">Añadir</button>
        <a href="../index.php" class="btn btn-danger">Cancelar</a>
      </div>
      <div class="d-flex justify-content-end mb-3">
          <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
      </div>
    </form>
  </div>
  <script>
    // Datos de productos y servicios desde PHP
    const productos = <?php echo json_encode($productos); ?>;
    const servicios = <?php echo json_encode($servicios); ?>;

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

    document.addEventListener('DOMContentLoaded', function() {
      const contenedor = document.getElementById('campos-dinamicos');

      // Delegación para cambio de tipo
      contenedor.addEventListener('change', function(e) {
        if (e.target.classList.contains('tipo-select')) {
          const grupo = e.target.closest('.grupo-campos');
          const selectArticulo = grupo.querySelector('.articulo-select');
          rellenarArticulos(e.target.value, selectArticulo);
        }
      });

      // Delegación para añadir y quitar campos
      contenedor.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-add-campo') && e.target.classList.contains('btn-success')) {
          e.preventDefault();
          const grupo = e.target.closest('.grupo-campos');
          const nuevoGrupo = grupo.cloneNode(true);
          // Limpiar valores
          nuevoGrupo.querySelectorAll('select, input').forEach(el => el.value = '');
          // Reset opciones del segundo select
          const selectArticulo = nuevoGrupo.querySelector('.articulo-select');
          selectArticulo.innerHTML = '<option value="">Seleccione primero un tipo</option>';
          // Cambiar el botón "+" por "-"
          const btn = nuevoGrupo.querySelector('.btn-add-campo');
          btn.classList.remove('btn-success');
          btn.classList.add('btn-danger');
          btn.textContent = '-';
          contenedor.appendChild(nuevoGrupo);
        } else if (e.target.classList.contains('btn-add-campo') && e.target.classList.contains('btn-danger')) {
          e.preventDefault();
          const grupo = e.target.closest('.grupo-campos');
          grupo.remove();
        }
      });
    });
  </script>
</body>
</html>
