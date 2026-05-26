<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
if ($id) { getDB()->prepare("UPDATE booking SET payment_status=1, remaining_price=0 WHERE booking_id=?")->execute([$id]); $_SESSION['success'] = "Reserva #$id marcada como pagada."; }
header("Location: /hotel/admin/bookings/index.php"); exit;
