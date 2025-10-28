<?php
session_start();
if (session_status() === PHP_SESSION_NONE) {
   session_start(); 
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Entrada/Salida</title>
  <link rel="stylesheet" href="public/css/estilos.css">
  <link rel="stylesheet" href="public/css/navbar.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
 

<nav class="navbar" id="navbar">
  <div class="navbar-container">
    <h1 class="navbar-logo">Menu</h1>

    <button class="navbar-toggle" id="navbarToggle" aria-label="Menú" aria-expanded="false">
      <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>

    <div class="navbar-actions" id="navbarMenu">
      <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
      <?php if (isset($_SESSION['usuario'])): ?>
        <a href="app/views/dashboard.php"
           class="navbar-link <?= $currentPage==='dashboard.php'?'active':''; ?>">Panel de Asistencias</a>

        <?php if (!empty($_SESSION['rol']) && $_SESSION['rol']==='admin'): ?>
          <a href="../../index.php"
             class="navbar-link <?= $currentPage==='index.php'?'active':''; ?>">Registrar Empleado</a>
        <?php endif; ?>

        <a href="app/models/logout.php" class="navbar-link logout-btn">Cerrar Sesión</a>
      <?php else: ?>
        <a href="app/views/login.php"
           class="navbar-link <?= $currentPage==='login.php'?'active':''; ?>">Iniciar Sesión</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

  <div class="login">
    <h2>Registro de Asistencia</h2>

    <input type="text" id="nombre" placeholder="Ingrese su nombre" required>
    
    <div class="botones">
      <button id="btnEntrada">Registrar Entrada</button>
      <button id="btnSalida">Registrar Salida</button>
    </div>

    <p id="mensaje"></p>
    <p id="hora-actual"></p>
  </div>

  <script src="public/js/navbar.js" defer></script>
  <script src="app/controllers/empleadosController.js"></script>

</body>
</html>