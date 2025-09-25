<?php
include("../backend/conexion.php");

$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.categoria, n.nombre AS negocio
        FROM productos p
        LEFT JOIN negocios n ON p.id_negocio = n.id_negocio";
$result = $conn->query($sql);
?>

<h2>Listado de Productos</h2>
<a href="productos_form.php">➕ Agregar Producto</a>
<table  cellpadding="8">
  <tr>
    <th>ID</th>
    <th>Negocio</th>
    <th>Nombre</th>
    <th>Descripción</th>
    <th>Precio</th>
    <th>Stock</th>
    <th>Categoría</th>
    <th>Acciones</th>
  </tr>

<?php while($row = $result->fetch_assoc()) { ?>
  <tr>
    <td><?= $row['id_producto'] ?></td>
    <td><?= $row['negocio'] ?></td>
    <td><?= $row['nombre'] ?></td>
    <td><?= $row['descripcion'] ?></td>
    <td><?= $row['precio'] ?></td>
    <td><?= $row['stock'] ?></td>
    <td><?= $row['categoria'] ?></td>
    <td>
      <a href="productos_form.php?id=<?= $row['id_producto'] ?>">✏️ Editar</a> |
      <a href="productos_borrar.php?id=<?= $row['id_producto'] ?>" onclick="return confirm('¿Seguro de eliminar?')">🗑 Eliminar</a>
    </td>
  </tr>
<?php } ?>
</table>
