<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    
    // Seleccionar también el rol del usuario
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute(['usuario' => $usuario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && password_verify($password, $resultado['password'])) {
        // Guardar usuario Y rol en la sesión
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
