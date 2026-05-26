<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();

$id = intval($_GET['id']);
$db = getDB();

// No borramos, ponemos como inactivo para no perder el historial
$stmt = $db->prepare("UPDATE staff SET active = 0 WHERE staff_id = ?");
$stmt->execute([$id]);

$_SESSION['success'] = "Empleado dado de baja correctamente.";
header("Location: /hotel/admin/staff/index.php");
exit;
