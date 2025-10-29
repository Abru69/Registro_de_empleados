<?php
include '../../config/db.php';
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $accion = $_POST['accion'] ?? '';
  
  if ($nombre === '') {
    echo json_encode(['status' => 'error', 'mensaje' => 'Debe ingresar su nombre']);
    exit;
  }

  $fecha = date('Y-m-d');
  $hora = date('H:i:s');

  // --- LÓGICA DE ENTRADA ---
  if ($accion === 'entrada') {
    // Verificar si hay un registro sin hora de salida
    $stmt = $conn->prepare("SELECT id FROM registros 
                           WHERE nombre = ? 
                           AND fecha = ? 
                           AND hora_salida IS NULL");
    $stmt->execute([$nombre, $fecha]);
    
    if ($stmt->fetch()) {
      echo json_encode([
        'status' => 'error', 
        'mensaje' => 'Debes registrar tu SALIDA antes de una nueva ENTRADA'
      ]);
      exit;
    }

    // Si no hay registros pendientes, crear nuevo registro
    $stmt = $conn->prepare("INSERT INTO registros (nombre, fecha, hora) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $fecha, $hora]);

    echo json_encode(['status' => 'ok', 'mensaje' => "Entrada registrada a las $hora"]);
  
  // --- LÓGICA DE SALIDA ---
  } elseif ($accion === 'salida') {
    // Buscar el último registro sin hora de salida
    $stmt = $conn->prepare("SELECT id, hora 
                           FROM registros 
                           WHERE nombre = ? 
                           AND fecha = ? 
                           AND hora_salida IS NULL 
                           ORDER BY hora DESC 
                           LIMIT 1");
    $stmt->execute([$nombre, $fecha]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
      echo json_encode([
        'status' => 'error', 
        'mensaje' => 'No hay una ENTRADA activa para registrar SALIDA'
      ]);
      exit;
    }

    // Validar que la hora de salida sea posterior a la entrada
    $horaEntrada = strtotime($registro['hora']);
    $horaSalida = strtotime($hora);
    
    if ($horaSalida <= $horaEntrada) {
      echo json_encode([
        'status' => 'error', 
        'mensaje' => 'La hora de SALIDA debe ser posterior a la hora de ENTRADA'
      ]);
      exit;
    }

    // Actualizar la hora de salida
    $stmt = $conn->prepare("UPDATE registros SET hora_salida = ? WHERE id = ?");
    $stmt->execute([$hora, $registro['id']]);

    echo json_encode(['status' => 'ok', 'mensaje' => "Salida registrada a las $hora"]);

  } else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Acción no válida']);
  }
}
?>