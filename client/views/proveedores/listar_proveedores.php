<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Listado de Proveedores</title>
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
      white-space: nowrap;
    }

    @media (max-width: 991.98px) {
      .hide-on-mobile {
        display: none;
      }
    }

    @media print {

      .navbar-color,
      .filter-section,
      .btn,
      .dropdown,
      .hide-on-mobile,
      .action-buttons {
        display: none !important;
      }

      .hide-on-mobile {
        display: table-cell !important;
      }

      table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 12pt !important;
      }

      th,
      td {
        border: 1px solid #333 !important;
        padding: 6px !important;
        color: #000 !important;
      }

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
  $apiGeneral = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=proveedores";
  $message_from_api = "";

  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete") {
    $proveedor_id_to_delete = $_POST["id_proveedor"] ?? null;
    if ($proveedor_id_to_delete) {
      $url_delete = $apiGeneral . "&id=" . $proveedor_id_to_delete;
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
      echo '<div class="alert alert-danger text-center m-3">⚠️ ID de proveedor no proporcionado para eliminar.</div>';
    }
  }

  $queryParams = [];
  $campos_filtrables = ['cif_nif', 'poblacion', 'nombre_proveedor'];
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

  $proveedores = [];
  if ($http_code === 200) {
    $api_response_data = json_decode($response, true);
    if (isset($api_response_data['message'])) {
      $message_from_api = htmlspecialchars($api_response_data['message']);
    } elseif (is_array($api_response_data)) {
      $proveedores = $api_response_data;
    } else {
      echo '<div class="alert alert-warning text-center m-3">Respuesta inesperada de la API, pero el código HTTP es 200.</div>';
    }
  } else {
    echo '<div class="alert alert-danger text-center m-3">Error al cargar proveedores desde la API (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
  }
  ?>

  <div class="p-3 navbar-color">
    <h3 class="text-white">Listado de Proveedores</h3>
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
        <div class="col-md-4">
          <label for="nombre_proveedor" class="form-label">Nombre / Razón Social</label>
          <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" value="<?php echo htmlspecialchars($_GET['nombre_proveedor'] ?? ''); ?>">
        </div>
        <div class="col-md-2 d-flex justify-content-end">
          <button type="submit" class="btn navbar-color text-white me-2">Buscar</button>
          <a href="listar_proveedores.php" class="btn btn-secondary" id="limpiarBtn">Limpiar</a>
        </div>
      </form>
    </div>

    <div class="d-flex justify-content-end mb-3">
      <button onclick="exportTableToExcel()" class="btn btn-success me-2">📥 Exportar a Excel</button>
      <button onclick="printTable()" class="btn btn-primary me-2">🖨️ Imprimir</button>
      <a href="nuevo_proveedor.php" class="btn navbar-color text-white">Añadir Nuevo Proveedor</a>
    </div>

    <div class="table-responsive">
      <?php if (!empty($proveedores)) : ?>
        <!-- Tabla visible para la web, con dropdown "Más" y acciones -->
        <table class="table table-striped table-hover" id="tablaProveedores">
          <thead class="table-dark">
            <tr>
              <th>Razón Social</th>
              <th>CIF/NIF</th>
              <th class="hide-on-mobile">Población</th>
              <th class="hide-on-mobile">Teléfono 1</th>
              <th class="hide-on-mobile">Email</th>
              <th>Acciones</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($proveedores as $proveedor) : ?>
              <tr>
                <td><?php echo htmlspecialchars($proveedor['razon_social'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['cif_nif'] ?? 'N/A'); ?></td>
                <td class="hide-on-mobile"><?php echo htmlspecialchars($proveedor['poblacion'] ?? 'N/A'); ?></td>
                <td class="hide-on-mobile"><?php echo htmlspecialchars($proveedor['telefono1'] ?? 'N/A'); ?></td>
                <td class="hide-on-mobile"><?php echo htmlspecialchars($proveedor['email'] ?? 'N/A'); ?></td>
                <td class="action-buttons">
                  <div class="d-flex flex-nowrap">
                    <form action="editar_proveedor.php" method="GET" class="me-2">
                      <input type="hidden" name="id_proveedor" value="<?php echo htmlspecialchars($proveedor['id_proveedor'] ?? ''); ?>">
                      <button type="submit" class="btn btn-warning btn-sm">Editar</button>
                    </form>
                    <form action="" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este proveedor?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id_proveedor" value="<?php echo htmlspecialchars($proveedor['id_proveedor'] ?? ''); ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                    </form>
                  </div>
                </td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-info btn-sm dropdown-toggle" type="button" id="dropdownMenuButton_<?php echo htmlspecialchars($proveedor['id_proveedor'] ?? ''); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                      Más
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton_<?php echo htmlspecialchars($proveedor['id_proveedor'] ?? ''); ?>">
                      <li>
                        <h6 class="dropdown-header">Detalles Adicionales</h6>
                      </li>
                      <li><a class="dropdown-item" href="#"><strong>Nombre:</strong> <?php echo htmlspecialchars($proveedor['nombre'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Apellidos:</strong> <?php echo htmlspecialchars($proveedor['apellidos'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Dirección:</strong> <?php echo htmlspecialchars($proveedor['direccion'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>C.P.:</strong> <?php echo htmlspecialchars($proveedor['cp'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Provincia:</strong> <?php echo htmlspecialchars($proveedor['provincia'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Persona Contacto 1:</strong> <?php echo htmlspecialchars($proveedor['persona_contacto_1'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Teléfono 2:</strong> <?php echo htmlspecialchars($proveedor['telefono2'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Persona Contacto 2:</strong> <?php echo htmlspecialchars($proveedor['persona_contacto_2'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Teléfono 3:</strong> <?php echo htmlspecialchars($proveedor['telefono3'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Persona Contacto 3:</strong> <?php echo htmlspecialchars($proveedor['persona_contacto_3'] ?? 'N/A'); ?></a></li>
                      <li><a class="dropdown-item" href="#"><strong>Observaciones:</strong> <?php echo htmlspecialchars($proveedor['observaciones'] ?? 'N/A'); ?></a></li>
                    </ul>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Tabla oculta para exportar a Excel, sin botones ni dropdown, con columnas extras visibles -->
        <!-- Tabla oculta para exportar a Excel e imprimir -->
        <table class="d-none" id="tablaProveedoresExport">
          <thead>
            <tr>
              <th>Razón Social</th>
              <th>Nombre</th>
              <th>Apellidos</th>
              <th>CIF/NIF</th>
              <th>Dirección</th>
              <th>Población</th>
              <th>C.P.</th>
              <th>Provincia</th>
              <th>Teléfono 1</th>
              <th>Teléfono 2</th>
              <th>Teléfono 3</th>
              <th>Email</th>
              <th>Persona Contacto 1</th>
              <th>Persona Contacto 2</th>
              <th>Persona Contacto 3</th>
              <th>Observaciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($proveedores as $proveedor) : ?>
              <tr>
                <td><?php echo htmlspecialchars($proveedor['razon_social'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['nombre'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['apellidos'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['cif_nif'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['direccion'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['poblacion'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['cp'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['provincia'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['telefono1'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['telefono2'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['telefono3'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['email'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['persona_contacto_1'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['persona_contacto_2'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['persona_contacto_3'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($proveedor['observaciones'] ?? 'N/A'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else : ?>
        <div class="alert alert-info text-center mt-4">
          <?php
          echo $message_from_api ? $message_from_api : 'No se encontraron proveedores.';
          echo (!empty($queryParams) && !$message_from_api ? ' Prueba a limpiar los filtros.' : '');
          ?>
        </div>
      <?php endif; ?>
      <div class="d-flex justify-content-end mb-3">
        <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function exportTableToExcel(tableID = 'tablaProveedoresExport', filename = '') {
      const table = document.getElementById(tableID);
      const wb = XLSX.utils.table_to_book(table, {
        sheet: "Proveedores"
      });
      XLSX.writeFile(wb, filename ? filename + ".xlsx" : "proveedores.xlsx");
    }

    function printTable() {
      // Clonamos la tabla de exportación que tiene todos los datos
      const printWindow = window.open('', '', 'width=800,height=600');
      printWindow.document.write(`
            <html>
                <head>
                    <title>Listado de Proveedores</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; text-align: center; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .no-print { display: none; }
                    </style>
                </head>
                <body>
                    <h1>Listado de Proveedores</h1>
                    ${document.getElementById('tablaProveedoresExport').outerHTML}
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 100);
                        };
                    <\/script>
                </body>
            </html>
        `);
      printWindow.document.close();
    }

    document.querySelectorAll('#filtroForm input[type="text"]').forEach(input => {
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