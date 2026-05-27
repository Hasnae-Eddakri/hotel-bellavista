<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Quejas y Reclamaciones';
$currentPage = 'complaints';

$db = getDB();

// Marcar queja como resuelta
if (isset($_GET['resolve'])) {
    $id       = (int)$_GET['resolve'];
    $respuesta = trim($_POST['response'] ?? 'Resuelto por el equipo del hotel.');
    $db->prepare("UPDATE complaint SET resolve_status=1, resolve_date=NOW(), response=? WHERE id=?")
       ->execute([$respuesta, $id]);
    $_SESSION['success'] = "Queja marcada como resuelta.";
    header("Location: index.php");
    exit;
}

// Cargamos todas las quejas
$quejas = $db->query("
    SELECT c.*, cu.customer_name
    FROM complaint c
    LEFT JOIN customer cu ON c.customer_id = cu.customer_id
    ORDER BY c.resolve_status ASC, c.created_at DESC
")->fetchAll();

// Contamos cuántas están pendientes
$pendientes = 0;
foreach ($quejas as $q) {
    if ($q['resolve_status'] == 0) $pendientes++;
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-exclamation-triangle text-gold me-2"></i>Quejas y Reclamaciones</h2>
    <?php if ($pendientes > 0): ?>
    <span class="badge bg-danger fs-6"><?= $pendientes ?> pendiente(s)</span>
    <?php endif; ?>
</div>

<!-- Botones de filtro (con jQuery) -->
<div class="mb-3">
    <button class="btn btn-sm btn-outline-secondary me-2" id="btnTodas">Todas</button>
    <button class="btn btn-sm btn-outline-danger me-2" id="btnPendientes">Solo pendientes</button>
    <button class="btn btn-sm btn-outline-success" id="btnResueltas">Solo resueltas</button>
</div>

<!-- Lista de quejas -->
<div class="row g-4" id="listaQuejas">
    <?php foreach ($quejas as $q): ?>
    <div class="col-12 queja-card <?= $q['resolve_status'] ? 'resuelta' : 'pendiente' ?>">
        <div class="card border-0 shadow-sm <?= $q['resolve_status'] ? '' : 'border-start border-danger border-3' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">
                            <?php if ($q['resolve_status']): ?>
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <?php else: ?>
                                <i class="bi bi-clock-fill text-danger me-2"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($q['complaint_type']) ?>
                        </h5>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($q['customer_name'] ?? 'Anónimo') ?>
                            &nbsp;|&nbsp;
                            <i class="bi bi-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?>
                        </p>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($q['complaint_text'])) ?></p>

                        <?php if ($q['resolve_status'] && !empty($q['response'])): ?>
                        <div class="mt-2 p-2 bg-light rounded">
                            <small class="text-success fw-bold">Respuesta del hotel:</small><br>
                            <small><?= nl2br(htmlspecialchars($q['response'])) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botón para resolver -->
                    <?php if (!$q['resolve_status']): ?>
                    <div>
                        <form method="POST" action="?resolve=<?= $q['id'] ?>">
                            <textarea name="response" class="form-control mb-2" rows="2"
                                      placeholder="Escribe una respuesta (opcional)..." style="min-width:220px;"></textarea>
                            <button type="submit" class="btn btn-sm btn-success w-100">
                                <i class="bi bi-check-circle me-1"></i>Marcar como resuelta
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <span class="badge bg-success">Resuelta el <?= date('d/m/Y', strtotime($q['resolve_date'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (count($quejas) === 0): ?>
    <div class="col-12">
        <p class="text-muted text-center py-4">No hay quejas registradas. ¡Buena señal!</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {

    // Filtrar quejas con jQuery
    $('#btnTodas').click(function() {
        $('.queja-card').show();
    });

    $('#btnPendientes').click(function() {
        $('.queja-card').hide();
        $('.queja-card.pendiente').show();
    });

    $('#btnResueltas').click(function() {
        $('.queja-card').hide();
        $('.queja-card.resuelta').show();
    });

});
</script>

<?php require_once '../../includes/footer.php'; ?>
