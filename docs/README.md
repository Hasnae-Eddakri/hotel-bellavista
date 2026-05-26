# Hotel Bellavista — Sistema de Gestión Hotelera

Proyecto Intermodular — Ciclo Superior DAW

## Descripción

Aplicación web de gestión hotelera: habitaciones, reservas, clientes, personal y reclamaciones.

## Tecnologías

- **Backend**: PHP puro + PDO + MySQL
- **Frontend**: HTML5 semántico + CSS3 + Bootstrap 5
- **JavaScript**: JS puro + jQuery 3.7 (Fade, Slide, AJAX)
- **Despliegue**: Git/GitHub + AWS

## Credenciales demo

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin | password | Administrador |
| recepcion | password | Recepcionista |

## Instalación local

1. Importar `hotelbellavista.sql` en phpMyAdmin
2. Configurar `config/database.php` con tus credenciales
3. Abrir en XAMPP: `http://localhost/hotel-bellavista/`

## Requisitos cumplidos

### DWEC
- Objeto Date (calculo de noches, edades)
- Validaciones con expresiones regulares
- Eventos (click, change, submit, blur, scroll)
- jQuery: Fade, Slide, Show/Hide
- Slideshow (clase Slideshow propia)
- AJAX: disponibilidad, tiempo, reviews

### DIW
- HTML5 semantico, CSS3 Mobile First, Bootstrap 5

### Despliegue
- Git con ramas, commits y tags
- Documentación en Markdown + GitHub Pages
- Despliegue en AWS

## Ramas

- main — produccion
- develop — desarrollo activo
- feature/crud-rooms, feature/crud-bookings, feature/frontend, feature/ajax
