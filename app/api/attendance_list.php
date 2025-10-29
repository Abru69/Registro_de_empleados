<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';
date_default_timezone_set('America/Mexico_City');

// Utilidades para total de horas
function minutosTrabajados($fecha, $horaEntrada, $horaSalida) {
  if (!$horaEntrada || !$horaSalida) return null;
  $e = strtotime("$fecha $horaEntrada");
  $s = strtotime("$fecha $horaSalida");
  if ($s < $e) $s = strtotime('+1 day', $s); // cruza medianoche
  return (int) round(($s - $e) / 60);
}
function hhmmFromMinutes($m){
  if ($m === null) return null;
  $h = floor($m/60); $mm = $m%60;
  return sprintf('%02d:%02d', $h, $mm);
}

// Filtros opcionales
$desde  = $_GET['desde']  ?? ''; // YYYY-MM-DD
$hasta  = $_GET['hasta']  ?? ''; // YYYY-MM-DD
$nombre = $_GET['nombre'] ?? ''; // texto

$cond   = [];
$params = [];

// valida formato de fecha simple
if ($desde && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
  $cond[]   = 'fecha >= ?';
  $params[] = $desde;
}
if ($hasta && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
  $cond[]   = 'fecha <= ?';
  $params[] = $hasta;
}
if ($nombre !== '') {
  $cond[]   = 'nombre LIKE ?';
  $params[] = '%'.$nombre.'%';
}

$where = $cond ? ('WHERE '.implode(' AND ', $cond)) : '';
$sql   = "SELECT id, nombre, fecha, hora, hora_salida, total_horas
          FROM registros
          $where
          ORDER BY fecha DESC, hora ASC";

try {
  if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
  } else {
    $stmt = $conn->query($sql);
  }
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $out = [];
  foreach ($rows as $r) {
    // si tienes columna total_horas, úsala; si viene NULL, calcula
    $mins = ($r['total_horas'] !== null)
      ? (int) round(((float)$r['total_horas']) * 60)
      : minutosTrabajados($r['fecha'], $r['hora'], $r['hora_salida'] ?? null);

    $out[] = [
      'id'          => (int)$r['id'],
      'nombre'      => $r['nombre'],
      'fecha'       => $r['fecha'],
      'hora'        => $r['hora'],
      'hora_salida' => $r['hora_salida'],
      'total_hhmm'  => $mins !== null ? hhmmFromMinutes($mins) : '---'
    ];
  }

  echo json_encode(['data' => $out]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => true, 'message' => 'db-error']);
}
