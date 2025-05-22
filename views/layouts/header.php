<?php
// views/layouts/header.php

$lang = $_GET['lang'] ?? 'es';
$self = basename($_SERVER['PHP_SELF']);

session_start();

$isLoggedIn = isset($_SESSION['usuario_id']) || isset($_COOKIE['usuario_id']);
$nombreUsuario = $_SESSION['usuario_nombre'] ?? $_COOKIE['usuario_nombre'] ?? '';

$isAdmin = (isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true) || 
           (isset($_COOKIE['es_admin']) && $_COOKIE['es_admin'] === 'true') ||
           (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == 1);

$textos = [
    'es' => [
        'admin_panel' => 'Panel del administrador',
        'cerrar_sesion' => 'Cerrar sesión',
        'iniciar_sesion' => 'Iniciar sesión'
    ],
    'en' => [
        'admin_panel' => 'Administrator panel',
        'cerrar_sesion' => 'Log out',
        'iniciar_sesion' => 'Log in'
    ]
];

$txt = $textos[$lang] ?? $textos['es'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="views/css/header.css">
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const params = new URLSearchParams(window.location.search);
      const urlLang = params.get('lang');
      const savedLang = localStorage.getItem('lang');
      if (savedLang && !urlLang) {
        params.set('lang', savedLang);
        window.location.search = params.toString();
      }
    });
  </script>
</head>
<body>
<header class="site-header">
  <div class="site-header__inner">
    <a href="/PI_ordinario/index.php?lang=<?= htmlspecialchars($lang) ?>" class="logo">
      <img src="/PI_ordinario/views/assets/Iguales.png" alt="" class="logo-img">
      <span class="logo-text" translate="no">Voces Igualitarias</span>
    </a>
    <nav class="main-nav">
      <button id="lang-switcher" class="lang-switch">
        🌐 <?= $lang === 'es' ? 'English' : 'Español' ?>
      </button>
  <?php if ($isLoggedIn): ?>
    <?php 
    $user_id = $_SESSION['usuario_id'] ?? $_COOKIE['usuario_id'] ?? 0;
    $show_admin = ($user_id == 1);
    if ($show_admin):
    ?>
      <a href="/PI_ordinario/admin/admin.php?lang=<?= htmlspecialchars($lang) ?>" class="admin-link">
        <?= $txt['admin_panel'] ?>
      </a>
    <?php endif; ?>

    <div class="user-menu-wrapper">
      <div class="user-info">
        <button class="btn-user">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="white"/>
            <path d="M12 14C7.58172 14 4 17.5817 4 22H20C20 17.5817 16.4183 14 12 14Z" fill="white"/>
          </svg>
        </button>
        <span class="username"><?= htmlspecialchars($nombreUsuario) ?></span>
      </div>
      <div class="user-popup">
        <a href="/PI_ordinario/auth/logout.php?lang=<?= htmlspecialchars($lang) ?>" class="logout">
          <?= $txt['cerrar_sesion'] ?>
        </a>
      </div>
    </div>

    <a href="/PI_ordinario/auth/logout.php?lang=<?= htmlspecialchars($lang) ?>" class="logout-link">
      <?= $txt['cerrar_sesion'] ?>
    </a>
  <?php else: ?>
    <a href="/PI_ordinario/auth/login.php?lang=<?= htmlspecialchars($lang) ?>" class="login-link">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="white"/>
        <path d="M12 14C7.58172 14 4 17.5817 4 22H20C20 17.5817 16.4183 14 12 14Z" fill="white"/>
      </svg>
      <?= $txt['iniciar_sesion'] ?>
    </a>
  <?php endif; ?>
</nav>
  </div>
</header>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('lang-switcher').addEventListener('click', () => {
      const current = new URLSearchParams(window.location.search).get('lang') || 'es';
      const next = current === 'es' ? 'en' : 'es';
      localStorage.setItem('lang', next);
      const params = new URLSearchParams(window.location.search);
      params.set('lang', next);
      window.location.search = params.toString();
    });
    const userMenu = document.querySelector('.user-menu-wrapper');
    if (userMenu) {
      const popup = userMenu.querySelector('.user-popup');
      userMenu.addEventListener('mouseenter', () => popup.classList.add('show'));
      userMenu.addEventListener('mouseleave', () => popup.classList.remove('show'));
    }
  });
</script>
