<?php
$revision_id = 206870;
$result = wp_restore_post_revision($revision_id);
if (is_wp_error($result)) {
    echo "ERROR: " . $result->get_error_message() . "\n";
} elseif ($result === false) {
    echo "FALSE: no se pudo restaurar\n";
} else {
    echo "Revision restaurada. Post ID afectado: " . $result . "\n";
}

// Verificar tamaño
$form = get_post(206853);
echo "Tamano post_content actual: " . strlen($form->post_content) . " chars\n";

// Verificar ajax_submit
$content = json_decode($form->post_content, true);
echo "ajax_submit: " . var_export($content['settings']['ajax_submit'] ?? '(no set)', true) . "\n";

// Verificar que hay fields
echo "Numero de fields: " . count($content['fields'] ?? []) . "\n";
