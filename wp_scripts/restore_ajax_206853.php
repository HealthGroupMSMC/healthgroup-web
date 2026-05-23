<?php
$form_id = 206853;
$form = get_post($form_id);
$content = json_decode($form->post_content, true);
echo "ANTES ajax_submit: " . var_export($content['settings']['ajax_submit'] ?? '(no set)', true) . "\n";

// Restaurar al valor original: string '1'
$content['settings']['ajax_submit'] = '1';

wp_update_post([
    'ID' => $form_id,
    'post_content' => wp_json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

$form2 = get_post($form_id);
$content2 = json_decode($form2->post_content, true);
echo "AHORA ajax_submit: " . var_export($content2['settings']['ajax_submit'] ?? '(no set)', true) . "\n";
