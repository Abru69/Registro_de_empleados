<?php
include 'db.php';
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
    // 1. Verificar si ya existe una entrada hoy
    $stmt = $conn->prepare("SELECT id FROM registros WHERE nombre = ? AND fecha = ?");
    $stmt->execute([$nombre, $fecha]);
    
    if ($stmt->fetch()) {
      // Si fetch() encuentra un registro, ya existe
      echo json_encode(['status' => 'error', 'mensaje' => 'Ya has registrado tu ENTRADA hoy']);
      exit;
    }

    // 2. Si no existe, insertar el nuevo registro
    $stmt = $conn->prepare("INSERT INTO registros (nombre, fecha, hora) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $fecha, $hora]);

    echo json_encode(['status' => 'ok', 'mensaje' => "Entrada registrada a las $hora"]);
  
  // --- LÓGICA DE SALIDA ---
  } elseif ($accion === 'salida') {
    
    // 1. Buscar el registro de entrada de hoy
    $stmt = $conn->prepare("SELECT id, hora_salida FROM registros WHERE nombre = ? AND fecha = ?");
    $stmt->execute([$nombre, $fecha]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
      // No se encontró registro de entrada
      echo json_encode(['status' => 'error', 'mensaje' => 'No puedes registrar SALIDA si no registraste ENTRADA hoy']);
      exit;
    }

    // 2. Verificar si ya registró la salida
    if ($registro['hora_salida'] !== null) {
      echo json_encode(['status' => 'error', 'mensaje' => 'Ya has registrado tu SALIDA hoy']);
      exit;
    }

    // 3. Si todo está bien, actualizar la hora_salida
    $stmt = $conn->prepare("UPDATE registros SET hora_salida = ? WHERE id = ?");
    $stmt->execute([$hora, $registro['id']]);

    echo json_encode(['status' => 'ok', 'mensaje' => "Salida registrada a las $hora"]);

  } else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Acción no válida']);
  }
}
?>