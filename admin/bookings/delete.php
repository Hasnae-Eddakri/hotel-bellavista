<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();

$id = intval($_GET['id']);

if ($id > 0) {
    $db = getDB();

    try {
        // Buscamos la habitacion para ponerla libre
        $stmt = $db->prepare("SELECT room_id FROM booking WHERE booking_id = ?");
        $stmt->execute([$id]);
        $reserva = $stmt->fetch();

        if ($reserva) {
            // 1. Borramos los servicios de la reserva primero
            $stmt = $db->prepare("DELETE FROM booking_service WHERE booking_id = ?");
            $stmt->execute([$id]);

            // 2. Borramos las valoraciones de la reserva
            $stmt = $db->prepare("DELETE FROM review WHERE booking_id = ?");
            $stmt->execute([$id]);

            // 3. Liberamos la habitacion
            $stmt = $db->prepare("UPDATE room SET check_in_status = 0 WHERE room_id = ?");
            $stmt->execute([$reserva['room_id']]);

            // 4. Borramos la reserva
            $stmt = $db->prepare("DELETE FROM booking WHERE booking_id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "Reserva #$id eliminada correctamente.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al eliminar la reserva: " . $e->getMessage();
    }
}

header("Location: /hotel/admin/bookings/index.php");
exit;
