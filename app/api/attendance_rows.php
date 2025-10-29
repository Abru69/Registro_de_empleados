<?php
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../utils/time_utils.php';
date_default_timezone_set('America/Mexico_City');

$filtro = $_GET['fecha'] ?? '';

if ($filtro) {
  $stmt = $conn->prepare("SELECT nombre, fecha, hora, hora_salida FROM registros WHERE fecha = ? ORDER BY hora ASC");
  $stmt->execute([$filtro]);
} else {
  $stmt = $conn->query("SELECT nombre, fecha, hora, hora_salida FROM registros ORDER BY fecha DESC, hora ASC");
}
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalesPorEmpleado = [];
$rowsHtml = '';
foreach ($registros as $r) {
  $minTrab = minutosTrabajados($r['fecha'], $r['hora'], $r['hora_salida'] ?? null);
  if (!isset($totalesPorEmpleado[$r['nombre']])) $totalesPorEmpleado[$r['nombre']] = 0;
  if ($minTrab !== null) $totalesPorEmpleado[$r['nombre']] += $minTrab;

  $rowsHtml .= '<tr>'
             . '<td>'.htmlspecialchars($r['nombre']).'</td>'
             . '<td>'.htmlspecialchars($r['fecha']).'</td>'
             . '<td>'.htmlspecialchars($r['hora']).'</td>'
             . '<td>'.htmlspecialchars($r['hora_salida'] ?? '---').'</td>'
             . '<td>'.($minTrab !== null ? formatHHMM($minTrab) : '---').'</td>'
             . '</tr>';
}

// (Opcional) Tabla de resumen por empleado
$totalsHtml = '';
if (!empty($totalesPorEmpleado)) {
  $totalsHtml .= '<div class="tabla-scroll"><table><thead><tr><th>Empleado</th><th>Total de horas</th></tr></thead><tbody>';
  foreach ($totalesPorEmpleado as $emp => $minTotal) {
    $totalsHtml .= '<tr><td>'.htmlspecialchars($emp).'</td><td>'.formatHHMM($minTotal).'</td></tr>';
  }
  $totalsHtml .= '</tbody></table></div>';
}

// Firma para saber si cambió algo
$sig = md5(json_encode($registros));

echo json_encode([
  'sig'        => $sig,
  'rows_html'  => $rowsHtml,
  'totals_html'=> $totalsHtml
]);
