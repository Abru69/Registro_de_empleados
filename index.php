<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Entrada/Salida</title>
  <link rel="stylesheet" href="public/css/estilos.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
 
  <nav class="navbar">
    <div class="navbar-container">
      <h1 class="navbar-logo"></h1>
      <a href="app/views/login.php" class="navbar-btn">Iniciar sesión</a>
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

  <script src="app/controllers/empleadosController.js"></script>
</body>
</html>