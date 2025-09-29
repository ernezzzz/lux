<?php
include("../backend/conexion.php");

$id_imagen = $_POST['id_imagen'] ?? $_GET['id_imagen'] ?? null;
$id_producto = $_POST['id_producto'] ?? $_GET['id_producto'] ?? null;

if ($id_imagen && $id_producto) {
    $stmt = $conn->prepare("SELECT ruta FROM productos_imagenes WHERE id_imagen = ?");
    $stmt->bind_param("i", $id_imagen);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $imagen = $result->fetch_assoc();
        $ruta = $imagen['ruta'];

        if (file_exists($ruta)) {
            unlink($ruta);
        }

        $stmtDel = $conn->prepare("DELETE FROM productos_imagenes WHERE id_imagen = ?");
        $stmtDel->bind_param("i", $id_imagen);
        $stmtDel->execute();
    }
}

header("Location: productos_form.php?id=" . $id_producto);
exit;
?>
