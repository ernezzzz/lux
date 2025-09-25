<?php
include("../backend/conexion.php");

$id = $_POST['id_producto'] ?? null;
$id_negocio = $_POST['id_negocio'];
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];
$categoria = $_POST['categoria'];

if ($id) {
    // UPDATE
    $sql = "UPDATE productos 
            SET id_negocio='$id_negocio', nombre='$nombre', descripcion='$descripcion',
                precio='$precio', stock='$stock', categoria='$categoria'
            WHERE id_producto=$id";
} else {
    // INSERT
    $sql = "INSERT INTO productos (id_negocio, nombre, descripcion, precio, stock, categoria)
            VALUES ('$id_negocio', '$nombre', '$descripcion', '$precio', '$stock', '$categoria')";
}

if ($conn->query($sql)) {
    header("Location: productos_listar.php");
} else {
    echo "Error: " . $conn->error;
}
?>
