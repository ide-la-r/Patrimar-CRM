<?php
session_start();

// Destruye todos los datos de la sesión
$_SESSION = [];
session_unset();
session_destroy();

// Redirige al login
header("Location: login.php");
exit();
