<?php
// index.php - Proyecto PI_ordinario (tema: Automóviles)

require __DIR__ . '/config/db.php';                  // Conexión a MySQL
require __DIR__ . '/azure/config.php';               // Constantes Azure
require __DIR__ . '/azure/azure-translator.php';     // Función de traducción

// Idioma solicitado
$lang = $_GET['lang'] ?? 'es';

// Traer publicaciones (siempre en ES)
try {
  $sql  = "SELECT id_noticia, fecha, titular, descripcion_corta, imagen_principal
           FROM publicaciones
           WHERE archivada = 0
           ORDER BY fecha DESC";
  $stmt = $pdo->query($sql);
  $pubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error al obtener publicaciones: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PI_ordinario | Mundo Automotriz</title>

  <!-- Estilos y Swiper -->
  <link rel="stylesheet" href="views/css/index.css">
  <link rel="stylesheet" href="views/css/header.css">
  <link rel="stylesheet" href="views/css/footer.css">
  <link rel="stylesheet" href="views/css/font/font.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/9.3.2/swiper-bundle.min.css" />

  <style>
    /* Estilos personalizados para tema de autos */
    .featured-message {
      width: 80%;
      margin: 20px auto;
      padding: 25px;
      background-color: rgba(255, 255, 255, 0.85);
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      text-align: center;
      position: relative;
      z-index: 2;
    }

    .featured-message h3 {
      color: #009193;
      margin-bottom: 10px;
      font-size: 1.8em;
    }

    .featured-message p {
      font-size: 1.2em;
      line-height: 1.6;
      color: #333;
    }

    .modern-slider { width: 85%; margin: 0 auto 30px; border-radius: 12px; overflow: hidden; position: relative; height: 350px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); }
    .modern-slider img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1s ease; }
    .modern-slider img.active { opacity: 1; }
    .slider-controls, .slider-dot, .slider-arrow, .swiper-container, .swiper-slide, .card-image, .card-content, .card-title, .card-description, .card-date, .card-more, .swiper-button-next, .swiper-button-prev, .swiper-pagination-bullet, .swiper-pagination-bullet-active {
      /* Puedes mantener los estilos anteriores que ya tienes */
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/views/layouts/header.php'; ?>

  <section class="featured-message">
    <h3>Bienvenidos a PI_ordinario</h3>
    <p>Explora las últimas noticias, reseñas, y novedades del mundo automotriz.</p>
  </section>

  <section class="modern-slider">
    <?php
      $imgs = glob(__DIR__ . '/carousel-fotos/*.jpg');
      shuffle($imgs);
      $imgs = array_slice($imgs, 0, 4);
      foreach ($imgs as $i => $file):
        $url = basename($file);
    ?>
      <img src="carousel-fotos/<?= $url ?>" class="<?= $i === 0 ? 'active' : '' ?>">
    <?php endforeach; ?>

    <div class="slider-controls">
      <?php for($i = 0; $i < count($imgs); $i++): ?>
        <div class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></div>
      <?php endfor; ?>
    </div>
    <div class="slider-arrow slider-prev">&#10094;</div>
    <div class="slider-arrow slider-next">&#10095;</div>
  </section>

<?php ob_start(); ?>

  <main>
    <h3 class="section-title">Noticias del Mundo Automotriz</h3>
    <div class="swiper-container">
      <div class="swiper-wrapper">
        <?php foreach ($pubs as $pub): ?>
          <div class="swiper-slide">
            <a href="views/layouts/ver_publicacion.php?id=<?= $pub['id_noticia'] ?>&lang=<?= $lang ?>">
              <img src="uploads/<?= htmlspecialchars($pub['imagen_principal']) ?>" alt="" class="card-image">
              <div class="card-content">
                <h5 class="card-title"><?= htmlspecialchars($pub['titular']) ?></h5>
                <p class="card-description"><?= htmlspecialchars($pub['descripcion_corta']) ?></p>
                <span class="card-date">
                  <?= ($lang === 'es') ? date("d/m/Y", strtotime($pub['fecha'])) : date("m/d/Y", strtotime($pub['fecha'])); ?>
                </span>
                <div class="card-more">Leer más...</div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
      <div class="swiper-pagination"></div>
    </div>
  </main>

  <?php include __DIR__ . '/views/layouts/footer.php'; ?>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/9.3.2/swiper-bundle.min.js"></script>
  <script>
    // Slider de imágenes (automático + controles)
    document.addEventListener('DOMContentLoaded', () => {
      const slides = document.querySelectorAll('.modern-slider img');
      const dots = document.querySelectorAll('.slider-dot');
      const prevBtn = document.querySelector('.slider-prev');
      const nextBtn = document.querySelector('.slider-next');
      let currentIndex = 0;

      function changeSlide(index) {
        slides[currentIndex].classList.remove('active');
        dots[currentIndex].classList.remove('active');
        currentIndex = (index + slides.length) % slides.length;
        slides[currentIndex].classList.add('active');
        dots[currentIndex].classList.add('active');
      }

      let slideInterval = setInterval(() => changeSlide(currentIndex + 1), 3000);
      prevBtn.onclick = () => { clearInterval(slideInterval); changeSlide(currentIndex - 1); };
      nextBtn.onclick = () => { clearInterval(slideInterval); changeSlide(currentIndex + 1); };
      dots.forEach((dot, i) => dot.onclick = () => { clearInterval(slideInterval); changeSlide(i); });

      new Swiper('.swiper-container', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        autoplay: { delay: 4000, disableOnInteraction: false },
        breakpoints: {
          640: { slidesPerView: 2 },
          768: { slidesPerView: 3 },
          1024: { slidesPerView: 4 }
        }
      });
    });
  </script>
</body>
</html>
<?php
$content = ob_get_clean();
echo ($lang === 'en') ? azureTranslate($content, 'en', 'es', true) : $content;
?>
