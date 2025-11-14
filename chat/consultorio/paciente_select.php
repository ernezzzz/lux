<?php
// paciente_select.php
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

$res = mysqli_query($conn, "SELECT id_usuario, nombre, apellido, email FROM usuarios WHERE id_rol = (SELECT id_rol FROM rol WHERE rol = 'paciente' LIMIT 1) ORDER BY nombre, apellido");
echo '<option value="">-- Seleccione paciente --</option>';
while($r = mysqli_fetch_assoc($res)) {
  $id = (int)$r['id_usuario'];
  $nombre = htmlspecialchars($r['nombre'] . ' ' . ($r['apellido'] ?? ''));
  $email = $r['email'] ? ' — ' . htmlspecialchars($r['email']) : '';
  echo "<option value=\"$id\">$nombre$email</option>";
}
