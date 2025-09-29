<?php
include("../backend/conexion.php");

$id = $_POST['id_producto'] ?? null;
$id_negocio = $_POST['id_negocio'];
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];
$categoria = $_POST['categoria'];

// Si es edición
if ($id) {
    $sql = "UPDATE productos 
            SET id_negocio=?, nombre=?, descripcion=?, precio=?, stock=?, categoria=?
            WHERE id_producto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdisi", $id_negocio, $nombre, $descripcion, $precio, $stock, $categoria, $id);
} else {
    // Si es nuevo
    $sql = "INSERT INTO productos (id_negocio, nombre, descripcion, precio, stock, categoria)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdis", $id_negocio, $nombre, $descripcion, $precio, $stock, $categoria);
}

if ($stmt->execute()) {
    // Tomar ID del producto recién insertado (o el editado)
    $producto_id = $id ? $id : $stmt->insert_id;

    // Manejo de múltiples imágenes
    if (isset($_FILES['imagenes']) && count($_FILES['imagenes']['name']) > 0) {
        $directorio = "uploads/";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        foreach ($_FILES['imagenes']['name'] as $key => $nombreArchivo) {
            if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                $rutaDestino = $directorio . time() . "_" . basename($nombreArchivo);
                if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$key], $rutaDestino)) {
                    $stmtImg = $conn->prepare("INSERT INTO productos_imagenes (id_producto, ruta) VALUES (?, ?)");
                    $stmtImg->bind_param("is", $producto_id, $rutaDestino);
                    $stmtImg->execute();
                }
            }
        }
    }

    header("Location: productos_listar.php");
    exit;
} else {
    echo "Error: " . $conn->error;
}
?>
