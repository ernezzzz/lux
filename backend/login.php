<?php
session_start();
include 'conexion.php'; // Asegúrate de tener la conexión en $conn

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['nombre']);
    $password = $_POST['password'];

    // Buscar usuario
    $stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, email, password, id_rol, id_negocio 
                            FROM usuarios 
                            WHERE nombre = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuarioData = $resultado->fetch_assoc();

        // ⚠️ Si usás contraseñas con hash -> usar password_verify()
        if ($password === $usuarioData['password']) {

            // Guardamos en sesión
            $_SESSION['id_usuario'] = $usuarioData['id_usuario'];
            $_SESSION['nombre'] = $usuarioData['nombre'];
            $_SESSION['id_rol'] = $usuarioData['id_rol'];
            $_SESSION['id_negocio'] = $usuarioData['id_negocio'];

            // Redirección según rol
            if ($usuarioData['id_rol'] == 1 || $usuarioData['id_rol'] == 2) {
                // Admin general o admin de negocio
                header('Location: ../libreria/dashboard.php');
            } elseif ($usuarioData['id_rol'] == 3) {
                header('Location: cliente_dashboard.php');
            } elseif ($usuarioData['id_rol'] == 4) {
                header('Location: empleado_dashboard.php');
            } elseif ($usuarioData['id_rol'] == 5) {
                header('Location: medico_dashboard.php');
            } else {
                $error = "Rol no reconocido.";
            }
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

<!-- Mostrar error si existe -->
<?php if (isset($error)): ?>
  <div class="container mt-4">
    <div class="alert alert-danger text-center" role="alert">
      <?= htmlspecialchars($error) ?>
    </div>
  </div>
<?php endif; ?>
