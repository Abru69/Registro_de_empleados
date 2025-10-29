<?php
// Convierte minutos totales a "HH:MM"
function formatHHMM($minutesTotal)
{
  $sign = $minutesTotal < 0 ? '-' : '';
  $minutesTotal = abs($minutesTotal);
  $h = floor($minutesTotal / 60);
  $m = $minutesTotal % 60;
  return sprintf('%s%02d:%02d', $sign, $h, $m);
}

// Calcula minutos trabajados entre hora entrada y salida 
function minutosTrabajados($fecha, $horaEntrada, $horaSalida)
{
  if (!$horaSalida) return null;

  try {
    $dt1 = new DateTime("$fecha $horaEntrada");
    $dt2 = new DateTime("$fecha $horaSalida");

    // Si hora_salida < hora_entrada, asumimos que cruzó medianoche
    if ($dt2 < $dt1) {
      $dt2->modify('+1 day');
    }

    $diff = $dt1->diff($dt2);
    return ($diff->h * 60) + $diff->i;
  } catch (Exception $e) {
    return null;
  }
}

/**
 * Convierte minutos a horas decimales
 * Ejemplo: 90 minutos = 1.5 horas
 */
function hoursFromMinutes($minutes)
{
  if ($minutes === null || $minutes < 0) {
    return 0;
  }

  return round($minutes / 60, 2);
}

/**
 * Convierte formato HH:MM a minutos
 */
function minutesFromHHMM($hhmm)
{
  if (empty($hhmm)) return 0;

  $parts = explode(':', $hhmm);
  if (count($parts) !== 2) return 0;

  $hours = (int)$parts[0];
  $mins = (int)$parts[1];

  return ($hours * 60) + $mins;
}


// Convierte minutos a formato HH:MM
function hhmmFromMinutes($minutes)
{
  if ($minutes === null || $minutes < 0) {
    return '00:00';
  }

  $hours = floor($minutes / 60);
  $mins = $minutes % 60;

  return sprintf('%02d:%02d', $hours, $mins);
}


//Formatea minutos a formato legible (ej: "2h 30m")
function formatMinutesToReadable($minutes)
{
  if ($minutes === null || $minutes < 0) {
    return '0h 0m';
  }

  $hours = floor($minutes / 60);
  $mins = $minutes % 60;

  return "{$hours}h {$mins}m";
}
