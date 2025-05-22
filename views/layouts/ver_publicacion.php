<?php
// ver_publicacion.php - Versión corregida

session_start();
require dirname(__DIR__, 2) . '/config/db.php';
require dirname(__DIR__, 2) . '/azure/config.php';
require dirname(__DIR__, 2) . '/azure/azure-translator.php';

$lang = $_GET['lang'] ?? 'es';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de noticia no válido.");
}
$id_noticia = (int)$_GET['id'];

// Recuperar la noticia
$stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id_noticia = :id");
$stmt->execute([':id' => $id_noticia]);
$noticia = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$noticia) {
    die("Noticia no encontrada.");
}

// Procesar el comentario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['usuario_id'])) {
        die("Debes iniciar sesión para comentar.");
    }

    $comentario = trim($_POST['comentario']);
    if (!empty($comentario)) {
        // Priorizar la validación del administrador
        if (isset($_SESSION['admin_id'])) {
            $tipo_usuario = 'admin';
            $id_usuario = $_SESSION['admin_id'];
        } else {
            $tipo_usuario = 'usuario';
            $id_usuario = $_SESSION['usuario_id'];
        }

        $stmt = $pdo->prepare("INSERT INTO comentarios (id_noticia, id_usuario, comentario, fecha_comentario, tipo_usuario) VALUES (:id_noticia, :id_usuario, :comentario, NOW(), :tipo_usuario)");
        $stmt->execute([
            ':id_noticia' => $id_noticia,
            ':id_usuario' => $id_usuario,
            ':comentario' => $comentario,
            ':tipo_usuario' => $tipo_usuario
        ]);
        header("Location: ver_publicacion.php?id=$id_noticia&lang=$lang");
        exit;
    }
}

// Recuperar comentarios existentes
$stmt = $pdo->prepare("
    SELECT c.comentario, c.fecha_comentario, 
           CASE 
               WHEN c.id_usuario = 1 THEN a.nombre
               ELSE u.nombre
           END AS nombre
    FROM comentarios c
    LEFT JOIN admin a ON c.id_usuario = a.id_admin
    LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
    WHERE c.id_noticia = :id_noticia
    ORDER BY c.fecha_comentario DESC
");
$stmt->execute([':id_noticia' => $id_noticia]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($noticia['titular']) ?></title>
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/noticia.css">
  <link rel="stylesheet" href="../css/footer.css">
  <link rel="stylesheet" href="../css/font/font.css">
  <!-- Estilos adicionales para comentarios y título -->
  <style>
    /* Estilos para la sección de comentarios */
    .comentarios-seccion {
      max-width: 700px;
      margin: 40px auto 70px;
      padding: 25px;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
    }
    
    .comentarios-titulo {
      font-size: 1.8rem;
      margin-bottom: 25px;
      color: #333;
      position: relative;
      padding-bottom: 15px;
      border-bottom: 1px solid #e0e0e0;
    }
    
    .comentarios-titulo::after {
      content: "";
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(to right, #00bcd4, #009688);
    }
    
    .comentarios-vacio {
      text-align: center;
      padding: 30px 0;
      color: #666;
      font-style: italic;
    }
    
    .comentar-form {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
    }
    
    .comentar-form textarea {
      width: 100%;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      resize: vertical;
      min-height: 100px;
      margin-bottom: 15px;
      font-family: inherit;
      background-color: #f9f9f9;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    
    .comentar-form textarea:focus {
      border-color: #00bcd4;
      box-shadow: 0 0 0 2px rgba(0, 188, 212, 0.2);
      outline: none;
    }
    
    .comentar-form button {
      background: linear-gradient(to right, #00bcd4, #009688);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .comentar-form button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* Estilo para comentarios individuales */
    .comentario {
      background-color: #f5f9fa;
      border-radius: 15px;
      padding: 15px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
      transition: transform 0.2s ease;
      border-left: 3px solid #00bcd4;
    }
    
    .comentario:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
    
    .comentario p:first-child {
      margin-bottom: 8px;
      color: #555;
      font-size: 0.9rem;
    }
    
    .comentario p:last-child {
      margin-bottom: 0;
      font-size: 1rem;
      line-height: 1.5;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>

<main class="noticia-page">
  <section class="encabezado">
    <h1><?= htmlspecialchars($noticia['titular']) ?></h1>
  </section>

  <section class="descripcion-centrada">
    <p><?= nl2br(htmlspecialchars($noticia['descripcion_corta'])) ?></p>
  </section>

  <?php if (!empty($noticia['imagen_principal'])): ?>
    <section class="noticia-figure">
      <img class="imagen-principal" src="../../uploads/<?= htmlspecialchars($noticia['imagen_principal']) ?>" alt="Imagen principal">
    </section>
  <?php endif; ?>

  <section class="contenido">
    <?= nl2br(htmlspecialchars($noticia['contenido'])) ?>
  </section>

  <section class="fecha-centrada">
    <p><strong></strong> <?= date("d/m/Y", strtotime($noticia['fecha'])) ?></p>
  </section>

  <?php if (!empty($noticia['referencia'])): ?>
    <section class="referencia">
      <h3>Referencia</h3>
      <a href="<?= htmlspecialchars($noticia['referencia']) ?>" target="_blank">
        <?= htmlspecialchars($noticia['referencia']) ?>
      </a>
    </section>
  <?php endif; ?>

  <!-- Sección de comentarios mejorada -->
  <section class="comentarios-seccion">
    <h2 class="comentarios-titulo">Comentarios</h2>

    <?php if (!empty($comentarios)): ?>
      <div class="comentarios-lista">
          <?php foreach ($comentarios as $comentario): ?>
              <div class="comentario">
                  <p><strong><?= htmlspecialchars($comentario['nombre']) ?></strong> - <?= date("d/m/Y H:i", strtotime($comentario['fecha_comentario'])) ?></p>
                  <p><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
              </div>
          <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="comentarios-vacio">
        Todavía no hay comentarios existentes. ¡Sé el primero en comentar!
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['usuario_id']) || isset($_SESSION['admin_id'])): ?>
      <form class="comentar-form" method="POST">
        <textarea name="comentario" placeholder="Escribe tu comentario aquí..." required></textarea>
        <button type="submit">Publicar comentario</button>
      </form>
    <?php else: ?>
      <div class="comentarios-vacio">
        Debes <a href="/Poder-Igualitario/auth/login.php?lang=<?= htmlspecialchars($lang) ?>">iniciar sesión</a> para comentar.
      </div>
    <?php endif; ?>
  </section>

  <?php if (!empty($noticia['imagenes'])): ?>
    <section class="imagenes">
      <h2>Galería de imágenes</h2>
      <div class="galeria">
        <?php foreach (explode(',', $noticia['imagenes']) as $img): ?>
          <img src="<?= htmlspecialchars(trim($img)) ?>" alt="">
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php include __DIR__ . '/footer.php'; ?>
</main>

</body>
</html>

<?php
$content = ob_get_clean();
echo ($lang === 'en') ? azureTranslate($content, 'en', 'es', true) : $content;
?>