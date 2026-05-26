<?php
// ============================================================
// admin/rooms/index.php
// Listado de habitaciones con filtros
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Habitaciones';
$currentPage = 'rooms';

$db = getDB();

// Filtros de búsqueda (GET)
$filtroTipo   = $_GET['tipo']   ?? '';
$filtroEstado = $_GET['estado'] ?? '';

// Construir la consulta con filtros opcionales
$sql = "SELECT r.*, rt.room_type_name, rt.base_price
        FROM room r
        JOIN room_type rt ON r.room_type_id = rt.room_type_id
        WHERE 1=1";
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
    <h2 class="font-playfair mb-0"><i class="bi bi-door-open text-gold me-2"></i>Habitaciones</h2>
    <?php if (currentRole() === 'admin'): ?>
    <a href="create.php" class="btn btn-gold">
        <i class="bi bi-plus-circle me-1"></i> Nueva habitación
    </a>
    <?php endif; ?>
</div>

<!-- Filtros (jQuery: mostrar/ocultar con slide) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="cursor:pointer" id="toggleFiltros">
        <span><i class="bi bi-funnel text-gold me-2"></i>Filtros de búsqueda</span>
        <i class="bi bi-chevron-down" id="iconFiltros"></i>
    </div>
    <div class="card-body" id="filtrosPanel">
        <form method="GET" action="">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small">Tipo de habitación</label>
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
                    <label class="form-label small">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="libre"        <?= $filtroEstado === 'libre'         ? 'selected' : '' ?>>Libre</option>
                        <option value="ocupada"      <?= $filtroEstado === 'ocupada'       ? 'selected' : '' ?>>Ocupada</option>
                        <option value="mantenimiento"<?= $filtroEstado === 'mantenimiento' ? 'selected' : '' ?>>En mantenimiento</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-gold me-2">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de habitaciones -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaHabitaciones">
                <thead class="table-dark">
                    <tr>
                        <th>Nº Hab.</th>
                        <th>Planta</th>
                        <th>Tipo</th>
                        <th>Precio/noche</th>
                        <th>Estado</th>
                        <th>Disponibilidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habitaciones as $hab): ?>
                    <tr>
                        <td><span class="badge bg-dark fs-6"><?= htmlspecialchars($hab['room_no']) ?></span></td>
                        <td>Planta <?= $hab['floor'] ?></td>
                        <td><?= htmlspecialchars($hab['room_type_name']) ?></td>
                        <td class="fw-bold text-gold"><?= number_format($hab['base_price'], 2, ',', '.') ?>€</td>
                        <td>
                            <?php if (!$hab['status']): ?>
                                <span class="badge bg-secondary">Mantenimiento</span>
                            <?php elseif ($hab['check_in_status']): ?>
                                <span class="badge bg-danger">Ocupada</span>
                            <?php else: ?>
                                <span class="badge bg-success">Libre</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Barra de disponibilidad visual -->
                            <?php if ($hab['check_in_status']): ?>
                                <div class="progress" style="height:6px;width:80px;">
                                    <div class="progress-bar bg-danger" style="width:100%"></div>
                                </div>
                            <?php else: ?>
                                <div class="progress" style="height:6px;width:80px;">
                                    <div class="progress-bar bg-success" style="width:100%"></div>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="view.php?id=<?= $hab['room_id'] ?>" class="btn btn-outline-primary" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (currentRole() === 'admin'): ?>
                                <a href="edit.php?id=<?= $hab['room_id'] ?>" class="btn btn-outline-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/hotel/admin/rooms/delete.php?id=<?= $hab['room_id'] ?>"
                                   class="btn btn-outline-danger"
                                   title="Eliminar"
                                   onclick="return confirm('¿Seguro que quieres eliminar la habitación <?= addslashes($hab['room_no']) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($habitaciones)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron habitaciones con esos filtros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted small">
        Total: <strong><?= count($habitaciones) ?></strong> habitación(es)
    </div>
</div>



<?php require_once '../../includes/footer.php'; ?>
