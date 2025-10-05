<?php
// Este archivo se incluye después de checklogin.php
// Así ya tenés $_SESSION cargada con el usuario logueado
?>

<!-- Barra superior -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
  <div class="collapse navbar-collapse">
    <ul class="navbar-nav ms-auto">
      <li class="nav-item me-3 text-white">
        👤 <?= htmlspecialchars($_SESSION['nombre']) ?> 
        (Rol: <?= htmlspecialchars($_SESSION['id_rol']) ?>)
      </li>
      <li class="nav-item">
      </li>
    </ul>
  </div>
</nav>

<?php
$roles = [
    1 => "AdminG",
    2 => "AdminN",
    3 => "Cliente",
    4 => "Empleado",
    5 => "Medico"
];
$rolNombre = $roles[$_SESSION['id_rol']] ?? "Desconocido";
?>

