<?php
include("../backend/conexion.php");

// Si viene con ID, es edición
$id = $_GET['id'] ?? null;
$nombre = $descripcion = $precio = $stock = $categoria = "";
$id_negocio = "";

if ($id) {
    $sql = "SELECT * FROM productos WHERE id_producto = $id";
    $result = $conn->query($sql);
    $producto = $result->fetch_assoc();

    $id_negocio = $producto['id_negocio'];
    $nombre = $producto['nombre'];
    $descripcion = $producto['descripcion'];
    $precio = $producto['precio'];
    $stock = $producto['stock'];
    $categoria = $producto['categoria'];
}

// Negocios para combo
$negocios = $conn->query("SELECT * FROM negocios");
?>

<h2><?= $id ? "Editar" : "Agregar" ?> Producto</h2>

<form method="post" action="productos_guardar.php">
    <input type="hidden" name="id_producto" value="<?= $id ?>">

    <label>Negocio:</label>
    <select name="id_negocio" required>
      <option value="">Seleccione...</option>
      <?php while($n = $negocios->fetch_assoc()) { ?>
        <option value="<?= $n['id_negocio'] ?>" <?= ($n['id_negocio']==$id_negocio)?"selected":"" ?>>
          <?= $n['nombre'] ?>
        </option>
      <?php } ?>
    </select><br><br>

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= $nombre ?>" required><br><br>

    <label>Descripción:</label>
    <textarea name="descripcion"><?= $descripcion ?></textarea><br><br>

    <label>Precio:</label>
    <input type="text" name="precio" value="<?= $precio ?>" required><br><br>

    <label>Stock:</label>
    <input type="number" name="stock" value="<?= $stock ?>" required><br><br>

    <label>Categoría:</label>
    <input type="text" name="categoria" value="<?= $categoria ?>"><br><br>

    <button type="submit">Guardar</button>
    <a href="productos_listar.php">Cancelar</a>
</form>
