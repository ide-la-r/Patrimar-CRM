<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Añadir Nuevo Proveedor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
  <?php
  // URL base de tu API para la tabla de proveedores
  $apiProveedores = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=proveedores";

  // --- Lógica para Procesar el Formulario de Añadir Proveedor (POST request) ---
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar campos obligatorios
    if (!empty($_POST["razon"]) && !empty($_POST["cif"])) {
      // Recopilar los datos del formulario en un array asociativo
      $data = [
        "nombre"             => $_POST["nombre"] ?? null,
        "apellidos"          => $_POST["apellidos"] ?? null,
        "razon_social"       => $_POST["razon"], // Campo obligatorio
        "cif_nif"            => $_POST["cif"],    // Campo obligatorio
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

      // Inicializar cURL para enviar los datos a la API
      $ch = curl_init($apiProveedores);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Devuelve la respuesta como string
      curl_setopt($ch, CURLOPT_POST, true);           // Método POST para insertar
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]); // Especifica que el contenido es JSON
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Codifica los datos a JSON

      $response = curl_exec($ch);      // Ejecuta la solicitud cURL
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtiene el código de estado HTTP
      $curl_error = curl_error($ch);   // Captura errores de cURL
      curl_close($ch);                 // Cierra la sesión cURL

      $responseData = json_decode($response, true); // Decodifica la respuesta JSON de la API

      // Muestra un mensaje de éxito o error basado en la respuesta de la API
      echo '<div class="alert text-center m-3 ' . ($http_code === 201 ? 'alert-success' : 'alert-danger') . '">';
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

      // Si la inserción fue exitosa, redirigir al listado de proveedores
      if ($http_code === 201) {
        header("Location: listar_proveedores.php?status=success&message=" . urlencode("Proveedor añadido correctamente."));
        exit();
      }
    } else {
      // Mensaje de error si faltan campos obligatorios
      echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Razón social</strong> y <strong>CIF/NIF</strong> son obligatorios.</div>';
    }
  }
  ?>

  <div class="p-3 navbar-color">
    <h3 class="text-white">Añadir Nuevo Proveedor</h3>
  </div>

  <div class="container mt-4">
    <div class="card-body rounded-4 p-4">
      <form action="" method="POST">
        <div class="row">
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input name="nombre" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Apellidos</label>
              <input name="apellidos" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Razón social*</label>
              <input name="razon" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['razon'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">CIF/NIF*</label>
              <input name="cif" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['cif'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Dirección</label>
              <input name="direccion" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Código postal</label>
              <input name="cp" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['cp'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Provincia</label>
              <input name="provincia" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['provincia'] ?? ''); ?>">
            </div>
          </div>

          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label">Población</label>
              <input name="poblacion" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['poblacion'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Personal Contacto 1</label>
              <input name="persona1" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['persona1'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Teléfono 1</label>
              <input name="tlf1" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['tlf1'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Personal Contacto 2</label>
              <input name="persona2" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['persona2'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Teléfono 2</label>
              <input name="tlf2" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['tlf2'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Personal Contacto 3</label>
              <input name="persona3" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['persona3'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Teléfono 3</label>
              <input name="tlf3" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['tlf3'] ?? ''); ?>">
            </div>
          </div>

          <div class="col-md-4 d-flex flex-column">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-3 flex-grow-1">
              <label class="form-label">Observaciones</label>
              <textarea name="observaciones" class="form-control" rows="10"><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-success me-2">Añadir Proveedor</button>
              <a href="../index.php" class="btn btn-danger">Cancelar</a>
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