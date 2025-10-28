<?php
session_start();
require '../../config/db.php';
require '../models/loginModel.php';
require_once '../middleware/auth_check.php';

//si tiene sesion activa, redirigir al dashboard
blockIfAuthenticated();

// Evitar cachear esta página (importante para el botón Atrás)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../../public/css/estilos.css" />
    <link rel="stylesheet" href="../../public/css/navbar.css">

  </head>
  <body>

  <nav class="navbar login-navbar" id="navbar">
    <div class="navbar-container">
      <a href="../../index.php" class="navbar-logo">Menu</a>

      <button class="navbar-toggle" id="navbarToggle" aria-label="Menú" aria-expanded="false">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
      </button>

      <div class="navbar-actions" id="navbarMenu">
        <a href="../../index.php" class="navbar-link back-btn">← Atrás</a>
        <a href="login.php" class="navbar-link active">Iniciar Sesión</a>
      </div>
    </div>
  </nav>

    <div class="login">
      <h2>Iniciar Sesión</h2>

      <form action="../models/loginModel.php" method="POST">
        <input
          type="text"
          name="usuario"
          placeholder="Usuario"
         
          required
        />
        <input
          type="password"
          name="password"
          
          placeholder="Contraseña"
          required
        />
   <?php
        if (!empty($_SESSION['mensaje'])) {
            echo '<p style="color: #e74c3c; background-color: #fadbd8; padding: 12px; border-radius: 5px; text-align: center; margin-top: 15px; border-left: 4px solid #e74c3c;">';
            echo htmlspecialchars($_SESSION['mensaje']);
            echo '</p>';
            unset($_SESSION['mensaje']);
        }
        ?>
        <button type="submit">Entrar</button>
      </form>

      <!-- <p class="login-texto-secundario">
        ¿No tienes cuenta?
        <a href="registro.php" class="login-enlace">Regístrate aquí</a>
      </p> -->
    </div>
    <script src="../../public/js/navbar.js" defer></script>
  </body>
</html>
