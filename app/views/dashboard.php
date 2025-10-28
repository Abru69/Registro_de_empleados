<?php
require_once '../middleware/auth_check.php';
requireAuth();

include '../../config/db.php';
date_default_timezone_set('America/Mexico_City');

// Evitar cache en páginas protegidas
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) { 
  session_start(); 
}

$filtro = $_GET['fecha'] ?? '';
if ($filtro) {
  // La consulta SELECT * ya tomará la nueva columna
  $stmt = $conn->prepare("SELECT * FROM registros WHERE fecha = ? ORDER BY hora ASC");
  $stmt->execute([$filtro]);
} else {
  $stmt = $conn->query("SELECT * FROM registros ORDER BY fecha DESC, hora ASC");
}
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Asistencias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <link rel="stylesheet" href="../../public/css/navbar.css">
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="navbar-container">
    <h1 class="navbar-logo">Menu</h1>

    <!-- Botón hamburguesa (solo móvil) -->
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

        <a href="../models/logout.php" class="navbar-link logout-btn">Cerrar Sesión</a>
      <?php else: ?>
        <a href="../models/login.php"
           class="navbar-link <?= $currentPage==='login.php'?'active':''; ?>">Iniciar Sesión</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

  <div class="panel">
    <h2>📋 Registros de Asistencia</h2>
    <form method="GET">
      <input type="date" name="fecha" value="<?= htmlspecialchars($filtro) ?>">
      <button type="submit">Filtrar</button>
      <a href="dashboard.php" class="btn-limpiar">Mostrar todo</a>
    </form>

    <div class="tabla-scroll">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Hora Entrada</th> <th>Hora Salida</th>  </tr>
        </thead>
        <tbody>
          <?php foreach ($registros as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= $r['fecha'] ?></td>
            <td><?= $r['hora'] ?></td>
            <td><?= $r['hora_salida'] ?? '---' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
    <script src="../../public/js/navbar.js" defer></script>
</body>
</html>