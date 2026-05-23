<?php
$form_id = 206853;
$form = get_post($form_id);
if (!$form) {
    echo "Form no encontrado\n";
    return;
}
$content = json_decode($form->post_content, true);
$antes = $content['settings']['ajax_submit'] ?? '(no set)';
echo "ANTES ajax_submit: " . var_export($antes, true) . "\n";

$content['settings']['ajax_submit'] = 0;

wp_update_post([
    'ID' => $form_id,
    'post_content' => wp_json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

// Reread
$form2 = get_post($form_id);
$content2 = json_decode($form2->post_content, true);
$despues = $content2['settings']['ajax_submit'] ?? '(no set)';
echo "AHORA ajax_submit: " . var_export($despues, true) . "\n";
