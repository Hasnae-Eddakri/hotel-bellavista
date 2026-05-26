<?php
// ============================================================
// admin/customers/index.php
// Listado y búsqueda de clientes
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Clientes';
$currentPage = 'customers';

$db = getDB();

// Búsqueda por nombre, email o DNI
$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $stmt = $db->prepare("
        SELECT c.*, ict.id_card_type,
               (SELECT COUNT(*) FROM booking b WHERE b.customer_id = c.customer_id) as num_reservas
        FROM customer c
        JOIN id_card_type ict ON c.id_card_type_id = ict.id_card_type_id
        WHERE c.customer_name LIKE ? OR c.email LIKE ? OR c.id_card_no LIKE ?
        ORDER BY c.customer_name
    ");
    $like = "%{$busqueda}%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->query("
        SELECT c.*, ict.id_card_type,
               (SELECT COUNT(*) FROM booking b WHERE b.customer_id = c.customer_id) as num_reservas
        FROM customer c
        JOIN id_card_type ict ON c.id_card_type_id = ict.id_card_type_id
        ORDER BY c.customer_name
    ");
}
$clientes = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="font-playfair mb-0"><i class="bi bi-people text-gold me-2"></i>Clientes</h2>
    <a href="create.php" class="btn btn-gold">
        <i class="bi bi-person-plus me-1"></i> Nuevo cliente
    </a>
</div>

<!-- Búsqueda en tiempo real -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" id="formBusqueda">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-gold"></i>
                </span>
                <input type="text" class="form-control border-start-0" id="busqueda" name="q"
                       placeholder="Buscar por nombre, email o número de documento..."
                       value="<?= htmlspecialchars($busqueda) ?>">
                <?php if ($busqueda): ?>
                <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-gold">Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de clientes -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Documento</th>
                        <th>Nº Doc.</th>
                        <th>Reservas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($c['customer_name']) ?></td>
                        <td>
                            <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($c['email']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($c['contact_no']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($c['id_card_type']) ?></span></td>
                        <td><?= htmlspecialchars($c['id_card_no']) ?></td>
                        <td>
                            <span class="badge bg-primary rounded-pill"><?= $c['num_reservas'] ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $c['customer_id'] ?>" class="btn btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $c['customer_id'] ?>" class="btn btn-outline-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                <a href="/hotel/admin/customers/delete.php?id=<?= $c['customer_id'] ?>"
                                   class="btn btn-outline-danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Seguro que quieres eliminar a <?= addslashes($c['customer_name']) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clientes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <?= $busqueda ? "No se encontraron clientes con \"" . htmlspecialchars($busqueda) . "\"" : "No hay clientes registrados." ?>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted small">
        <?= count($clientes) ?> cliente(s) <?= $busqueda ? "encontrado(s)" : "en total" ?>
    </div>
</div>




<?php require_once '../../includes/footer.php'; ?>
