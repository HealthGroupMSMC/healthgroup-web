<?php
/**
 * replace_home_slider.php
 *
 * Sustituye el shortcode [rev_slider alias="Home"] de la home (post 25)
 * por un UX Slider nativo de Flatsome con 4 banners equivalentes.
 *
 * Aplica el patrón seguro:
 *  - wp_unslash() antes de leer el post_content
 *  - Verifica que el shortcode antiguo existe ANTES de tocar nada
 *  - Hace str_replace puntual (no reescribe todo el contenido)
 *  - wp_slash() antes de guardar
 *  - Verifica tamaño antes/después para detectar corrupción
 *  - WordPress crea revision automáticamente para rollback
 */

$post_id = 25;
$old_shortcode = '[rev_slider alias="Home"]';

$new_shortcode = <<<'EOT'
[ux_slider auto_slide="true" timer="6000" arrows="false" nav_pos="outside" hide_nav="false"]

[ux_banner height="500px" bg_overlay="rgba(0,0,0,0.4)" bg="https://healthgroup.es/wp-content/uploads/2018/04/slide_2_2.jpg"]
[text_box width="70" width__sm="92" position_x="50" position_y="50" text_align="center"]
<p style="color:#fff; letter-spacing:2px; text-transform:uppercase; font-size:0.95em; margin-bottom:0.5em;">Trabaja en el sector sanitario</p>
<h2 style="color:#fff; font-weight:300; line-height:1.2;">Sabemos en qué empresa puedes desarrollar tu carrera profesional</h2>
[button text="Ofertas de empleo" link="/ofertas-empleo/" style="outline" color="white" radius="2"]
[/text_box]
[/ux_banner]

[ux_banner height="500px" bg_overlay="rgba(0,0,0,0.4)" bg="https://healthgroup.es/wp-content/uploads/2018/04/Slide_1_2.jpg"]
[text_box width="70" width__sm="92" position_x="50" position_y="50" text_align="center"]
<p style="color:#fff; letter-spacing:2px; text-transform:uppercase; font-size:0.95em; margin-bottom:0.5em;">Consultoría de Recursos Humanos</p>
<h2 style="color:#fff; font-weight:300; line-height:1.2;">Somos una empresa consultora de RRHH especializada en perfiles sanitarios</h2>
[button text="Quiénes somos" link="/quienes-somos/" style="outline" color="white" radius="2"]
[/text_box]
[/ux_banner]

[ux_banner height="500px" bg_overlay="rgba(0,0,0,0.4)" bg="https://healthgroup.es/wp-content/uploads/2018/04/slide_3_2.jpg"]
[text_box width="70" width__sm="92" position_x="50" position_y="50" text_align="center"]
<p style="color:#fff; letter-spacing:2px; text-transform:uppercase; font-size:0.95em; margin-bottom:0.5em;">Captamos talento para empresas</p>
<h2 style="color:#fff; font-weight:300; line-height:1.2;">Tenemos 20 años de experiencia en la contratación de personal sanitario</h2>
[button text="Selección de personal" link="/seleccion-personal-sanitario/" style="outline" color="white" radius="2"]
[/text_box]
[/ux_banner]

[ux_banner height="500px" bg_overlay="rgba(0,0,0,0.4)" bg="https://healthgroup.es/wp-content/uploads/2019/09/slide_4_2.jpg"]
[text_box width="70" width__sm="92" position_x="50" position_y="50" text_align="center"]
<p style="color:#fff; letter-spacing:2px; text-transform:uppercase; font-size:0.95em; margin-bottom:0.5em;">Consultora tecnológica y de marketing</p>
<h2 style="color:#fff; font-weight:300; line-height:1.2;">Proveemos soluciones especializadas para el sector salud</h2>
[button text="Saber más" link="https://healthgroupsolutions.com/" target="_blank" style="outline" color="white" radius="2"]
[/text_box]
[/ux_banner]

[/ux_slider]
EOT;

$post = get_post($post_id);
if (!$post) {
    echo "ERROR: post {$post_id} no encontrado\n";
    return;
}

$current = wp_unslash($post->post_content);
$len_before = strlen($current);

echo "== Pre-cambio ==\n";
echo "  Post ID: {$post_id}\n";
echo "  Tamaño actual: {$len_before} chars\n";
echo "  Contiene [rev_slider]?: " . (strpos($current, $old_shortcode) !== false ? "SI" : "NO") . "\n";
echo "  Contiene [ux_slider]?: " . (strpos($current, '[ux_slider') !== false ? "SI (raro!)" : "NO") . "\n";

if (strpos($current, $old_shortcode) === false) {
    echo "\n!!! ABORTADO: shortcode [rev_slider alias=\"Home\"] no encontrado !!!\n";
    return;
}

$count = 0;
$new_content = str_replace($old_shortcode, $new_shortcode, $current, $count);
echo "  str_replace ejecutado, reemplazos: {$count}\n";

if ($count !== 1) {
    echo "\n!!! ABORTADO: se esperaba 1 reemplazo, se hizo {$count} !!!\n";
    return;
}

$len_after = strlen($new_content);
echo "  Tamaño nuevo: {$len_after} chars (delta " . ($len_after - $len_before) . ")\n";

// Sanity check: el delta debe estar entre +500 y +3500 chars
$delta = $len_after - $len_before;
if ($delta < 500 || $delta > 5000) {
    echo "\n!!! ABORTADO: delta de tamaño fuera de rango razonable !!!\n";
    return;
}

echo "\n== Aplicando update ==\n";
$result = wp_update_post([
    'ID' => $post_id,
    'post_content' => wp_slash($new_content),
]);

if (is_wp_error($result)) {
    echo "ERROR wp_update_post: " . $result->get_error_message() . "\n";
    return;
}

$post2 = get_post($post_id);
$verify = wp_unslash($post2->post_content);
$len_final = strlen($verify);

echo "  Post ID actualizado: {$result}\n";
echo "  Tamaño tras update: {$len_final} chars\n";
echo "  Contiene [ux_slider]?: " . (strpos($verify, '[ux_slider') !== false ? "SI" : "NO") . "\n";
echo "  Contiene [rev_slider]?: " . (strpos($verify, '[rev_slider') !== false ? "SI (FALLO)" : "NO") . "\n";

if (strpos($verify, '[ux_slider') === false || strpos($verify, '[rev_slider') !== false) {
    echo "\n!!! VERIFICACION FALLIDA - RECUPERA REVISION !!!\n";
}

echo "\n== Revisions disponibles para rollback ==\n";
$revisions = wp_get_post_revisions($post_id, ['numberposts' => 3]);
foreach ($revisions as $rev) {
    echo "  Revision ID {$rev->ID} - {$rev->post_date}\n";
}
