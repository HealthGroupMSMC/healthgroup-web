<?php
global $wpdb;
$slides = $wpdb->get_results(
    "SELECT id, slide_order, params, layers
     FROM {$wpdb->prefix}revslider_slides
     WHERE slider_id=1 ORDER BY slide_order",
    ARRAY_A
);

foreach ($slides as $s) {
    echo "===== SLIDE order={$s['slide_order']} (id={$s['id']}) =====\n";

    $params = json_decode($s['params'], true);
    $bg = $params['bg_image'] ?? $params['image'] ?? '';
    if (!$bg && isset($params['image_source_type']) && $params['image_source_type'] === 'media_library') {
        $bg = $params['bg_image_id'] ?? '';
    }
    echo "  bg_keys_disponibles: " . implode(', ', array_filter(array_keys($params), function($k){
        return stripos($k, 'image') !== false || stripos($k, 'bg') !== false;
    })) . "\n";

    // probar las claves más comunes
    foreach (['image', 'bg_image', 'slide_thumb', 'thumb_image'] as $k) {
        if (!empty($params[$k])) {
            echo "  params[$k]: " . $params[$k] . "\n";
        }
    }

    $layers = json_decode($s['layers'], true);
    if (!is_array($layers)) {
        echo "  layers: no parseable\n";
        continue;
    }
    echo "  num_layers: " . count($layers) . "\n";
    foreach ($layers as $i => $layer) {
        $type = $layer['type'] ?? '?';
        $text = $layer['text'] ?? '';
        $link = $layer['link'] ?? ($layer['actions']['action_url'] ?? '');
        if (is_array($link)) $link = json_encode($link);
        $text_clean = trim(strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
        if ($text_clean === '' && $type !== 'text' && $type !== 'button') continue;
        if (strlen($text_clean) > 140) $text_clean = substr($text_clean, 0, 140) . '...';
        echo "    [$i] type=$type | text=\"$text_clean\"";
        if ($link) echo " | link=$link";
        echo "\n";
    }
    echo "\n";
}
