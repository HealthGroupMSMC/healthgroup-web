<?php
global $wpdb;
$slides = $wpdb->get_results(
    "SELECT id, slide_order, layers
     FROM {$wpdb->prefix}revslider_slides
     WHERE slider_id=1 ORDER BY slide_order",
    ARRAY_A
);

foreach ($slides as $s) {
    $layers = json_decode($s['layers'], true);
    if (!is_array($layers)) continue;
    foreach ($layers as $i => $layer) {
        if (($layer['type'] ?? '') !== 'button') continue;
        echo "Slide {$s['slide_order']} - layer[$i] BUTTON:\n";
        $text = trim(strip_tags(html_entity_decode($layer['text'] ?? '', ENT_QUOTES, 'UTF-8')));
        echo "  text: $text\n";
        // Slider Revolution v5 guarda los links en formato variado
        foreach (['link', 'url', 'action_url'] as $k) {
            if (!empty($layer[$k])) echo "  $k: " . (is_array($layer[$k]) ? json_encode($layer[$k]) : $layer[$k]) . "\n";
        }
        if (isset($layer['actions'])) {
            echo "  actions: " . (is_array($layer['actions']) ? json_encode($layer['actions']) : $layer['actions']) . "\n";
        }
        echo "  layer_keys: " . implode(', ', array_keys($layer)) . "\n";
        echo "\n";
    }
}
