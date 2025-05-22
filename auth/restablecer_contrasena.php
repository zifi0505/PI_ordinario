<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar el token
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset) {
            // Actualizar la contraseña del usuario
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = :password WHERE correo = :email");
            $stmt->execute(['password' => $hashedPassword, 'email' => $reset['email']]);

            // Eliminar el token de la base de datos
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
            $stmt->execute(['token' => $token]);

            $success = "Tu contraseña ha sido restablecida correctamente.";
        } else {
            $error = "El enlace de restablecimiento no es válido o ha expirado.";
        }
    }
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    die("Token no proporcionado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer contraseña</title>
</head>
<body>
  <h2>Restablecer contraseña</h2>
  <?php if (!empty($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p style="color: green;"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>
  <?php if (empty($success)): ?>
    <form method="POST">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <input type="password" name="password" placeholder="Nueva contraseña" required>
      <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
      <button type="submit">Restablecer contraseña</button>
    </form>
  <?php endif; ?>
</body>
</html>