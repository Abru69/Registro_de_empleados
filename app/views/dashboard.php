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
$registros = [];
try {
  // Valida que el filtro sea YYYY-MM-DD (lo que espera <input type="date">)
  $esFechaValida = $filtro && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtro);

  if ($esFechaValida) {
    $stmt = $conn->prepare("SELECT id, nombre, fecha, hora, hora_salida, total_horas
                            FROM registros
                            WHERE fecha = ?
                            ORDER BY hora ASC");
    $stmt->execute([$filtro]);
  } else {
    // si no hay filtro válido, trae lo más reciente primero
    $stmt = $conn->query("SELECT id, nombre, fecha, hora, hora_salida, total_horas
                          FROM registros
                          ORDER BY fecha DESC, hora ASC");
  }

  if ($stmt) {
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  // opcional: error_log($e->getMessage());
  $registros = [];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - Asistencia de Empleados</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../public/css/estilos.css">
  <link rel="stylesheet" href="../../public/css/navbar.css">
  <link rel="stylesheet" href="../../public/css/tabla.css">
  <link rel="icon" type="image/x-icon" href="../../public/img/favicon.ico">

  <!-- AG-Grid CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.3/styles/ag-grid.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.3/styles/ag-theme-alpine.css">

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
            class="navbar-link <?= $currentPage === 'dashboard.php' ? 'active' : ''; ?>">Panel de Asistencias</a>

          <?php if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <a href="../../index.php"
              class="navbar-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>">Registrar Empleado</a>
          <?php endif; ?>

          <a href="../models/logout.php" class="navbar-link logout-btn">Cerrar Sesión</a>
        <?php else: ?>
          <a href="../models/login.php"
            class="navbar-link <?= $currentPage === 'login.php' ? 'active' : ''; ?>">Iniciar Sesión</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="panel">
    <div class="dashboard-container">
      <h2>📋 Registros de Asistencia</h2>
      <div class="filter-section">
        <h3>Filtros de Búsqueda</h3>
        <div class="filter-group">
          <div class="filter-input">
            <label for="filterNombre">Nombre:</label>
            <input type="text" id="filterNombre" placeholder="Buscar por nombre...">
          </div>
          <div class="filter-input">
            <label for="filterDesde">Fecha Desde:</label>
            <input type="date" id="filterDesde">
          </div>
          <div class="filter-input">
            <label for="filterHasta">Fecha Hasta:</label>
            <input type="date" id="filterHasta">
          </div>
        </div>
        <div class="filter-buttons">
          <button id="btnFiltrar" class="btn btn-primary">Buscar</button>
          <button id="btnMostrarTodo" class="btn btn-secondary">Limpiar filtro</button>
          <button id="btnExportar" class="btn btn-primary">Exportar a Excel</button>
        </div>
      </div>
      <div id="myGrid" class="ag-theme-alpine"></div>
    </div>
    <div class="dashboard-container" style="margin-top: 40px;">


      <div id="tab-semanales" class="tab-content">
        <div class="filter-section">
          <h3>Filtros de Búsqueda</h3>
          <div class="filter-group">
            <div class="filter-input">
              <label for="filterNombreSemanal">Nombre del Empleado:</label>
              <input type="text" id="filterNombreSemanal" placeholder="Buscar por nombre...">
            </div>
            <div class="filter-input">
              <label for="filterSemana">Semana:</label>
              <input type="week" id="filterSemana">
            </div>
          </div>
          <div class="filter-buttons">
            <button id="btnFiltrarSemanal" class="btn btn-primary">Filtrar</button>
            <button id="btnMostrarTodoSemanal" class="btn btn-secondary">Limpiar filtros</button>
            <button id="btnExportarSemanal" class="btn btn-primary">Exportar a Excel</button>
          </div>
        </div>
        <div id="weeklyGrid" class="ag-theme-alpine grid-container"></div>

     
      <script>
        function openTab(evt, tabName) {
          const tabContents = document.getElementsByClassName("tab-content");
          for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.remove("active");
          }

          const tabButtons = document.getElementsByClassName("tab-button");
          for (let i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove("active");
          }

          document.getElementById(tabName).classList.add("active");
          evt.currentTarget.classList.add("active");
        }
      </script>

      <div id=" probe" data-url="../api/attendance_rows.php<?= $filtro ? '?fecha=' . urlencode($filtro) : '' ?>"></div>
      <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.3/dist/ag-grid-community.min.js"></script>

      <script src="../../public/js/navbar.js" defer></script>
      <script src="../../public/js/attendance_poll.js" defer></script>
      <script src="../../public/js/registros_table.js" defer></script>
      <script src="../../public/js/weekly_hours_table.js" defer></script>


</body>
</html>