<?php
/**
 * Plugin Name: HG - Frontend CSS (paleta y fixes Select2/WPForms)
 * Description: Inyecta CSS personalizado de Health Group en wp_head con prioridad alta.
 *              Necesario porque el custom_css del Customizer no se imprime con el tema Flatsome.
 * Version: 1.2
 * Author: Health Group
 *
 * Destino: wp-content/mu-plugins/hg-frontend-css.php
 *
 * Por seguridad: solo inyecta CSS (no JS, no PHP ejecutable en frontend).
 * Reversible borrando este archivo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_head', function () {
    ?>
<style id="hg-frontend-css">
/* === HG: Inputs de WPForms (texto legible) === */
.wpforms-container input[type="text"],
.wpforms-container input[type="email"],
.wpforms-container input[type="tel"],
.wpforms-container input[type="url"],
.wpforms-container input[type="number"],
.wpforms-container input[type="password"],
.wpforms-container textarea {
    color: #1B3A5C !important;
    background-color: #FFFFFF !important;
    -webkit-text-fill-color: #1B3A5C !important;
}
.wpforms-container input::placeholder,
.wpforms-container textarea::placeholder {
    color: #999 !important;
    opacity: 1 !important;
}

/* === HG: Selects nativos === */
.wpforms-container select,
.wpforms-container select option {
    color: #1B3A5C !important;
    background-color: #FFFFFF !important;
    -webkit-text-fill-color: #1B3A5C !important;
}

/* === HG: Select2 (libreria que reemplaza dropdowns) === */
.wpforms-container .select2-container .select2-selection--single,
body .select2-container .select2-selection--single {
    height: auto !important;
    min-height: 40px !important;
    padding: 6px 10px !important;
    border: 1px solid #ccc !important;
    background-color: #FFFFFF !important;
}
.wpforms-container .select2-container .select2-selection__rendered,
body .select2-container .select2-selection__rendered {
    color: #1B3A5C !important;
    -webkit-text-fill-color: #1B3A5C !important;
    line-height: 28px !important;
    padding-left: 0 !important;
}
.wpforms-container .select2-container .select2-selection__arrow,
body .select2-container .select2-selection__arrow {
    height: 38px !important;
}

/* Dropdown abierto */
.select2-container--default .select2-dropdown,
.select2-dropdown {
    background-color: #FFFFFF !important;
    border: 1px solid #ccc !important;
    z-index: 99999 !important;
}
.select2-container--default .select2-results__option,
.select2-results__option {
    color: #1B3A5C !important;
    -webkit-text-fill-color: #1B3A5C !important;
    background-color: #FFFFFF !important;
    padding: 8px 12px !important;
}
.select2-container--default .select2-results__option--highlighted,
.select2-results__option--highlighted {
    background-color: #5EBA9E !important;
    color: #FFFFFF !important;
    -webkit-text-fill-color: #FFFFFF !important;
}
.select2-container--default .select2-results__option[aria-selected="true"],
.select2-results__option[aria-selected="true"] {
    background-color: #EDEEEC !important;
    color: #1B3A5C !important;
    -webkit-text-fill-color: #1B3A5C !important;
}
.select2-search__field,
.select2-container--default .select2-search--dropdown .select2-search__field {
    color: #1B3A5C !important;
    -webkit-text-fill-color: #1B3A5C !important;
    background-color: #FFFFFF !important;
    border: 1px solid #ccc !important;
    padding: 6px !important;
}

/* === HG: Mensajes de validacion visibles === */
.wpforms-container .wpforms-error,
.wpforms-container label.wpforms-error,
.wpforms-error-container,
.wpforms-error-container * {
    color: #E94B3C !important;
    -webkit-text-fill-color: #E94B3C !important;
    font-weight: 600 !important;
    background-color: transparent !important;
}

/* === HG: Boton Enviar con paleta HG === */
.wpforms-container button[type="submit"],
.wpforms-container input[type="submit"] {
    background-color: #5EBA9E !important;
    color: #FFFFFF !important;
    -webkit-text-fill-color: #FFFFFF !important;
    border: none !important;
    padding: 12px 32px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    border-radius: 4px !important;
}
.wpforms-container button[type="submit"]:hover,
.wpforms-container input[type="submit"]:hover {
    background-color: #4DA68C !important;
}

/* === HG: Fix solapamiento visual en Newsletter del footer (CF7 #85) ===
 * El plugin Cloudflare Turnstile inserta un div con `margin-bottom:-15px`
 * inline que tira hacia arriba el siguiente elemento (.wpcf7-acceptance),
 * solapandolo con el border-bottom del input email del .flex-row.form-flat.
 * El usuario percibia esto como un "tachado" sobre "Politica de Privacidad".
 * Diagnosticado 2026-05-23 con tooling Playwright. */
#footer .cf7-cf-turnstile,
.footer-wrapper .cf7-cf-turnstile,
footer .cf7-cf-turnstile {
    margin-bottom: 0 !important;
}
#footer .wpcf7-acceptance,
.footer-wrapper .wpcf7-acceptance,
footer .wpcf7-acceptance {
    display: inline-block;
    margin-top: 14px;
}

/* === HG: Listado de ofertas en /ofertas-empleo/ — 2 cards por fila ===
 * La Toolset View "ofertas" (ID 204991) usa Bootstrap col-sm-3 (4 cards/fila)
 * que con solo 3 ofertas activas en un col span=8 quedaba muy apretado.
 * Forzamos 2 cards por fila para dar respiracion visual.
 * Aplica solo a /ofertas-empleo/ (post 13). */
body.page-id-13 .col-sm-3 {
    width: 50% !important;
    flex: 0 0 50% !important;
    max-width: 50% !important;
}
@media (max-width: 768px) {
    body.page-id-13 .col-sm-3 {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}
</style>
    <?php
}, 999 );
