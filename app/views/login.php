<?php
session_start();
require '../../config/db.php';
require '../models/loginModel.php';

?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../../public/css/estilos.css" />
  </head>
  <body>

    <nav class="navbar">
      <div class="navbar-container">
        <a href="../../index.php" class="navbar-logo">Atras</a>
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
  </body>
</html>
