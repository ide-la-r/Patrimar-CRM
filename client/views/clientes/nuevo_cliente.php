<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Nuevo Cliente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheets/style.css">
</head>

<body>
  <?php
  $apiGeneral = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes";

  // Función para comprobar si un campo ya existe en la base de datos vía API
  function campoDuplicado($campo, $valor)
  {
    $url = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=clientes&" . urlencode($campo) . "=" . urlencode($valor);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (is_array($data) && count($data) > 0) {
      foreach ($data as $cliente) {
        if (isset($cliente[$campo]) && $cliente[$campo] === $valor) {
          return true;
        }
      }
    }
    return false;
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["razon"]) && !empty($_POST["cif"])) {

      // Validar duplicados antes de enviar a la API
      if (campoDuplicado('cif_nif', $_POST['cif'])) {
        echo '<div class="alert alert-danger text-center m-3">❌ El CIF/NIF <strong>' . htmlspecialchars($_POST['cif']) . '</strong> ya existe.</div>';
      } else if (!empty($_POST['email']) && campoDuplicado('email', $_POST['email'])) {
        echo '<div class="alert alert-danger text-center m-3">❌ El email <strong>' . htmlspecialchars($_POST['email']) . '</strong> ya existe.</div>';
      } else {
        $data = [
          "nombre" => $_POST["nombre"] ?? null,
          "apellidos" => $_POST["apellidos"] ?? null,
          "razon_social" => $_POST["razon"],
          "cif_nif" => $_POST["cif"],
          "direccion" => $_POST["direccion"] ?? null,
          "cp" => $_POST["cp"] ?? null,
          "poblacion" => $_POST["poblacion"] ?? null,
          "provincia" => $_POST["provincia"] ?? null,
          "persona_contacto_1" => $_POST["persona1"] ?? null,
          "telefono1" => $_POST["tlf1"] ?? null,
          "persona_contacto_2" => $_POST["persona2"] ?? null,
          "telefono2" => $_POST["tlf2"] ?? null,
          "persona_contacto_3" => $_POST["persona3"] ?? null,
          "telefono3" => $_POST["tlf3"] ?? null,
          "email" => $_POST["email"] ?? null,
          "observaciones" => $_POST["observaciones"] ?? null,
          "cuenta_bancaria" => $_POST["banco"] ?? null,
          "metodo_pago" => $_POST["metodo"] ?? null,
          "puede_pedir" => $_POST["puede_pedir"] ?? null
        ];

        $data = array_filter($data, fn($v) => $v !== null && $v !== "");

        $ch = curl_init($apiGeneral);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        echo '<div class="alert text-center m-3 ' . ($http_code === 200 || $http_code === 201 ? 'alert-success' : 'alert-danger') . '">';
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
      }
    } else {
      echo '<div class="alert alert-warning text-center m-3">⚠️ Los campos <strong>Razón social</strong> y <strong>CIF/NIF</strong> son obligatorios.</div>';
    }
  }
  ?>


  <!-- Cabecera -->
  <div class="p-3 navbar-color">
    <h3 class="text-white">Nuevo Cliente</h3>
  </div>

  <!-- Formulario -->
  <div class="container mt-4">
    <div class="card-body rounded-4 p-4">
      <form action="" method="POST">
        <div class="row">
          <!-- Columna 1 -->
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
              <input name="razon" type="text" class="form-control" required value="<?php echo htmlspecialchars($_POST['razon'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">CIF/NIF*</label>
              <input name="cif" type="text" class="form-control" required value="<?php echo htmlspecialchars($_POST['cif'] ?? ''); ?>">
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

          <!-- Columna 2 -->
          <div class="col-md-4">
            <div class="mb-3">
              <label class="form-label">Poblacion</label>
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

          <!-- Columna 3 -->
          <div class="col-md-4 d-flex flex-column">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Cuenta bancaria</label>
              <input name="banco" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['banco'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Método de pago</label>
              <input name="metodo" type="text" class="form-control" value="<?php echo htmlspecialchars($_POST['metodo'] ?? ''); ?>">
            </div>
            <div class="mb-3 flex-grow-1">
              <label class="form-label">Observaciones</label>
              <textarea name="observaciones" class="form-control" rows="6"><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
            </div>
            <div class="form-check mb-3">
              <input name="puede_pedir" class="form-check-input" type="checkbox" id="puede_pedir" <?= !empty($cliente['puede_pedir']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="puede_pedir">Puede pedir</label>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-success me-2">Añadir</button>
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