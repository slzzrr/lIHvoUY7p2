<?php
// layout/header.php
// Asegúrate de que 'config.php' ya esté incluido, o inclúyelo si no lo está:
if (!defined('BASE_URL')) {
    include_once __DIR__ . '/../config.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Panel Administrativo</title>
  <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/img/favicon.png">

  <!-- Iconos y Fuentes -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.3/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i|Roboto+Mono:300,400,700|Roboto+Slab:300,400,700" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  
  <!-- CSS de Material Design (opcional) -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/material.min.css">
  
  <!-- Tu CSS principal global (home.css) -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/home.css">
</head>
<body>
