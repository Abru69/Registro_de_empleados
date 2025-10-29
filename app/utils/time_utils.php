<?php
// Convierte minutos totales a "HH:MM"
function formatHHMM($minutesTotal) {
  $sign = $minutesTotal < 0 ? '-' : '';
  $minutesTotal = abs($minutesTotal);
  $h = floor($minutesTotal / 60);
  $m = $minutesTotal % 60;
  return sprintf('%s%02d:%02d', $sign, $h, $m);
}

// Calcula minutos entre entrada y salida (soporta cruce de medianoche)
function minutosTrabajados($fecha, $horaEntrada, $horaSalida) {
  if (empty($horaEntrada) || empty($horaSalida)) return null;
  $entrada = DateTime::createFromFormat('Y-m-d H:i:s', $fecha.' '.$horaEntrada) ?: new DateTime($fecha.' '.$horaEntrada);
  $salida  = DateTime::createFromFormat('Y-m-d H:i:s', $fecha.' '.$horaSalida)  ?: new DateTime($fecha.' '.$horaSalida);
  if ($salida < $entrada) $salida->modify('+1 day');
  return (int) round(($salida->getTimestamp() - $entrada->getTimestamp()) / 60);
}
