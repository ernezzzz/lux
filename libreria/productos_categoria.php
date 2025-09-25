<?php
include("../backend/conexion.php");

// 1. Validar categoría recibida por GET
$categoria = $_GET['cat'] ?? '';

if (!$categoria) {
    echo "<div class='alert alert-warning'>⚠️ No se especificó categoría</div>";
    exit;
}

// 2. Preparar consulta segura
$stmt = $conn->prepare("SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, n.nombre AS negocio 
                       FROM productos p
                       LEFT JOIN negocios n ON p.id_negocio = n.id_negocio
                       WHERE p.categoria = ?");
$stmt->bind_param("s", $categoria);
$stmt->execute();
$result = $stmt->get_result();

// 3. Mostrar resultados
if ($result->num_rows > 0) {
    echo "<h3>Productos en la categoría: <span class='text-primary'>$categoria</span></h3>";
    echo "<table class='table table-striped table-bordered'>";
    echo "<thead class='table-dark'>
            <tr>
              <th>ID</th>
              <th>Negocio</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Precio</th>
              <th>Stock</th>
            </tr>
          </thead>
          <tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_producto']}</td>
                <td>{$row['negocio']}</td>
                <td>{$row['nombre']}</td>
                <td>{$row['descripcion']}</td>
                <td>{$row['precio']}</td>
                <td>{$row['stock']}</td>
              </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<div class='alert alert-info'>No hay productos en la categoría <b>$categoria</b>.</div>";
}

$stmt->close();
$conn->close();
?>
