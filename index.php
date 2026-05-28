<?php
// ============================================================
// index.php — Página principal pública de Hotel Bellavista
// ============================================================
require_once 'config/database.php';

$db = getDB();

// Cargar tipos de habitación para el buscador
$tipos = $db->query("SELECT * FROM room_type ORDER BY base_price")->fetchAll();

// Cargar habitaciones destacadas
$destacadas = $db->query("
    SELECT r.*, rt.room_type_name, rt.base_price, rt.capacity
    FROM room r
    JOIN room_type rt ON r.room_type_id = rt.room_type_id
    WHERE r.status = 1
    ORDER BY rt.base_price DESC
    LIMIT 6
")->fetchAll();

// Servicios del hotel
$servicios = $db->query("SELECT * FROM service WHERE active = 1 LIMIT 6")->fetchAll();

$iconosServicio = [
    'Desayuno'         => 'bi-cup-hot',
    'Media Pensión'    => 'bi-egg-fried',
    'Pensión Completa' => 'bi-food-menu',  // corregido
    'Parking'          => 'bi-p-circle',
    'Spa'              => 'bi-droplet',
    'Transfer'         => 'bi-airplane',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Bellavista — Tu escapada perfecta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="/hotel/assets/css/style.css" rel="stylesheet">
</head>

<body>

    <!-- ============================================================
     NAVBAR (fijo, transparente, se vuelve oscuro al scrollar)
============================================================ -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-public fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="/">
                <i class="bi bi-building fs-4 text-gold"></i>
                <span class="font-playfair fs-4">Hotel Bellavista</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navPub">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navPub">
                <ul class="navbar-nav ms-auto me-3 gap-1">
                    <li class="nav-item"><a class="nav-link" href="#habitaciones">Habitaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
                    <li class="nav-item"><a class="nav-link" href="#galeria">Galería</a></li>
                    <li class="nav-item"><a class="nav-link" href="#valoraciones">Opiniones</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                </ul>
                <?php
                require_once __DIR__ . '/includes/auth.php';
                if (isLoggedIn() && currentRole() === "cliente"): ?>
                    <a href="/hotel/mi-cuenta.php" class="btn btn-gold btn-sm px-3">
                        <i class="bi bi-person-circle me-1"></i>Mi cuenta
                    </a>
                    <a href="/hotel/logout-cliente.php" class="btn btn-sm btn-outline-light">Salir</a>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="/hotel/login-cliente.php" class="btn btn-outline-light btn-sm px-3 me-1">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                    </a>
                    <a href="/hotel/registro.php" class="btn btn-gold btn-sm px-3">
                        <i class="bi bi-person-plus me-1"></i>Registrarse
                    </a>
                <?php else: ?>
                    <a href="/hotel/login.php" class="btn btn-gold btn-sm px-3">
                        <i class="bi bi-person-circle me-1"></i>Área privada
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>


    <!-- ============================================================
     HERO SECTION
============================================================ -->
    <section class="hero" id="inicio">
        <div class="container text-center position-relative" style="z-index:5;">
            <p class="text-uppercase letter-spacing-3 text-gold mb-2" style="letter-spacing:4px; font-size:0.85rem;">Bienvenido a</p>
            <h1 class="hero-title text-white mb-3">Hotel Bellavista</h1>
            <p class="hero-subtitle text-white mb-5">Donde el lujo se encuentra con el horizonte</p>
            <a href="#buscar" class="btn btn-gold btn-lg px-5 py-3 rounded-pill shadow-lg">
                <i class="bi bi-search me-2"></i>Buscar habitación
            </a>
        </div>
    </section>


    <!-- ============================================================
     BUSCADOR DE DISPONIBILIDAD (AJAX + Objeto Date)
============================================================ -->
    <section class="py-5 bg-light" id="buscar" style="margin-top:-1px;">
        <div class="container">
            <div class="disponibilidad-widget shadow-lg">
                <h3 class="font-playfair text-center mb-4">
                    <i class="bi bi-calendar-check text-gold me-2"></i>Comprueba disponibilidad
                </h3>

                <div class="row g-3 align-items-end" id="formBusqueda">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-bold">Check-in</label>
                        <input type="date" class="form-control" id="pub_checkin"
                            min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-bold">Check-out</label>
                        <input type="date" class="form-control" id="pub_checkout"
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label small fw-bold">Tipo</label>
                        <select class="form-select" id="pub_tipo">
                            <option value="">Cualquier tipo</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['room_type_id'] ?>">
                                    <?= htmlspecialchars($t['room_type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <button class="btn btn-gold w-100 py-2" id="btnBuscar" type="button">
                            <i class="bi bi-search me-2"></i>Buscar
                        </button>
                    </div>
                </div>

                <!-- Error de validación JS -->
                <div class="alert alert-warning mt-3 d-none" id="alertaBuscador">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="alertaBuscadorTexto"></span>
                </div>

                <!-- Resultados AJAX -->
                <div id="resultadosBusqueda" class="mt-4"></div>
            </div>
        </div>
    </section>


    <!-- ============================================================
     HABITACIONES DESTACADAS
============================================================ -->
    <section class="py-5" id="habitaciones">
        <div class="container">
            <div class="text-center mb-5">
                <p class="text-gold small text-uppercase fw-bold" style="letter-spacing:3px;">Nuestras habitaciones</p>
                <h2 class="font-playfair display-6">Confort y elegancia</h2>
                <p class="text-muted">Cada habitación ha sido diseñada para ofrecerte la mejor experiencia</p>
            </div>

            <div class="row g-4">
                <?php foreach ($destacadas as $hab): ?>
                    <div class="col-12 col-md-6 col-lg-4 animate-on-scroll">
                        <div class="card room-card h-100">
                            <!-- Imagen placeholder (en producción usar imagen real) -->
                           <?php
$img = $db->prepare("
    SELECT image_path
    FROM room_image
    WHERE room_id = ?
    ORDER BY is_main DESC
    LIMIT 1
");
$img->execute([$hab['room_id']]);
$imagen = $img->fetch();
$tieneImagen = ($imagen !== false && !empty($imagen['image_path']));
?>

<?php if ($tieneImagen): ?>
    <img
        src="/hotel/<?= htmlspecialchars($imagen['image_path']) ?>"
        class="card-img-top"
        style="height:220px; object-fit:cover;"
        alt="<?= htmlspecialchars($hab['room_type_name']) ?>"
        onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
    <div class="card-img-top align-items-center justify-content-center text-white"
        style="height:220px; background:linear-gradient(135deg,#1a2942,#2c4a7c); display:none;">
        <div class="text-center">
            <i class="bi bi-building" style="font-size:3rem; opacity:0.4;"></i>
            <p class="mt-2 small mb-0 opacity-75 font-playfair"><?= htmlspecialchars($hab['room_type_name']) ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="card-img-top d-flex align-items-center justify-content-center text-white"
        style="height:220px; background:linear-gradient(135deg,#1a2942,#2c4a7c);">
        <div class="text-center">
            <i class="bi bi-building" style="font-size:3rem; opacity:0.4;"></i>
            <p class="mt-2 small mb-0 opacity-75 font-playfair"><?= htmlspecialchars($hab['room_type_name']) ?></p>
        </div>
    </div>
<?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="card-title font-playfair mb-1">
                                            Habitación <?= htmlspecialchars($hab['room_type_name']) ?>
                                        </h5>
                                        <span class="badge bg-dark">Nº <?= htmlspecialchars($hab['room_no']) ?></span>
                                        <span class="badge bg-secondary ms-1">Planta <?= $hab['floor'] ?></span>
                                    </div>
                                </div>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($hab['description'] ?? 'Habitación confortable y bien equipada.') ?>
                                </p>
                                <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                                    <i class="bi bi-people"></i>
                                    <span>Hasta <?= $hab['capacity'] ?> persona(s)</span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="price-tag"><?= number_format($hab['base_price'], 0, ',', '.') ?>€</span>
                                    <span class="text-muted small">/noche</span>
                                </div>
                                <a href="/hotel/reservar.php?room_id=<?= $hab['room_id'] ?>" class="btn btn-sm btn-gold"><i class="bi bi-calendar-plus me-1"></i>Reservar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- ============================================================
     SERVICIOS DEL HOTEL
============================================================ -->
    <section class="py-5 bg-light" id="servicios">
        <div class="container">
            <div class="text-center mb-5">
                <p class="text-gold small text-uppercase fw-bold" style="letter-spacing:3px;">Lo que ofrecemos</p>
                <h2 class="font-playfair display-6">Nuestros servicios</h2>
            </div>

            <div class="row g-4 text-center">
                <?php foreach ($servicios as $srv): ?>
                    <div class="col-6 col-md-4 col-lg-2 service-item">
                        <div class="service-icon">
                            <i class="bi <?= $iconosServicio[$srv['service_name']] ?? 'bi-star' ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($srv['service_name']) ?></h6>
                        <p class="text-muted small mb-1"><?= htmlspecialchars($srv['description']) ?></p>
                        <span class="badge bg-gold text-dark"><?= number_format($srv['price'], 0, ',', '.') ?>€/persona</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- ============================================================
     GALERÍA CON SLIDESHOW (DWEC: Slideshow jQuery)
============================================================ -->
    <section class="py-5" id="galeria">
        <div class="container">
            <div class="text-center mb-5">
                <p class="text-gold small text-uppercase fw-bold" style="letter-spacing:3px;">Galería</p>
                <h2 class="font-playfair display-6">Descubre el hotel</h2>
            </div>

            <div class="slideshow-container" id="galeria-slideshow">
                <!-- Slide 1 -->
                <div class="slide active">
                    <div class="d-flex align-items-center justify-content-center text-white"
                        style="height:400px; background:linear-gradient(135deg,#1a2942,#2c4a7c);">
                        <div class="text-center">
                            <i class="bi bi-water" style="font-size:5rem; opacity:0.5;"></i>
                            <p class="mt-3 fs-4 font-playfair">Piscina infinita con vistas al mar</p>
                        </div>
                    </div>
                </div>
                <!-- Slide 2 -->
                <div class="slide">
                    <div class="d-flex align-items-center justify-content-center text-white"
                        style="height:400px; background:linear-gradient(135deg,#2c1a42,#4a2c7c);">
                        <div class="text-center">
                            <i class="bi bi-cup-hot" style="font-size:5rem; opacity:0.5;"></i>
                            <p class="mt-3 fs-4 font-playfair">Restaurante gourmet con terraza</p>
                        </div>
                    </div>
                </div>
                <!-- Slide 3 -->
                <div class="slide">
                    <div class="d-flex align-items-center justify-content-center text-white"
                        style="height:400px; background:linear-gradient(135deg,#1a4229,#2c7c4a);">
                        <div class="text-center">
                            <i class="bi bi-droplet" style="font-size:5rem; opacity:0.5;"></i>
                            <p class="mt-3 fs-4 font-playfair">Spa y centro de bienestar</p>
                        </div>
                    </div>
                </div>
                <!-- Slide 4 -->
                <div class="slide">
                    <div class="d-flex align-items-center justify-content-center text-white"
                        style="height:400px; background:linear-gradient(135deg,#421a1a,#7c2c2c);">
                        <div class="text-center">
                            <i class="bi bi-building" style="font-size:5rem; opacity:0.5;"></i>
                            <p class="mt-3 fs-4 font-playfair">Suites de lujo con vistas panorámicas</p>
                        </div>
                    </div>
                </div>

                <!-- Controles -->
                <button class="btn btn-gold slide-prev" style="position:absolute;top:50%;left:10px;transform:translateY(-50%);">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-gold slide-next" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);">
                    <i class="bi bi-chevron-right"></i>
                </button>

                <!-- Puntos -->
                <div class="slideshow-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================
     TIEMPO METEOROLÓGICO + REVIEWS (AJAX, DWEC)
============================================================ -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4 align-items-start">
                <!-- Tiempo (AJAX) -->
                <div class="col-12 col-md-4">
                    <h5 class="font-playfair mb-3"><i class="bi bi-cloud-sun text-gold me-2"></i>El tiempo hoy</h5>
                    <div id="weatherWidget">Cargando...</div>
                </div>

                <!-- Valoraciones (AJAX) -->
                <div class="col-12 col-md-8" id="seccionReviews">
                    <h5 class="font-playfair mb-3"><i class="bi bi-star text-gold me-2"></i>Lo que dicen nuestros huéspedes</h5>
                    <div id="reviewsContainer">
                        <p class="text-muted">Cargando opiniones...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================
     FORMULARIO DE CONTACTO / CONSULTA (DWEC: validación JS)
============================================================ -->
    <section class="py-5" id="contacto">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="text-center mb-5">
                        <p class="text-gold small text-uppercase fw-bold" style="letter-spacing:3px;">Escríbenos</p>
                        <h2 class="font-playfair display-6">Contacta con nosotros</h2>
                        <p class="text-muted">¿Tienes alguna pregunta? Estaremos encantados de ayudarte.</p>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-lg-5">

                            <div class="alert alert-success d-none" id="mensajeEnviado">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                ¡Mensaje recibido! Te contactaremos en menos de 24 horas.
                            </div>

                            <div class="alert alert-danger d-none" id="errorContacto">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <span id="errorContactoTexto"></span>
                            </div>

                            <form id="formContacto" novalidate>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="con_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="con_nombre" placeholder="Tu nombre completo" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="con_email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="con_email" placeholder="tu@email.com" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="con_telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="con_telefono" placeholder="600123456">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="con_asunto" class="form-label">Asunto</label>
                                        <select class="form-select" id="con_asunto">
                                            <option value="">Selecciona un asunto</option>
                                            <option>Consulta de reserva</option>
                                            <option>Información de servicios</option>
                                            <option>Eventos y celebraciones</option>
                                            <option>Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="con_mensaje" class="form-label">Mensaje <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="con_mensaje" rows="4"
                                            placeholder="Escribe tu mensaje aquí..." required></textarea>
                                        <div class="form-text">
                                            <span id="countMensaje">0</span>/500 caracteres
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 text-center">
                                    <button type="button" class="btn btn-gold btn-lg px-5" id="btnEnviarContacto">
                                        <i class="bi bi-send me-2"></i>Enviar mensaje
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ============================================================
     FOOTER
============================================================ -->
    <footer class="footer-public">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <h5 class="font-playfair text-gold mb-3">
                        <i class="bi bi-building me-2"></i>Hotel Bellavista
                    </h5>
                    <p class="small opacity-75">Un refugio de lujo con vistas al mar Mediterráneo. Donde cada estancia es un recuerdo inolvidable.</p>
                </div>
                <div class="col-6 col-md-2">
                    <h6 class="text-gold mb-3">Hotel</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#habitaciones">Habitaciones</a></li>
                        <li><a href="#servicios">Servicios</a></li>
                        <li><a href="#galeria">Galería</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-2">
                    <h6 class="text-gold mb-3">Info</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#contacto">Contacto</a></li>
                        <li><a href="#">Política de privacidad</a></li>
                        <li><a href="#">Términos y condiciones</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-4">
                    <h6 class="text-gold mb-3">Contacto</h6>
                    <p class="small opacity-75">
                        <i class="bi bi-geo-alt me-2"></i>Paseo Marítimo 42, Alicante<br>
                        <i class="bi bi-telephone me-2"></i>+34 965 000 000<br>
                        <i class="bi bi-envelope me-2"></i>info@hotelbellavista.com
                    </p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.15);">
            <p class="text-center small opacity-50 mb-0">
                &copy; <?= date('Y') ?> Hotel Bellavista. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- Botón volver arriba -->
    <button id="btnScrollTop" class="btn btn-gold rounded-circle shadow" style="position:fixed;bottom:25px;right:25px;display:none;z-index:999;width:45px;height:45px;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/hotel/assets/js/validation.js"></script>
    <script src="/hotel/assets/js/main.js"></script>

    <script>
        $(document).ready(function() {

            // ============================================================
            // BUSCADOR DE DISPONIBILIDAD CON AJAX (DWEC)
            // ============================================================

            // Actualizar mínimo de check_out cuando cambia check_in (Objeto Date)
            $('#pub_checkin').on('change', function() {
                const d = new Date($(this).val());
                d.setDate(d.getDate() + 1);
                $('#pub_checkout').attr('min', d.toISOString().split('T')[0]);
            });

            $('#btnBuscar').on('click', function() {
                const checkin = $('#pub_checkin').val();
                const checkout = $('#pub_checkout').val();
                const tipo = $('#pub_tipo').val();
                const alerta = $('#alertaBuscador');

                alerta.addClass('d-none');

                // Validaciones con objeto Date (DWEC)
                if (!checkin) {
                    $('#alertaBuscadorTexto').text('Selecciona la fecha de entrada.');
                    alerta.removeClass('d-none').hide().fadeIn(300);
                    return;
                }
                if (!checkout) {
                    $('#alertaBuscadorTexto').text('Selecciona la fecha de salida.');
                    alerta.removeClass('d-none').hide().fadeIn(300);
                    return;
                }

                const noches = calcularNoches(checkin, checkout);
                if (noches <= 0) {
                    $('#alertaBuscadorTexto').text('La fecha de salida debe ser posterior a la de entrada.');
                    alerta.removeClass('d-none').hide().fadeIn(300);
                    return;
                }

                // Mostrar spinner mientras carga
                const results = $('#resultadosBusqueda');
                results.html('<div class="text-center py-4"><div class="spinner-gold mx-auto"></div><p class="text-muted mt-2 small">Buscando disponibilidad...</p></div>');

                // Llamada AJAX (DWEC)
                $.ajax({
                    url: '/hotel/api/check_availability.php',
                    method: 'GET',
                    data: {
                        checkin: checkin,
                        checkout: checkout,
                        tipo: tipo
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            results.html('<p class="text-danger text-center">' + data.error + '</p>');
                            return;
                        }

                        const checkinFmt = formatearFecha(checkin);
                        const checkoutFmt = formatearFecha(checkout);

                        let html = `<div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>${data.disponibles}</strong> habitación(es) disponible(s) del <strong>${checkinFmt}</strong> al <strong>${checkoutFmt}</strong> (${data.noches} noche(s))
                </div>`;

                        if (data.disponibles === 0) {
                            html += '<p class="text-center text-muted py-3">No hay habitaciones disponibles para esas fechas. Prueba otras fechas.</p>';
                        } else {
                            html += '<div class="row g-3">';
                            data.habitaciones.forEach(function(h) {
                                html += `<div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="font-playfair mb-0">${h.room_type_name}</h6>
                                        <span class="badge bg-dark">Nº ${h.room_no}</span>
                                    </div>
                                    <p class="text-muted small mb-2">${h.description || ''}</p>
                                    <div class="small text-muted mb-3">
                                        <i class="bi bi-people me-1"></i>Hasta ${h.capacity} personas
                                        &nbsp;|&nbsp;
                                        <i class="bi bi-stairs me-1"></i>Planta ${h.floor}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fs-5 fw-bold text-gold">${h.total_price.toFixed(2).replace('.',',')}€</span>
                                            <span class="text-muted small"> total (${h.base_price.toFixed(2).replace('.',',')}€/noche)</span>
                                        </div>
                                        <a href="/hotel/reservar.php?room_id=${h.room_id}&checkin=${checkin}&checkout=${checkout}" class="btn btn-sm btn-gold">
                                            Reservar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                            });
                            html += '</div>';
                        }

                        // Insertar con fadeIn (jQuery)
                        results.hide().html(html).fadeIn(400);
                    },
                    error: function() {
                        results.html('<p class="text-danger text-center">Error al buscar disponibilidad. Inténtalo de nuevo.</p>');
                    }
                });
            });

            // ============================================================
            // FORMULARIO DE CONTACTO (validación JS + DWEC)
            // ============================================================

            // Contador de caracteres del mensaje (DOM + eventos)
            $('#con_mensaje').on('input', function() {
                const len = $(this).val().length;
                $('#countMensaje').text(len);
                if (len > 450) {
                    $('#countMensaje').addClass('text-danger');
                } else {
                    $('#countMensaje').removeClass('text-danger');
                }
            });

            // Validar email en tiempo real
            $('#con_email').on('blur', function() {
                if (validarEmail($(this).val())) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            // Enviar formulario (simulado, con validación JS)
            $('#btnEnviarContacto').on('click', function() {
                const nombre = $('#con_nombre').val().trim();
                const email = $('#con_email').val().trim();
                const telefono = $('#con_telefono').val().trim();
                const mensaje = $('#con_mensaje').val().trim();
                const errorDiv = $('#errorContacto');

                errorDiv.addClass('d-none');
                $('#mensajeEnviado').addClass('d-none');

                if (nombre.length < 2) {
                    $('#errorContactoTexto').text('El nombre debe tener al menos 2 caracteres.');
                    errorDiv.removeClass('d-none').hide().fadeIn(300);
                    return;
                }
                if (!validarEmail(email)) {
                    $('#errorContactoTexto').text('Introduce un email válido.');
                    errorDiv.removeClass('d-none').hide().fadeIn(300);
                    return;
                }
                if (telefono && !validarTelefono(telefono)) {
                    $('#errorContactoTexto').text('El teléfono debe tener 9 dígitos empezando por 6, 7, 8 o 9.');
                    errorDiv.removeClass('d-none').hide().fadeIn(300);
                    return;
                }
                if (mensaje.length < 10) {
                    $('#errorContactoTexto').text('El mensaje debe tener al menos 10 caracteres.');
                    errorDiv.removeClass('d-none').hide().fadeIn(300);
                    return;
                }
                if (mensaje.length > 500) {
                    $('#errorContactoTexto').text('El mensaje no puede superar los 500 caracteres.');
                    errorDiv.removeClass('d-none').hide().fadeIn(300);
                    return;
                }

                // Simulación de envío exitoso (con animación fade)
                $('#mensajeEnviado').removeClass('d-none').hide().fadeIn(400);
                $('#formContacto')[0].reset();
                $('#countMensaje').text('0');
                $('html, body').animate({
                    scrollTop: $('#mensajeEnviado').offset().top - 80
                }, 600);
            });

        });
    </script>

</body>

</html>