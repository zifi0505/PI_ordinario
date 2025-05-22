<?php
// auth/logout.php
session_start();

// Obtener el idioma actual para redireccionar
$lang = $_GET['lang'] ?? 'es';

// Limpiar todas las variables de sesión
$_SESSION = [];

// Eliminar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"] ?? false, $params["httponly"] ?? false
    );
}

// Destruir la sesión
session_destroy();

// Eliminar cookies relacionadas con la autenticación
$expired = time() - 3600;
setcookie('usuario_id', '', $expired, '/');
setcookie('usuario_nombre', '', $expired, '/');
setcookie('es_admin', '', $expired, '/');

// Redirigir al inicio del nuevo proyecto
header("Location: /PI_ordinario/index.php?lang=" . urlencode($lang));
exit;
