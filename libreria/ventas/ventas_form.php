<?php
include("../../backend/conexion.php");

$id = $_GET['id'] ?? null;
$id_negocio = $id_usuario = $fecha = $total = $solicitud = "";

if ($id) {
    $sql = "SELECT * FROM ventas WHERE id_venta = $id";
    $res = $conn->query($sql);
    $venta = $res->fetch_assoc();
    $id_negocio = $venta['id_negocio'];
    $id_usuario = $venta['id_usuario'];
    $fecha = $venta['fecha'];
    $total = $venta['total'];
    $solicitud = $venta['solicitud'];
}

// Para los combos
$negocios = $conn->query("SELECT id_negocio, nombre FROM negocios");
$usuarios = $conn->query("SELECT id_usuario, nombre, apellido FROM usuarios");
?>

<h2><?= $id ? "Editar Venta" : "Nueva Venta" ?></h2>

<form method="post" action="ventas_guardar.php">
  <input type="hidden" name="id_venta" value="<?= $id ?>">

  <label>Negocio:</label>
  <select name="id_negocio" class="form-control" required>
    <option value="">Seleccione...</option>
    <?php while ($n = $negocios->fetch_assoc()) { ?>
      <option value="<?= $n['id_negocio'] ?>" <?= ($n['id_negocio']==$id_negocio)?"selected":"" ?>>
        <?= $n['nombre'] ?>
      </option>
    <?php } ?>
  </select><br>

  <label>Usuario:</label>
  <select name="id_usuario" class="form-control" required>
    <option value="">Seleccione...</option>
    <?php while ($u = $usuarios->fetch_assoc()) { ?>
      <option value="<?= $u['id_usuario'] ?>" <?= ($u['id_usuario']==$id_usuario)?"selected":"" ?>>
        <?= $u['nombre']." ".$u['apellido'] ?>
      </option>
    <?php } ?>
  </select><br>

  <label>Fecha:</label>
  <input type="date" name="fecha" class="form-control" value="<?= $fecha ?>" required><br>

  <label>Total:</label>
  <input type="text" name="total" class="form-control" value="<?= $total ?>" required><br>

  <label>Solicitud:</label>
  <input type="text" name="solicitud" class="form-control" value="<?= $solicitud ?>"><br>

  <button type="submit" class="btn btn-success">Guardar</button>
  <a href="ventas_listar.php" class="btn btn-secondary">Cancelar</a>
</form>
