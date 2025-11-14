<?php
session_start(); // ¡DEBE SER LA PRIMERA LÍNEA SIN NADA ANTES!

// Redirige a login si no hay sesión iniciada o no es un array válido
if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$nombre_a_mostrar = htmlspecialchars($usuario['nombre'] ?? 'Usuario');
$rol_usuario = (int)($usuario['rol'] ?? 0);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - PATRIMAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="stylesheets/style.css">
    <link rel="stylesheet" href="stylesheets/inicio.css">

    <style>
        body {
            background-color: #1e1e2e;
            color: white;
        }

        .text-center h1,
        .text-center p.lead {
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .btn-panel {
            font-size: 1.1rem;
            padding: 12px 0;
            margin-bottom: 15px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-panel:hover {
            transform: translateY(-3px);
            opacity: 0.95;
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-group-row {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
        }

        .container,
        .row,
        .col-12,
        .col-md-6 {
            background-color: transparent !important;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="text-center mb-5">
            <h1>Bienvenido, <?php echo $nombre_a_mostrar; ?> 👋</h1>
            <p class="lead">Gestiona tu empresa de forma eficiente</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php if ($rol_usuario === 1) : // Rol 1 = Administrador 
            ?>
                <div class="col-12 text-center mb-4">
                    <a href="usuarios/listar_usuarios.php" class="btn btn-primary w-50 btn-panel">
                        ⚙️ Gestión de Usuarios
                    </a>
                </div>
            <?php endif; ?>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="clientes/nuevo_cliente.php" class="btn btn-success w-100 btn-panel">➕ Nuevo Cliente</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="clientes/listar_clientes.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Clientes</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="proveedores/nuevo_proveedor.php" class="btn btn-success w-100 btn-panel">➕ Nuevo Proveedor</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="proveedores/listar_proveedores.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Proveedores</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="productos/nuevo_producto.php" class="btn btn-success w-100 btn-panel">📦 Nuevo Producto</a>
                <a href="servicios/nuevo_servicio.php" class="btn btn-success w-100 btn-panel">🛠️ Nuevo Servicio</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="productos/listar_productos.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Productos</a>
                <a href="servicios/listar_servicios.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Servicios</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="presupuestos/nuevo_presupuesto.php" class="btn btn-success w-100 btn-panel">📄 Nuevo Presupuesto</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="presupuestos/listar_presupuestos.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Presupuestos</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="pedidos/nuevo_pedido.php" class="btn btn-success w-100 btn-panel">🛒 Nuevo Pedido</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="pedidos/listar_pedidos.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Pedidos</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="albaranes/nuevo_albaran.php" class="btn btn-success w-100 btn-panel">🚚 Nuevo Albarán</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="albaranes/listar_albaranes.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Albaranes</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="facturas/nueva_factura.php" class="btn btn-success w-100 btn-panel">🧾 Nueva Factura</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="facturas/listar_facturas.php" class="btn btn-primary w-100 btn-panel">📊 Listado de Facturas</a>
            </div>

            <div class="col-md-6 d-flex btn-group-row">
                <a href="diarios/nuevo_diario_facturas.php" class="btn btn-info w-100 btn-panel">✏️ Registrar Factura (Diario)</a>
                <a href="diarios/nuevo_diario_proveedores.php" class="btn btn-info w-100 btn-panel">✏️ Registrar Proveedor (Diario)</a>
                <a href="diarios/nuevo_diario_gastos.php" class="btn btn-info w-100 btn-panel">✏️ Registrar Gasto (Diario)</a>
            </div>
            <div class="col-md-6 d-flex btn-group-row">
                <a href="diarios/diario_facturas.php" class="btn btn-warning w-100 btn-panel">📈 Diario de Facturas</a>
                <a href="diarios/diario_proveedores.php" class="btn btn-warning w-100 btn-panel">📈 Diario de Proveedores</a>
                <a href="diarios/diario_gastos.php" class="btn btn-warning w-100 btn-panel">📈 Diario de Gastos</a>
            </div>

        </div>

        <div class="text-center mt-5 mb-4">
            <a href="logout.php" class="btn btn-danger w-25 btn-panel">🔓 Cerrar sesión</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>