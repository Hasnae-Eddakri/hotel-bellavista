/**
 * assets/js/main.js — JavaScript principal de Hotel Bellavista
 * Contiene: efectos jQuery, objeto Date, DOM, eventos, AJAX, Slideshow
 */

$(document).ready(function () {

    // Navbar: cambiar estilo al hacer scroll
    $(window).on('scroll', function () {
        if ($('.navbar-public').length) {
            if ($(this).scrollTop() > 80) {
                $('.navbar-public').addClass('scrolled');
            } else {
                $('.navbar-public').removeClass('scrolled');
            }
        }
    });

    // Alertas de éxito: desaparecen automáticamente con fade
    setTimeout(function () { $('.alert-success').fadeOut(800); }, 4000);

    // Tooltips de Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Botón volver arriba
    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 300) { $('#btnScrollTop').fadeIn(300); }
        else { $('#btnScrollTop').fadeOut(300); }
    });
    $('#btnScrollTop').on('click', function () {
        $('html, body').animate({ scrollTop: 0 }, 600);
    });

    // Inicializar slideshow si existe
    if ($('#galeria-slideshow').length) {
        window.galeria = new Slideshow('galeria-slideshow', 4500);
    }

    // Cargar tiempo meteorológico
    cargarTiempo();
    }
});

// ============================================================
// CLASE SLIDESHOW (DWEC: clases definidas por el usuario)
// ============================================================
class Slideshow {
    constructor(containerId, intervalo = 4000) {
        this.container = $('#' + containerId);
        this.slides    = this.container.find('.slide');
        this.dots      = this.container.find('.dot');
        this.current   = 0;
        this.total     = this.slides.length;
        this.intervalo = intervalo;
        this.timer     = null;
        if (this.total > 0) this.init();
    }

    init() { this.mostrarSlide(0); this.iniciarAuto(); this.bindEventos(); }

    mostrarSlide(index) {
        if (index >= this.total) index = 0;
        if (index < 0)           index = this.total - 1;
        this.current = index;
        this.slides.fadeOut(400);
        $(this.slides[index]).fadeIn(600);
        this.dots.removeClass('active');
        $(this.dots[index]).addClass('active');
    }

    siguiente() { this.mostrarSlide(this.current + 1); }
    anterior()  { this.mostrarSlide(this.current - 1); }
    iniciarAuto()  { this.timer = setInterval(() => this.siguiente(), this.intervalo); }
    detenerAuto()  { clearInterval(this.timer); }

    bindEventos() {
        const self = this;
        this.container.find('.slide-prev').on('click', function () { self.detenerAuto(); self.anterior(); self.iniciarAuto(); });
        this.container.find('.slide-next').on('click', function () { self.detenerAuto(); self.siguiente(); self.iniciarAuto(); });
        this.dots.on('click', function () { const i = $(this).index(); self.detenerAuto(); self.mostrarSlide(i); self.iniciarAuto(); });
        this.container.on('mouseenter', function () { self.detenerAuto(); }).on('mouseleave', function () { self.iniciarAuto(); });
    }
}

// ============================================================
// AJAX: Cargar tiempo meteorológico (DWEC)
// ============================================================
function cargarTiempo() {
    const widget = $('#weatherWidget');
    if (!widget.length) return;
    widget.html('<div class="text-center p-3"><div class="spinner-gold mx-auto"></div></div>');
    $.ajax({
        url: '/hotel/api/weather.php', method: 'GET', dataType: 'json', timeout: 6000,
        success: function (data) {
            const iconos = {'Despejado':'☀️','Mayormente despejado':'🌤️','Parcialmente nublado':'⛅','Nublado':'☁️','Lluvia ligera':'🌧️','Tormenta':'⛈️','Niebla':'🌫️'};
            const icono  = iconos[data.desc] || '🌡️';
            // Objeto Date (DWEC)
            const ahora = new Date();
            const hora  = ahora.getHours() + ':' + String(ahora.getMinutes()).padStart(2, '0');
            widget.html(`<div class="weather-widget text-center">
                <div class="fs-1 mb-1">${icono}</div>
                <div class="weather-temp">${data.temp}°C</div>
                <div class="fw-bold mt-1">${data.desc}</div>
                <div class="small opacity-75 mt-1"><i class="bi bi-wind"></i> ${data.windspeed} km/h &nbsp;|&nbsp; <i class="bi bi-geo-alt"></i> ${data.city}</div>
                <div class="small opacity-60 mt-1">Actualizado a las ${hora}</div>
            </div>`);
        },
        error: function () { widget.html('<div class="text-muted text-center p-3">Tiempo no disponible</div>'); }
    });
}

