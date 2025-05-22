<?php
require_once '../config/db.php'; // Este archivo debe definir $pdo con PDO como ya mostraste

$lang = $_GET['lang'] ?? 'es';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $correo = trim($_POST['correo'] ?? '');
  $usuario = trim($_POST['usuario'] ?? '');
  $contrasena = $_POST['contrasena'] ?? '';
  $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
  $aceptar_tos = isset($_POST['aceptar_tos']);

  // Validaciones básicas
  if (!$correo || !$usuario || !$contrasena || !$confirmar_contrasena) {
    $errores[] = "Todos los campos son obligatorios.";
  }

  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo no es válido.";
  }

  if (strlen($contrasena) < 6) {
    $errores[] = "La contraseña debe tener al menos 6 caracteres.";
  }

  if ($contrasena !== $confirmar_contrasena) {
    $errores[] = "Las contraseñas no coinciden.";
  }

  if (!$aceptar_tos) {
    $errores[] = "Debes aceptar los términos y condiciones.";
  }

  // Verificar si el correo o usuario ya existen
  if (empty($errores)) {
    $sql = "SELECT id_usuario FROM usuarios WHERE correo = :correo OR nombre = :nombre";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':correo' => $correo,
      ':nombre' => $usuario
    ]);
    if ($stmt->fetch()) {
      $errores[] = "El correo o el usuario ya están registrados.";
    }
  }

  // Insertar si no hay errores
  if (empty($errores)) {
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre, correo, contrasena) VALUES (:nombre, :correo, :contrasena)";
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([
      ':nombre' => $usuario,
      ':correo' => $correo,
      ':contrasena' => $hash
    ]);

    if ($resultado) {
      header("Location: login.php?lang=$lang&registro=exito");
      exit();
    } else {
      $errores[] = "Error al registrar. Intenta nuevamente.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registro</title>
  <link rel="stylesheet" href="../views/css/registro.css">
  <link rel="stylesheet" href="../views/css/font/font.css">
  <link rel="stylesheet" href="../views/css/auth-transitions.css">
  <script defer src="../views/js/auth-carousel.js"></script>
  <script defer src="../views/js/auth-transition.js"></script>
</head>
<body>
  <div class="auth-container">
    <!-- IZQ: Formulario -->
    <div class="pane form-pane">
      <h2>Registro</h2>

      <?php if (!empty($errores)): ?>
        <div class="errores">
          <ul>
            <?php foreach ($errores as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach ?>
          </ul>
        </div>
      <?php endif; ?>

      <form action="registro.php?lang=<?= $lang ?>" method="POST">
        <input type="email" name="correo" placeholder="Correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required maxlength="100">
        <input type="text" name="usuario" placeholder="Usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required maxlength="100">
        <input type="password" name="contrasena" placeholder="Contraseña" required minlength="6">
        <input type="password" name="confirmar_contrasena" placeholder="Confirmar contraseña" required minlength="6">
        <div class="tos">
          <input type="checkbox" id="tos-signup" name="aceptar_tos" <?= isset($_POST['aceptar_tos']) ? 'checked' : '' ?> required>
          <label for="tos-signup">Acepto términos y condiciones</label>
        </div>
        <button type="submit" class="btn-signup">Registrarme</button>
      </form>

      <div class="links">
        <a href="login.php?lang=<?= $lang ?>" class="switch">¿Ya tienes cuenta? ¡Inicia sesión!</a>
      </div>
    </div>

    <!-- DER: Carrusel -->
    <div class="pane carousel-pane">
      <?php foreach (range(1,5) as $n): ?>
        <div class="slide <?= $n === 1 ? 'is-active' : '' ?>">
          <img src="../carousel-fotos/<?= $n ?>.jpg" alt="Slide <?= $n ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>