<?php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "ismael17v2";
$DB_NAME = "programa";

try {
    $_conexion = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8", $DB_USER, $DB_PASS);
    $_conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "❌ Error de conexión PDO: " . $e->getMessage()]));
}
