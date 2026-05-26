<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();

$id = intval($_GET['id']);
$db = getDB();

// Comprobar que no tiene reservas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM booking WHERE room_id = ?");
$stmt->execute([$id]);
$resultado = $stmt->fetch();

if ($resultado['total'] > 0) {
    $_SESSION['error'] = "No puedes eliminar esta habitación porque tiene reservas asociadas.";
} else {
    $stmt2 = $db->prepare("DELETE FROM room WHERE room_id = ?");
    $stmt2->execute([$id]);
    $_SESSION['success'] = "Habitación eliminada correctamente.";
}

header("Location: /hotel/admin/rooms/index.php");
exit;
