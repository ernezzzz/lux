<?php
session_start();
// include 'backend/checklogin.php'; // <-- QUITAR: evita el redirect automático en la página de login
include 'backend/conexion.php'; // Asegúrate de tener la conexión en $conn

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

            // Redirección según rol y negocio
            $id_negocio = (int)$usuarioData['id_negocio'];

            if ($usuarioData['id_rol'] == 1) {
                header('Location: admin.php');
            } else {
                // Mapa de redirecciones por id_negocio — ajustá los ids/rutas si hace falta
                $redirectMap = [
                    2 => 'clinica/dashboard.php',
                    2 => 'tecnologia/dashboard.php',
                    3 => 'farmacia/dashboard.php',   // farmacia (id 3)
                    4 => 'libreria/dashboard.php',   // librería (ejemplo id 4)
                    // agregar más mapeos si tenés otros negocios
                ];
                $target = $redirectMap[$id_negocio] ?? 'libreria/dashboard.php';
                header('Location: ' . $target);
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
