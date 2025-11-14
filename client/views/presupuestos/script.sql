CREATE SCHEMA programa;
USE programa;

CREATE TABLE clientes (
  id_cliente INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(45),
  apellidos VARCHAR(45),
  razon_social VARCHAR(100) NOT NULL,
  cif_nif VARCHAR(20) NOT NULL UNIQUE,
  direccion VARCHAR(200),
  cp VARCHAR(10),
  poblacion VARCHAR(50),
  provincia VARCHAR(50),
  persona_contacto_1 VARCHAR(50),
  telefono1 VARCHAR(25),
  persona_contacto_2 VARCHAR(50),
  telefono2 VARCHAR(25),
  persona_contacto_3 VARCHAR(50),
  telefono3 VARCHAR(25),
  email VARCHAR(255),
  observaciones TEXT,
  cuenta_bancaria VARCHAR(255),
  metodo_pago VARCHAR(255),
  puede_pedir BOOLEAN
);

CREATE TABLE proveedores (
  id_proveedor INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(45),
  apellidos VARCHAR(45),
  razon_social VARCHAR(100) NOT NULL,
  cif_nif VARCHAR(20) NOT NULL UNIQUE,
  direccion VARCHAR(200),
  cp VARCHAR(10),
  poblacion VARCHAR(50),
  provincia VARCHAR(50),
  persona_contacto_1 VARCHAR(50),
  telefono1 VARCHAR(25),
  persona_contacto_2 VARCHAR(50),
  telefono2 VARCHAR(25),
  persona_contacto_3 VARCHAR(50),
  telefono3 VARCHAR(25),
  email VARCHAR(255),
  observaciones TEXT
);

CREATE TABLE gastos (
  id_gasto INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(255),
  tipo VARCHAR(255)
);

CREATE TABLE diario_facturas (
  id_factura INT PRIMARY KEY AUTO_INCREMENT,
  fecha_factura DATE,
  semana_del_ano INT,
  mes INT,
  trimestre INT,
  id_cliente INT NOT NULL,
  cliente VARCHAR(255),
  n_factura VARCHAR(255) NOT NULL,
  debe DECIMAL(8, 3),
  haber DECIMAL(8, 3),
  iva DECIMAL(8, 3),
  iva_debe DECIMAL(8, 3),
  iva_haber DECIMAL(8, 3),
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE diario_proveedores (
  id_diario_proveedor INT PRIMARY KEY AUTO_INCREMENT,
  fecha_proveedor DATE,
  semana_del_ano INT,
  mes INT,
  trimestre INT,
  id_proveedor INT NOT NULL,
  proveedor VARCHAR(255),
  n_factura VARCHAR(255) NOT NULL,
  debe DECIMAL(8, 3),
  haber DECIMAL(8, 3),
  iva DECIMAL(8, 3),
  iva_debe DECIMAL(8, 3),
  iva_haber DECIMAL(8, 3),
  FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor) ON DELETE CASCADE
);

CREATE TABLE diario_gastos (
  id_diario_gasto INT PRIMARY KEY AUTO_INCREMENT,
  fecha_gasto DATE,
  semana_del_ano INT,
  mes INT,
  trimestre INT,
  id_gasto INT NOT NULL,
  tipo VARCHAR(255),
  gasto VARCHAR(255),
  concepto VARCHAR(255),
  debe DECIMAL(8, 3),
  haber DECIMAL(8, 3),
  iva DECIMAL(8, 3),
  iva_debe DECIMAL(8, 3),
  iva_haber DECIMAL(8, 3),
  FOREIGN KEY (id_gasto) REFERENCES gastos(id_gasto) ON DELETE CASCADE
);

CREATE TABLE productos (
  id_producto INT PRIMARY KEY AUTO_INCREMENT,
  codigo_producto VARCHAR(255),
  producto VARCHAR(255),
  importe DECIMAL(10, 2)
);

