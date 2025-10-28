<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../config/db.php';

// Si ya hay sesión y alguien intenta POST a login con usuario distinto:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario'])) {
    $usuarioPost = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    if ($usuarioPost !== '' && $usuarioPost !== $_SESSION['usuario']) {
        $_SESSION['mensaje'] = "Ya tienes una sesión activa como '{$_SESSION['usuario']}'. Cierra sesión para cambiar de usuario.";
        header("Location: ../views/dashboard.php");
        exit;
    } elseif ($usuarioPost === $_SESSION['usuario']) {
        // Si es el mismo usuario, simplemente redirige al dashboard
        header("Location: ../views/dashboard.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute(['usuario' => $usuario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado && password_verify($password, $resultado['password'])) {
        // Regenerar ID de sesión al iniciar sesión correctamente
        session_regenerate_id(true);

        $_SESSION['usuario'] = $usuario;
        $_SESSION['rol'] = $resultado['rol'];
        $_SESSION['user_id'] = $resultado['id'];

        unset($_SESSION['mensaje']);
        header("Location: ../views/dashboard.php");
        exit;
    } else {
        $_SESSION['mensaje'] = "Usuario o contraseña incorrectos.";
        header("Location: ../views/login.php");
        exit;
    }
}
?>
