<?php
error_reporting(E_ALL & ~E_DEPRECATED);
$usuario = "u889162940_ts";
$password = "Cheater099?!";
$servidor = "localhost";
$basededatos = "u889162940_ts";

$conexion = new mysqli($servidor, $usuario, $password, $basededatos);
if ($conexion->connect_error) {
    die("Connection failed: " . $conexion->connect_error);
}

// Ajusta la ruta a la carpeta real de tu proyecto:
define('BASE_URL', 'https://ts.salan.group/');
date_default_timezone_set('America/Mexico_City');
?>
