<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();
$pageTitle = 'Editar Cliente'; $currentPage = 'customers';
$db = getDB(); $id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM customer WHERE customer_id = ?"); $stmt->execute([$id]); $cliente = $stmt->fetch();
if (!$cliente) { $_SESSION['error'] = "Cliente no encontrado."; header("Location: /hotel/admin/customers/index.php"); exit; }
$tipos = $db->query("SELECT * FROM id_card_type")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name = trim($_POST['customer_name']); $email = trim($_POST['email']); $phone = trim($_POST['contact_no']);
    $idTypeId = (int)$_POST['id_card_type_id']; $idCardNo = trim($_POST['id_card_no']);
    $address = trim($_POST['address']); $nationality = trim($_POST['nationality']); $birthDate = $_POST['birth_date'] ?? null;
    $errors = [];
    if (empty($name)) $errors[] = "El nombre es obligatorio.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "El email no es válido.";
    if (empty($phone)) $errors[] = "El teléfono es obligatorio.";
    $chk = $db->prepare("SELECT customer_id FROM customer WHERE email = ? AND customer_id != ?"); $chk->execute([$email, $id]);
    if ($chk->fetch()) $errors[] = "Ya existe otro cliente con ese email.";
    if (empty($errors)) {
        $db->prepare("UPDATE customer SET customer_name=?,email=?,contact_no=?,id_card_type_id=?,id_card_no=?,address=?,nationality=?,birth_date=? WHERE customer_id=?")
           ->execute([$name,$email,$phone,$idTypeId,$idCardNo,$address,$nationality,$birthDate?:null,$id]);
        $_SESSION['success'] = "Cliente actualizado."; header("Location: /hotel/admin/customers/view.php?id=".$id); exit;
    }
}
require_once '../../includes/header.php';
?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="/hotel/admin/customers/view.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h2 class="font-playfair mb-0">Editar Cliente</h2>
</div>
<div class="row justify-content-center"><div class="col-12 col-lg-9">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div><?php endif; ?>
<form method="POST" id="formCliente" novalidate>
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="customer_name" value="<?= htmlspecialchars($_POST['customer_name'] ?? $cliente['customer_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $cliente['email']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Teléfono <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" id="contact_no" name="contact_no" value="<?= htmlspecialchars($_POST['contact_no'] ?? $cliente['contact_no']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Fecha de nacimiento</label>
            <input type="date" class="form-control" name="birth_date" value="<?= htmlspecialchars($_POST['birth_date'] ?? $cliente['birth_date'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Tipo de documento <span class="text-danger">*</span></label>
            <select class="form-select" name="id_card_type_id" required>
                <?php foreach($tipos as $t): ?><option value="<?= $t['id_card_type_id'] ?>" <?= ($_POST['id_card_type_id'] ?? $cliente['id_card_type_id']) == $t['id_card_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['id_card_type']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-6"><label class="form-label">Nº documento <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="id_card_no" value="<?= htmlspecialchars($_POST['id_card_no'] ?? $cliente['id_card_no']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Nacionalidad</label>
            <input type="text" class="form-control" name="nationality" value="<?= htmlspecialchars($_POST['nationality'] ?? $cliente['nationality'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Dirección <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($_POST['address'] ?? $cliente['address']) ?>" required></div>
    </div>
    <hr>
    <div class="d-flex gap-2 justify-content-end">
        <a href="/hotel/admin/customers/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-gold"><i class="bi bi-save me-1"></i>Guardar cambios</button>
    </div>
</form>
</div></div></div></div>
<?php require_once '../../includes/footer.php'; ?>
