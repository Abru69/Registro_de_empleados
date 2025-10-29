<?php
// IMPORTANTE: No debe haber NINGÚN espacio o línea antes de este <?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../config/db.php';
require_once '../utils/time_utils.php';

if (ob_get_level()) {
    ob_end_clean();
}

ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    $conn->exec("SET NAMES 'utf8mb4'");

    $nombre = $_GET['nombre'] ?? null;
    $semana = $_GET['semana'] ?? null;

    $cond = [];
    $params = [];

    if ($nombre) {
        $cond[] = 'nombre LIKE ?';
        $params[] = '%' . $nombre . '%';
    }

    if ($semana) {
        if (preg_match('/^(\d{4})-W(\d{2})$/', $semana, $matches)) {
            $year = (int)$matches[1];
            $week = (int)$matches[2];

            $dto = new DateTime();
            $dto->setISODate($year, $week, 1);
            $inicioSemana = $dto->format('Y-m-d');
            $dto->setISODate($year, $week, 7);
            $finSemana = $dto->format('Y-m-d');

            $cond[] = 'fecha >= ?';
            $params[] = $inicioSemana;
            $cond[] = 'fecha <= ?';
            $params[] = $finSemana;
        }
    }

    $where = $cond ? ('WHERE ' . implode(' AND ', $cond)) : '';

    $sql = "SELECT 
        nombre,
        YEARWEEK(fecha, 1) as year_week,
        MIN(fecha) as fecha_inicio,
        MAX(fecha) as fecha_fin,
        COUNT(DISTINCT fecha) as dias_con_datos,
        COUNT(*) as total_registros
    FROM registros
    $where
    GROUP BY nombre, YEARWEEK(fecha, 1)
    ORDER BY year_week DESC, nombre ASC";

    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $conn->query($sql);
    }

    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];

    foreach ($grupos as $grupo) {
        $nombreEmpleado = $grupo['nombre'];
        $fechaInicio = $grupo['fecha_inicio'];
        $fechaFin = $grupo['fecha_fin'];
        $yearWeek = $grupo['year_week'];
        $diasConDatos = (int)$grupo['dias_con_datos'];

        $year = substr($yearWeek, 0, 4);
        $weekNum = (int)substr($yearWeek, 4);

        $dto = new DateTime();
        $dto->setISODate($year, $weekNum, 1);
        $lunesISO = $dto->format('Y-m-d');
        $lunesFormato = $dto->format('d/m/Y');

        $dto->setISODate($year, $weekNum, 7);
        $domingoISO = $dto->format('Y-m-d');
        $domingoFormato = $dto->format('d/m/Y');

        $sqlDetalle = "SELECT fecha, hora, hora_salida, total_horas
        FROM registros
        WHERE nombre = ?
        AND YEARWEEK(fecha, 1) = ?
        ORDER BY fecha ASC, hora ASC";

        $stmtDetalle = $conn->prepare($sqlDetalle);
        $stmtDetalle->execute([$nombreEmpleado, $yearWeek]);
        $registros = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        $totalMinutos = 0;
        foreach ($registros as $reg) {
            $mins = ($reg['total_horas'] !== null)
                ? (int) round(((float)$reg['total_horas']) * 60)
                : minutosTrabajados($reg['fecha'], $reg['hora'], $reg['hora_salida'] ?? null);

            if ($mins !== null && $mins > 0) {
                $totalMinutos += $mins;
            }
        }

        $horasDecimal = $totalMinutos / 60;
        $datosSuficientes = $diasConDatos >= 1;

        $out[] = [
            'nombre' => utf8_encode_safe($nombreEmpleado),
            'semana_iso' => "Semana $weekNum",
            'fecha_inicio' => $lunesISO,
            'fecha_fin' => $domingoISO,
            'rango_formato' => "Lunes $lunesFormato a Domingo $domingoFormato",
            'total_registros' => $diasConDatos,  
            'total_horas_decimal' => $datosSuficientes ? round($horasDecimal, 2) : null,
            'total_minutos' => $datosSuficientes ? $totalMinutos : null,
            'datos_suficientes' => $datosSuficientes,
            'mensaje' => $datosSuficientes ? null : 'Datos insuficientes para calcular semana completa'
        ];
    }

    ob_clean();

    echo json_encode([
        'success' => true,
        'data' => $out,
        'count' => count($out)
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'success' => false,
        'message' => 'Error al procesar datos: ' . $e->getMessage(),
        'data' => [],
        'count' => 0
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();

function utf8_encode_safe($str)
{
    if (mb_detect_encoding($str, 'UTF-8', true) === false) {
        return utf8_encode($str);
    }
    return $str;
}
