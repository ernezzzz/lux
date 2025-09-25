<?php
session_start();
include 'conexion.php'; // Asegúrate de tener una conexión válida en este archivo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['nombre']);
    $password = $_POST['password'];

    // Preparar la consulta para evitar inyecciones SQL
    $stmt = $conn->prepare("SELECT id_usuario, nombre, password FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuarioData = $resultado->fetch_assoc();

        // Verificar contraseña
        if ($password === $usuarioData['password']) {
            // Autenticación exitosa
            $_SESSION['id_usuario'] = $usuarioData['id_usuario'];
            $_SESSION['nombre'] = $usuarioData['nombre'];
        
            header('Location: ../admin/admin.php');
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }        
    } else {
        $error = "Usuario no encontrado.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!-- Mostrar mensaje de error si existe -->
<?php if (isset($error)): ?>
  <div class="container mt-4">
    <div class="alert alert-danger text-center" role="alert">
      <?= htmlspecialchars($error) ?>
    </div>
  </div>
<?php endif; ?>
