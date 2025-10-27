<?php
include '../../config/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
    $stmt->execute(['usuario' => $usuario]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && password_verify($password, $resultado['password'])) {
        $_SESSION['usuario'] = $usuario;
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
