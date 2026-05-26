<?php
// ============================================================
// admin/complaints/index.php
// Gestión de quejas y reclamaciones
// ============================================================
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

$pageTitle   = 'Quejas y Reclamaciones';
$currentPage = 'complaints';

$db = getDB();

// Marcar queja como resuelta
if ($_GET['resolve'] ?? false) {
    $id       = (int)$_GET['resolve'];
    $response = trim($_POST['response'] ?? 'Resuelto por el equipo del hotel.');
    $db->prepare("UPDATE complaint SET resolve_status=1, resolve_date=NOW(), response=? WHERE id=?")->execute([$response, $id]);
    $_SESSION['success'] = "Queja marcada como resuelta.";
    header("Location: index.php");
    exit;
}

$quejas = $db->query("
    SELECT c.*, cu.customer_name
    FROM complaint c
    LEFT JOIN customer cu ON c.customer_id = cu.customer_id
    ORDER BY c.resolve_status ASC, c.created_at DESC
")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="font-playfair mb-0"><i class="bi bi-exclamation-triangle text-gold me-2"></i>Quejas y Reclamaciones</h2>
    <div id="contadorPendientes" class="badge bg-danger fs-6"></div>
</div>

<!-- Filtro show/hide (jQuery) -->
<div class="mb-3">
    <button class="btn btn-sm btn-outline-secondary me-2" id="btnTodas">Todas</button>
    <button class="btn btn-sm btn-outline-danger me-2" id="btnPendientes">Solo pendientes</button>
    <button class="btn btn-sm btn-outline-success" id="btnResueltas">Solo resueltas</button>
</div>

<div class="row g-4" id="listaQuejas">
    <?php foreach ($quejas as $q): ?>
    <div class="col-12 queja-card <?= $q['resolve_status'] ? 'resuelta' : 'pendiente' ?>">
        <div class="card border-0 shadow-sm <?= $q['resolve_status'] ? '' : 'border-start border-danger border-3' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">
                            <?= $q['resolve_status']
                                ? '<i class="bi bi-check-circle-fill text-success me-2"></i>'
                                : '<i class="bi bi-clock-fill text-danger me-2"></i>' ?>
                            <?= htmlspecialchars($q['complaint_type']) ?>
                        </h5>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($q['complainant_name']) ?>
                            <?php if ($q['customer_name']): ?>
                            — <a href="../customers/view.php?id=<?= $q['customer_id'] ?>"><?= htmlspecialchars($q['customer_name']) ?></a>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?>
                        </p>
                    </div>
                    <div>
                        <?php if ($q['resolve_status']): ?>
                            <span class="badge bg-success">Resuelta</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Pendiente</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Descripción (expandible con jQuery) -->
                <div class="mt-3">
                    <p class="mb-2"><?= htmlspecialchars($q['complaint']) ?></p>
                </div>

                <?php if ($q['resolve_status'] && $q['response']): ?>
                <!-- Respuesta del hotel -->
                <div class="alert alert-success py-2 mb-2">
                    <small><i class="bi bi-reply-fill me-1"></i><strong>Respuesta del hotel:</strong>
                    <?= htmlspecialchars($q['response']) ?></small>
                </div>
                <?php endif; ?>

                <?php if (!$q['resolve_status']): ?>
                <!-- Formulario para resolver (show/hide con jQuery) -->
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-success btn-resolver" data-id="<?= $q['id'] ?>">
                        <i class="bi bi-check-circle me-1"></i>Marcar como resuelta
                    </button>
                    <!-- Panel de respuesta (oculto) -->
                    <div class="resolver-panel mt-2 d-none" id="panel-<?= $q['id'] ?>">
                        <form method="POST" action="?resolve=<?= $q['id'] ?>">
                            <div class="input-group">
                                <textarea class="form-control" name="response" rows="2"
                                          placeholder="Escribe la respuesta al cliente..."><?= htmlspecialchars($q['response'] ?? '') ?></textarea>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($quejas)): ?>
    <div class="col-12"><p class="text-center text-muted py-4">No hay quejas registradas.</p></div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // --------------------------------------------------------
    // Contar pendientes y mostrar en el badge (DOM)
    // --------------------------------------------------------
    const pendientes = $('.queja-card.pendiente').length;
    if (pendientes > 0) {
        $('#contadorPendientes').text(pendientes + ' pendiente(s)').show();
    }

    // --------------------------------------------------------
    // Botones de filtro (jQuery show/hide)
    // --------------------------------------------------------
    $('#btnTodas').on('click', function() {
        $('.queja-card').slideDown(300);
    });

    $('#btnPendientes').on('click', function() {
        $('.queja-card.resuelta').slideUp(200);
        $('.queja-card.pendiente').slideDown(300);
    });

    $('#btnResueltas').on('click', function() {
        $('.queja-card.pendiente').slideUp(200);
        $('.queja-card.resuelta').slideDown(300);
    });

    // --------------------------------------------------------
    // Mostrar panel de resolución con slideDown (jQuery)
    // --------------------------------------------------------
    $('.btn-resolver').on('click', function() {
        const id = $(this).data('id');
        $('#panel-' + id).slideToggle(300);
        $(this).toggleClass('btn-outline-success btn-outline-secondary');
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
