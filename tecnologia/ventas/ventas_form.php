<?php
include("../../backend/conexion.php");

$id = $_GET['id'] ?? null;
$fecha = $total = $solicitud = "";
$id_usuario = $id_negocio = "";

// Si es edición, cargo datos
if ($id) {
    $sql = "SELECT * FROM ventas WHERE id_venta = $id";
    $venta = $conn->query($sql)->fetch_assoc();
    $fecha = $venta['fecha'];
    $total = $venta['total'];
    $solicitud = $venta['solicitud'];
    $id_usuario = $venta['id_usuario'];
    $id_negocio = $venta['id_negocio'];
}

// Listas para selects
$usuarios = $conn->query("SELECT id_usuario, CONCAT(nombre,' ',apellido) as nombre FROM usuarios WHERE id_rol = 3"); // clientes
$negocios = $conn->query("SELECT * FROM negocios");
$productos = $conn->query("SELECT id_producto, nombre, precio FROM productos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= $id ? "Editar" : "Nueva" ?> Venta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .producto-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .producto-row select, .producto-row input { flex:1; }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">

  <h2><?= $id ? "✏️ Editar Venta" : "➕ Nueva Venta" ?></h2>

  <form method="post" action="ventas_guardar.php">
    <input type="hidden" name="id_venta" value="<?= $id ?>">

    <div class="mb-3">
      <label>Cliente:</label>
      <select name="id_usuario" class="form-select" required>
        <option value="">Seleccione cliente...</option>
        <?php while($u = $usuarios->fetch_assoc()) { ?>
          <option value="<?= $u['id_usuario'] ?>" <?= ($u['id_usuario']==$id_usuario)?"selected":"" ?>>
            <?= $u['nombre'] ?>
          </option>
        <?php } ?>
      </select>
    </div>

    <div class="mb-3">
      <label>Negocio:</label>
      <select name="id_negocio" class="form-select" required>
        <option value="">Seleccione negocio...</option>
        <?php while($n = $negocios->fetch_assoc()) { ?>
          <option value="<?= $n['id_negocio'] ?>" <?= ($n['id_negocio']==$id_negocio)?"selected":"" ?>>
            <?= $n['nombre'] ?>
          </option>
        <?php } ?>
      </select>
    </div>

    <div class="mb-3">
      <label>Método de pago:</label>
      <select name="solicitud" class="form-select" required>
        <option value="Efectivo" <?= $solicitud=="Efectivo"?"selected":"" ?>>Efectivo</option>
        <option value="Tarjeta" <?= $solicitud=="Tarjeta"?"selected":"" ?>>Tarjeta</option>
        <option value="Transferencia" <?= $solicitud=="Transferencia"?"selected":"" ?>>Transferencia</option>
      </select>
    </div>

    <h5>🛒 Productos</h5>
    <div id="productos-container">
      <!-- filas dinámicas de productos -->
      <div class="producto-row">
        <select name="productos[]" class="form-select" required>
          <option value="">Seleccione producto...</option>
          <?php
          $productos->data_seek(0); // reiniciar puntero
          while($p = $productos->fetch_assoc()) { ?>
            <option value="<?= $p['id_producto'] ?>" data-precio="<?= $p['precio'] ?>">
              <?= $p['nombre'] ?> - $<?= $p['precio'] ?>
            </option>
          <?php } ?>
        </select>
        <input type="number" name="cantidades[]" class="form-control" placeholder="Cantidad" min="1" value="1" required>
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-secondary my-2" onclick="agregarProducto()">➕ Agregar producto</button>

    <div class="mb-3">
      <label>Total:</label>
      <input type="text" id="total" name="total" class="form-control" value="<?= $total ?>" readonly>
    </div>

    <button type="submit" class="btn btn-success">💾 Guardar</button>
    <a href="ventas_listar.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>

<script>
function agregarProducto() {
  const container = document.getElementById("productos-container");
  const row = container.firstElementChild.cloneNode(true);
  row.querySelectorAll("input").forEach(i => i.value = 1);
  row.querySelector("select").selectedIndex = 0;
  container.appendChild(row);
}

document.addEventListener("input", function() {
  let total = 0;
  document.querySelectorAll("#productos-container .producto-row").forEach(row => {
    const select = row.querySelector("select");
    const cantidad = parseInt(row.querySelector("input").value) || 0;
    const precio = parseFloat(select.selectedOptions[0]?.getAttribute("data-precio")) || 0;
    total += cantidad * precio;
  });
  document.getElementById("total").value = total.toFixed(2);
});
</script>
</body>
</html>
