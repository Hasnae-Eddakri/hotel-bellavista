<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Personal';
$currentPage = 'staff';

$db = getDB();

$empleados = $db->query("
    SELECT s.*, st.staff_type_name, sh.shift_name
    FROM staff s
    JOIN staff_type st ON s.staff_type_id = st.staff_type_id
    JOIN shift sh ON s.shift_id = sh.shift_id
    WHERE s.active = 1
    ORDER BY s.staff_name
")->fetchAll();

$esAdmin = ($_SESSION['user_role'] == 'admin');

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="font-playfair mb-0">
        <i class="bi bi-person-badge text-gold me-2"></i>Personal del Hotel
    </h2>
    <?php if ($esAdmin): ?>
    <a href="/hotel/admin/staff/create.php" class="btn btn-gold">
        <i class="bi bi-plus-circle me-1"></i> Nuevo empleado
    </a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Puesto</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Turno</th>
                        <?php if ($esAdmin): ?>
                        <th>Salario</th>
                        <th>Fecha alta</th>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empleados as $emp): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($emp['staff_name']) ?></td>
                        <td>
                            <span class="badge bg-primary">
                                <?= htmlspecialchars($emp['staff_type_name']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($emp['email'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($emp['contact_no'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($emp['shift_name']) ?></td>
                        <?php if ($esAdmin): ?>
                        <td><?= $emp['salary'] ? number_format($emp['salary'], 2, ',', '.') . '€' : '—' ?></td>
                        <td><?= $emp['hire_date'] ? date('d/m/Y', strtotime($emp['hire_date'])) : '—' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/hotel/admin/staff/edit.php?id=<?= $emp['staff_id'] ?>"
                                   class="btn btn-outline-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <!-- Enlace directo sin modal para evitar problemas -->
                                <a href="/hotel/admin/staff/delete.php?id=<?= $emp['staff_id'] ?>"
                                   class="btn btn-outline-danger btn-baja"
                                   data-nombre="<?= htmlspecialchars($emp['staff_name']) ?>"
                                   title="Dar de baja"
                                   onclick="return confirm('¿Seguro que quieres dar de baja a <?= addslashes($emp['staff_name']) ?>?')">
                                    <i class="bi bi-person-x"></i>
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (count($empleados) == 0): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay empleados registrados.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted small">
        Total: <?= count($empleados) ?> empleado(s) activo(s)
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
