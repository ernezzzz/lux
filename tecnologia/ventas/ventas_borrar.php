<?php
include("../../backend/conexion.php");

$id = $_GET['id'] ?? null;
if ($id) {
    $sql = "DELETE FROM ventas WHERE id_venta=$id";
    if ($conn->query($sql)) {
        header("Location: ventas_listar.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
