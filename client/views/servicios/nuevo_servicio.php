<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Servicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheets/style.css">
</head>
<body>
<?php
$apiServicios = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=servicios";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!empty($_POST["codigo_servicio"]) && !empty($_POST["servicio"]) && !empty($_POST["importe"])) {
    $data = [
      "codigo_servicio" => $_POST["codigo_servicio"],
      "servicio"        => $_POST["servicio"],
      "importe"         => $_POST["importe"]
    ];

    $ch = curl_init($apiServicios);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

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
    echo '<div class="alert alert-warning text-center m-3">⚠️ Todos los campos son obligatorios.</div>';
  }
}
?>

<div class="p-3 navbar-color">
  <h3 class="text-white">Nuevo Servicio</h3>
</div>

<div class="container mt-5">
  <form method="POST">
    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label">Código del Servicio*</label>
        <input name="codigo_servicio" type="text" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nombre del Servicio*</label>
        <input name="servicio" type="text" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Importe (€)*</label>
        <input name="importe" type="number" class="form-control" step="0.01" min="0" required>
      </div>
    </div>

    <div class="d-flex justify-content-end">
      <button type="submit" class="btn navbar-color text-white me-2">Añadir</button>
      <a href="../index.php" class="btn btn-danger">Cancelar</a>
    </div>
  </form>
  <div class="d-flex justify-content-end mb-3">
      <a href="../index.php" class="btn navbar-color text-white">Volver al inicio</a>
  </div>
</div>
</body>
</html>