CREATE TABLE servicios (
  id_servicio INT PRIMARY KEY AUTO_INCREMENT,
  codigo_servicio VARCHAR(255),
  servicio VARCHAR(255),
  importe DECIMAL(10, 2)
);

CREATE TABLE presupuestos (
  id_presupuesto INT PRIMARY KEY AUTO_INCREMENT,
  n_presupuesto VARCHAR(255) UNIQUE,
  fecha DATE,
  id_cliente INT,
  razon_social VARCHAR(100),
  iva DECIMAL(8, 3),
  completado BOOLEAN,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE articulos_presupuesto (
  id_articulo_presupuesto INT PRIMARY KEY AUTO_INCREMENT,
  n_presupuesto VARCHAR(255),
  codigo_articulo VARCHAR(255),
  cantidad INT,
  tipo INT,
  FOREIGN KEY (n_presupuesto) REFERENCES presupuestos(n_presupuesto) ON DELETE CASCADE
);

CREATE TABLE pedidos (
  id_pedido INT PRIMARY KEY AUTO_INCREMENT,
  n_pedido VARCHAR(255) UNIQUE,
  fecha DATE,
  id_cliente INT,
  razon_social VARCHAR(100),
  iva DECIMAL(8, 3),
  n_presupuesto VARCHAR(255),
  FOREIGN KEY (n_presupuesto) REFERENCES presupuestos(n_presupuesto) ON DELETE CASCADE,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE articulos_pedido (
  id_articulo_pedido INT PRIMARY KEY AUTO_INCREMENT,
  n_pedido VARCHAR(255),
  codigo_articulo VARCHAR(255),
  cantidad INT,
  tipo INT,
  FOREIGN KEY (n_pedido) REFERENCES pedidos(n_pedido) ON DELETE CASCADE
);

CREATE TABLE albaranes (
  id_albaran INT PRIMARY KEY AUTO_INCREMENT,
  n_albaran VARCHAR(255) UNIQUE,
  fecha DATE,
  id_cliente INT,
  razon_social VARCHAR(100),
  iva DECIMAL(8, 3),
  n_presupuesto VARCHAR(255),
  n_pedido VARCHAR(255),
  FOREIGN KEY (n_presupuesto) REFERENCES presupuestos(n_presupuesto) ON DELETE CASCADE,
  FOREIGN KEY (n_pedido) REFERENCES pedidos(n_pedido) ON DELETE CASCADE,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE articulos_albaran (
  id_articulo_albaran INT PRIMARY KEY AUTO_INCREMENT,
  n_albaran VARCHAR(255),
  codigo_articulo VARCHAR(255),
  cantidad INT,
  tipo INT,
  FOREIGN KEY (n_albaran) REFERENCES albaranes(n_albaran) ON DELETE CASCADE
);

CREATE TABLE facturas (
  id_factura INT PRIMARY KEY AUTO_INCREMENT,
  n_factura VARCHAR(255) UNIQUE,
  fecha DATE,
  id_cliente INT,
  razon_social VARCHAR(100),
  iva DECIMAL(8, 3),
  n_presupuesto VARCHAR(255),
  n_pedido VARCHAR(255),
  n_albaran VARCHAR(255),
  FOREIGN KEY (n_presupuesto) REFERENCES presupuestos(n_presupuesto) ON DELETE CASCADE,
  FOREIGN KEY (n_pedido) REFERENCES pedidos(n_pedido) ON DELETE CASCADE,
  FOREIGN KEY (n_albaran) REFERENCES albaranes(n_albaran) ON DELETE CASCADE,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE articulos_factura (
  id_articulo_factura INT PRIMARY KEY AUTO_INCREMENT,
  n_factura VARCHAR(255),
  codigo_articulo VARCHAR(255),
  cantidad INT,
  tipo INT,
  FOREIGN KEY (n_factura) REFERENCES facturas(n_factura) ON DELETE CASCADE
);

CREATE TABLE usuarios (
  id_usuario INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(40),
  contrasena VARCHAR(40),
  fecha_creacion DATE,
  rol INT
);
