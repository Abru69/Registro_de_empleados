<?php
// Verificar si la sesión está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['usuario']) && isset($_SESSION['rol']);
}

// Función para verificar si el usuario es admin
function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

// Función para requerir autenticación
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['mensaje'] = "Debes iniciar sesión para acceder a esta página.";
        header("Location: ../views/login.php");
        exit;
    }
}

// Función para requerir rol de administrador
function requireAdmin() {
    if (!isAuthenticated()) {
        $_SESSION['mensaje'] = "Debes iniciar sesión para acceder a esta página.";
        header("Location: ../views/login.php");
        exit;
    }
    
    if (!isAdmin()) {
        $_SESSION['mensaje'] = "No tienes permisos para acceder a esta página. Solo administradores.";
        header("Location: ../views/dashboard.php");
        exit;
    }
}

// Función para bloquear acceso si ya está autenticado (para login/registro)
function blockIfAuthenticated() {
    if (isAuthenticated()) {
        header("Location: ../views/dashboard.php");
        exit;
    }
}
?>
