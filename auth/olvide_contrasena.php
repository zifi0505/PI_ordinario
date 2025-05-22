<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['success'] = false;
        $response['error'] = "Por favor, ingresa un correo válido.";
    } else {
        // Verificar si el correo existe en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generar un token único
            $token = bin2hex(random_bytes(32));

            // Guardar el token en la base de datos
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (:email, :token)");
            $stmt->execute(['email' => $email, 'token' => $token]);

            // Enviar el correo con el enlace de restablecimiento
            $resetLink = "http://yourdomain.com/auth/restablecer_contrasena.php?token=$token";
            $subject = "Restablecimiento de contraseña";
            $message = "Hola, haz clic en el siguiente enlace para restablecer tu contraseña: $resetLink";
            $headers = "From: no-reply@yourdomain.com";

            mail($email, $subject, $message, $headers);

            $response['success'] = true;
            $response['message'] = "Se ha enviado un enlace de restablecimiento a tu correo.";
        } else {
            $response['success'] = false;
            $response['error'] = "No se encontró una cuenta con ese correo.";
        }
    }
} else {
    $response['success'] = false;
    $response['error'] = "Método no permitido.";
}

echo json_encode($response);