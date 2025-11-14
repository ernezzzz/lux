<?php
// consultorio/paciente_guardar_ajax.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  // Recibir datos
  $id_usuario = isset($_POST['id_usuario']) && $_POST['id_usuario'] !== '' ? intval($_POST['id_usuario']) : 0;
  $nombre = trim($_POST['nombre'] ?? '');
  $apellido = trim($_POST['apellido'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $id_rol = isset($_POST['id_rol']) ? intval($_POST['id_rol']) : 6;
  $id_negocio = isset($_POST['id_negocio']) ? intval($_POST['id_negocio']) : 1;

  if ($nombre === '' || $apellido === '') {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'Nombre y apellido son obligatorios.']);
    exit;
  }

  // validar email si fue enviado
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'Email con formato inválido.']);
    exit;
  }

  // 1) verificar email único (si se envió)
  if ($email !== '') {
    $stmt = mysqli_prepare($conn, "SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $foundId = 0;
    if (mysqli_stmt_num_rows($stmt) > 0) {
      mysqli_stmt_bind_result($stmt, $foundId);
      mysqli_stmt_fetch($stmt);
    }
    mysqli_stmt_close($stmt);

    if ($foundId && $foundId != $id_usuario) {
      http_response_code(409); // conflicto
      echo json_encode(['success'=>false, 'error'=>'El email ya está registrado en otro paciente.']);
      exit;
    }
  }

  // 2) buscar coincidencias por nombre+apellido (para generar warning si corresponde)
  $warning = '';
  $stmt = mysqli_prepare($conn, "SELECT id_usuario, email FROM usuarios WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) AND LOWER(TRIM(apellido)) = LOWER(TRIM(?))" . ($id_usuario ? " AND id_usuario <> ?" : "") . " LIMIT 1");
  if ($id_usuario) {
    mysqli_stmt_bind_param($stmt, 'ssi', $nombre, $apellido, $id_usuario);
  } else {
    mysqli_stmt_bind_param($stmt, 'ss', $nombre, $apellido);
  }
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);
  if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_bind_result($stmt, $foundSameId, $foundSameEmail);
    mysqli_stmt_fetch($stmt);
    // si existe otro con mismo nombre/apellido
    if ($foundSameId) {
      if ($email === '') {
        $warning = 'Existe un paciente con el mismo nombre y apellido. Considerá agregar un email para diferenciarlos.';
      } else {
        // si el otro tiene email distinto, permitimos la creación/actualización pero avisamos
        if (trim(strtolower($foundSameEmail)) !== trim(strtolower($email))) {
          $warning = 'Existe otro paciente con el mismo nombre y apellido; se guardó un registro separado ya que el email difiere.';
        } else {
          // mismo nombre+apellido y mismo email -> es el mismo registro (o duplicado exacto)
          // si foundSameId != id_usuario (y email igual), podemos decidir devolver warning o error.
          // Aquí permitimos actualizar/retornar éxito (siempre que foundSameId == id_usuario o estamos actualizando).
        }
      }
    }
  }
  mysqli_stmt_close($stmt);

  // 3) Insertar o actualizar usando prepared statements
  if ($id_usuario) {
    // UPDATE
    $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, id_rol = ?, id_negocio = ? WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssiii', $nombre, $apellido, $email, $id_rol, $id_negocio, $id_usuario);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok === false) {
      $err = mysqli_error($conn);
      mysqli_stmt_close($stmt);
      throw new Exception('Error al actualizar: ' . $err);
    }
    mysqli_stmt_close($stmt);
    $result_id = $id_usuario;
  } else {
    // INSERT
    // ajustar columnas según tu tabla; asumimos que existen: nombre, apellido, email, id_rol, id_negocio
    $sql = "INSERT INTO usuarios (nombre, apellido, email, id_rol, id_negocio) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssii', $nombre, $apellido, $email, $id_rol, $id_negocio);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok === false) {
      $err = mysqli_error($conn);
      mysqli_stmt_close($stmt);
      throw new Exception('Error al insertar: ' . $err);
    }
    $result_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
  }

  $nombre_completo = trim($nombre . ' ' . $apellido);

  $out = ['success'=>true, 'id'=>(int)$result_id, 'nombre_completo'=>$nombre_completo, 'email'=>$email];
  if ($warning) $out['warning'] = $warning;

  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  http_response_code(500);
  echo json_encode(['success'=>false, 'error'=>$ex->getMessage()]);
  exit;
}


