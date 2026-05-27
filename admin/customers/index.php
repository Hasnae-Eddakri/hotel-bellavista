<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Clientes';
$currentPage = 'customers';

$db = getDB();

// Búsqueda por nombre, email o DNI
$busqueda = trim($_GET['q'] ?? '');

if ($busqueda !== '') {
    $like = "%" . $busqueda . "%";
    $stmt = $db->prepare("
        SELECT c.*, ict.id_card_type,
               (SELECT COUNT(*) FROM booking b WHERE b.customer_id = c.customer_id) as num_reservas
        FROM customer c
        JOIN id_card_type ict ON c.id_card_type_id = ict.id_card_type_id
        WHERE c.customer_name LIKE ? OR c.email LIKE ? OR c.id_card_no LIKE ?
        ORDER BY c.customer_name
    ");
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
    <h2 class="mb-0"><i class="bi bi-people text-gold me-2"></i>Clientes</h2>
    <a href="create.php" class="btn btn-gold">
        <i class="bi bi-person-plus me-1"></i> Nuevo cliente
    </a>
</div>

<!-- Búsqueda -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET">
            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search text-gold"></i>
                </span>
                <input type="text" class="form-control" name="q"
                       placeholder="Buscar por nombre, email o número de documento..."
                       value="<?= htmlspecialchars($busqueda) ?>"
                       id="campoBusqueda">
                <button type="submit" class="btn btn-gold">Buscar</button>
                <?php if ($busqueda !== ''): ?>
                <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de clientes -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (count($clientes) === 0): ?>
        <p class="text-muted text-center py-4">
            <?= $busqueda !== '' ? 'No se encontraron clientes con esa búsqueda.' : 'No hay clientes registrados todavía.' ?>
        </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaClientes">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Documento</th>
                        <th>Reservas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($c['customer_name']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($c['id_card_type']) ?></span>
                            <?= htmlspecialchars($c['id_card_no']) ?>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?= $c['num_reservas'] ?></span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $c['customer_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $c['customer_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (currentRole() === 'admin'): ?>
                            <a href="delete.php?id=<?= $c['customer_id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Seguro que quieres eliminar este cliente?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 text-muted small">
            Total: <?= count($clientes) ?> cliente(s)
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    // Búsqueda en tiempo real en la tabla (filtra sin recargar la página)
    $('#campoBusqueda').on('keyup', function() {
        var texto = $(this).val().toLowerCase();

        $('#tablaClientes tbody tr').each(function() {
            var fila = $(this).text().toLowerCase();
            if (fila.indexOf(texto) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
