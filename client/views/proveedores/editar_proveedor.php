<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Editar Proveedor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
  <?php
  $apiProveedores = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=proveedores";
  // Obtener el ID del proveedor de la URL para edición
  $proveedor_id = $_GET["id_proveedor"] ?? null;

  $proveedor_data = []; // Almacenará los datos del proveedor a editar

  // --- Lógica para Cargar Datos del Proveedor al Cargar la Página (GET request) ---
  if ($proveedor_id && $_SERVER["REQUEST_METHOD"] == "GET") {
    // Pedir un proveedor específico por ID (usando id_proveedor)
    $ch = curl_init($apiProveedores . "&id_proveedor=" . urlencode($proveedor_id));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200) {
      $api_response_data = json_decode($response, true);

      // La API devuelve un ARRAY de resultados, incluso si es solo uno.
      if (is_array($api_response_data) && !empty($api_response_data)) {
          $proveedor_data = $api_response_data[0]; // Tomamos el primer (y único) proveedor del array
          // Verificamos si los datos cargados contienen el ID correcto
          if (!isset($proveedor_data['id_proveedor']) || $proveedor_data['id_proveedor'] != $proveedor_id) {
              echo '<div class="alert alert-danger text-center m-3">Error: ID del proveedor no coincide con los datos cargados.</div>';
              $proveedor_data = []; // Limpiar para evitar errores en el formulario
          }
      } else {
          // Si el array está vacío o no es un array válido, el proveedor no fue encontrado
          echo '<div class="alert alert-danger text-center m-3">Error: No se encontró el proveedor con el ID especificado.</div>';
          $proveedor_data = []; // Limpiar para evitar errores en el formulario
      }
    } else {
      // Manejo de errores si la API devuelve un estado HTTP diferente de 200
      echo '<div class="alert alert-danger text-center m-3">Error al cargar el proveedor para editar (Código HTTP: ' . htmlspecialchars($http_code) . '): ' . htmlspecialchars($response) . ($curl_error ? "<br>cURL Error: " . htmlspecialchars($curl_error) : "") . '</div>';
    }
  }

  // --- Lógica para Procesar la Edición (POST request) ---
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ID del proveedor enviado desde el formulario
    $proveedor_id_post = $_POST["id_proveedor_hidden"] ?? null;
    if (!$proveedor_id_post) {
        echo '<div class="alert alert-danger text-center m-3">⚠️ ID de proveedor no proporcionado para actualizar.</div>';
    } elseif (!empty($_POST["razon"]) && !empty($_POST["cif"])) {

      $data = [
        "id_proveedor"       => $proveedor_id_post, // El ID es crucial para la actualización
        "nombre"             => $_POST["nombre"] ?? null,
        "apellidos"          => $_POST["apellidos"] ?? null,
        "razon_social"       => $_POST["razon"],
        "cif_nif"            => $_POST["cif"],
        "direccion"          => $_POST["direccion"] ?? null,
        "cp"                 => $_POST["cp"] ?? null,
        "poblacion"          => $_POST["poblacion"] ?? null,
        "provincia"          => $_POST["provincia"] ?? null,
        "persona_contacto_1" => $_POST["persona1"] ?? null,
        "telefono1"          => $_POST["tlf1"] ?? null,
        "persona_contacto_2" => $_POST["persona2"] ?? null,
        "telefono2"          => $_POST["tlf2"] ?? null,
        "persona_contacto_3" => $_POST["persona3"] ?? null,
        "telefono3"          => $_POST["tlf3"] ?? null,
        "email"              => $_POST["email"] ?? null,
        "observaciones"      => $_POST["observaciones"] ?? null
      ];


      $ch = curl_init($apiProveedores."&id=" . $proveedor_id_post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // Método PUT para actualización
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curl_error = curl_error($ch);
      curl_close($ch);

      $responseData = json_decode($response, true);

      echo '<div class="alert text-center m-3 ' . ($http_code === 200 ? 'alert-success' : 'alert-danger') . '">';
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

      // Después de la actualización exitosa, redirigir
      if ($http_code === 200) {
          header("Location: listar_proveedores.php?status=success&message=" . urlencode("Proveedor actualizado correctamente."));
          exit();
      } else {
          // Si hubo un error en la actualización, recargar los datos actuales del proveedor
          // para que los cambios no validados no se pierdan en el formulario.
          if ($proveedor_id_post) {
            $ch_reload = curl_init($apiProveedores . "&id_proveedor=" . urlencode($proveedor_id_post));
            curl_setopt($ch_reload, CURLOPT_RETURNTRANSFER, true);
            $response_reload = curl_exec($ch_reload);
            curl_close($ch_reload);
            $api_response_data_reload = json_decode($response_reload, true);
            if (is_array($api_response_data_reload) && !empty($api_response_data_reload)) {
                $proveedor_data = $api_response_data_reload[0];
            }
          }
      }

    } else {
      echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Razón social</strong> y <strong>CIF/NIF</strong> son obligatorios.</div>';
      // Recargar los datos actuales del proveedor para que no se pierdan los campos en el formulario tras un error de validación
      if ($proveedor_id_post) {
        $ch_reload = curl_init($apiProveedores . "&id_proveedor=" . urlencode($proveedor_id_post));
        curl_setopt($ch_reload, CURLOPT_RETURNTRANSFER, true);
        $response_reload = curl_exec($ch_reload);
        curl_close($ch_reload);
        $api_response_data_reload = json_decode($response_reload, true);
        if (is_array($api_response_data_reload) && !empty($api_response_data_reload)) {
            $proveedor_data = $api_response_data_reload[0];
        }
      }
    }
  }
  ?>

  <div class="p-3 navbar-color">
    <h3 class="text-white">Editar Proveedor</h3>
  </div>

  <div class="container mt-4">
    <div class="card-body rounded-4 p-4">
      <form action="" method="POST">
        <input type="hidden" name="id_proveedor_hidden" value="<?php echo htmlspecialchars($proveedor_id ?? ''); ?>">

        <div class="row">
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input name="nombre" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['nombre'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Apellidos</label>
              <input name="apellidos" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['apellidos'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Razón social*</label>
              <input name="razon" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['razon_social'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">CIF/NIF*</label>
              <input name="cif" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['cif_nif'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Dirección</label>
              <input name="direccion" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['direccion'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Código postal</label>
              <input name="cp" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['cp'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Provincia</label>
              <input name="provincia" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['provincia'] ?? ''); ?>">
            </div>
          </div>

          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label">Población</label>
              <input name="poblacion" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['poblacion'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Personal Contacto 1</label>
              <input name="persona1" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['persona_contacto_1'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Teléfono 1</label>
              <input name="tlf1" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['telefono1'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Personal Contacto 2</label>
              <input name="persona2" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['persona_contacto_2'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Teléfono 2</label>
              <input name="tlf2" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['telefono2'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Personal Contacto 3</label>
              <input name="persona3" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['persona_contacto_3'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Teléfono 3</label>
              <input name="tlf3" type="text" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['telefono3'] ?? ''); ?>">
            </div>
          </div>

          <div class="col-md-4 d-flex flex-column">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($proveedor_data['email'] ?? ''); ?>">
            </div>
            <div class="mb-3 flex-grow-1">
              <label class="form-label">Observaciones</label>
              <textarea name="observaciones" class="form-control" rows="10"><?php echo htmlspecialchars($proveedor_data['observaciones'] ?? ''); ?></textarea>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn navbar-color text-white me-2">Confirmar Cambios</button>
              <a href="listar_proveedores.php" class="btn btn-danger">Cancelar</a>
            </div>
            <div class="d-flex justify-content-end mb-3">
                <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

</body>

</html>