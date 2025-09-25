<?php
include("../backend/conexion.php");

$id = $_GET['id'];
$sql = "DELETE FROM productos WHERE id_producto=$id";

if ($conn->query($sql)) {
    header("Location: productos_listar.php");
} else {
    echo "Error: " . $conn->error;
}
?>
