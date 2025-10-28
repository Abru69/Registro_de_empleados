<?php
require_once '../middleware/auth_check.php';
requireAuth();

include '../../config/db.php';
date_default_timezone_set('America/Mexico_City');

// Evitar cache en páginas protegidas
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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
</head>
<body>

<nav class="navbar">
  <div class="navbar-container">
    <h1 class="navbar-logo"></h1>
    <a href="../models/logout.php" class="navbar-btn">Cerrar sesión</a>
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
</body>
</html>