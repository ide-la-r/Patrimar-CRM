<?php
session_start(); // ¡DEBE SER LA PRIMERA LÍNEA SIN NADA ANTES!

// Activa la visualización de todos los errores (¡IMPORTANTE! Desactivar en producción)
error_reporting(E_ALL);
ini_set("display_errors", 1);

$login_error_message = ""; // Inicializa el mensaje de error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario_ingresado = $_POST["nombre"] ?? '';
    $contrasena_ingresada = $_POST["contrasena"] ?? '';

    if (empty($nombre_usuario_ingresado) || empty($contrasena_ingresada)) {
        $login_error_message = "Por favor, introduce usuario y contraseña.";
    } else {
        // La URL de la API para el login (necesita la acción 'login')
        $apiLoginUrl = "http://localhost/PATRIMAR/webpatrimar/server/api.php?tabla=usuarios&accion=login";

        // Prepara los datos para enviar como JSON en la petición POST
        $data_to_send = json_encode([
            "nombre_usuario" => $nombre_usuario_ingresado,
            "contrasena" => $contrasena_ingresada
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiLoginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Recibir la respuesta como string
        curl_setopt($ch, CURLOPT_POST, true); // ¡MUY IMPORTANTE! Configurar como petición POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_send); // Enviar los datos JSON en el cuerpo
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Indicar que el contenido es JSON

        // --- INICIO DEPURACIÓN cURL ---
        // Estas opciones ayudarán a ver qué está haciendo cURL
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Muestra la comunicación detallada
        $verbose = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose); // Captura la salida detallada
        // --- FIN DEPURACIÓN cURL ---

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        // --- LECTURA Y CIERRE DE DEPURACIÓN cURL ---
        rewind($verbose);
        $curl_debug_log = stream_get_contents($verbose);
        fclose($verbose);
        // --- FIN LECTURA Y CIERRE DE DEPURACIÓN cURL ---

        curl_close($ch);

        // --- MANEJO DE ERRORES MEJORADO ---
        if ($curl_error) {
            $login_error_message = "Error de conexión cURL: " . htmlspecialchars($curl_error);
            $login_error_message .= "<br>Detalles de cURL: <pre>" . htmlspecialchars($curl_debug_log) . "</pre>";
        } elseif ($response === false) {
            $login_error_message = "La API no devolvió ninguna respuesta. Posible problema de red o API no accesible.";
            $login_error_message .= "<br>Detalles de cURL: <pre>" . htmlspecialchars($curl_debug_log) . "</pre>";
        } elseif (json_last_error() !== JSON_ERROR_NONE) {
            $login_error_message = "La API devolvió una respuesta no válida (no es JSON).";
            $login_error_message .= "<br>Respuesta recibida: <pre>" . htmlspecialchars($response) . "</pre>";
            $login_error_message .= "<br>Error de JSON: " . json_last_error_msg();
        } else {
            $responseData = json_decode($response, true);

            if ($http_code === 200 && isset($responseData['success']) && $responseData['success'] === true) {
                // Login exitoso
                $_SESSION['usuario'] = [
                    'id_usuario' => $responseData['usuario']['id_usuario'],
                    'nombre' => $responseData['usuario']['nombre'],
                    'rol' => $responseData['usuario']['rol']
                ];
                header("Location: index.php");
                exit();
            } else {
                // Login fallido o error reportado por la API
                $api_message = $responseData['message'] ?? 'Respuesta inesperada de la API.';
                $login_error_message = "Error en el login: " . htmlspecialchars($api_message) . " (Código HTTP: " . $http_code . ")";
                // Para depuración avanzada, puedes mostrar la respuesta completa de la API
                $login_error_message .= "<br>Respuesta completa de la API: <pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="../stylesheets/login.css">
    <link rel="stylesheet" href="../stylesheets/style.css">
</head>

<body>
    <div class="container card text-center card-sesion mt-5" style="width: 40rem;">
        <h1 class="title mt-3">Iniciar sesión</h1>
        <form method="POST" action="" class="form-floating p-3">
            <div class="row justify-content-center">
                <div class="mb-3 col-8">
                    <input id="nombre" name="nombre" class="form-control" type="text" placeholder="Usuario*" required value="<?php echo htmlspecialchars($_POST['nombre_usuario_ingresado'] ?? ''); ?>">
                </div>
                <div class="mb-3 col-8">
                    <input id="contrasena" name="contrasena" class="form-control" type="password" placeholder="Contraseña*" required>
                </div>
            </div>
            <input type="submit" class="btn btn-custom col-4" value="Entrar">
        </form>

        <?php
        // Muestra el mensaje de error si existe
        if ($login_error_message) {
            echo '<div class="alert alert-danger text-center mt-3">' . $login_error_message . '</div>';
        }
        ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq"
        crossorigin="anonymous"></script>
</body>

</html>