<?php
session_start();


$max_intentos = 3;            //  intentos permitidos
$tiempo_bloqueo = 300;        //  bloqueo en segundos (5 minutos)

// Simulación de credenciales (en producción usa hash y base de datos)
$usuario_valido = 'admin';
$contrasena_valida = '1234';

// Inicializar variables de sesión si no existen
if (!isset($_SESSION['intentos'])) {
    $_SESSION['intentos'] = 0;
    $_SESSION['ultimo_intento'] = time();
}

// Verificar si el usuario está bloqueado
if ($_SESSION['intentos'] >= $max_intentos) {
    $tiempo_restante = $tiempo_bloqueo - (time() - $_SESSION['ultimo_intento']);

    if ($tiempo_restante > 0) {
        echo "Demasiados intentos fallidos. Intenta de nuevo en $tiempo_restante segundos.";
        exit;
    } else {
        // Reiniciar intentos después del tiempo de bloqueo
        $_SESSION['intentos'] = 0;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    if ($usuario === $usuario_valido && $contrasena === $contrasena_valida) {
        echo "Inicio de sesión exitoso.";
        $_SESSION['intentos'] = 0; // Reiniciar contador
    } else {
        $_SESSION['intentos']++;
        $_SESSION['ultimo_intento'] = time();
        echo "Credenciales incorrectas. Intento {$_SESSION['intentos']} de $max_intentos.";
    }
}
?>

<!-- Formulario de inicio de sesión -->
<form method="post">
    Usuario: <input type="text" name="usuario" required><br>
    Contraseña: <input type="password" name="contrasena" required><br>
    <button type="submit">Iniciar sesión</button>
</form>
