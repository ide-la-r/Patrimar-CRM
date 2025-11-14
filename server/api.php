<?php
// Asegúrate de que este archivo esté en la ruta correcta, por ejemplo, server/api.php
// Y _conexion.php esté accesible desde aquí.
require_once './config/conexion.php'; // RUTA CRÍTICA: Ajusta si _conexion.php está en otro lugar (ej. './config/conexion.php')

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite peticiones desde cualquier origen (ajusta en producción)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Maneja pre-flight requests de OPTIONS (necesario para CORS con algunos métodos/headers)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$tabla = $_GET['tabla'] ?? '';

switch ($tabla) {
    case 'usuarios':
        $usuario_id = $_GET['id'] ?? null;
        $accion_login = $_GET['accion'] ?? null; // Identifica la acción 'login' para el POST

        switch ($method) {
            case 'GET':
                if ($usuario_id) {
                    $stmt = $_conexion->prepare("SELECT id_usuario, nombre, fecha_creacion, rol FROM usuarios WHERE id_usuario = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($usuario) {
                        echo json_encode($usuario);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Usuario no encontrado."]);
                    }
                } else {
                    $stmt = $_conexion->query("SELECT id_usuario, nombre, fecha_creacion, rol FROM usuarios ORDER BY nombre ASC");
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($usuarios);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);

                // --- Lógica para LOGIN ---
                if ($accion_login === 'login' && isset($data['nombre_usuario'], $data['contrasena'])) {
                    $nombre_usuario_login = $data['nombre_usuario'];
                    $contrasena_ingresada_login = $data['contrasena'];

                    try {
                        $stmt = $_conexion->prepare("SELECT id_usuario, nombre, contrasena, rol FROM usuarios WHERE nombre = ?");
                        $stmt->execute([$nombre_usuario_login]);
                        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                        // IMPORTANTE: AJUSTA ESTA LÍNEA SEGÚN CÓMO ALMACENES CONTRASEÑAS.
                        // Si usas password_hash() (¡MUY RECOMENDADO!):
                        // if ($usuario && password_verify($contrasena_ingresada_login, $usuario['contrasena'])) {
                        // Si es texto plano (como en tu código original, MENOS SEGURO):
                        if ($usuario && $contrasena_ingresada_login === $usuario['contrasena']) {
                            http_response_code(200);
                            echo json_encode([
                                "message" => "Login exitoso.",
                                "success" => true,
                                "usuario" => [
                                    "id_usuario" => $usuario['id_usuario'],
                                    "nombre" => $usuario['nombre'],
                                    "rol" => $usuario['rol']
                                ]
                            ]);
                        } else {
                            http_response_code(401); // Unauthorized
                            echo json_encode(["message" => "Credenciales inválidas.", "success" => false]);
                        }
                    } catch (PDOException $e) {
                        http_response_code(500); // Internal Server Error
                        echo json_encode(["message" => "Error de servidor al intentar login: " . $e->getMessage(), "success" => false]);
                    }
                }
                // --- Lógica para AÑADIR NUEVO USUARIO (si no es una petición de login) ---
                elseif (isset($data['nombre'], $data['contrasena'], $data['rol'])) {
                    // Cifrado de contraseña: USAR password_hash() en producción
                    // $contrasena_hasheada = password_hash($data['contrasena'], PASSWORD_DEFAULT);
                    $contrasena_plana = $data['contrasena']; // Solo para fines de prueba, ¡CAMBIAR!

                    $sql = "INSERT INTO usuarios (nombre, contrasena, fecha_creacion, rol) VALUES (?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([$data['nombre'], $contrasena_plana, date("Y-m-d"), $data['rol']]);
                    http_response_code(201);
                    echo json_encode(["message" => "Usuario añadido con éxito.", "id_usuario" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Petición POST no válida para usuarios."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($usuario_id && isset($data['nombre'], $data['rol'])) {
                    $sql = "UPDATE usuarios SET nombre = ?, rol = ? WHERE id_usuario = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([$data['nombre'], $data['rol'], $usuario_id]);

                    if (isset($data['contrasena']) && !empty($data['contrasena'])) {
                        $contrasena_plana = $data['contrasena'];
                        $sql_pass = "UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?";
                        $stmt_pass = $_conexion->prepare($sql_pass);
                        $stmt_pass->execute([$contrasena_plana, $usuario_id]);
                    }

                    if ($stmt->rowCount() > 0 || (isset($stmt_pass) && $stmt_pass->rowCount() > 0)) {
                        echo json_encode(["message" => "Usuario actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Usuario no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios (nombre, rol) para actualizar el usuario o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($usuario_id) {
                    $stmt = $_conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
                    $stmt->execute([$usuario_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Usuario eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Usuario no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de usuario no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    case 'clientes':
        $cliente_id = $_GET['id'] ?? null;
        switch ($method) {
            case 'GET':
                if ($cliente_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
                    $stmt->execute([$cliente_id]);
                    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($cliente) {
                        echo json_encode($cliente);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Cliente no encontrado."]);
                    }
                } else {
                    // Filtros opcionales
                    $filtros = [];
                    $valores = [];

                    if (!empty($_GET['cif_nif'])) {
                        $filtros[] = 'cif_nif LIKE ?';
                        $valores[] = '%' . $_GET['cif_nif'] . '%';
                    }
                    if (!empty($_GET['nombre'])) {
                        $filtros[] = 'nombre LIKE ?';
                        $valores[] = '%' . $_GET['nombre'] . '%';
                    }
                    if (!empty($_GET['razon_social'])) {
                        $filtros[] = 'razon_social LIKE ?';
                        $valores[] = '%' . $_GET['razon_social'] . '%';
                    }
                    if (!empty($_GET['poblacion'])) {
                        $filtros[] = 'poblacion LIKE ?';
                        $valores[] = '%' . $_GET['poblacion'] . '%';
                    }

                    $sql = "SELECT * FROM clientes";
                    if (!empty($filtros)) {
                        $sql .= " WHERE " . implode(" AND ", $filtros);
                    }
                    $sql .= " ORDER BY nombre ASC";

                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute($valores);
                    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($clientes);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['cif_nif'], $data['razon_social'])) {
                    $sql = "INSERT INTO clientes (
                    nombre, apellidos, razon_social, cif_nif, direccion, cp, poblacion, provincia,
                    persona_contacto_1, telefono1, persona_contacto_2, telefono2, persona_contacto_3, telefono3,
                    email, observaciones, cuenta_bancaria, metodo_pago, puede_pedir
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['nombre'] ?? null,
                        $data['apellidos'] ?? null,
                        $data['razon_social'],
                        $data['cif_nif'],
                        $data['direccion'] ?? null,
                        $data['cp'] ?? null,
                        $data['poblacion'] ?? null,
                        $data['provincia'] ?? null,
                        $data['persona_contacto_1'] ?? null,
                        $data['telefono1'] ?? null,
                        $data['persona_contacto_2'] ?? null,
                        $data['telefono2'] ?? null,
                        $data['persona_contacto_3'] ?? null,
                        $data['telefono3'] ?? null,
                        $data['email'] ?? null,
                        $data['observaciones'] ?? null,
                        $data['cuenta_bancaria'] ?? null,
                        $data['metodo_pago'] ?? null,
                        isset($data['puede_pedir']) ? (bool)$data['puede_pedir'] : null
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Cliente añadido con éxito.", "id_cliente" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el cliente (cif_nif y razon_social)."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($cliente_id && isset($data['cif_nif'], $data['razon_social'])) {
                    $sql = "UPDATE clientes SET
                    nombre = ?, apellidos = ?, razon_social = ?, cif_nif = ?, direccion = ?, cp = ?, poblacion = ?, provincia = ?,
                    persona_contacto_1 = ?, telefono1 = ?, persona_contacto_2 = ?, telefono2 = ?, persona_contacto_3 = ?, telefono3 = ?,
                    email = ?, observaciones = ?, cuenta_bancaria = ?, metodo_pago = ?, puede_pedir = ?
                    WHERE id_cliente = ?";

                    $params = [
                        $data['nombre'] ?? null,
                        $data['apellidos'] ?? null,
                        $data['razon_social'],
                        $data['cif_nif'],
                        $data['direccion'] ?? null,
                        $data['cp'] ?? null,
                        $data['poblacion'] ?? null,
                        $data['provincia'] ?? null,
                        $data['persona_contacto_1'] ?? null,
                        $data['telefono1'] ?? null,
                        $data['persona_contacto_2'] ?? null,
                        $data['telefono2'] ?? null,
                        $data['persona_contacto_3'] ?? null,
                        $data['telefono3'] ?? null,
                        $data['email'] ?? null,
                        $data['observaciones'] ?? null,
                        $data['cuenta_bancaria'] ?? null,
                        $data['metodo_pago'] ?? null,
                        isset($data['puede_pedir']) ? (bool)$data['puede_pedir'] : null,
                        $cliente_id
                    ];

                    try {
                        $stmt = $_conexion->prepare($sql);
                        $stmt->execute($params);

                        if ($stmt->rowCount() > 0) {
                            echo json_encode(["message" => "Cliente actualizado con éxito."]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["message" => "Cliente no encontrado o sin cambios."]);
                        }
                    } catch (PDOException $e) {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al actualizar cliente: " . $e->getMessage()]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el cliente (cif_nif y razon_social) o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($cliente_id) {
                    $stmt = $_conexion->prepare("DELETE FROM clientes WHERE id_cliente = ?");
                    try {
                        $stmt->execute([$cliente_id]);

                        if ($stmt->rowCount() > 0) {
                            echo json_encode(["message" => "Cliente eliminado con éxito."]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["message" => "Cliente no encontrado o sin cambios."]);
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al eliminado cliente: " . $e->getMessage()]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de cliente no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;


    case 'proveedores':
        $proveedor_id = $_GET['id'] ?? null;
        switch ($method) {
            case 'GET':
                if ($proveedor_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
                    $stmt->execute([$proveedor_id]);
                    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($proveedor) {
                        echo json_encode($proveedor);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Proveedor no encontrado."]);
                    }
                } else {
                    $stmt = $_conexion->query("SELECT * FROM proveedores ORDER BY nombre ASC");
                    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($proveedores);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['cif_nif'], $data['razon_social'])) {
                    $sql = "INSERT INTO proveedores (
                        nombre, apellidos, razon_social, cif_nif, direccion, cp, poblacion, provincia,
                        persona_contacto_1, telefono1, persona_contacto_2, telefono2, persona_contacto_3, telefono3,
                        email, observaciones
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['nombre'] ?? null,
                        $data['apellidos'] ?? null,
                        $data['razon_social'],
                        $data['cif_nif'],
                        $data['direccion'] ?? null,
                        $data['cp'] ?? null,
                        $data['poblacion'] ?? null,
                        $data['provincia'] ?? null,
                        $data['persona_contacto_1'] ?? null,
                        $data['telefono1'] ?? null,
                        $data['persona_contacto_2'] ?? null,
                        $data['telefono2'] ?? null,
                        $data['persona_contacto_3'] ?? null,
                        $data['telefono3'] ?? null,
                        $data['email'] ?? null,
                        $data['observaciones'] ?? null
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Proveedor añadido con éxito.", "id_proveedor" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el proveedor."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($proveedor_id && isset($data['cif_nif'], $data['razon_social'])) {
                    $sql = "UPDATE proveedores SET
                    nombre = ?, apellidos = ?, razon_social = ?, cif_nif = ?, direccion = ?, cp = ?, poblacion = ?, provincia = ?,
                    persona_contacto_1 = ?, telefono1 = ?, persona_contacto_2 = ?, telefono2 = ?, persona_contacto_3 = ?, telefono3 = ?,
                    email = ?, observaciones = ?
                    WHERE id_proveedor = ?";

                    $params = [
                        $data['nombre'] ?? null,
                        $data['apellidos'] ?? null,
                        $data['razon_social'],
                        $data['cif_nif'],
                        $data['direccion'] ?? null,
                        $data['cp'] ?? null,
                        $data['poblacion'] ?? null,
                        $data['provincia'] ?? null,
                        $data['persona_contacto_1'] ?? null,
                        $data['telefono1'] ?? null,
                        $data['persona_contacto_2'] ?? null,
                        $data['telefono2'] ?? null,
                        $data['persona_contacto_3'] ?? null,
                        $data['telefono3'] ?? null,
                        $data['email'] ?? null,
                        $data['observaciones'] ?? null,
                        $proveedor_id
                    ];

                    try {
                        $stmt = $_conexion->prepare($sql);
                        $stmt->execute($params);

                        if ($stmt->rowCount() > 0) {
                            echo json_encode(["message" => "Proveedor actualizado con éxito."]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["message" => "Proveedor no encontrado o sin cambios."]);
                        }
                    } catch (PDOException $e) {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al actualizar proveedor: " . $e->getMessage()]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el proveedor (cif_nif y razon_social) o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($proveedor_id) {
                    $stmt = $_conexion->prepare("DELETE FROM proveedores WHERE id_proveedor = ?");
                    try {
                        $stmt->execute([$proveedor_id]);

                        if ($stmt->rowCount() > 0) {
                            echo json_encode(["message" => "Prooveedor eliminado con éxito."]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["message" => "Prooveedor no encontrado o sin cambios."]);
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al eliminado prooveedor: " . $e->getMessage()]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de prooveedor no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    case 'productos':
        $producto_id = $_GET['id'] ?? null;
        switch ($method) {
            case 'GET':
                if ($producto_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM productos WHERE id_producto = ?");
                    $stmt->execute([$producto_id]);
                    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($producto) {
                        echo json_encode($producto);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Producto no encontrado."]);
                    }
                } else {
                    $stmt = $_conexion->query("SELECT * FROM productos ORDER BY producto ASC");
                    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($productos);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['producto'], $data['importe'])) {
                    $sql = "INSERT INTO productos (codigo_producto, producto, importe) VALUES (?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo_producto'] ?? null,
                        $data['producto'],
                        $data['importe']
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Producto añadido con éxito.", "id_producto" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios (nombre del producto o importe) para añadir el producto."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($producto_id && isset($data['producto'], $data['importe'])) {
                    $sql = "UPDATE productos SET codigo_producto = ?, producto = ?, importe = ? WHERE id_producto = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo_producto'] ?? null,
                        $data['producto'],
                        $data['importe'],
                        $producto_id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Producto actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Producto no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios (nombre del producto o importe) para actualizar el producto o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($producto_id) {
                    $stmt = $_conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
                    $stmt->execute([$producto_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Producto eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Producto no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de producto no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    case 'servicios':
        $servicio_id = $_GET['id'] ?? null;
        switch ($method) {
            case 'GET':
                if ($servicio_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM servicios WHERE id_servicio = ?");
                    $stmt->execute([$servicio_id]);
                    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($servicio) {
                        echo json_encode($servicio);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Servicio no encontrado."]);
                    }
                } else {
                    $stmt = $_conexion->query("SELECT * FROM servicios ORDER BY servicio ASC");
                    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($servicios);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['servicio'], $data['importe'])) {
                    $sql = "INSERT INTO servicios (codigo_servicio, servicio, importe) VALUES (?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo_servicio'] ?? null,
                        $data['servicio'],
                        $data['importe']
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Servicio añadido con éxito.", "id_servicio" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios (nombre del servicio o importe) para añadir el servicio."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($servicio_id && isset($data['servicio'], $data['importe'])) {
                    $sql = "UPDATE servicios SET codigo_servicio = ?, servicio = ?, importe = ? WHERE id_servicio = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo_servicio'] ?? null,
                        $data['servicio'],
                        $data['importe'],
                        $servicio_id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Servicio actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Servicio no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios (nombre del servicio o importe) para actualizar el servicio o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($servicio_id) {
                    $stmt = $_conexion->prepare("DELETE FROM servicios WHERE id_servicio = ?");
                    $stmt->execute([$servicio_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Servicio eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Servicio no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de servicio no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    // AÑADE ESTE BLOQUE PARA ARTICULOS
    case 'articulos':
        $articulo_id = $_GET['id'] ?? null; // Si buscas un artículo por ID
        switch ($method) {
            case 'GET':
                if ($articulo_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM articulos WHERE id_articulo = ?");
                    $stmt->execute([$articulo_id]);
                    $articulo = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($articulo) {
                        echo json_encode($articulo);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Artículo no encontrado."]);
                    }
                } else {
                    // Si no se especifica ID, obtener todos los artículos
                    // Puedes añadir filtros aquí si es necesario (ej. por nombre, código)
                    $sql = "SELECT * FROM articulos";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute();
                    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($articulos);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['articulo'], $data['precio'])) { // Ajusta los campos obligatorios
                    $sql = "INSERT INTO articulos (codigo, articulo, descripcion, precio, stock) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo'] ?? null,
                        $data['articulo'],
                        $data['descripcion'] ?? null,
                        $data['precio'],
                        $data['stock'] ?? 0
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Artículo añadido con éxito.", "id_articulo" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el artículo (articulo, precio)."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($articulo_id && isset($data['articulo'], $data['precio'])) {
                    $sql = "UPDATE articulos SET codigo = ?, articulo = ?, descripcion = ?, precio = ?, stock = ? WHERE id_articulo = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo'] ?? null,
                        $data['articulo'],
                        $data['descripcion'] ?? null,
                        $data['precio'],
                        $data['stock'] ?? 0,
                        $articulo_id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Artículo actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Artículo no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el artículo o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($articulo_id) {
                    $stmt = $_conexion->prepare("DELETE FROM articulos WHERE id_articulo = ?");
                    $stmt->execute([$articulo_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Artículo eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Artículo no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de artículo no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido para artículos."]);
                break;
        }
        break;
    // FIN DEL BLOQUE PARA ARTICULOS

    case 'presupuestos':
        $presupuesto_id = $_GET['id'] ?? null;
        switch ($method) {
            case 'GET':
                if ($presupuesto_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM presupuestos WHERE id_presupuesto = ?");
                    $stmt->execute([$presupuesto_id]);
                    $presupuesto = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($presupuesto) {
                        echo json_encode($presupuesto);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Presupuesto no encontrado."]);
                    }
                } else {
                    $stmt = $_conexion->query("SELECT * FROM presupuestos ORDER BY fecha DESC");
                    $presupuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($presupuestos);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['n_presupuesto'], $data['id_cliente'])) {
                    $sql = "INSERT INTO presupuestos (n_presupuesto, fecha, id_cliente, razon_social, iva, completado)
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['n_presupuesto'],
                        $data['fecha'] ?? null,
                        $data['id_cliente'],
                        $data['razon_social'] ?? null,
                        $data['iva'] ?? null,
                        isset($data['completado']) ? (bool)$data['completado'] : 0
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Presupuesto añadido con éxito.", "id_presupuesto" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el presupuesto (n_presupuesto, id_cliente)."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($presupuesto_id && isset($data['n_presupuesto'], $data['id_cliente'])) {
                    $sql = "UPDATE presupuestos SET n_presupuesto = ?, fecha = ?, id_cliente = ?, razon_social = ?, iva = ?, completado = ?
                            WHERE id_presupuesto = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['n_presupuesto'],
                        $data['fecha'] ?? null,
                        $data['id_cliente'],
                        $data['razon_social'] ?? null,
                        $data['iva'] ?? null,
                        isset($data['completado']) ? (bool)$data['completado'] : 0,
                        $presupuesto_id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Presupuesto actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Presupuesto no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el presupuesto (n_presupuesto, id_cliente) o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($presupuesto_id) {
                    $stmt = $_conexion->prepare("DELETE FROM presupuestos WHERE id_presupuesto = ?");
                    $stmt->execute([$presupuesto_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Presupuesto eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Presupuesto no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de presupuesto no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    case 'articulos_presupuesto':
        $articulo_id = $_GET['id'] ?? null;
        $n_presupuesto = $_GET['n_presupuesto'] ?? null;
        switch ($method) {
            case 'GET':
                if ($articulo_id) {
                    $stmt = $_conexion->prepare("SELECT * FROM articulos_presupuesto WHERE id_articulo_presupuesto = ?");
                    $stmt->execute([$articulo_id]);
                    $articulo = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($articulo) {
                        echo json_encode($articulo);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Artículo de presupuesto no encontrado."]);
                    }
                } elseif ($n_presupuesto) {
                    $stmt = $_conexion->prepare("SELECT * FROM articulos_presupuesto WHERE n_presupuesto = ?");
                    $stmt->execute([$n_presupuesto]);
                    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($articulos);
                } else {
                    $stmt = $_conexion->query("SELECT * FROM articulos_presupuesto");
                    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($articulos);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['n_presupuesto'], $data['codigo_articulo'], $data['cantidad'], $data['tipo'])) {
                    $sql = "INSERT INTO articulos_presupuesto (n_presupuesto, codigo_articulo, cantidad, tipo)
                            VALUES (?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['n_presupuesto'],
                        $data['codigo_articulo'],
                        $data['cantidad'],
                        $data['tipo']
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Artículo añadido al presupuesto con éxito.", "id_articulo_presupuesto" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el artículo al presupuesto (n_presupuesto, codigo_articulo, cantidad, tipo)."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($articulo_id && isset($data['codigo_articulo'], $data['cantidad'], $data['tipo'])) {
                    $sql = "UPDATE articulos_presupuesto SET codigo_articulo = ?, cantidad = ?, tipo = ? WHERE id_articulo_presupuesto = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['codigo_articulo'],
                        $data['cantidad'],
                        $data['tipo'],
                        $articulo_id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Artículo de presupuesto actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Artículo de presupuesto no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el artículo del presupuesto o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($articulo_id) {
                    $stmt = $_conexion->prepare("DELETE FROM articulos_presupuesto WHERE id_articulo_presupuesto = ?");
                    $stmt->execute([$articulo_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Artículo de presupuesto eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Artículo de presupuesto no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de artículo de presupuesto no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    case 'pedidos':
        // Ahora, podemos buscar por 'id_pedido' o por 'n_pedido'
        $pedido_id = $_GET['id'] ?? null; // Si se busca por id_pedido
        $n_pedido_param = $_GET['n_pedido'] ?? null; // Si se busca por n_pedido (para detalles del pedido)

        switch ($method) {
            case 'GET':
                $sql = "SELECT * FROM pedidos";
                $params = [];
                $where_clause = [];

                if ($pedido_id) {
                    $where_clause[] = "id_pedido = ?";
                    $params[] = $pedido_id;
                }
                if ($n_pedido_param) {
                    $where_clause[] = "n_pedido = ?";
                    $params[] = $n_pedido_param;
                }

                if (!empty($where_clause)) {
                    $sql .= " WHERE " . implode(" AND ", $where_clause);
                } else {
                    $sql .= " ORDER BY fecha DESC"; // Para obtener todos los pedidos si no hay filtro
                }

                $stmt = $_conexion->prepare($sql);
                $stmt->execute($params);
                $pedidos_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($pedidos_data)) {
                    // Si se busca un único pedido (por id o n_pedido), adjuntar sus artículos
                    if (count($pedidos_data) === 1) {
                        $pedido = $pedidos_data[0];
                        $n_pedido_actual = $pedido['n_pedido'];

                        // Obtener los artículos asociados a este pedido
                        $sql_articulos = "SELECT codigo_articulo, cantidad, tipo FROM articulos_pedido WHERE n_pedido = ?";
                        $stmt_articulos = $_conexion->prepare($sql_articulos);
                        $stmt_articulos->execute([$n_pedido_actual]);
                        $articulos = $stmt_articulos->fetchAll(PDO::FETCH_ASSOC);

                        $pedido['articulos'] = $articulos; // Añadir los artículos al objeto del pedido
                        echo json_encode($pedido); // Devolver el pedido con sus artículos
                    } else {
                        // Si se piden todos los pedidos o varios, no adjuntar artículos para cada uno (rendimiento)
                        echo json_encode($pedidos_data);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Pedido(s) no encontrado(s)."]);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['n_pedido'], $data['id_cliente'])) {
                    $sql = "INSERT INTO pedidos (n_pedido, fecha, id_cliente, razon_social, iva, completado, n_presupuesto)
                            VALUES (?, ?, ?, ?, ?, ?, ?)"; // Añadido 'completado' que estaba en tu esquema original
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['n_pedido'],
                        $data['fecha'] ?? null,
                        $data['id_cliente'],
                        $data['razon_social'] ?? null,
                        $data['iva'] ?? null,
                        $data['completado'] ?? 0, // Valor por defecto si no se envía
                        $data['n_presupuesto'] ?? null
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Pedido añadido con éxito.", "id_pedido" => $_conexion->lastInsertId()]);
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el pedido (n_pedido, id_cliente)."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                $target_id = $pedido_id ?? $data['id_pedido'] ?? null; // Permitir PUT con ID en URL o en body

                if ($target_id && isset($data['n_pedido'], $data['id_cliente'])) {
                    $sql = "UPDATE pedidos SET n_pedido = ?, fecha = ?, id_cliente = ?, razon_social = ?, iva = ?, completado = ?, n_presupuesto = ?
                            WHERE id_pedido = ?";
                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute([
                        $data['n_pedido'],
                        $data['fecha'] ?? null,
                        $data['id_cliente'],
                        $data['razon_social'] ?? null,
                        $data['iva'] ?? null,
                        $data['completado'] ?? 0, // Asegura que se actualiza si está presente o usa defecto
                        $data['n_presupuesto'] ?? null,
                        $target_id
                    ]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Pedido actualizado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Pedido no encontrado o sin cambios."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el pedido (n_pedido, id_cliente) o ID no proporcionado."]);
                }
                break;

            case 'DELETE':
                if ($pedido_id) {
                    $stmt = $_conexion->prepare("DELETE FROM pedidos WHERE id_pedido = ?");
                    $stmt->execute([$pedido_id]);
                    if ($stmt->rowCount() > 0) {
                        echo json_encode(["message" => "Pedido eliminado con éxito."]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Pedido no encontrado."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "ID de pedido no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break;

    case 'albaranes':
        $n_albaran_param = $_GET['n_albaran'] ?? null; // Usamos n_albaran como identificador para GET, PUT, DELETE

        switch ($method) {
            case 'GET':
                if ($n_albaran_param) {
                    // Obtener un albarán específico por su n_albaran
                    $stmt = $_conexion->prepare("SELECT * FROM albaranes WHERE n_albaran = ?");
                    $stmt->execute([$n_albaran_param]);
                    $albaran = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($albaran) {
                        echo json_encode($albaran);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Albarán no encontrado."]);
                    }
                } else {
                    // Obtener todos los albaranes o filtrar por parámetros opcionales
                    $filtros = [];
                    $valores = [];

                    if (!empty($_GET['id_cliente'])) {
                        $filtros[] = 'id_cliente = ?';
                        $valores[] = $_GET['id_cliente'];
                    }
                    if (!empty($_GET['razon_social'])) {
                        $filtros[] = 'razon_social LIKE ?';
                        $valores[] = '%' . $_GET['razon_social'] . '%';
                    }
                    if (!empty($_GET['n_presupuesto'])) {
                        $filtros[] = 'n_presupuesto = ?';
                        $valores[] = $_GET['n_presupuesto'];
                    }
                    if (!empty($_GET['n_pedido'])) {
                        $filtros[] = 'n_pedido = ?';
                        $valores[] = $_GET['n_pedido'];
                    }
                    // Puedes añadir más filtros si los necesitas (ej. por fecha)

                    $sql = "SELECT * FROM albaranes";
                    if (!empty($filtros)) {
                        $sql .= " WHERE " . implode(" AND ", $filtros);
                    }
                    $sql .= " ORDER BY fecha DESC, n_albaran DESC"; // Ordenar por fecha y número de albarán

                    $stmt = $_conexion->prepare($sql);
                    $stmt->execute($valores);
                    $albaranes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($albaranes);
                }
                break;

            case 'POST':
                $data = json_decode(file_get_contents("php://input"), true);
                if (isset($data['n_albaran'], $data['id_cliente'])) {
                    // Validar que n_albaran sea único
                    $stmt_check = $_conexion->prepare("SELECT COUNT(*) FROM albaranes WHERE n_albaran = ?");
                    $stmt_check->execute([$data['n_albaran']]);
                    if ($stmt_check->fetchColumn() > 0) {
                        http_response_code(409); // Conflict
                        echo json_encode(["message" => "El número de albarán ya existe."]);
                        break;
                    }

                    $sql = "INSERT INTO albaranes (
                    n_albaran, fecha, id_cliente, razon_social, iva, n_presupuesto, n_pedido
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $_conexion->prepare($sql);
                    try {
                        $stmt->execute([
                            $data['n_albaran'],
                            $data['fecha'] ?? null, // Puede ser null si no se envía
                            $data['id_cliente'],
                            $data['razon_social'] ?? null,
                            $data['iva'] ?? null,
                            $data['n_presupuesto'] ?? null, // Puede ser null
                            $data['n_pedido'] ?? null      // Puede ser null
                        ]);
                        http_response_code(201); // Created
                        echo json_encode(["message" => "Albarán añadido con éxito.", "id_albaran" => $_conexion->lastInsertId()]);
                    } catch (PDOException $e) {
                        // Manejar errores de clave foránea o otros errores de la BD
                        if (strpos($e->getMessage(), 'Foreign key constraint fails') !== false) {
                            http_response_code(400);
                            echo json_encode(["message" => "Error de clave foránea. Asegúrate que el cliente, presupuesto o pedido asociado existe. Detalle: " . $e->getMessage()]);
                        } else {
                            http_response_code(500);
                            echo json_encode(["message" => "Error al añadir el albarán: " . $e->getMessage()]);
                        }
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para añadir el albarán (n_albaran, id_cliente)."]);
                }
                break;

            case 'PUT':
                $data = json_decode(file_get_contents("php://input"), true);
                if ($n_albaran_param && isset($data['id_cliente'])) { // n_albaran_param es el identificador en la URL
                    // Construir la consulta de actualización dinámicamente
                    $campos_a_actualizar = [];
                    $valores_a_actualizar = [];

                    if (isset($data['n_albaran']) && $data['n_albaran'] !== $n_albaran_param) { // Si el número de albarán cambia, hay que validar unicidad
                        $stmt_check = $_conexion->prepare("SELECT COUNT(*) FROM albaranes WHERE n_albaran = ? AND n_albaran != ?");
                        $stmt_check->execute([$data['n_albaran'], $n_albaran_param]);
                        if ($stmt_check->fetchColumn() > 0) {
                            http_response_code(409); // Conflict
                            echo json_encode(["message" => "El nuevo número de albarán ya existe para otro albarán."]);
                            break;
                        }
                        $campos_a_actualizar[] = 'n_albaran = ?';
                        $valores_a_actualizar[] = $data['n_albaran'];
                    }
                    if (isset($data['fecha'])) {
                        $campos_a_actualizar[] = 'fecha = ?';
                        $valores_a_actualizar[] = $data['fecha'];
                    }
                    if (isset($data['id_cliente'])) {
                        $campos_a_actualizar[] = 'id_cliente = ?';
                        $valores_a_actualizar[] = $data['id_cliente'];
                    }
                    if (isset($data['razon_social'])) {
                        $campos_a_actualizar[] = 'razon_social = ?';
                        $valores_a_actualizar[] = $data['razon_social'];
                    }
                    if (isset($data['iva'])) {
                        $campos_a_actualizar[] = 'iva = ?';
                        $valores_a_actualizar[] = $data['iva'];
                    }
                    // Permitir que n_presupuesto y n_pedido sean null si se envían como cadena vacía o null
                    if (array_key_exists('n_presupuesto', $data)) {
                        $campos_a_actualizar[] = 'n_presupuesto = ?';
                        $valores_a_actualizar[] = ($data['n_presupuesto'] === '' ? null : $data['n_presupuesto']);
                    }
                    if (array_key_exists('n_pedido', $data)) {
                        $campos_a_actualizar[] = 'n_pedido = ?';
                        $valores_a_actualizar[] = ($data['n_pedido'] === '' ? null : $data['n_pedido']);
                    }

                    if (empty($campos_a_actualizar)) {
                        http_response_code(400);
                        echo json_encode(["message" => "No se proporcionaron campos para actualizar."]);
                        break;
                    }

                    $sql = "UPDATE albaranes SET " . implode(", ", $campos_a_actualizar) . " WHERE n_albaran = ?";
                    $valores_a_actualizar[] = $n_albaran_param; // Añadir el n_albaran para la cláusula WHERE

                    try {
                        $stmt = $_conexion->prepare($sql);
                        $stmt->execute($valores_a_actualizar);

                        if ($stmt->rowCount() > 0) {
                            echo json_encode(["message" => "Albarán actualizado con éxito."]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["message" => "Albarán no encontrado o sin cambios para el n_albaran: " . $n_albaran_param]);
                        }
                    } catch (PDOException $e) {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al actualizar el albarán: " . $e->getMessage()]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Faltan datos obligatorios para actualizar el albarán (n_albaran en URL y id_cliente en body)."]);
                }
                break;

            case 'DELETE':
                if ($n_albaran_param) {
                    $stmt = $_conexion->prepare("DELETE FROM albaranes WHERE n_albaran = ?");
                    try {
                        $stmt->execute([$n_albaran_param]);

                        if ($stmt->rowCount() > 0) {
                            echo json_encode(["message" => "Albarán eliminado con éxito."]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["message" => "Albarán no encontrado para eliminar."]);
                        }
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode(["message" => "Error al eliminar el albarán: " . $e->getMessage()]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["message" => "Número de albarán no proporcionado para eliminar."]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(["message" => "Método no permitido."]);
                break;
        }
        break; // Fin del case 'albaranes'

    case 'facturas':
        if ($method === 'GET') {
            if (isset($_GET['n_factura'])) {
                // Obtener una factura específica (para editar, ver, etc.)
                $n_factura = $_GET['n_factura'];
                $sql = "SELECT f.*, c.razon_social, c.cif_nif, c.direccion, c.poblacion, c.provincia, c.cp, c.telefono, c.email, c.forma_pago
                            FROM facturas f 
                            LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
                            WHERE f.n_factura = :n_factura";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':n_factura', $n_factura);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                // Obtener todas las facturas con datos del cliente para el listado
                $sql = "SELECT f.*, c.razon_social, c.cif_nif, c.direccion, c.poblacion, c.provincia, c.cp, c.telefono, c.email, c.forma_pago
                            FROM facturas f 
                            LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
                            ORDER BY f.fecha DESC, f.n_factura DESC"; // Ordena por fecha y luego número
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                header('Content-Type: application/json');
                echo json_encode($result);
            }
        } elseif ($method === 'POST') {
            // Crear nueva factura
            if (
                isset($input['n_factura']) && isset($input['fecha']) && isset($input['id_cliente']) &&
                isset($input['iva']) && isset($input['base_imponible']) && isset($input['iva_total']) && isset($input['total_factura'])
            ) {
                $sql = "INSERT INTO facturas (n_factura, fecha, id_cliente, razon_social, iva, n_presupuesto, n_pedido, n_albaran, base_imponible, iva_total, total_factura) 
                            VALUES (:n_factura, :fecha, :id_cliente, :razon_social, :iva, :n_presupuesto, :n_pedido, :n_albaran, :base_imponible, :iva_total, :total_factura)";
                $stmt = $pdo->prepare($sql);

                // Asegúrate de que las claves existen antes de enlazarlas
                $n_presupuesto = $input['n_presupuesto'] ?? NULL;
                $n_pedido = $input['n_pedido'] ?? NULL;
                $n_albaran = $input['n_albaran'] ?? NULL;

                $stmt->bindParam(':n_factura', $input['n_factura']);
                $stmt->bindParam(':fecha', $input['fecha']);
                $stmt->bindParam(':id_cliente', $input['id_cliente']);
                $stmt->bindParam(':razon_social', $input['razon_social']);
                $stmt->bindParam(':iva', $input['iva']);
                $stmt->bindParam(':n_presupuesto', $n_presupuesto);
                $stmt->bindParam(':n_pedido', $n_pedido);
                $stmt->bindParam(':n_albaran', $n_albaran);
                $stmt->bindParam(':base_imponible', $input['base_imponible']);
                $stmt->bindParam(':iva_total', $input['iva_total']);
                $stmt->bindParam(':total_factura', $input['total_factura']);

                $stmt->execute();
                http_response_code(201); // Created
                echo json_encode(['message' => 'Factura creada con éxito.', 'n_factura' => $input['n_factura']]);
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Faltan parámetros para crear la factura.', 'received_data' => $input]);
            }
        } elseif ($method === 'PUT') {
            // Actualizar factura
            if (
                isset($_GET['n_factura']) && isset($input['fecha']) && isset($input['id_cliente']) &&
                isset($input['iva']) && isset($input['base_imponible']) && isset($input['iva_total']) && isset($input['total_factura'])
            ) {
                $n_factura_to_update = $_GET['n_factura'];
                $sql = "UPDATE facturas SET fecha = :fecha, id_cliente = :id_cliente, razon_social = :razon_social, iva = :iva, 
                                n_presupuesto = :n_presupuesto, n_pedido = :n_pedido, n_albaran = :n_albaran,
                                base_imponible = :base_imponible, iva_total = :iva_total, total_factura = :total_factura
                            WHERE n_factura = :n_factura_to_update";
                $stmt = $pdo->prepare($sql);

                // Asegúrate de que las claves existen antes de enlazarlas
                $n_presupuesto = $input['n_presupuesto'] ?? NULL;
                $n_pedido = $input['n_pedido'] ?? NULL;
                $n_albaran = $input['n_albaran'] ?? NULL;

                $stmt->bindParam(':fecha', $input['fecha']);
                $stmt->bindParam(':id_cliente', $input['id_cliente']);
                $stmt->bindParam(':razon_social', $input['razon_social']);
                $stmt->bindParam(':iva', $input['iva']);
                $stmt->bindParam(':n_presupuesto', $n_presupuesto);
                $stmt->bindParam(':n_pedido', $n_pedido);
                $stmt->bindParam(':n_albaran', $n_albaran);
                $stmt->bindParam(':base_imponible', $input['base_imponible']);
                $stmt->bindParam(':iva_total', $input['iva_total']);
                $stmt->bindParam(':total_factura', $input['total_factura']);
                $stmt->bindParam(':n_factura_to_update', $n_factura_to_update);
                $stmt->execute();

                // Comprobar si se afectó alguna fila (factura encontrada y actualizada)
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Factura actualizada con éxito.']);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Factura no encontrada o sin cambios.']);
                }
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Faltan parámetros o n_factura para actualizar.', 'received_data' => $input]);
            }
        } elseif ($method === 'DELETE') {
            if (isset($_GET['n_factura'])) {
                $n_factura = $_GET['n_factura'];
                // La eliminación de la factura principal debe usar transacciones
                // para asegurar que si falla la eliminación de artículos, la factura no se elimina.
                // O, si la clave foránea tiene ON DELETE CASCADE, los artículos se borrarán automáticamente.
                // Asumo ON DELETE CASCADE en facturas_articulos, así que una simple eliminación es suficiente.
                $sql = "DELETE FROM facturas WHERE n_factura = :n_factura";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':n_factura', $n_factura);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    http_response_code(204); // No Content
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Factura no encontrada.']);
                }
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Falta el parámetro n_factura para eliminar.']);
            }
        }
        break;

    // --- FACTURAS_ARTICULOS ---
    case 'facturas_articulos':
        if ($method === 'GET') {
            if (isset($_GET['n_factura'])) {
                $n_factura = $_GET['n_factura'];
                $sql = "SELECT * FROM facturas_articulos WHERE n_factura = :n_factura ORDER BY id_factura_articulo ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':n_factura', $n_factura);
                $stmt->execute();
                header('Content-Type: application/json');
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else if (isset($_GET['id_factura_articulo'])) {
                // Obtener un artículo específico de factura por su ID
                $id_articulo = $_GET['id_factura_articulo'];
                $sql = "SELECT * FROM facturas_articulos WHERE id_factura_articulo = :id_articulo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_articulo', $id_articulo);
                $stmt->execute();
                header('Content-Type: application/json');
                echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Falta el parámetro n_factura o id_factura_articulo para obtener artículos de la factura.']);
            }
        } elseif ($method === 'POST') {
            if (
                isset($input['n_factura']) && isset($input['descripcion']) &&
                isset($input['cantidad']) && isset($input['precio_unitario']) && isset($input['iva_articulo'])
            ) {
                $sql = "INSERT INTO facturas_articulos (n_factura, descripcion, cantidad, precio_unitario, iva_articulo) 
                            VALUES (:n_factura, :descripcion, :cantidad, :precio_unitario, :iva_articulo)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':n_factura', $input['n_factura']);
                $stmt->bindParam(':descripcion', $input['descripcion']);
                $stmt->bindParam(':cantidad', $input['cantidad']);
                $stmt->bindParam(':precio_unitario', $input['precio_unitario']);
                $stmt->bindParam(':iva_articulo', $input['iva_articulo']);
                $stmt->execute();
                http_response_code(201); // Created
                echo json_encode(['message' => 'Artículo de factura creado con éxito.', 'id_factura_articulo' => $pdo->lastInsertId()]);
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Faltan parámetros para crear el artículo de factura.', 'received_data' => $input]);
            }
        } elseif ($method === 'PUT') {
            if (
                isset($_GET['id_factura_articulo']) && isset($input['descripcion']) &&
                isset($input['cantidad']) && isset($input['precio_unitario']) && isset($input['iva_articulo'])
            ) {
                $id_articulo = $_GET['id_factura_articulo'];
                $sql = "UPDATE facturas_articulos SET descripcion = :descripcion, cantidad = :cantidad, 
                                precio_unitario = :precio_unitario, iva_articulo = :iva_articulo
                            WHERE id_factura_articulo = :id_articulo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':descripcion', $input['descripcion']);
                $stmt->bindParam(':cantidad', $input['cantidad']);
                $stmt->bindParam(':precio_unitario', $input['precio_unitario']);
                $stmt->bindParam(':iva_articulo', $input['iva_articulo']);
                $stmt->bindParam(':id_articulo', $id_articulo);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    http_response_code(200); // OK
                    echo json_encode(['message' => 'Artículo de factura actualizado con éxito.']);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Artículo de factura no encontrado o sin cambios.']);
                }
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Faltan parámetros o id_factura_articulo para actualizar el artículo.']);
            }
        } elseif ($method === 'DELETE') {
            // Para eliminar un artículo específico por su ID
            if (isset($_GET['id_factura_articulo'])) {
                $id_articulo = $_GET['id_factura_articulo'];
                $sql = "DELETE FROM facturas_articulos WHERE id_factura_articulo = :id_articulo";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_articulo', $id_articulo);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    http_response_code(204); // No Content
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['error' => 'Artículo de factura no encontrado.']);
                }
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Falta el ID del artículo para eliminar.']);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["message" => "Tabla no especificada o no válida."]);
        break;
}
