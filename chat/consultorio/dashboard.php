<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Consultorio - Grupo Lux</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
  <h1 class="mb-4">Consultorio Virtual</h1>
  <div class="row g-3">
    <div class="col-md-3"><a href="turnos_listar.php" class="btn btn-outline-primary w-100 p-4">📋 Listar Turnos</a></div>
    <div class="col-md-3"><a href="turnos_form.php" class="btn btn-outline-success w-100 p-4">➕ Nuevo Turno</a></div>
    <div class="col-md-3"><a href="medicos_listar.php" class="btn btn-outline-secondary w-100 p-4">🩺 Médicos</a></div>
    <div class="col-md-3"><a href="historia_listar.php" class="btn btn-outline-info w-100 p-4">📚 Historias Clínicas</a></div>
    <div class="col-md-3"><a href="pacientes_listar.php" class="btn btn-outline-info w-100 p-4">📚 Pacientes</a></div>
  </div>
  <div class="mt-4">
    <a href="../admin.php" class="btn btn-dark">🔙 Volver</a>
  </div>
</main>
<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>