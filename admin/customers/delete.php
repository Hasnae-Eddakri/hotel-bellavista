<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireAdmin();

$id = intval($_GET['id']);
$db = getDB();

try {
    // Primero borramos los datos relacionados en orden
    // para que MySQL no se queje de las foreign keys

    // 1. Borramos las valoraciones del cliente
    $stmt = $db->prepare("DELETE FROM review WHERE customer_id = ?");
    $stmt->execute([$id]);

    // 2. Borramos las quejas del cliente
    $stmt = $db->prepare("DELETE FROM complaint WHERE customer_id = ?");
    $stmt->execute([$id]);

    // 3. Borramos los servicios de sus reservas
    $stmt = $db->prepare("DELETE FROM booking_service WHERE booking_id IN (SELECT booking_id FROM booking WHERE customer_id = ?)");
    $stmt->execute([$id]);

    // 4. Borramos sus reservas
    $stmt = $db->prepare("DELETE FROM booking WHERE customer_id = ?");
    $stmt->execute([$id]);

    // 5. Desvinculamos su cuenta de cliente si tiene
    $stmt = $db->prepare("UPDATE customer_user SET customer_id = NULL WHERE customer_id = ?");
    $stmt->execute([$id]);

    // 6. Ahora ya podemos borrar el cliente
    $stmt = $db->prepare("DELETE FROM customer WHERE customer_id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "Cliente eliminado correctamente.";

} catch (Exception $e) {
    $_SESSION['error'] = "Error al eliminar el cliente: " . $e->getMessage();
}

header("Location: /hotel/admin/customers/index.php");
exit;
