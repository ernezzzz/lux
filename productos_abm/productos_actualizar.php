<?php
header('Content-Type: application/json');
include("../backend/conexion.php");

$response = ["success" => false, "message" => "Error desconocido"];

try {
    if (!isset($_POST['id_producto'], $_POST['nombre'], $_POST['precio'], $_POST['stock'])) {
        throw new Exception("Faltan campos obligatorios");
    }

    $id          = $_POST['id_producto'];
    // Mantener el negocio actual si no se envía en POST
if (isset($_POST['id_negocio'])) {
    $id_negocio = $_POST['id_negocio'];
} else {
    // Recuperar desde BD
    $stmtNeg = $conn->prepare("SELECT id_negocio FROM productos WHERE id_producto=?");
    $stmtNeg->bind_param("i", $_POST['id_producto']);
    $stmtNeg->execute();
    $resNeg = $stmtNeg->get_result()->fetch_assoc();
    $id_negocio = $resNeg['id_negocio'];
}

    $nombre      = $_POST['nombre'];
    $descripcion = $_POST['descripcion'] ?? '';
    $precio      = $_POST['precio'];
    $stock       = $_POST['stock'];
    $categoria   = $_POST['categoria'] ?? '';

    // Actualizar producto
    $sql = "UPDATE productos 
            SET id_negocio=?, nombre=?, descripcion=?, precio=?, stock=?, categoria=? 
            WHERE id_producto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdisi", $id_negocio, $nombre, $descripcion, $precio, $stock, $categoria, $id);

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar producto: " . $stmt->error);
    }

    // Manejo de imágenes nuevas (si suben)
    if (isset($_FILES['imagenes']) && count($_FILES['imagenes']['name']) > 0) {
        $directorio = "../imagenes/";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        foreach ($_FILES['imagenes']['name'] as $key => $nombreArchivo) {
            if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                $rutaDestino = $directorio . time() . "_" . basename($nombreArchivo);
                if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$key], $rutaDestino)) {
                    $rutaDB = "http://localhost/lux/imagenes/" . basename($rutaDestino);
                    $stmtImg = $conn->prepare("INSERT INTO productos_imagenes (id_producto, ruta) VALUES (?, ?)");
                    $stmtImg->bind_param("is", $id, $rutaDB);
                    $stmtImg->execute();
                }
            }
        }
    }

    $response = ["success" => true, "message" => "Producto actualizado correctamente"];

} catch (Exception $e) {
    $response = ["success" => false, "message" => $e->getMessage()];
}

echo json_encode($response);
