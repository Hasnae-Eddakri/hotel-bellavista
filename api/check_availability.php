<?php
// api/check_availability.php — AJAX: disponibilidad de habitaciones
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$checkin  = $_GET['checkin']  ?? '';
$checkout = $_GET['checkout'] ?? '';
$tipoId   = (int)($_GET['tipo'] ?? 0);

if (!$checkin || !$checkout) { echo json_encode(['error'=>'Fechas requeridas']); exit; }

$d1 = DateTime::createFromFormat('Y-m-d', $checkin);
$d2 = DateTime::createFromFormat('Y-m-d', $checkout);
if (!$d1 || !$d2 || $d2 <= $d1) { echo json_encode(['error'=>'Fechas inválidas']); exit; }

$db = getDB();
$sql = "SELECT r.room_id, r.room_no, r.floor, r.description,
               rt.room_type_name, rt.base_price, rt.capacity
        FROM room r JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE r.status = 1
        AND r.room_id NOT IN (SELECT room_id FROM booking WHERE check_in < ? AND check_out > ?)";
$params = [$checkout, $checkin];
if ($tipoId > 0) { $sql .= " AND r.room_type_id = ?"; $params[] = $tipoId; }
$sql .= " ORDER BY rt.base_price ASC, r.room_no ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$hab = $stmt->fetchAll();
$noches = $d1->diff($d2)->days;

echo json_encode([
    'noches' => $noches,
    'disponibles' => count($hab),
    'habitaciones' => array_map(fn($h) => [
        'room_id' => $h['room_id'], 'room_no' => $h['room_no'],
        'floor' => $h['floor'], 'room_type_name' => $h['room_type_name'],
        'capacity' => $h['capacity'], 'base_price' => (float)$h['base_price'],
        'total_price' => (float)$h['base_price'] * $noches,
        'description' => $h['description'],
    ], $hab)
]);
