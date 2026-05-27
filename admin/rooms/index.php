<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Habitaciones';
$currentPage = 'rooms';

$db = getDB();

// Filtros de búsqueda (vienen por GET)
$filtroTipo   = $_GET['tipo']   ?? '';
$filtroEstado = $_GET['estado'] ?? '';

// Construimos la consulta según los filtros
$sql    = "SELECT r.*, rt.room_type_name, rt.base_price FROM room r JOIN room_type rt ON r.room_type_id = rt.room_type_id WHERE 1=1";
$params = [];

if ($filtroTipo !== '') {
    $sql .= " AND r.room_type_id = ?";
    $params[] = $filtroTipo;
}
if ($filtroEstado === 'libre') {
    $sql .= " AND r.check_in_status = 0 AND r.status = 1";
} elseif ($filtroEstado === 'ocupada') {
    $sql .= " AND r.check_in_status = 1";
} elseif ($filtroEstado === 'mantenimiento') {
    $sql .= " AND r.status = 0";
}

$sql .= " ORDER BY r.room_no ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$habitaciones = $stmt->fetchAll();

// Tipos de habitación para el filtro
$tipos = $db->query("SELECT * FROM room_type ORDER BY room_type_name")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-door-open text-gold me-2"></i>Habitaciones</h2>
    <?php if (currentRole() === 'admin'): ?>
    <a href="create.php" class="btn btn-gold">
        <i class="bi bi-plus-circle me-1"></i> Nueva habitación
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Tipo de habitación</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['room_type_id'] ?>" <?= $filtroTipo == $t['room_type_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['room_type_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="libre" <?= $filtroEstado === 'libre' ? 'selected' : '' ?>>Libre</option>
                    <option value="ocupada" <?= $filtroEstado === 'ocupada' ? 'selected' : '' ?>>Ocupada</option>
                    <option value="mantenimiento" <?= $filtroEstado === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-gold">Filtrar</button>
                <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de habitaciones -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (count($habitaciones) === 0): ?>
        <p class="text-muted text-center py-4">No se encontraron habitaciones con esos filtros.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nº</th>
                        <th>Tipo</th>
                        <th>Precio/noche</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habitaciones as $hab): ?>
                    <tr>
                        <td class="fw-bold">Hab. <?= htmlspecialchars($hab['room_no']) ?></td>
                        <td><?= htmlspecialchars($hab['room_type_name']) ?></td>
                        <td><?= number_format($hab['base_price'], 2) ?>€</td>
                        <td>
                            <?php if ($hab['status'] == 0): ?>
                                <span class="badge bg-secondary">Mantenimiento</span>
                            <?php elseif ($hab['check_in_status'] == 1): ?>
                                <span class="badge bg-danger">Ocupada</span>
                            <?php else: ?>
                                <span class="badge bg-success">Libre</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $hab['room_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (currentRole() === 'admin'): ?>
                            <a href="edit.php?id=<?= $hab['room_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?id=<?= $hab['room_id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Seguro que quieres eliminar esta habitación?')">
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
            Total: <?= count($habitaciones) ?> habitación(es)
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
