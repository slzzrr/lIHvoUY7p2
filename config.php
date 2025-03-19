<?php
error_reporting(E_ALL & ~E_DEPRECATED);
$usuario = "root";
$password = "";
$servidor = "localhost";
$basededatos = "common";

$conexion = new mysqli($servidor, $usuario, $password, $basededatos);
if ($conexion->connect_error) {
    die("Connection failed: " . $conexion->connect_error);
}

// Ajusta la ruta a la carpeta real de tu proyecto:
define('BASE_URL', 'http://localhost/lok/');
