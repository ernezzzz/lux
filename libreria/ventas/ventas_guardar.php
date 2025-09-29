<?php
include("../../backend/conexion.php");

// Datos del formulario
$id_venta   = $_POST['id_venta'] ?? null;
$id_usuario = $_POST['id_usuario'];
$id_negocio = $_POST['id_negocio'];
$solicitud  = $_POST['solicitud'];
$total      = $_POST['total'];
$productos  = $_POST['productos'] ?? [];
$cantidades = $_POST['cantidades'] ?? [];
$fecha      = date("Y-m-d H:i:s");

// Si no hay productos, cancelamos
if (empty($productos)) {
    die("⚠️ Debes seleccionar al menos un producto.");
}

if ($id_venta) {
    // --- UPDATE (editar venta) ---
    $stmt = $conn->prepare("UPDATE ventas 
        SET id_usuario=?, id_negocio=?, fecha=?, total=?, solicitud=?
        WHERE id_venta=?");
    $stmt->bind_param("iisdsi", $id_usuario, $id_negocio, $fecha, $total, $solicitud, $id_venta);
    $stmt->execute();

    // Borro detalles previos
    $conn->query("DELETE FROM ventas_detalle WHERE id_venta = $id_venta");

} else {
    // --- INSERT (nueva venta) ---
// Insertar cabecera
$stmt = $conn->prepare("INSERT INTO ventas (id_negocio, id_usuario, fecha, total, metodo_pago) 
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisds", $id_negocio, $id_usuario, $fecha, $total, $metodo_pago);

if ($stmt->execute()) {
    $id_venta = $stmt->insert_id;
} else {
    die("Error al guardar la venta: " . $conn->error);
}


// --- Insertar productos en ventas_detalle ---
$stmtDetalle = $conn->prepare("INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_unitario, subtotal)
    VALUES (?, ?, ?, ?, ?)");

foreach ($productos as $i => $id_producto) {
    $cantidad = (int) $cantidades[$i];

    // Obtener precio actual del producto
    $resPrecio = $conn->query("SELECT precio FROM productos WHERE id_producto = $id_producto");
    $precioUnitario = $resPrecio->fetch_assoc()['precio'] ?? 0;

    $subtotal = $cantidad * $precioUnitario;

    $stmtDetalle->bind_param("iiidd", $id_venta, $id_producto, $cantidad, $precioUnitario, $subtotal);
    $stmtDetalle->execute();
}

// Redirigir al listado
}
header("Location: ventas_listar.php");
exit;
?>
