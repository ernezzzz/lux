<?php
include("../../backend/conexion.php");

$id = $_POST['id_venta'] ?? null;
$id_negocio = $_POST['id_negocio'];
$id_usuario = $_POST['id_usuario'];
$fecha = $_POST['fecha'];
$total = $_POST['total'];
$solicitud = $_POST['solicitud'];

if ($id) {
    // UPDATE
    $sql = "UPDATE ventas 
            SET id_negocio='$id_negocio', id_usuario='$id_usuario', fecha='$fecha', 
                total='$total', solicitud='$solicitud'
            WHERE id_venta=$id";
} else {
    // INSERT
    $sql = "INSERT INTO ventas (id_negocio, id_usuario, fecha, total, solicitud)
            VALUES ('$id_negocio', '$id_usuario', '$fecha', '$total', '$solicitud')";
}

if ($conn->query($sql)) {
    header("Location: ventas_listar.php");
} else {
    echo "Error: " . $conn->error;
}
?>
